<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Models\MerchantCommission;
use App\Domain\Merchant\Models\MerchantCredential;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Identity\Models\Courier;
use App\Domain\Identity\Models\UserCatalogEvent;
use App\Domain\Accounting\Services\PaymentAccountingService;
use App\Domain\Accounting\Services\AccountLedgerService;
use Illuminate\Support\Facades\Log;

/**
 * MerchantPurchaseService
 *
 * Creates MerchantPurchase records from cart items, grouped by merchant.
 * Handles payment ownership, shipping processing, and debt ledger calculations.
 *
 * STRICT ARCHITECTURAL RULES:
 * - owner_id = 0 → Platform service (NEVER NULL)
 * - owner_id > 0 → Merchant/Other service
 * - Sales ALWAYS registered to merchant in MerchantPurchase
 */
class MerchantPurchaseService
{
    public function __construct(
        private PaymentAccountingService $accountingService,
        private AccountLedgerService $ledgerService,
    ) {}

    /**
     * Create MerchantPurchase records for a purchase
     *
     * @param object $cart Cart with items
     * @param Purchase $purchase The main purchase record
     * @param array $checkoutData Optional checkout data
     * @throws \RuntimeException If required fields are missing
     */
    public function createFromCart(object $cart, Purchase $purchase, array $checkoutData = []): void
    {
        $merchantGroups = $this->groupCartByMerchant($cart, $purchase->id);

        $customerShippingChoice = $this->parseShippingChoice($purchase->customer_shipping_choice);
        $paymentMethod = strtolower($purchase->method ?? '');
        $isCOD = $this->isCashOnDelivery($paymentMethod);

        foreach ($merchantGroups as $merchantId => $merchantData) {
            $this->createMerchantPurchase(
                $purchase,
                $merchantId,
                $merchantData,
                $customerShippingChoice,
                $checkoutData,
                $isCOD,
                $cart
            );
        }
    }

    /**
     * Group cart items by merchant
     */
    private function groupCartByMerchant(object $cart, int $purchaseId): array
    {
        $merchantGroups = [];

        foreach ($cart->items as $key => $cartItem) {
            $merchantId = $this->extractMerchantId($cartItem, $key, $purchaseId);
            $this->validateCartItem($cartItem, $key, $purchaseId);

            if (!isset($merchantGroups[$merchantId])) {
                $merchantGroups[$merchantId] = [
                    'items' => [],
                    'totalQty' => 0,
                    'totalPrice' => 0,
                ];
            }

            $merchantGroups[$merchantId]['items'][$key] = $cartItem;
            $merchantGroups[$merchantId]['totalQty'] += (int) $cartItem['qty'];
            $merchantGroups[$merchantId]['totalPrice'] += (float) ($cartItem['total_price'] ?? $cartItem['price']);
        }

        return $merchantGroups;
    }

    /**
     * Extract merchant ID from cart item
     */
    private function extractMerchantId(array $cartItem, string $key, int $purchaseId): int
    {
        $merchantId = (int) ($cartItem['merchant_id'] ?? $cartItem['user_id'] ?? $cartItem['item']['user_id'] ?? 0);

        if ($merchantId <= 0) {
            throw new \RuntimeException(
                "Cart item '{$key}' has invalid merchant_id: {$merchantId}. Purchase ID: {$purchaseId}"
            );
        }

        return $merchantId;
    }

    /**
     * Validate required cart item fields
     */
    private function validateCartItem(array $cartItem, string $key, int $purchaseId): void
    {
        if (!isset($cartItem['qty'])) {
            throw new \RuntimeException(
                "Cart item '{$key}' missing required field: qty. Purchase ID: {$purchaseId}"
            );
        }

        if (!isset($cartItem['total_price']) && !isset($cartItem['price'])) {
            throw new \RuntimeException(
                "Cart item '{$key}' missing required field: total_price. Purchase ID: {$purchaseId}"
            );
        }
    }

    /**
     * Parse shipping choice from purchase
     */
    private function parseShippingChoice($customerShippingChoice): array
    {
        if (is_string($customerShippingChoice)) {
            return json_decode($customerShippingChoice, true) ?? [];
        }
        return $customerShippingChoice ?? [];
    }

    /**
     * Check if payment method is Cash on Delivery
     */
    private function isCashOnDelivery(string $paymentMethod): bool
    {
        return str_contains($paymentMethod, 'cod') || str_contains($paymentMethod, 'cash');
    }

    /**
     * Create a single MerchantPurchase record
     */
    private function createMerchantPurchase(
        Purchase $purchase,
        int $merchantId,
        array $merchantData,
        array $customerShippingChoice,
        array $checkoutData,
        bool $isCOD,
        object $cart
    ): void {
        try {
            $itemsTotal = $merchantData['totalPrice'];

            // Calculate commission
            $merchantCommission = MerchantCommission::getOrCreateForMerchant($merchantId);
            $commissionAmount = $merchantCommission->calculateCommission($itemsTotal);

            // Calculate proportional tax
            $taxAmount = $this->calculateProportionalTax($purchase, $itemsTotal, $cart);

            // Process shipping choice
            $shippingChoice = $customerShippingChoice[$merchantId] ?? $customerShippingChoice[(string) $merchantId] ?? null;
            $shippingData = $this->processShippingChoice($shippingChoice, $merchantId);

            // Determine payment owner
            $paymentOwnerId = $this->determinePaymentOwner($purchase, $checkoutData, $shippingData, $isCOD);
            $paymentType = ($paymentOwnerId === 0) ? 'platform' : 'merchant';

            // Calculate amounts
            $netAmount = $itemsTotal - $commissionAmount;
            $deliveryMethod = $this->mapDeliveryMethod($shippingData['type']);
            $deliveryProvider = $this->getDeliveryProvider($shippingData, $shippingChoice);
            $platformShippingFee = ($shippingData['owner_id'] === 0) ? $shippingData['cost'] : 0;

            // Get accounting data from service
            $accountingData = $this->accountingService->calculateDebtLedger([
                'payment_method' => $isCOD ? 'cod' : 'online',
                'payment_owner_id' => $paymentOwnerId,
                'delivery_method' => $deliveryMethod,
                'delivery_provider' => $deliveryProvider,
                'price' => $itemsTotal,
                'commission_amount' => $commissionAmount,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingData['cost'],
                'courier_fee' => ($shippingData['type'] === 'courier') ? $shippingData['cost'] : 0,
                'platform_shipping_fee' => $platformShippingFee,
            ]);

            // Create MerchantPurchase
            $merchantPurchase = new MerchantPurchase();
            $merchantPurchase->purchase_id = $purchase->id;
            $merchantPurchase->user_id = $merchantId;
            $merchantPurchase->cart = $merchantData['items'];
            $merchantPurchase->qty = $merchantData['totalQty'];
            $merchantPurchase->price = $itemsTotal;
            $merchantPurchase->purchase_number = $purchase->purchase_number;
            $merchantPurchase->status = 'pending';
            $merchantPurchase->commission_amount = $commissionAmount;
            $merchantPurchase->tax_amount = $taxAmount;
            $merchantPurchase->shipping_cost = $shippingData['cost'];
            $merchantPurchase->courier_fee = ($shippingData['type'] === 'courier') ? $shippingData['cost'] : 0;
            $merchantPurchase->platform_shipping_fee = $platformShippingFee;
            $merchantPurchase->net_amount = $netAmount;
            $merchantPurchase->payment_type = $paymentType;
            $merchantPurchase->shipping_type = $shippingData['type'];
            $merchantPurchase->payment_owner_id = $paymentOwnerId;
            $merchantPurchase->shipping_owner_id = $shippingData['owner_id'];
            $merchantPurchase->shipping_id = $shippingData['shipping_id'];
            $merchantPurchase->courier_id = $shippingData['courier_id'];

            // Accounting fields
            $merchantPurchase->money_holder = $accountingData['money_holder'];
            $merchantPurchase->delivery_method = $accountingData['delivery_method'];
            $merchantPurchase->delivery_provider = $accountingData['delivery_provider'];
            $merchantPurchase->cod_amount = $accountingData['cod_amount'];
            $merchantPurchase->collection_status = $accountingData['collection_status'];

            // Debt ledger
            $merchantPurchase->platform_owes_merchant = $accountingData['platform_owes_merchant'];
            $merchantPurchase->merchant_owes_platform = $accountingData['merchant_owes_platform'];
            $merchantPurchase->courier_owes_platform = $accountingData['courier_owes_platform'];
            $merchantPurchase->shipping_company_owes_merchant = $accountingData['shipping_company_owes_merchant'];
            $merchantPurchase->shipping_company_owes_platform = $accountingData['shipping_company_owes_platform'];

            $merchantPurchase->settlement_status = 'pending';
            $merchantPurchase->save();

            // Record ledger entries
            $this->recordLedgerEntries($merchantPurchase);

            // Create notification
            $this->createMerchantNotification($merchantId, $purchase->purchase_number);

        } catch (\Exception $e) {
            Log::error('MerchantPurchaseService error: ' . $e->getMessage(), [
                'merchant_id' => $merchantId,
                'purchase_id' => $purchase->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate proportional tax for merchant's items
     */
    private function calculateProportionalTax(Purchase $purchase, float $itemsTotal, object $cart): float
    {
        $purchaseTax = (float) $purchase->tax;
        $cartTotalPrice = 0;

        foreach ($cart->items as $item) {
            $cartTotalPrice += (float) ($item['total_price'] ?? $item['price'] ?? 0);
        }

        return $cartTotalPrice > 0 ? ($itemsTotal / $cartTotalPrice) * $purchaseTax : 0;
    }

    /**
     * Map shipping type to delivery method constant
     */
    private function mapDeliveryMethod(string $type): string
    {
        return match ($type) {
            'courier' => MerchantPurchase::DELIVERY_LOCAL_COURIER,
            'platform', 'merchant' => MerchantPurchase::DELIVERY_SHIPPING_COMPANY,
            'pickup' => MerchantPurchase::DELIVERY_PICKUP,
            default => MerchantPurchase::DELIVERY_NONE,
        };
    }

    /**
     * Get delivery provider string
     */
    private function getDeliveryProvider(array $shippingData, ?array $shippingChoice): ?string
    {
        if ($shippingData['type'] === 'courier') {
            return 'courier_' . ($shippingData['courier_id'] ?? 0);
        }
        return $shippingChoice['provider'] ?? null;
    }

    /**
     * Record ledger entries for merchant purchase
     */
    private function recordLedgerEntries(MerchantPurchase $merchantPurchase): void
    {
        try {
            $this->ledgerService->recordDebtsForMerchantPurchase($merchantPurchase);
        } catch (\Exception $e) {
            Log::warning('Failed to record ledger entries for purchase #' . $merchantPurchase->purchase_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Create notification for merchant
     */
    private function createMerchantNotification(int $merchantId, string $purchaseNumber): void
    {
        $notification = new UserCatalogEvent();
        $notification->user_id = $merchantId;
        $notification->purchase_number = $purchaseNumber;
        $notification->save();
    }

    /**
     * Determine payment owner based on strict rules
     *
     * RULES:
     * - Online payment → Check merchant credentials, else platform (0)
     * - COD + Courier → Platform (0)
     * - COD + Shipping Company → shipping_owner_id
     */
    private function determinePaymentOwner(Purchase $purchase, array $checkoutData, array $shippingData, bool $isCOD): int
    {
        if ($isCOD) {
            // COD + Courier → Platform receives via courier
            if ($shippingData['type'] === 'courier') {
                return 0;
            }
            // COD + Shipping Company → Money goes to shipping owner
            return $shippingData['owner_id'];
        }

        // Online payment - check for merchant gateway
        $merchantPaymentGatewayId = $checkoutData['merchant_payment_gateway_id'] ?? 0;
        if ($merchantPaymentGatewayId > 0) {
            $credential = MerchantCredential::where('id', $merchantPaymentGatewayId)
                ->where('is_active', true)
                ->first();

            if ($credential && $credential->user_id > 0) {
                return $credential->user_id;
            }
        }

        // Check merchant payment credentials
        $merchantId = $checkoutData['merchant_id'] ?? 0;
        if ($merchantId > 0) {
            $paymentMethod = strtolower($purchase->method ?? '');
            $serviceName = $this->getPaymentServiceName($paymentMethod);

            $hasMerchantCredentials = MerchantCredential::where('user_id', $merchantId)
                ->where('service_name', $serviceName)
                ->where('is_active', true)
                ->exists();

            if ($hasMerchantCredentials) {
                return $merchantId;
            }
        }

        // Default: Platform gateway
        return 0;
    }

    /**
     * Get payment service name from payment method
     */
    private function getPaymentServiceName(string $paymentMethod): string
    {
        $methodMap = [
            'myfatoorah' => 'myfatoorah',
            'stripe' => 'stripe',
            'paypal' => 'paypal',
            'razorpay' => 'razorpay',
            'tap' => 'tap',
            'moyasar' => 'moyasar',
        ];

        foreach ($methodMap as $key => $service) {
            if (str_contains($paymentMethod, $key)) {
                return $service;
            }
        }

        return $paymentMethod;
    }

    /**
     * Process shipping choice to determine owner and type
     *
     * STRICT RULE: owner_id = 0 → Platform, owner_id > 0 → Owner
     */
    private function processShippingChoice(?array $shippingChoice, int $merchantId): array
    {
        $result = [
            'type' => 'platform',
            'cost' => 0,
            'owner_id' => 0,
            'shipping_id' => 0,
            'courier_id' => 0,
        ];

        if (!$shippingChoice) {
            return $result;
        }

        $result['cost'] = (float) ($shippingChoice['price'] ?? 0);
        $provider = $shippingChoice['provider'] ?? '';

        if ($provider === 'tryoto' || $provider === 'shipping_company') {
            $result['type'] = 'platform';
            $result['owner_id'] = 0;
            $result['shipping_id'] = (int) ($shippingChoice['delivery_option_id'] ?? 0);
        } elseif ($provider === 'local_courier' || $provider === 'courier') {
            $result['type'] = 'courier';
            $result['courier_id'] = (int) ($shippingChoice['courier_id'] ?? 0);

            if ($result['courier_id'] > 0) {
                $courier = Courier::find($result['courier_id']);
                $result['owner_id'] = $courier ? (int) ($courier->user_id ?? 0) : 0;
            }
        } elseif ($provider === 'pickup') {
            $result['type'] = 'pickup';
            $result['owner_id'] = $merchantId;
            $result['cost'] = 0;
        } else {
            $shippingId = (int) ($shippingChoice['shipping_id'] ?? 0);
            if ($shippingId > 0) {
                $shipping = Shipping::find($shippingId);
                if ($shipping) {
                    $result['owner_id'] = (int) ($shipping->user_id ?? 0);
                    $result['type'] = ($result['owner_id'] === 0) ? 'platform' : 'merchant';
                    $result['shipping_id'] = $shippingId;
                } else {
                    $result['type'] = 'merchant';
                    $result['owner_id'] = $merchantId;
                }
            } else {
                $result['type'] = 'merchant';
                $result['owner_id'] = $merchantId;
            }
        }

        return $result;
    }
}
