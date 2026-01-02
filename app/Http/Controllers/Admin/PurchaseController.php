<?php

namespace App\Http\Controllers\Admin;

use App\Classes\MuaadhMailer;use App\Models\AffliateBonus;
use App\Models\Cart;use App\Models\DeliveryRider;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\PurchaseTimeline;
use App\Models\Package;

use App\Models\CatalogItem;
use App\Models\Rider;
use App\Models\RiderServiceArea;
use App\Models\Shipping;

use App\Models\User;
use Carbon\Carbon;
use Datatables;
use Illuminate\Http\Request;
use Session;

class PurchaseController extends AdminBaseController
{
    //*** GET Request
    public function purchases(Request $request)
    {
        if ($request->status == 'pending') {
            return view('admin.purchase.pending');
        } else if ($request->status == 'processing') {
            return view('admin.purchase.processing');
        } else if ($request->status == 'completed') {
            return view('admin.purchase.completed');
        } else if ($request->status == 'declined') {
            return view('admin.purchase.declined');
        } else {
            return view('admin.purchase.index');
        }
    }

    public function processing()
    {
        return view('admin.purchase.processing');
    }

    public function completed()
    {
        return view('admin.purchase.completed');
    }

    public function declined()
    {
        return view('admin.purchase.declined');
    }

    public function datatables($status)
    {
        if ($status == 'pending') {
            $datas = Purchase::where('status', '=', 'pending')->latest('id')->get();
        } elseif ($status == 'processing') {
            $datas = Purchase::where('status', '=', 'processing')->latest('id')->get();
        } elseif ($status == 'completed') {
            $datas = Purchase::where('status', '=', 'completed')->latest('id')->get();
        } elseif ($status == 'declined') {
            $datas = Purchase::where('status', '=', 'declined')->latest('id')->get();
        } else {
            $datas = Purchase::latest('id')->get();
        }

        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('id', function (Purchase $data) {
                $id = '<a href="' . route('admin-purchase-invoice', $data->id) . '">' . $data->purchase_number . '</a>';
                return $id;
            })
            ->editColumn('pay_amount', function (Purchase $data) {
                return \PriceHelper::showOrderCurrencyPrice((($data->pay_amount + $data->wallet_price) * $data->currency_value), $data->currency_sign);
            })
            ->addColumn('action', function (Purchase $data) {
                $purchases = '<a href="javascript:;" data-href="' . route('admin-purchase-edit', $data->id) . '" class="delivery" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fas fa-dollar-sign"></i> ' . __('Delivery Status') . '</a>';
                return '<div class="godropdown"><button class="go-dropdown-toggle">' . __('Actions') . '<i class="fas fa-chevron-down"></i></button><div class="action-list"><a href="' . route('admin-purchase-show', $data->id) . '" > <i class="fas fa-eye"></i> ' . __('View Details') . '</a><a href="javascript:;" class="send" data-email="' . $data->customer_email . '" data-bs-toggle="modal" data-bs-target="#vendorform"><i class="fas fa-envelope"></i> ' . __('Send') . '</a><a href="javascript:;" data-href="' . route('admin-purchase-timeline', $data->id) . '" class="track" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fas fa-truck"></i> ' . __('Track Purchase') . '</a>' . $purchases . '</div></div>';
            })
            ->rawColumns(['id', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function show($id)
    {
        $purchase = Purchase::findOrFail($id);
        $cart = json_decode($purchase->cart, true);
        return view('admin.purchase.details', compact('purchase', 'cart'));
    }

    public function invoice($id)
    {
        $purchase = Purchase::findOrFail($id);
        $cart = json_decode($purchase->cart, true);
        return view('admin.purchase.invoice', compact('purchase', 'cart'));
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
        $cart = json_decode($purchase->cart, true);
        return view('admin.purchase.print', compact('purchase', 'cart'));
    }

    public function license(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);
        $cart = json_decode($purchase->cart, true);
        $cart['items'][$request->license_key]['license'] = $request->license;
        $new_cart = json_encode($cart);
        $purchase->cart = $new_cart;
        $purchase->update();
        $msg = __('Successfully Changed The License Key.');
        return redirect()->back()->with('license', $msg);
    }

    public function edit($id)
    {
        $data = Purchase::find($id);
        return view('admin.purchase.delivery', compact('data'));
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
                            $deliveryRider = DeliveryRider::where('purchase_id', $data->id)->where('merchant_id', $mid)->first();
                            if ($deliveryRider) {
                                $rider = Rider::findOrFail($deliveryRider->rider_id);
                                $service_area = RiderServiceArea::findOrFail($deliveryRider->service_area_id);
                                $rider->balance += $service_area->price;
                                $rider->update();
                            }
                        }
                    }

                    foreach ($data->merchantPurchases as $merchantPurchase) {
                        $uprice = User::find($merchantPurchase->user_id);
                        $uprice->current_balance = $uprice->current_balance + $merchantPurchase->price;
                        $merchantPurchase->status = 'completed';
                        $merchantPurchase->update();

                        $uprice->update();
                        $uprice->update();
                    }

                    if (User::where('id', $data->affilate_user)->exists()) {
                        $auser = User::where('id', $data->affilate_user)->first();
                        $auser->affilate_income += $data->affilate_charge;
                        $auser->update();

                        $affiliate_bonus = new AffliateBonus();
                        $affiliate_bonus->refer_id = $auser->id;
                        $affiliate_bonus->bonus = $data->affilate_charge;
                        $affiliate_bonus->type = 'Purchase';
                        $affiliate_bonus->user_id = $data->user_id;
                        $affiliate_bonus->created_at = Carbon::now();
                        $affiliate_bonus->customer_email = $data->customer_email;
                        $affiliate_bonus->save();
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

                    // Refund User Wallet If Any
                    if ($data->user_id != 0) {
                        if ($data->wallet_price != 0) {
                            $user = User::find($data->user_id);
                            if ($user) {
                                $user->balance = $user->balance + $data->wallet_price;
                                $user->save();
                            }
                        }
                    }

                    $cart = json_decode($data->cart, true);

                    // Restore Product Stock If Any - Update merchant_products instead
                    foreach ($cart->items as $prod) {
                        $x = (string) $prod['stock'];
                        if ($x != null) {
                            // Find the merchant product that was used for this purchase item
                            $merchantId = $prod['item']['user_id'] ?? null;
                            if ($merchantId) {
                                $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $prod['item']['id'])
                                    ->where('user_id', $merchantId)
                                    ->first();

                                if ($merchantItem) {
                                    $merchantItem->stock = $merchantItem->stock + $prod['qty'];
                                    $merchantItem->update();
                                }
                            }
                        }
                    }

                    // Restore Product Size Qty If Any - Update merchant_products instead
                    foreach ($cart->items as $prod) {
                        $x = (string) $prod['size_qty'];
                        if (!empty($x)) {
                            $merchantId = $prod['item']['user_id'] ?? null;
                            if ($merchantId) {
                                $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $prod['item']['id'])
                                    ->where('user_id', $merchantId)
                                    ->first();

                                if ($merchantItem && $merchantItem->size_qty) {
                                    $x = (int) $x;
                                    $temp = explode(',', $merchantItem->size_qty);
                                    $temp[$prod['size_key']] = $x;
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
                    $title = ucwords($request->status);
                    $ck = PurchaseTimeline::where('purchase_id', '=', $id)->where('title', '=', $title)->first();
                    if ($ck) {
                        $ck->purchase_id = $id;
                        $ck->title = $title;
                        $ck->text = $request->track_text;
                        $ck->update();
                    } else {
                        $data = new PurchaseTimeline;
                        $data->purchase_id = $id;
                        $data->title = $title;
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

    public function product_submit(Request $request)
    {
        $sku = $request->sku;
        $merchantId = $request->merchant_id;

        // Find product through merchant_items relationship
        $merchantItem = \App\Models\MerchantItem::where('user_id', $merchantId)
            ->whereHas('catalogItem', function($query) use ($sku) {
                $query->where('sku', $sku)->where('status', 1);
            })
            ->with('catalogItem')
            ->where('status', 1)
            ->first();

        $data = array();
        if (!$merchantItem || !$merchantItem->catalogItem) {
            $data[0] = false;
            $data[1] = __('No Product Found');
        } else {
            $data[0] = true;
            $data[1] = $merchantItem->product->id;
        }
        return response()->json($data);
    }

    public function product_show($id)
    {
        $data['productt'] = CatalogItem::find($id);
        $data['curr'] = $this->curr;
        return view('admin.purchase.add-product', $data);
    }

    public function addcart($id)
    {
        $purchase = Purchase::find($id);
        $id = $_GET['id'];
        $qty = $_GET['qty'];
        $size = str_replace(' ', '-', $_GET['size']);
        $color = $_GET['color'];
        $size_qty = $_GET['size_qty'];
        $size_price = (float) $_GET['size_price'];
        $size_key = $_GET['size_key'];
        $affilate_user = isset($_GET['affilate_user']) ? $_GET['affilate_user'] : '0';
        $keys = $_GET['keys'];
        $keys = explode(",", $keys);
        $values = $_GET['values'];
        $values = explode(",", $values);
        $prices = $_GET['prices'];
        $prices = explode(",", $prices);
        $keys = $keys == "" ? '' : implode(',', $keys);
        $values = $values == "" ? '' : implode(',', $values);
        $size_price = ($size_price / $purchase->currency_value);

        // Get product with merchant data
        $product = CatalogItem::where('id', '=', $id)->first(['id', 'slug', 'name', 'photo', 'type', 'file', 'link', 'license', 'license_qty', 'measure', 'attributes']);

        // Get merchant-specific data from merchant_items
        $merchantId = (int) ($_GET['merchant_id'] ?? 0);
        $merchantItem = null;
        if ($merchantId > 0) {
            $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $id)
                ->where('user_id', $merchantId)
                ->where('status', 1)
                ->first();
        }

        if (!$merchantItem) {
            return redirect()->back()->with('unsuccess', __('Product not available from this vendor.'));
        }

        // Create a combined product object with merchant data
        $prod = (object) array_merge($product->toArray(), [
            'user_id' => $merchantItem->user_id,
            'price' => $merchantItem->price,
            'stock' => $merchantItem->stock,
            'size' => $merchantItem->size ? explode(',', $merchantItem->size) : null,
            'size_qty' => $merchantItem->size_qty ? explode(',', $merchantItem->size_qty) : null,
            'size_price' => $merchantItem->size_price ? explode(',', $merchantItem->size_price) : null,
            'minimum_qty' => $merchantItem->minimum_qty,
            'whole_sell_qty' => $merchantItem->whole_sell_qty,
            'whole_sell_discount' => $merchantItem->whole_sell_discount
        ]);

        if ($prod->user_id != 0) {
            $prc = $prod->price + $this->gs->fixed_commission + ($prod->price / 100) * $this->gs->percentage_commission;
            $prod->price = round($prc, 2);
        }
        if (!empty($prices)) {
            if (!empty($prices[0])) {
                foreach ($prices as $data) {
                    $prod->price += ($data / $purchase->currency_value);
                }
            }
        }

        if (!empty($prod->license_qty)) {
            $lcheck = 1;
            foreach ($prod->license_qty as $ttl => $dtl) {
                if ($dtl < 1) {
                    $lcheck = 0;
                } else {
                    $lcheck = 1;
                    break;
                }
            }
            if ($lcheck == 0) {
                return 0;
            }
        }

        if (empty($size)) {
            if (!empty($prod->size)) {
                $size = trim($prod->size[0]);
            }
            $size = str_replace(' ', '-', $size);
        }

        if (empty($color)) {
            // Get color from merchant colors (merchant_items.color_all)
            $merchantColors = $prod->getMerchantColors();
            if (!empty($merchantColors)) {
                $color = $merchantColors[0];
            }
        }

        $color = str_replace('#', '', $color);
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        if (!empty($cart->items)) {
            if (!empty($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] < $minimum_qty) {
                    return redirect()->back()->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            } else {
                if ($prod->minimum_qty != null) {
                    $minimum_qty = (int) $prod->minimum_qty;
                    if ($qty < $minimum_qty) {
                        return redirect()->back()->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                    }
                }
            }
        } else {
            $minimum_qty = (int) $prod->minimum_qty;
            if ($prod->minimum_qty != null) {
                if ($qty < $minimum_qty) {
                    return redirect()->back()->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            }
        }
        $color_price = isset($request->color_price) ? (float) $_GET['color_price'] : 0;
        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $affilate_user);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
            return redirect()->back()->with('unsuccess', __('This item is already in the cart.'));
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {
            return redirect()->back()->with('unsuccess', __('Out Of Stock.'));
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] > $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                return redirect()->back()->with('unsuccess', __('Out Of Stock.'));
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        $o_cart = json_decode($purchase->cart, true);

        $purchase->totalQty = $purchase->totalQty + $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
        $purchase->pay_amount = $purchase->pay_amount + $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];

        $prev_qty = 0;
        $prev_price = 0;

        if (!empty($o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {
            $prev_qty = $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
            $prev_price = $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];
        }

        $prev_qty += $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
        $prev_price += $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];

        $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)] = $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)];
        $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] = $prev_qty;
        $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'] = $prev_price;

        $purchase->cart = json_encode($o_cart);

        $purchase->update();
        return redirect()->back()->with('success', __('Successfully Added To Cart.'));
    }

    public function product_edit($id, $itemid, $purchaseid)
    {

        $product = CatalogItem::find($itemid);

        $purchase = Purchase::find($purchaseid);
        $cart = json_decode($purchase->cart, true);
        $data['productt'] = $product;
        $data['item_id'] = $id;
        $data['prod'] = $id;
        $data['purchase'] = $purchase;
        $data['item'] = $cart['items'][$id];
        $data['curr'] = $this->curr;

        return view('admin.purchase.edit-product', $data);
    }

    public function updatecart($id)
    {
        $purchase = Purchase::find($id);
        $id = $_GET['id'];
        $qty = $_GET['qty'];
        $size = str_replace(' ', '-', $_GET['size']);
        $color = $_GET['color'];
        $size_qty = $_GET['size_qty'];
        $size_price = (float) $_GET['size_price'];
        $size_key = $_GET['size_key'];
        $affilate_user = isset($_GET['affilate_user']) ? $_GET['affilate_user'] : '0';
        $keys = $_GET['keys'];
        $keys = explode(",", $keys);
        $values = $_GET['values'];
        $values = explode(",", $values);
        $prices = $_GET['prices'];
        $prices = explode(",", $prices);
        $keys = $keys == "" ? '' : implode(',', $keys);
        $values = $values == "" ? '' : implode(',', $values);

        $item_id = $_GET['item_id'];

        $size_price = ($size_price / $purchase->currency_value);

        // Get product with merchant data
        $product = CatalogItem::where('id', '=', $id)->first(['id', 'slug', 'name', 'photo', 'type', 'file', 'link', 'license', 'license_qty', 'measure', 'attributes']);

        // Get merchant-specific data from merchant_items
        $merchantId = (int) ($_GET['merchant_id'] ?? 0);
        $merchantItem = null;
        if ($merchantId > 0) {
            $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $id)
                ->where('user_id', $merchantId)
                ->where('status', 1)
                ->first();
        }

        if (!$merchantItem) {
            return redirect()->back()->with('unsuccess', __('Product not available from this vendor.'));
        }

        // Create a combined product object with merchant data
        $prod = (object) array_merge($product->toArray(), [
            'user_id' => $merchantItem->user_id,
            'price' => $merchantItem->price,
            'stock' => $merchantItem->stock,
            'size' => $merchantItem->size ? explode(',', $merchantItem->size) : null,
            'size_qty' => $merchantItem->size_qty ? explode(',', $merchantItem->size_qty) : null,
            'size_price' => $merchantItem->size_price ? explode(',', $merchantItem->size_price) : null,
            'minimum_qty' => $merchantItem->minimum_qty,
            'whole_sell_qty' => $merchantItem->whole_sell_qty,
            'whole_sell_discount' => $merchantItem->whole_sell_discount
        ]);

        if ($prod->user_id != 0) {
            $prc = $prod->price + $this->gs->fixed_commission + ($prod->price / 100) * $this->gs->percentage_commission;
            $prod->price = round($prc, 2);
        }
        if (!empty($prices)) {
            if (!empty($prices[0])) {
                foreach ($prices as $data) {
                    $prod->price += ($data / $purchase->currency_value);
                }
            }
        }

        if (!empty($prod->license_qty)) {
            $lcheck = 1;
            foreach ($prod->license_qty as $ttl => $dtl) {
                if ($dtl < 1) {
                    $lcheck = 0;
                } else {
                    $lcheck = 1;
                    break;
                }
            }
            if ($lcheck == 0) {
                return 0;
            }
        }
        if (empty($size)) {
            if (!empty($prod->size)) {
                $size = trim($prod->size[0]);
            }
            $size = str_replace(' ', '-', $size);
        }

        if (empty($color)) {
            // Get color from merchant colors (merchant_items.color_all)
            $merchantColors = $prod->getMerchantColors();
            if (!empty($merchantColors)) {
                $color = $merchantColors[0];
            }
        }
        $color = str_replace('#', '', $color);
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        if (!empty($cart->items)) {
            if (!empty($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] < $minimum_qty) {
                    return redirect()->back()->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            } else {
                if ($prod->minimum_qty != null) {
                    $minimum_qty = (int) $prod->minimum_qty;
                    if ($qty < $minimum_qty) {
                        return redirect()->back()->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                    }
                }
            }
        } else {
            $minimum_qty = (int) $prod->minimum_qty;
            if ($prod->minimum_qty != null) {
                if ($qty < $minimum_qty) {
                    return redirect()->back()->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            }
        }
        $color_price = 0;

        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $affilate_user);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
            return redirect()->back()->with('unsuccess', __('This item is already in the cart.'));
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {
            return redirect()->back()->with('unsuccess', __('Out Of Stock.'));
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] > $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                return redirect()->back()->with('unsuccess', __('Out Of Stock.'));
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        $o_cart = json_decode($purchase->cart, true);

        if (!empty($o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {

            $cart_qty = $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
            $cart_price = $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];

            $prev_qty = $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
            $prev_price = $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];

            $temp_qty = 0;
            $temp_price = 0;

            if ($o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] < $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty']) {

                $temp_qty = $cart_qty - $prev_qty;
                $temp_price = $cart_price - $prev_price;

                $purchase->totalQty += $temp_qty;
                $purchase->pay_amount += $temp_price;
                $prev_qty += $temp_qty;
                $prev_price += $temp_price;
            } elseif ($o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] > $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty']) {

                $temp_qty = $prev_qty - $cart_qty;
                $temp_price = $prev_price - $cart_price;

                $purchase->totalQty -= $temp_qty;
                $purchase->pay_amount -= $temp_price;
                $prev_qty -= $temp_qty;
                $prev_price -= $temp_price;
            }
        } else {

            $purchase->totalQty -= $o_cart['items'][$item_id]['qty'];

            $purchase->pay_amount -= $o_cart['items'][$item_id]['price'];

            unset($o_cart['items'][$item_id]);

            $purchase->totalQty = $purchase->totalQty + $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
            $purchase->pay_amount = $purchase->pay_amount + $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];

            $prev_qty = 0;
            $prev_price = 0;

            if (!empty($o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {
                $prev_qty = $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
                $prev_price = $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];
            }

            $prev_qty += $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'];
            $prev_price += $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'];
        }

        $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)] = $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)];
        $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] = $prev_qty;
        $o_cart['items'][$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['price'] = $prev_price;

        $purchase->cart = json_encode($o_cart);

        $purchase->update();
        return redirect()->back()->with('success', __('Successfully Updated The Cart.'));
    }

    public function product_delete($id, $purchaseid)
    {

        $purchase = Purchase::find($purchaseid);
        $cart = json_decode($purchase->cart, true);

        $purchase->totalQty = $purchase->totalQty - $cart['items'][$id]['qty'];
        $purchase->pay_amount = $purchase->pay_amount - $cart['items'][$id]['price'];
        unset($cart['items'][$id]);
        $purchase->cart = json_encode($cart);

        $purchase->update();

        return redirect()->back()->with('success', __('Successfully Deleted From The Cart.'));
    }
}
