<?php

namespace App\Console\Commands;

use App\Models\MerchantCommission;
use App\Models\MerchantPurchase;
use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix existing purchases with missing/incorrect data
 *
 * FIXES:
 * 1. Removes duplicate MerchantPurchase records (should be 1 per merchant per purchase)
 * 2. Recalculates and updates merchant purchase totals
 * 3. Populates missing cart data in MerchantPurchase
 * 4. Calculates commission, tax, and net amounts
 */
class FixPurchaseData extends Command
{
    protected $signature = 'purchase:fix-data {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix existing purchases with missing or incorrect data';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Get all purchases
        $purchases = Purchase::with('merchantPurchases')->get();
        $this->info("Found {$purchases->count()} purchases to process");

        $fixedCount = 0;
        $duplicatesRemoved = 0;

        foreach ($purchases as $purchase) {
            $this->line("Processing Purchase #{$purchase->purchase_number} (ID: {$purchase->id})");

            // Get cart data
            $cart = $purchase->cart;
            if (is_string($cart)) {
                $cart = json_decode($cart, true);
            }

            if (!$cart || !isset($cart['items'])) {
                $this->warn("  - No cart items found, skipping");
                continue;
            }

            // Group cart items by merchant
            $merchantGroups = [];
            foreach ($cart['items'] as $key => $item) {
                $merchantId = $item['item']['user_id'] ?? $item['user_id'] ?? 0;
                if ($merchantId == 0) continue;

                if (!isset($merchantGroups[$merchantId])) {
                    $merchantGroups[$merchantId] = [
                        'items' => [],
                        'totalQty' => 0,
                        'totalPrice' => 0,
                    ];
                }
                $merchantGroups[$merchantId]['items'][$key] = $item;
                $merchantGroups[$merchantId]['totalQty'] += (int)($item['qty'] ?? 1);
                $merchantGroups[$merchantId]['totalPrice'] += (float)($item['price'] ?? 0);
            }

            $this->info("  - Found " . count($merchantGroups) . " merchant(s) in cart");

            // Check for duplicates
            $existingMerchantPurchases = $purchase->merchantPurchases;
            $merchantPurchaseCounts = $existingMerchantPurchases->groupBy('user_id')
                ->map(fn($group) => $group->count());

            foreach ($merchantPurchaseCounts as $merchantId => $count) {
                if ($count > 1) {
                    $this->warn("  - Found $count duplicate records for merchant #$merchantId");

                    if (!$dryRun) {
                        // Keep only the first one and delete the rest
                        $merchantPurchasesForMerchant = $existingMerchantPurchases
                            ->where('user_id', $merchantId)
                            ->sortBy('id');

                        $keepRecord = $merchantPurchasesForMerchant->first();
                        $deleteRecords = $merchantPurchasesForMerchant->skip(1);

                        foreach ($deleteRecords as $record) {
                            $record->delete();
                            $duplicatesRemoved++;
                            $this->info("    - Deleted duplicate MerchantPurchase #{$record->id}");
                        }
                    } else {
                        $duplicatesRemoved += ($count - 1);
                    }
                }
            }

            // Now update/create MerchantPurchase records
            foreach ($merchantGroups as $merchantId => $merchantData) {
                $merchantPurchase = MerchantPurchase::where('purchase_id', $purchase->id)
                    ->where('user_id', $merchantId)
                    ->first();

                if (!$merchantPurchase) {
                    $this->info("  - Creating MerchantPurchase for merchant #$merchantId");
                    if (!$dryRun) {
                        $merchantPurchase = new MerchantPurchase();
                        $merchantPurchase->purchase_id = $purchase->id;
                        $merchantPurchase->user_id = $merchantId;
                        $merchantPurchase->purchase_number = $purchase->purchase_number;
                        $merchantPurchase->status = $purchase->status;
                    }
                }

                // Calculate commission (per-merchant from merchant_commissions table)
                $itemsTotal = $merchantData['totalPrice'];
                $merchantCommission = MerchantCommission::getOrCreateForMerchant($merchantId);
                $commissionAmount = $merchantCommission->calculateCommission($itemsTotal);

                // Calculate tax (proportional)
                $purchaseTax = (float)$purchase->tax;
                $cartTotalPrice = 0;
                foreach ($cart['items'] as $item) {
                    $cartTotalPrice += (float)($item['price'] ?? 0);
                }
                $taxAmount = $cartTotalPrice > 0 ? ($itemsTotal / $cartTotalPrice) * $purchaseTax : 0;

                // Get shipping cost from customer_shipping_choice
                $shippingCost = 0;
                $shippingType = null;
                $shippingChoice = $purchase->getCustomerShippingChoice($merchantId);
                if ($shippingChoice) {
                    $shippingCost = (float)($shippingChoice['price'] ?? 0);
                    $provider = $shippingChoice['provider'] ?? '';
                    if ($provider === 'local_courier' || $provider === 'courier') {
                        $shippingType = 'courier';
                    } elseif ($provider === 'tryoto') {
                        $shippingType = 'platform';
                    } else {
                        $shippingType = 'merchant';
                    }
                }

                // Determine payment type
                $paymentMethod = strtolower($purchase->method ?? '');
                $paymentType = 'platform';
                $moneyReceivedBy = 'platform';
                if (strpos($paymentMethod, 'cod') !== false || strpos($paymentMethod, 'cash') !== false) {
                    if ($shippingType === 'courier') {
                        $moneyReceivedBy = 'courier';
                    } else {
                        $moneyReceivedBy = 'merchant';
                    }
                }

                // Calculate net amount
                $netAmount = $itemsTotal - $commissionAmount;

                $this->info("  - Merchant #$merchantId: Items Total = {$itemsTotal}, Commission = {$commissionAmount}, Tax = {$taxAmount}, Shipping = {$shippingCost}");

                if (!$dryRun && $merchantPurchase) {
                    $merchantPurchase->cart = $merchantData['items'];
                    $merchantPurchase->qty = $merchantData['totalQty'];
                    $merchantPurchase->price = $itemsTotal;
                    $merchantPurchase->commission_amount = $commissionAmount;
                    $merchantPurchase->tax_amount = $taxAmount;
                    $merchantPurchase->shipping_cost = $shippingCost;
                    $merchantPurchase->net_amount = $netAmount;
                    $merchantPurchase->payment_type = $paymentType;
                    $merchantPurchase->shipping_type = $shippingType;
                    $merchantPurchase->money_received_by = $moneyReceivedBy;
                    $merchantPurchase->save();
                    $fixedCount++;
                }
            }
        }

        // Also fix shipping_cost in main purchases table
        $purchasesFixed = 0;
        foreach ($purchases as $purchase) {
            $totalShippingCost = 0;
            $customerShippingChoice = $purchase->customer_shipping_choice;

            if ($customerShippingChoice) {
                if (is_string($customerShippingChoice)) {
                    $customerShippingChoice = json_decode($customerShippingChoice, true) ?? [];
                }
                foreach ($customerShippingChoice as $merchantId => $choice) {
                    $totalShippingCost += (float)($choice['price'] ?? 0);
                }
            }

            // Check for courier shipping in delivery_couriers
            $deliveryCourier = DB::table('delivery_couriers')
                ->where('purchase_id', $purchase->id)
                ->first();
            if ($deliveryCourier && $deliveryCourier->delivery_fee > 0) {
                $totalShippingCost = (float)$deliveryCourier->delivery_fee;
            }

            if ($totalShippingCost > 0 && $purchase->shipping_cost != $totalShippingCost) {
                $this->info("Updating Purchase #{$purchase->id} shipping_cost from {$purchase->shipping_cost} to {$totalShippingCost}");
                if (!$dryRun) {
                    $purchase->shipping_cost = $totalShippingCost;
                    $purchase->save();
                }
                $purchasesFixed++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Purchases processed: {$purchases->count()}");
        $this->info("  - Duplicate MerchantPurchases removed: $duplicatesRemoved");
        $this->info("  - MerchantPurchases fixed/created: $fixedCount");
        $this->info("  - Purchases shipping_cost updated: $purchasesFixed");

        if ($dryRun) {
            $this->warn("\nThis was a DRY RUN. Run without --dry-run to apply changes.");
        }

        return Command::SUCCESS;
    }
}
