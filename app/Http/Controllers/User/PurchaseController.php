<?php

namespace App\Http\Controllers\User;

use App\{
    Models\Purchase,
    Models\CatalogItem,
    Models\DeliveryCourier
};
use App\Services\TrackingViewService;
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

        return view('user.purchase.index', compact('user', 'purchases'));
    }

    public function purchasetrack()
    {
        $user = $this->user;

        // Get user's purchases (no eager loading - service handles all)
        $purchases = Purchase::where('user_id', $user->id)
            ->latest('id')
            ->get();

        // Prepare tracking DTOs via service (no models in Blade)
        $purchasesData = app(TrackingViewService::class)->forPurchasesList($purchases);

        return view('user.purchase-track', compact('user', 'purchasesData'));
    }

    public function trackload($id)
    {
        $user = $this->user;
        $purchase = $user->purchases()->where('purchase_number','=',$id)->first();
        $datas = array('Pending','Processing','On Delivery','Completed');

        // Load shipment trackings for tracking display
        $shipmentLogs = collect();
        if ($purchase) {
            $shipmentLogs = \App\Models\ShipmentTracking::where('purchase_id', $purchase->id)
                ->orderBy('occurred_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('load.track-load', compact('purchase', 'datas', 'shipmentLogs'));
    }


    public function purchase($id)
    {
        $user = $this->user;
        $purchase = $user->purchases()->whereId($id)->firstOrFail();
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        return view('user.purchase.details', compact('user', 'purchase', 'cart', 'trackingData'));
    }

    // Digital downloads removed - Physical-only system
    public function purchasedownload($slug, $id)
    {
        return redirect()->back();
    }

    public function purchaseprint($id)
    {
        $user = $this->user;
        // Security: Only allow printing own purchases
        $purchase = $user->purchases()->whereId($id)->firstOrFail();
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        return view('user.purchase.print', compact('user', 'purchase', 'cart', 'trackingData'));
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
                'title' => __('Customer Confirmed Receipt'),
                'text' => __('Customer has confirmed receiving the delivery')
            ]);

            return redirect()->back()->with('success', __('Thank you! You have confirmed receiving your order.'));
        }

        return redirect()->back()->with('error', __('Delivery is not in a state that can be confirmed'));
    }

}
