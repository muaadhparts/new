<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use App\Models\ShipmentTracking;
use App\Services\ShipmentTrackingService;
use App\Services\TrackingViewService;
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

        return view('merchant.purchase.index', compact('purchases', 'user'));
    }

    public function show($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart;

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forMerchant($purchase, $user->id);

        return view('merchant.purchase.details', compact('user', 'purchase', 'cart', 'trackingData'));
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

        // جلب بيانات MerchantPurchase في الـ Controller بدلاً من الـ View
        $merchantPurchase = $purchase->merchantPurchases()
            ->where('user_id', $user->id)
            ->first();

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

        return view('merchant.purchase.invoice', compact('user', 'purchase', 'cart', 'trackingData', 'merchantInvoiceData'));
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

        return view('merchant.purchase.print', compact('user', 'purchase', 'cart', 'trackingData'));
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
