<?php

namespace App\Http\Controllers\Operator;

use App\Classes\MuaadhMailer;
use App\Models\ReferralCommission;
// MerchantCart removed - use MerchantCartManager for customer cart
use App\Models\DeliveryCourier;
use App\Models\MerchantCommission;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\PurchaseTimeline;
use App\Models\CatalogItem;
use App\Models\Courier;
use App\Models\CourierServiceArea;
use App\Models\Shipping;
use App\Models\User;
use App\Services\TrackingViewService;
use Carbon\Carbon;
use Datatables;
use Illuminate\Http\Request;
use Session;

class PurchaseController extends OperatorBaseController
{
    //*** GET Request
    public function purchases(Request $request)
    {
        if ($request->status == 'pending') {
            return view('operator.purchase.pending');
        } else if ($request->status == 'processing') {
            return view('operator.purchase.processing');
        } else if ($request->status == 'completed') {
            return view('operator.purchase.completed');
        } else if ($request->status == 'declined') {
            return view('operator.purchase.declined');
        } else {
            return view('operator.purchase.index');
        }
    }

    public function processing()
    {
        return view('operator.purchase.processing');
    }

    public function completed()
    {
        return view('operator.purchase.completed');
    }

    public function declined()
    {
        return view('operator.purchase.declined');
    }

    public function datatables($status)
    {
        // Load purchases with merchant relationships
        $query = Purchase::with(['merchantPurchases.user']);

        if ($status == 'pending') {
            $datas = $query->where('status', '=', 'pending')->latest('id')->get();
        } elseif ($status == 'processing') {
            $datas = $query->where('status', '=', 'processing')->latest('id')->get();
        } elseif ($status == 'completed') {
            $datas = $query->where('status', '=', 'completed')->latest('id')->get();
        } elseif ($status == 'declined') {
            $datas = $query->where('status', '=', 'declined')->latest('id')->get();
        } else {
            $datas = $query->latest('id')->get();
        }

        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('id', function (Purchase $data) {
                $id = '<a href="' . route('operator-purchase-invoice', $data->id) . '">' . $data->purchase_number . '</a>';
                return $id;
            })
            ->editColumn('pay_amount', function (Purchase $data) {
                return \PriceHelper::showOrderCurrencyPrice(($data->pay_amount * $data->currency_value), $data->currency_sign);
            })
            ->addColumn('merchants', function (Purchase $data) {
                // Show merchants involved in this purchase
                $merchantNames = $data->merchantPurchases->map(function ($mp) {
                    return $mp->user ? ($mp->user->shop_name ?? $mp->user->name) : __('Unknown');
                })->unique()->implode(', ');
                return $merchantNames ?: __('N/A');
            })
            ->addColumn('action', function (Purchase $data) {
                $purchases = '<a href="javascript:;" data-href="' . route('operator-purchase-edit', $data->id) . '" class="delivery" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fas fa-dollar-sign"></i> ' . __('Delivery Status') . '</a>';
                return '<div class="godropdown"><button class="go-dropdown-toggle">' . __('Actions') . '<i class="fas fa-chevron-down"></i></button><div class="action-list"><a href="' . route('operator-purchase-show', $data->id) . '" > <i class="fas fa-eye"></i> ' . __('View Details') . '</a><a href="javascript:;" class="send" data-email="' . $data->customer_email . '" data-bs-toggle="modal" data-bs-target="#merchantform"><i class="fas fa-envelope"></i> ' . __('Send') . '</a><a href="javascript:;" data-href="' . route('operator-purchase-timeline', $data->id) . '" class="track" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fas fa-truck"></i> ' . __('Track Purchase') . '</a>' . $purchases . '</div></div>';
            })
            ->rawColumns(['id', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function show($id)
    {
        $purchase = Purchase::findOrFail($id);
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        return view('operator.purchase.details', compact('purchase', 'cart', 'trackingData'));
    }

    public function invoice($id)
    {
        $purchase = Purchase::findOrFail($id);
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        return view('operator.purchase.invoice', compact('purchase', 'cart', 'trackingData'));
    }

    public function emailsub(Request $request)
    {
        $gs = Muaadhsetting::findOrFail(1);
        if ($gs->is_smtp == 1) {
            $data = [
                'to' => $request->to,
                'subject' => $request->subject,
                'body' => $request->message,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);
        } else {
            $data = 0;
            $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
            $mail = mail($request->to, $request->subject, $request->message, $headers);
            if ($mail) {
                $data = 1;
            }
        }

        return response()->json($data);
    }

    public function printpage($id)
    {
        $purchase = Purchase::findOrFail($id);
        $cart = $purchase->cart; // Model cast handles decoding

        // Prepare tracking data for view (no logic in Blade)
        $trackingData = app(TrackingViewService::class)->forPurchase($purchase);

        return view('operator.purchase.print', compact('purchase', 'cart', 'trackingData'));
    }

    public function edit($id)
    {
        $data = Purchase::find($id);
        return view('operator.purchase.delivery', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Logic Section
        $data = Purchase::findOrFail($id);

        $input = $request->all();
        if ($request->has('status')) {
            if ($data->status == "completed") {
                $input['status'] = "completed";
                $data->update($input);
                $msg = __('Status Updated Successfully.');
                return response()->json($msg);
            } else {
                if ($input['status'] == "completed") {

                    if ($data->merchant_ids) {
                        $merchant_ids = json_decode($data->merchant_ids, true);

                        foreach ($merchant_ids as $mid) {
                            $deliveryCourier = DeliveryCourier::where('purchase_id', $data->id)->where('merchant_id', $mid)->first();
                            if ($deliveryCourier) {
                                $courier = Courier::findOrFail($deliveryCourier->courier_id);
                                $service_area = CourierServiceArea::findOrFail($deliveryCourier->service_area_id);
                                $courier->balance += $service_area->price;
                                $courier->update();
                            }
                        }
                    }

                    foreach ($data->merchantPurchases as $merchantPurchase) {
                        $merchantPurchase->status = 'completed';
                        $merchantPurchase->update();
                    }

                    if (User::where('id', $data->affilate_user)->exists()) {
                        $auser = User::where('id', $data->affilate_user)->first();
                        $auser->affilate_income += $data->affilate_charge;
                        $auser->update();

                        $referral_commission = new ReferralCommission();
                        $referral_commission->refer_id = $auser->id;
                        $referral_commission->bonus = $data->affilate_charge;
                        $referral_commission->type = 'Purchase';
                        $referral_commission->user_id = $data->user_id;
                        $referral_commission->created_at = Carbon::now();
                        $referral_commission->customer_email = $data->customer_email;
                        $referral_commission->save();
                    }

                    if ($data->affilate_users != null) {
                        $ausers = json_decode($data->affilate_users, true);
                        foreach ($ausers as $auser) {
                            $user = User::find($auser['user_id']);
                            if ($user) {
                                $user->affilate_income += $auser['charge'];
                                $user->update();
                            }
                        }
                    }

                    $maildata = [
                        'to' => $data->customer_email,
                        'subject' => 'Your purchase ' . $data->purchase_number . ' is Confirmed!',
                        'body' => "Hello " . $data->customer_name . "," . "\n Thank you for shopping with us. We are looking forward to your next visit.",
                    ];

                    $mailer = new MuaadhMailer();
                    $mailer->sendCustomMail($maildata);
                }
                if ($input['status'] == "declined") {

                    $cart = $data->cart; // Model cast handles decoding

                    // Restore CatalogItem Stock If Any - Update merchant_items instead
                    foreach ($cart['items'] as $cartItem) {
                        $x = (string) $cartItem['stock'];
                        if ($x != null) {
                            // Find the merchant item that was used for this purchase item
                            $merchantId = $cartItem['item']['user_id'] ?? null;
                            if ($merchantId) {
                                $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $cartItem['item']['id'])
                                    ->where('user_id', $merchantId)
                                    ->first();

                                if ($merchantItem) {
                                    $merchantItem->stock = $merchantItem->stock + $cartItem['qty'];
                                    $merchantItem->update();
                                }
                            }
                        }
                    }

                    // Restore CatalogItem Size Qty If Any - Update merchant_items instead
                    foreach ($cart['items'] as $cartItem) {
                        $x = (string) $cartItem['size_qty'];
                        if (!empty($x)) {
                            $merchantId = $cartItem['item']['user_id'] ?? null;
                            if ($merchantId) {
                                $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $cartItem['item']['id'])
                                    ->where('user_id', $merchantId)
                                    ->first();

                                if ($merchantItem && $merchantItem->size_qty) {
                                    $x = (int) $x;
                                    $temp = explode(',', $merchantItem->size_qty);
                                    $temp[$cartItem['size_key']] = $x;
                                    $temp1 = implode(',', $temp);
                                    $merchantItem->size_qty = $temp1;
                                    $merchantItem->update();
                                }
                            }
                        }
                    }

                    $maildata = [
                        'to' => $data->customer_email,
                        'subject' => 'Your purchase ' . $data->purchase_number . ' is Declined!',
                        'body' => "Hello " . $data->customer_name . "," . "\n We are sorry for the inconvenience caused. We are looking forward to your next visit.",
                    ];
                    $mailer = new MuaadhMailer();
                    $mailer->sendCustomMail($maildata);
                }

                $data->update($input);

                if ($request->track_text) {
                    $name = ucwords($request->status);
                    $ck = PurchaseTimeline::where('purchase_id', '=', $id)->where('name', '=', $name)->first();
                    if ($ck) {
                        $ck->purchase_id = $id;
                        $ck->name = $name;
                        $ck->text = $request->track_text;
                        $ck->update();
                    } else {
                        $data = new PurchaseTimeline;
                        $data->purchase_id = $id;
                        $data->name = $name;
                        $data->text = $request->track_text;
                        $data->save();
                    }
                }

                $msg = __('Status Updated Successfully.');
                return response()->json($msg);
            }
        }

        $data->update($input);
        $msg = __('Data Updated Successfully.');
        return redirect()->back()->with('success', $msg);
    }

    public function catalogItem_submit(Request $request)
    {
        $part_number = $request->part_number;
        $merchantId = $request->merchant_id;

        // Find catalogItem through merchant_items relationship
        $merchantItem = \App\Models\MerchantItem::where('user_id', $merchantId)
            ->whereHas('catalogItem', function($query) use ($part_number) {
                $query->where('part_number', $part_number)->where('status', 1);
            })
            ->with('catalogItem')
            ->where('status', 1)
            ->first();

        $data = array();
        if (!$merchantItem || !$merchantItem->catalogItem) {
            $data[0] = false;
            $data[1] = __('No CatalogItem Found');
        } else {
            $data[0] = true;
            $data[1] = $merchantItem->catalogItem->id;
        }
        return response()->json($data);
    }

    public function catalogItem_show($id)
    {
        $data['catalogItem'] = CatalogItem::find($id);
        $data['curr'] = $this->curr;
        return view('operator.purchase.add-catalogItem', $data);
    }

    public function catalogItem_edit($id, $itemid, $purchaseid)
    {

        $catalogItem = CatalogItem::find($itemid);

        $purchase = Purchase::find($purchaseid);
        $cart = $purchase->cart; // Model cast handles decoding
        $data['catalogItem'] = $catalogItem;
        $data['item_id'] = $id;
        $data['prod'] = $id;
        $data['purchase'] = $purchase;
        $data['item'] = $cart['items'][$id];
        $data['curr'] = $this->curr;

        return view('operator.purchase.edit-catalogItem', $data);
    }

    public function catalogItem_delete($id, $purchaseid)
    {

        $purchase = Purchase::find($purchaseid);
        $cart = $purchase->cart; // Model cast handles decoding

        $purchase->totalQty = $purchase->totalQty - $cart['items'][$id]['qty'];
        $purchase->pay_amount = $purchase->pay_amount - $cart['items'][$id]['price'];
        unset($cart['items'][$id]);
        $purchase->cart = $cart; // Model cast handles encoding

        $purchase->update();

        return redirect()->back()->with('success', __('Successfully Deleted From The Cart.'));
    }
}
