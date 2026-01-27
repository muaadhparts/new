<?php

namespace App\Http\Controllers\User;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Commerce\Services\InvoiceSellerService;
use App\Domain\Shipping\Services\TrackingViewService;
use Illuminate\Http\Request;

class PurchaseController extends UserBaseController
{

    public function purchases()
    {
        $user = $this->user;
        // ✅ استخدام paginate بدلاً من get لتوفير التصفح
        $purchases = Purchase::where('user_id', '=', $user->id)
            ->latest('id')
            ->paginate(12);

        // PRE-COMPUTED: All display values (DATA_FLOW_POLICY - no formatting in view)
        $purchases->getCollection()->transform(function ($purchase) {
            // Status CSS class
            $purchase->status_class = in_array($purchase->status, ['pending', 'processing'])
                ? 'yellow-btn'
                : ($purchase->status == 'completed'
                    ? 'green-btn'
                    : ($purchase->status == 'declined'
                        ? 'red-btn'
                        : 'black-btn'));
            // Pre-formatted values
            $purchase->status_label = ucwords($purchase->status);
            $purchase->date_formatted = $purchase->created_at?->format('d M Y') ?? 'N/A';
            $purchase->total_formatted = \PriceHelper::showAdminCurrencyPrice(
                $purchase->pay_amount * $purchase->currency_value,
                $purchase->currency_sign
            );
            $purchase->details_url = route('user-purchase', $purchase->id);
            return $purchase;
        });

        return view('user.purchase.index', ['purchases' => $purchases]);
    }

    public function purchasetrack()
    {
        // Get user's purchases (no eager loading - service handles all)
        $purchases = Purchase::where('user_id', $this->user->id)
            ->latest('id')
            ->get();

        // Prepare tracking DTOs via service (no models in Blade)
        $purchasesData = app(TrackingViewService::class)->forPurchasesList($purchases);

        return view('user.purchase-track', ['purchasesData' => $purchasesData]);
    }

    public function trackload($id)
    {
        $user = $this->user;
        $purchase = $user->purchases()->where('purchase_number','=',$id)->first();
        $datas = array('Pending','Processing','On Delivery','Completed');

        // Load shipment trackings for tracking display
        $shipmentLogs = collect();
        if ($purchase) {
            $shipmentLogs = \App\Domain\Shipping\Models\ShipmentTracking::where('purchase_id', $purchase->id)
                ->orderBy('occurred_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // PRE-COMPUTED: Group logs by tracking number (DATA_FLOW_POLICY)
        $groupedLogs = $shipmentLogs->groupBy('tracking_number');

        return view('load.track-load', compact('purchase', 'datas', 'shipmentLogs', 'groupedLogs'));
    }


    public function purchase($id)
    {
        $purchase = $this->user->purchases()->whereId($id)->firstOrFail();
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        // Pre-compute shipping names for view (DATA_FLOW_POLICY)
        $shippingNamesFormatted = $purchase->getFormattedShippingNames();

        // PRE-COMPUTED: All display data (DATA_FLOW_POLICY - no formatting in view)
        $purchaseDisplay = [
            'date_formatted' => $purchase->created_at?->format('d-M-Y') ?? 'N/A',
            'tax_formatted' => \PriceHelper::showOrderCurrencyPrice($purchase->tax / $purchase->currency_value, $purchase->currency_sign),
            'paid_amount_formatted' => \PriceHelper::showOrderCurrencyPrice($purchase->pay_amount * $purchase->currency_value, $purchase->currency_sign),
            'shipping_cost_formatted' => \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost * $purchase->currency_value, $purchase->currency_sign),
            'payment_status_label' => $purchase->payment_status == 'Pending' ? __('Unpaid') : __('Paid'),
            'payment_status_class' => $purchase->payment_status == 'Pending' ? 'text-danger' : 'text-success',
            'print_url' => route('user-purchase-print', $purchase->id),
            'confirm_url' => route('user-confirm-delivery', $purchase->id),
        ];

        // PRE-COMPUTED: Cart items display data (DATA_FLOW_POLICY - no @php in view)
        $cartItemsDisplay = [];
        if (!empty($cart['items'])) {
            foreach ($cart['items'] as $key => $catalogItem) {
                $partNumber = $catalogItem['item']['part_number'] ?? '';
                $itemPrice = ($catalogItem['price'] ?? 0) * $purchase->currency_value;
                $discount = $catalogItem['discount'] ?? 0;
                $discountText = $discount == 0 ? '' : '(' . $discount . '% ' . __('Off') . ')';

                $cartItemsDisplay[$key] = [
                    'productUrl' => !empty($partNumber) ? route('front.part-result', $partNumber) : '#',
                    'unit_price_formatted' => \PriceHelper::showCurrencyPrice($itemPrice),
                    'total_price_formatted' => \PriceHelper::showCurrencyPrice($itemPrice),
                    'discount_text' => $discountText,
                ];
            }
        }

        // Pre-compute delivery fee if exists
        if (isset($trackingData['firstDelivery']['deliveryFee'])) {
            $trackingData['firstDelivery']['delivery_fee_formatted'] = \PriceHelper::showOrderCurrencyPrice(
                $trackingData['firstDelivery']['deliveryFee'] * $purchase->currency_value,
                $purchase->currency_sign
            );
        }

        return view('user.purchase.details', [
            'purchase' => $purchase,
            'purchaseDisplay' => $purchaseDisplay,
            'cart' => $cart,
            'trackingData' => $trackingData,
            'shippingNamesFormatted' => $shippingNamesFormatted,
            'cartItemsDisplay' => $cartItemsDisplay,
        ]);
    }

    // Digital downloads removed - Physical-only system
    public function purchasedownload($slug, $id)
    {
        return redirect()->back();
    }

    public function purchaseprint($id)
    {
        // Security: Only allow printing own purchases
        $purchase = $this->user->purchases()->whereId($id)->firstOrFail();
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        // Get seller info for each merchant purchase
        $merchantPurchases = $purchase->merchantPurchases()->with('user')->get();
        $sellersInfoLookup = app(InvoiceSellerService::class)->getSellerInfoBatch($merchantPurchases);

        // PRE-COMPUTED: Invoice header display data (DATA_FLOW_POLICY - no @php in view)
        $firstSeller = isset($sellersInfoLookup) && count($sellersInfoLookup) > 0 ? reset($sellersInfoLookup) : null;

        // PRE-COMPUTED: All print display values (DATA_FLOW_POLICY - no formatting in view)
        $printDisplayData = [
            'firstSeller' => $firstSeller,
            'showPlatform' => !$firstSeller || count($sellersInfoLookup) > 1 || ($firstSeller['is_platform'] ?? true),
            'date_formatted' => $purchase->created_at?->format('d-M-Y') ?? 'N/A',
            'tax_formatted' => \PriceHelper::showOrderCurrencyPrice($purchase->tax / $purchase->currency_value, $purchase->currency_sign),
            'paid_amount_formatted' => \PriceHelper::showOrderCurrencyPrice($purchase->pay_amount * $purchase->currency_value, $purchase->currency_sign),
        ];

        // Pre-compute delivery fee if exists
        if (isset($trackingData['firstDelivery']['deliveryFee'])) {
            $printDisplayData['delivery_fee_formatted'] = \PriceHelper::showOrderCurrencyPrice(
                $trackingData['firstDelivery']['deliveryFee'] * $purchase->currency_value,
                $purchase->currency_sign
            );
        }

        // PRE-COMPUTED: Cart items display data
        $cartItemsDisplay = [];
        if (!empty($cart['items'])) {
            foreach ($cart['items'] as $key => $catalogItem) {
                $itemPrice = ($catalogItem['price'] ?? 0) * $purchase->currency_value;
                $discount = $catalogItem['discount'] ?? 0;
                $discountText = $discount == 0 ? '' : '(' . $discount . '% ' . __('Off') . ')';

                $cartItemsDisplay[$key] = [
                    'unit_price_formatted' => \PriceHelper::showCurrencyPrice($itemPrice),
                    'total_price_formatted' => \PriceHelper::showCurrencyPrice($itemPrice),
                    'discount_text' => $discountText,
                ];
            }
        }

        return view('user.purchase.print', [
            'purchase' => $purchase,
            'cart' => $cart,
            'trackingData' => $trackingData,
            'sellersInfoLookup' => $sellersInfoLookup,
            'printDisplayData' => $printDisplayData,
            'cartItemsDisplay' => $cartItemsDisplay,
        ]);
    }

    public function trans()
    {
        $user = $this->user;
        $id = request()->input('id');
        $trans = request()->input('tin');

        // Security: Only allow updating own purchases
        $purchase = $user->purchases()->whereId($id)->firstOrFail();

        // Validate transaction ID
        if (empty($trans) || strlen($trans) > 255) {
            return response()->json(['error' => 'Invalid transaction ID'], 400);
        }

        $purchase->txnid = $trans;
        $purchase->update();
        $data = $purchase->txnid;
        return response()->json($data);
    }

    /**
     * STEP 5 (Optional): Customer confirms delivery receipt
     * NEW WORKFLOW: delivered -> confirmed
     */
    public function confirmDeliveryReceipt(Request $request, $purchaseId)
    {
        $user = $this->user;

        // Security: Only allow confirming own purchases
        $purchase = $user->purchases()->findOrFail($purchaseId);

        // Find the delivery courier record(s) for this purchase
        $deliveries = DeliveryCourier::where('purchase_id', $purchase->id)->get();

        if ($deliveries->isEmpty()) {
            return redirect()->back()->with('error', __('No courier delivery found for this purchase'));
        }

        $confirmed = 0;
        foreach ($deliveries as $delivery) {
            // Only confirm if status is 'delivered'
            if ($delivery->isDelivered()) {
                try {
                    $delivery->confirmByCustomer();
                    $confirmed++;
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }
        }

        if ($confirmed > 0) {
            // Add tracking entry
            $purchase->tracks()->create([
                'name' => __('Customer Confirmed Receipt'),
                'text' => __('Customer has confirmed receiving the delivery')
            ]);

            return redirect()->back()->with('success', __('Thank you! You have confirmed receiving your order.'));
        }

        return redirect()->back()->with('error', __('Delivery is not in a state that can be confirmed'));
    }

}
