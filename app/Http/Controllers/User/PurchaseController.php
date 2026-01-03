<?php

namespace App\Http\Controllers\User;

use App\{
    Models\Purchase,
    Models\CatalogItem
};

class PurchaseController extends UserBaseController
{

    public function purchases()
    {
        $user = $this->user;
        $purchases = Purchase::where('user_id','=',$user->id)->latest('id')->get();
        return view('user.purchase.index',compact('user','purchases'));
    }

    public function purchasetrack()
    {
        $user = $this->user;
        return view('user.purchase-track',compact('user'));
    }

    public function trackload($id)
    {
        $user = $this->user;
        $purchase = $user->purchases()->where('purchase_number','=',$id)->first();
        $datas = array('Pending','Processing','On Delivery','Completed');

        // Load shipment logs for tracking display
        $shipmentLogs = collect();
        if ($purchase) {
            $shipmentLogs = \App\Models\ShipmentStatusLog::where('purchase_id', $purchase->id)
                ->orderBy('status_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('load.track-load', compact('purchase', 'datas', 'shipmentLogs'));
    }


    public function purchase($id)
    {
        $user = $this->user;
        $purchase = $user->purchases()->whereId($id)->firstOrFail();
        $cart = json_decode($purchase->cart, true);;
        return view('user.purchase.details',compact('user','purchase','cart'));
    }

    public function purchasedownload($slug,$id)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number','=',$slug)->first();
        $catalogItem = CatalogItem::findOrFail($id);
        if(!isset($purchase) || $catalogItem->type == 'Physical' || $purchase->user_id != $user->id)
        {
            return redirect()->back();
        }
        return response()->download(public_path('assets/files/'.$catalogItem->file));
    }

    public function purchaseprint($id)
    {
        $user = $this->user;
        // Security: Only allow printing own purchases
        $purchase = $user->purchases()->whereId($id)->firstOrFail();
        $cart = json_decode($purchase->cart, true);
        return view('user.purchase.print',compact('user','purchase','cart'));
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

}
