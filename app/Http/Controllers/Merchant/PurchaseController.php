<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Commerce\Services\InvoiceSellerService;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Shipping\Services\TrackingViewService;
use Illuminate\Http\Request;

class PurchaseController extends MerchantBaseController
{

    public function index()
    {
        $user = $this->user;

        // ============================================================
        // OPTIMIZED: Query purchases directly via MerchantPurchase
        // Was: Loading ALL purchases then filtering in PHP (N+1 problem)
        // Now: Single query with proper database filtering + pagination
        // ============================================================
        $purchases = Purchase::whereHas('merchantPurchases', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['merchantPurchases' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->orderby('id', 'desc')
        ->paginate(15);

        // PRE-COMPUTED: Display data for each purchase (no @php in view)
        $purchasesDisplay = $purchases->map(function ($purchase) {
            $merchantItems = $purchase->merchantPurchases;
            return [
                'id' => $purchase->id,
                'purchase' => $purchase,
                'purchase_number' => $purchase->purchase_number,
                'totalQty' => $merchantItems->sum('qty'),
                'totalPrice' => $merchantItems->sum('price'),
                'formattedPrice' => \PriceHelper::showOrderCurrencyPrice($merchantItems->sum('price'), $purchase->currency_sign),
                'method' => $purchase->method,
                'status' => $purchase->status,
                'statusClass' => match($purchase->status) {
                    'pending' => 'bg-pending',
                    'processing' => 'bg-processing',
                    'completed' => 'bg-complete',
                    'declined' => 'bg-declined',
                    default => 'bg-secondary',
                },
                'viewUrl' => route('merchant-purchase-show', $purchase->purchase_number),
            ];
        });

        return view('merchant.purchase.index', compact('purchases', 'user', 'purchasesDisplay'));
    }

    public function show($slug)
    {
        $user = $this->user;
        $merchantId = $user->id;

        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $merchantId)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart;

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forMerchant($purchase, $merchantId);

        // ✅ تحميل بيانات التاجر في الـ Controller بدلاً من الـ View
        $merchantPurchaseData = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->selectRaw('SUM(qty) as total_qty, SUM(price) as total_price, COUNT(*) as items_count')
            ->first();

        // ✅ تحميل بيانات الفرع (Branch-scoped checkout)
        $merchantPurchase = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->with('merchantBranch')
            ->first();

        $branchData = null;
        if ($merchantPurchase && $merchantPurchase->merchantBranch) {
            $branch = $merchantPurchase->merchantBranch;
            $branchData = [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city ?? '',
                'address' => $branch->address ?? '',
            ];
        }

        // ✅ فحص حالة التوصيل والإكمال في الـ Controller
        $deliveryCourier = \App\Domain\Shipping\Models\DeliveryCourier::where('merchant_id', $merchantId)
            ->where('purchase_id', $purchase->id)
            ->first();

        $completedCount = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->where('status', 'completed')
            ->count();

        $purchaseStats = [
            'totalQty' => (int) ($merchantPurchaseData->total_qty ?? 0),
            'totalPrice' => (float) ($merchantPurchaseData->total_price ?? 0),
            'itemsCount' => (int) ($merchantPurchaseData->items_count ?? 0),
            'isDelivered' => $deliveryCourier && $deliveryCourier->status === 'delivered',
            'completedCount' => $completedCount,
            'canMarkComplete' => $deliveryCourier
                && $deliveryCourier->status === 'delivered'
                && $completedCount === 0,
        ];

        // ✅ تحميل بيانات التجار وحالات الطلبات مسبقاً (بدلاً من queries في الـ View)
        $cartItems = $cart['items'] ?? [];
        $merchantIds = collect($cartItems)->pluck('item.user_id')->filter()->unique()->values()->toArray();

        // تحميل كل التجار مرة واحدة
        $merchantsLookup = \App\Domain\Identity\Models\User::whereIn('id', $merchantIds)
            ->get()
            ->keyBy('id')
            ->toArray();

        // تحميل كل MerchantPurchases لهذا الـ Purchase مرة واحدة
        $merchantPurchasesLookup = $purchase->merchantPurchases()
            ->get()
            ->keyBy('user_id')
            ->toArray();

        // ============================================================
        // PRE-COMPUTED: Cart items display data (no @php URL generation in view)
        // ============================================================
        $cartItemsDisplay = [];
        foreach ($cartItems as $key => $catalogItem) {
            $itemUserId = $catalogItem['item']['user_id'] ?? 0;
            $partNumber = $catalogItem['item']['part_number'] ?? '';

            $cartItemsDisplay[$key] = [
                'productUrl' => !empty($partNumber) ? route('front.part-result', $partNumber) : '#',
                'merchant' => $merchantsLookup[$itemUserId] ?? null,
                'merchantPurchase' => $merchantPurchasesLookup[$itemUserId] ?? null,
            ];
        }

        return view('merchant.purchase.details', compact(
            'user',
            'purchase',
            'cart',
            'trackingData',
            'purchaseStats',
            'cartItemsDisplay',
            'branchData'
        ));
    }

    public function invoice($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart;
        $trackingData = app(TrackingViewService::class)->forMerchant($purchase, $user->id);

        // جلب بيانات MerchantPurchase مع الفرع في الـ Controller بدلاً من الـ View
        $merchantPurchase = $purchase->merchantPurchases()
            ->where('user_id', $user->id)
            ->with('merchantBranch')
            ->first();

        // ✅ بيانات الفرع (Branch-scoped checkout)
        $branchData = null;
        if ($merchantPurchase && $merchantPurchase->merchantBranch) {
            $branch = $merchantPurchase->merchantBranch;
            $branchData = [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city ?? '',
                'address' => $branch->address ?? '',
                'phone' => $branch->phone ?? '',
            ];
        }

        // تحضير البيانات المالية للعرض - كل القيم من قاعدة البيانات
        $merchantInvoiceData = [
            'price' => $merchantPurchase ? $merchantPurchase->price : 0,
            'commission_amount' => $merchantPurchase ? ($merchantPurchase->commission_amount ?? 0) : 0,
            'net_amount' => $merchantPurchase ? ($merchantPurchase->net_amount ?? $merchantPurchase->price ?? 0) : 0,
            'payment_type' => $merchantPurchase ? ($merchantPurchase->payment_type ?? 'platform') : 'platform',
            'shipping_type' => $merchantPurchase ? ($merchantPurchase->shipping_type ?? 'shipping') : 'shipping',
            'money_received_by' => $merchantPurchase ? ($merchantPurchase->money_received_by ?? 'platform') : 'platform',
            'courier_fee' => $merchantPurchase ? ($merchantPurchase->courier_fee ?? 0) : 0,
            'tax_amount' => $merchantPurchase ? ($merchantPurchase->tax_amount ?? 0) : 0,
        ];

        // Get seller info for invoice header
        $sellerInfo = $merchantPurchase
            ? app(InvoiceSellerService::class)->getSellerInfo($merchantPurchase)
            : app(InvoiceSellerService::class)->getSellerInfo(new MerchantPurchase(['payment_owner_id' => 0, 'shipping_owner_id' => 0]));

        // ============================================================
        // PRE-COMPUTED: Invoice calculations (no @php in view)
        // ============================================================
        $cartItems = $cart['items'] ?? [];
        $subtotal = 0;
        $cartItemsDisplay = [];

        foreach ($cartItems as $key => $catalogItem) {
            $itemUserId = $catalogItem['item']['user_id'] ?? 0;

            // Only count items belonging to this merchant
            if ($itemUserId == $user->id) {
                $subtotal += round(($catalogItem['price'] ?? 0) * $purchase->currency_value, 2);
            }

            // Pre-compute URL for each item
            $partNumber = $catalogItem['item']['part_number'] ?? '';
            $cartItemsDisplay[$key] = [
                'productUrl' => !empty($partNumber) ? route('front.part-result', $partNumber) : '#',
            ];
        }

        // Shipping cost (only if merchant is shipping owner)
        $shippingCostForThisMerchant = 0;
        if (\Illuminate\Support\Facades\Auth::user()->id == $purchase->merchant_shipping_id && $purchase->shipping_cost != 0) {
            $shippingCostForThisMerchant = round($purchase->shipping_cost, 2);
        }

        // Delivery fee from tracking data
        $deliveryFee = 0;
        if ($trackingData['hasDelivery'] && ($trackingData['deliveryFee'] ?? 0) > 0) {
            $deliveryFee = round($trackingData['deliveryFee'] * $purchase->currency_value, 2);
        }

        // Tax calculation
        $tax = 0;
        $subtotalAfterTax = $subtotal;
        if ($purchase->tax != 0) {
            $tax = ($subtotal / 100) * $purchase->tax;
            $subtotalAfterTax = $subtotal + $tax;
        }

        // Total
        $total = $subtotalAfterTax + $shippingCostForThisMerchant + $deliveryFee;

        $invoiceCalculations = [
            'subtotal' => $subtotal,
            'subtotalAfterTax' => $subtotalAfterTax,
            'shippingCost' => $shippingCostForThisMerchant,
            'deliveryFee' => $deliveryFee,
            'tax' => $tax,
            'total' => $total,
            'showShippingCost' => $shippingCostForThisMerchant > 0,
            'showDeliveryFee' => $deliveryFee > 0,
            'showTax' => $purchase->tax != 0,
        ];

        return view('merchant.purchase.invoice', compact(
            'user',
            'purchase',
            'cart',
            'trackingData',
            'merchantInvoiceData',
            'branchData',
            'sellerInfo',
            'cartItemsDisplay',
            'invoiceCalculations'
        ));
    }

    public function printpage($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart;
        $trackingData = app(TrackingViewService::class)->forMerchant($purchase, $user->id);

        // ✅ بيانات الفرع (Branch-scoped checkout)
        $merchantPurchase = $purchase->merchantPurchases()
            ->where('user_id', $user->id)
            ->with('merchantBranch')
            ->first();

        $branchData = null;
        if ($merchantPurchase && $merchantPurchase->merchantBranch) {
            $branch = $merchantPurchase->merchantBranch;
            $branchData = [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city ?? '',
                'address' => $branch->address ?? '',
                'phone' => $branch->phone ?? '',
            ];
        }

        // Get seller info for invoice header
        $sellerInfo = $merchantPurchase
            ? app(InvoiceSellerService::class)->getSellerInfo($merchantPurchase)
            : app(InvoiceSellerService::class)->getSellerInfo(new MerchantPurchase(['payment_owner_id' => 0, 'shipping_owner_id' => 0]));

        // PRE-COMPUTED: Print calculations (DATA_FLOW_POLICY - no @php in view)
        $printCalculations = $this->calculatePrintTotals($purchase, $cart, $user->id);

        return view('merchant.purchase.print', compact('user', 'purchase', 'cart', 'trackingData', 'branchData', 'sellerInfo', 'printCalculations'));
    }

    /**
     * Calculate totals for merchant print page
     * PRE-COMPUTED: All values to avoid @php in view (DATA_FLOW_POLICY)
     */
    private function calculatePrintTotals(Purchase $purchase, array $cart, int $merchantId): array
    {
        $subtotal = 0;
        $items = $cart['items'] ?? [];

        // Sum prices of merchant's items only
        foreach ($items as $item) {
            $itemUserId = $item['item']['user_id'] ?? 0;
            if ($itemUserId != 0 && $itemUserId == $merchantId) {
                $subtotal += round(($item['price'] ?? 0) * $purchase->currency_value, 2);
            }
        }

        // Shipping cost only if merchant is shipping owner
        $shippingCost = 0;
        $showShippingCost = false;
        if ($merchantId == $purchase->merchant_shipping_id && $purchase->shipping_cost != 0) {
            $shippingCost = round($purchase->shipping_cost, 2);
            $showShippingCost = true;
        }

        // Tax calculation
        $tax = 0;
        $showTax = false;
        if ($purchase->tax != 0) {
            $tax = ($subtotal / 100) * $purchase->tax;
            $showTax = true;
        }

        // Total with tax and shipping
        $total = $subtotal + $tax + $shippingCost;

        return [
            'subtotal' => $subtotal,
            'shippingCost' => $shippingCost,
            'showShippingCost' => $showShippingCost,
            'tax' => $tax,
            'showTax' => $showTax,
            'total' => $total,
        ];
    }

    /**
     * Update merchant purchase status
     *
     * Uses ShipmentTrackingService as the single source of truth.
     * Status changes create tracking records, and the Observer updates purchase status automatically.
     *
     * @param string $slug Purchase number
     * @param string $status New status (pending, processing, on delivery, completed, declined)
     */
    public function status($slug, $status)
    {
        $user = $this->user;

        // Get THIS merchant's record only (not any merchant with same purchase_number)
        $merchantPurchase = MerchantPurchase::where('purchase_number', $slug)
            ->where('user_id', $user->id)
            ->first();

        if (!$merchantPurchase) {
            return redirect()->back()->with('unsuccess', __('Purchase not found or unauthorized'));
        }

        if ($merchantPurchase->status === 'completed') {
            return redirect()->back()->with('success', __('This Purchase is Already Completed'));
        }

        // Map purchase status to tracking status
        $trackingStatus = $this->mapPurchaseStatusToTrackingStatus($status);

        // Use ShipmentTrackingService to create tracking record
        // The Observer will automatically update Purchase and MerchantPurchase status
        $trackingService = app(ShipmentTrackingService::class);

        // Check if shipment tracking exists for this merchant
        $existingTracking = ShipmentTracking::getLatestForPurchase(
            $merchantPurchase->purchase_id,
            $user->id
        );

        if ($existingTracking) {
            // Update existing shipment tracking
            $trackingService->updateManually(
                $merchantPurchase->purchase_id,
                $user->id,
                $trackingStatus,
                null,
                __('Status updated by merchant')
            );
        } else {
            // Create new manual shipment tracking
            $trackingService->createManualShipment(
                $merchantPurchase->purchase_id,
                $user->id,
                0, // shipping_id - 0 for manual
                'manual',
                null,
                __('Manual Delivery'),
                0,
                0
            );

            // If status is not just 'created', update to the requested status
            if ($trackingStatus !== ShipmentTracking::STATUS_CREATED) {
                $trackingService->updateManually(
                    $merchantPurchase->purchase_id,
                    $user->id,
                    $trackingStatus,
                    null,
                    __('Status updated by merchant')
                );
            }
        }

        return redirect()->route('merchant-purchase-index')->with('success', __('Purchase Status Updated Successfully'));
    }

    /**
     * Map purchase status to shipment tracking status
     */
    private function mapPurchaseStatusToTrackingStatus(string $purchaseStatus): string
    {
        return match ($purchaseStatus) {
            'pending' => ShipmentTracking::STATUS_CREATED,
            'processing' => ShipmentTracking::STATUS_PICKED_UP,
            'on delivery' => ShipmentTracking::STATUS_IN_TRANSIT,
            'completed' => ShipmentTracking::STATUS_DELIVERED,
            'declined', 'cancelled' => ShipmentTracking::STATUS_CANCELLED,
            default => ShipmentTracking::STATUS_CREATED,
        };
    }

}
