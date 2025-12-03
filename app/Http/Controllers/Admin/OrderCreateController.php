<?php

namespace App\Http\Controllers\Admin;

use App\Classes\MuaadhMailer;
use App\Helpers\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Pagesetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class OrderCreateController extends AdminBaseController
{
    public function create(Request $request)
    {
        if ($request->products) {
            $selectd_products = $request->products;
            foreach ($selectd_products as $product) {
                $products[] = Product::findOrFail($product);
            }
        } else {
            $selectd_products = [];
            $products = [];
        }

        $sign = $this->curr;
        Session::forget('order_products');

        return view('admin.order.create.index', compact('products', 'selectd_products', 'sign'));
    }

    public function datatables()
    {
        // الاستعلام على السجلات التجارية مباشرة - كل سجل تجاري = صف مستقل
        $datas = \App\Models\MerchantProduct::with(['product', 'user', 'qualityBrand'])
            ->where('status', 1)
            ->whereHas('product', function($q) {
                $q->where('status', 1);
            });

        return Datatables::of($datas)
            ->addColumn('name', function (\App\Models\MerchantProduct $mp) {
                $product = $mp->product;
                if (!$product) return __('N/A');

                // السعر من merchant_products مع العمولة
                $gs = cache()->remember('generalsettings', now()->addDay(), fn () => \DB::table('generalsettings')->first());
                $price = (float) $mp->price;
                $base = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);
                $finalPrice = $base * $this->curr->value;

                $photoUrl = filter_var($product->photo, FILTER_VALIDATE_URL)
                    ? $product->photo
                    : ($product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png'));
                $img = '<img src="' . $photoUrl . '" alt="Image" class="img-thumbnail" width="100"> <br>';
                $name = getLocalizedProductName($product, 50);

                // معلومات البائع
                $vendorInfo = $mp->user ? '<span class="badge badge-info">' . ($mp->user->shop_name ?: $mp->user->name) . '</span>' : '';

                // حالة المنتج (جديد/مستعمل)
                $condition = $mp->product_condition == 1 ? ' <span class="badge badge-warning">' . __('Used') . '</span>' : '';

                // المخزون
                $stock = $mp->stock === null ? __('Unlimited') : (($mp->stock > 0) ? $mp->stock : '<span class="text-danger">' . __('Out Of Stock') . '</span>');

                return $img . $name . $condition . '<br>' . $vendorInfo . '<br><small>' . __("Price") . ': ' . number_format($finalPrice, 2) . ' ' . $this->curr->sign . '</small><br><small>' . __("Stock") . ': ' . $stock . '</small>';
            })

            ->addColumn('action', function (\App\Models\MerchantProduct $mp) {
                // نستخدم merchant_product_id بدلاً من product_id
                return '<div class="action-list"><a href="javascript:;" class="order_product_add" data-bs-toggle="modal" class="add-btn-small pl-2" data-bs-target="#add-product" data-href="' . $mp->id . '" data-product-id="' . $mp->product_id . '"> <i class="fas fa-plus"></i></a></div>';
            })

            ->rawColumns(['name', 'action'])
            ->toJson();
    }


    public function addProduct($product_id)
    {

        $order_products = Session::get('order_products');
        if (!$order_products) {
            $order_products = [];
        }
        if (!in_array($product_id, $order_products)) {
            $order_products[] = $product_id;
        }

        Session::put('order_products', $order_products);

        $sign = $this->curr;
        return view('admin.order.partials.product_add_table', compact('sign'));
    }


    public function orderStore(Request $request)
    {
        dd($request->all());
    }


    public function removeOrderProduct($product_id)
    {
        $products = Session::get('order_products');
        foreach ($products as $key => $product) {
            if ($product == $product_id) {
                unset($products[$key]);
            }
        }
        $sign = $this->curr;
        if ($products) {
            Session::put('order_products', $products);
        } else {
            Session::forget('order_products');
        }

        return view('admin.order.partials.product_add_table', compact('sign'));
    }


    public function product_show($id)
    {
        $data['productt'] = Product::find($id);
        $data['curr'] = $this->curr;
        return view('admin.order.create.add-product', $data);
    }

    public function addcart(Request $request)
    {
    
        $id = $_GET['id'];
        $qty = $_GET['qty'];
        $size = str_replace(' ', '-', $_GET['size']);
        $color = $_GET['color'];
        $size_qty = $_GET['size_qty'];
        $size_price = (float)$_GET['size_price'];
        $size_key = $_GET['size_key'];
        $keys =  $_GET['keys'];
        $color_price = isset($request->color_price) ? (float) $_GET['color_price'] : 0;
        $values = $_GET['values'] ? $_GET['values'] : null;
        $prices = $_GET['prices'] ? $_GET['prices'] : null;
        $affilate_user = isset($_GET['affilate_user']) ? $_GET['affilate_user'] : '0';
        $keys = $keys == "" ? '' : $keys;
        $values = $values == "" ? '' : $values;
        $curr = $this->curr;
       
        $size_price = ($size_price / $curr->value);
        $color_price = ($color_price / $curr->value);
        $prod = Product::where('id', '=', $id)->first(['id', 'slug', 'name', 'photo', 'type', 'file', 'measure', 'attributes']);
        if ($prod->type != 'Physical') {
            $qty = 1;
        }

        // Get the first active merchant product for this product
        $merchantProduct = $prod->merchantProducts()
            ->where('status', 1)
            ->orderBy('price')
            ->first();

        if ($merchantProduct) {
            $prc = $merchantProduct->price + $this->gs->fixed_commission + ($merchantProduct->price / 100) * $this->gs->percentage_commission;
            $prod->price = round($prc, 2);
            // Use merchant product data
            $prod->stock = $merchantProduct->stock;
            $prod->size = $merchantProduct->size;
            $prod->size_qty = $merchantProduct->size_qty;
            $prod->size_price = $merchantProduct->size_price;
            $prod->color = $merchantProduct->color;
            $prod->minimum_qty = $merchantProduct->minimum_qty;
            $prod->stock_check = $merchantProduct->stock_check;
            $prod->color_all = $merchantProduct->color_all;
        } else {
            // Fallback if no merchant product found
            $prod->price = 0;
            $prod->stock = 0;
        }
        if (!empty($prices)) {
            foreach (explode(',',$prices) as $data) {
                $prod->price += ($data / $curr->value);
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

        if ($size_qty == '0' && $prod->stock_check == 1) {

            return 0;
        }

        if (empty($color)) {
            // Get color from vendor colors (merchant_products.color_all)
            $vendorColors = $prod->getVendorColors();
            if (!empty($vendorColors)) {
                $color = $vendorColors[0];
            }
        }


        $color = str_replace('#', '', $color);
        $oldCart = Session::has('admin_cart') ? Session::get('admin_cart') : null;
        $cart = new Cart($oldCart);

        

        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $affilate_user);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
            return view('admin.order.create.product_add_table');
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {

            return view('admin.order.create.product_add_table');
        }
        if ($prod->stock_check == 1) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] > $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                    return view('admin.order.create.product_add_table');
                }
            }
        }


        $cart->totalPrice = 0;
        foreach ($cart->items as $data)
            $cart->totalPrice += $data['price'];
        Session::put('admin_cart', $cart);
        $data[0] = count($cart->items);
        $data[1] = $cart->totalPrice;
        $data[1] = \PriceHelper::showCurrencyPrice($data[1] * $curr->value);
        
        return view('admin.order.create.product_add_table');
    }



    public function removecart($id)
    {

        $oldCart = Session::has('admin_cart') ? Session::get('admin_cart') : null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
        Session::forget('admin_cart');
        if (count($cart->items) > 0) {
            Session::put('admin_cart', $cart);
        }

        return view('admin.order.create.product_add_table');
    }


    public function userAddress(Request $request)
    {
        Session::forget('order_address');
        if ($request->user_id == 'guest') {
            $isUser = 0;
            $country = Country::all();
            return view('admin.order.create.address_form', compact('country', 'isUser'));
        } else {
            $isUser = 1;
            $user = User::findOrFail($request->user_id);
            $country = Country::all();
            return view('admin.order.create.address_form', compact('user', 'country', 'isUser'));
        }
    }


    public function userAddressSubmit(Request $request)
    {
        Session::put('order_address', $request->all());
        return back();
    }


    public function viewCreateOrder(Request $request)
    {
        
        Session::put('order_address', $request->all());
      
        $cart = Session::get('admin_cart');
        $address = Session::get('order_address');
   
        return view('admin.order.create.view', compact('cart', 'address'));
    }


    public function CreateOrderSubmit()
    {

        $address = Session::get('order_address');
        $input = $address;
        $curr = Currency::where('is_default', '=', 1)->first();

        $oldCart = Session::get('admin_cart');
        $cart = new Cart($oldCart);
        OrderHelper::license_check($cart); // For License Checking
        $t_oldCart = Session::get('admin_cart');
        $t_cart = new Cart($t_oldCart);
        $new_cart = [];
        $new_cart['totalQty'] = $t_cart->totalQty;
        $new_cart['totalPrice'] = $t_cart->totalPrice;
        $new_cart['items'] = $t_cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

        $order = new Order;
        $input['cart'] = $new_cart;
        $input['totalQty'] = $t_cart->totalQty;
        $input['user_id'] = $address['user_id'] != 'guest' ? $address['user_id'] : NULL;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $cart->totalPrice / $this->curr->value;
        $input['order_number'] = Str::random(8) . time();
        $input['payment_status'] = 'Pending';

        $input['payment_status'] = "Completed";
        $input['txnid'] = Str::random(8) . time();
        $input['tax'] = 0;
        $input['method'] = 'Created By Admin';
        $input['currency_sign'] = $curr->sign;
        $input['currency_name'] = $curr->name;
        $input['currency_value'] = $curr->value;
        $input['shipping_cost'] = 0;
        $input['packing_cost'] = 0;



        $order->fill($input)->save();
        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
        $order->notifications()->create();


        OrderHelper::size_qty_check($cart); // For Size Quantiy Checking
        OrderHelper::stock_check($cart); // For Stock Checking
        OrderHelper::vendor_order_check($cart, $order); // For Vendor Order Checking

        Session::forget('admin_cart');
        Session::forget('order_address');


        if ($order->user_id != 0 && $order->wallet_price != 0) {
            OrderHelper::add_to_transaction($order, $order->wallet_price); // Store To Transactions
        }

        //Sending Email To Buyer
        $data = [
            'to' => $order->customer_email,
            'type' => "new_order",
            'cname' => $order->customer_name,
            'oamount' => "",
            'aname' => "",
            'aemail' => "",
            'wtitle' => "",
            'onumber' => $order->order_number,
        ];

        $mailer = new MuaadhMailer();
        $mailer->sendAutoOrderMail($data, $order->id);
        $ps = Pagesetting::first();
        //Sending Email To Admin
        $data = [
            'to' => $ps->contact_email,
            'subject' => "New Order Recieved!!",
            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
        ];
        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($data);

        return redirect(route('admin-order-show',$order->id))->with('added', 'Order has been placed successfully!');
    }
}
