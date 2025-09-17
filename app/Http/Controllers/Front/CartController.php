<?php

namespace App\Http\Controllers\Front;

use App\Models\Cart;
use App\Models\Country;
use App\Models\Generalsetting;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends FrontBaseController
{
    /* ===========================================================
     |  صفحة السلة
     |===========================================================*/
    public function cart(Request $request)
    {
        if (!Session::has('cart')) {
            return view('frontend.cart');
        }
        if (Session::has('already')) {
            Session::forget('already');
        }
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }
        if (Session::has('coupon_total')) {
            Session::forget('coupon_total');
        }
        if (Session::has('coupon_total1')) {
            Session::forget('coupon_total1');
        }
        if (Session::has('coupon_percentage')) {
            Session::forget('coupon_percentage');
        }

        $oldCart   = Session::get('cart');
        $cart      = new Cart($oldCart);
        $products  = $cart->items;
        $totalPrice = $cart->totalPrice;
        $mainTotal  = $totalPrice;

        if ($request->ajax()) {
            return view('frontend.ajax.cart-page', compact('products', 'totalPrice', 'mainTotal'));
        }

        return view('frontend.cart', compact('products', 'totalPrice', 'mainTotal'));
    }

    public function cartview()
    {
        return view('load.cart');
    }

    public function view_cart()
    {
        if (!Session::has('cart')) {
            return view('frontend.cart');
        }
        if (Session::has('already')) {
            Session::forget('already');
        }
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }
        if (Session::has('coupon_code')) {
            Session::forget('coupon_code');
        }
        if (Session::has('coupon_total')) {
            Session::forget('coupon_total');
        }
        if (Session::has('coupon_total1')) {
            Session::forget('coupon_total1');
        }
        if (Session::has('coupon_percentage')) {
            Session::forget('coupon_percentage');
        }

        $oldCart   = Session::get('cart');
        $cart      = new Cart($oldCart);
        $products  = $cart->items;
        $totalPrice = $cart->totalPrice;
        $mainTotal  = $totalPrice;

        return view('frontend.ajax.cart-page', compact('products', 'totalPrice', 'mainTotal'));
    }

    /* ===========================================================
     |  إضافة سريع للسلة (Ajax قديم) — يتطلب vendorId = request('user')
     |===========================================================*/
    public function addcart($id)
    {
        $vendorId = (int) request('user'); // إلزامي حسب السياسة
        if (!$vendorId) {
            return 0;
        }

        // المنتج (هوية فقط) — بدون user_id
        $prod = Product::where('id', $id)->first([
            'id','slug','name','photo','size','size_qty','size_price','color','sku',
            'price','stock','weight','type','file','link','license','license_qty',
            'measure','whole_sell_qty','whole_sell_discount','attributes','color_all','color_price'
        ]);
        if (!$prod) {
            return 0;
        }

        // سجل البائع
        $mp = MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) {
            return 0;
        }

        // حقن سياق البائع داخل الـ Product (خصائص runtime للتوافق مع كود Cart)
        $prod->user_id             = $vendorId;              // inject فقط (ليس عمود)
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice(); // السعر النهائي (مقاس+خصائص+عمولة)
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        // 🔧 حقن خصائص المقاس للبائع (لتفعيل accessors على Product)
        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);
        // خصائص إضافية لو مخزنة على merchant_products
        $prod->setAttribute('stock_check',         $mp->stock_check ?? $prod->stock_check);
        $prod->setAttribute('minimum_qty',         $mp->minimum_qty ?? $prod->minimum_qty);
        $prod->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? $prod->whole_sell_qty);
        $prod->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? $prod->whole_sell_discount);
        $prod->setAttribute('color_all',           $mp->color_all ?? $prod->color_all);

        // Set Attrubutes
        $keys = '';
        $values = '';

        if (!empty($prod->license_qty)) {
            $lcheck = 1;
            foreach ($prod->license_qty as $ttl => $dtl) {
                if ($dtl < 1) { $lcheck = 0; } else { $lcheck = 1; break; }
            }
            if ($lcheck == 0) {
                return 0;
            }
        }

        // Size
        $size = '';
        if (!empty($prod->size)) {
            $size = trim($prod->size[0]);
        }
        $size = str_replace(' ', '-', $size);

        // Color
        $color = '';
        if (!empty($prod->color)) {
            $color = $prod->color[0];
            $color = str_replace('#', '', $color);
        }

        // Attributes prices
        if (!empty($prod->attributes)) {
            $attrArr = json_decode($prod->attributes, true);
            if (!empty($attrArr)) {
                $count = count($attrArr);
                $j = 0;
                foreach ($attrArr as $attrKey => $attrVal) {
                    if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                        $keys .= $attrKey . ($j == $count - 1 ? '' : ','); $j++;
                        foreach ($attrVal['values'] as $optionKey => $optionVal) {
                            $values      .= $optionVal . ',';
                            $prod->price += $attrVal['prices'][$optionKey];
                            break;
                        }
                    }
                }
            }
        }
        $keys   = rtrim($keys, ',');
        $values = rtrim($values, ',');

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        if ($cart->items != null && @$cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
            return 'digital';
        }

        $cart->add($prod, $prod->id, $size, $color, $keys, $values);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {
            return 0;
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] >
                $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                return 0;
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);
        $data[0] = count($cart->items);
        return response()->json($data);
    }

    /* ===========================================================
     |  إضافة للسلة (تحقق من minimum_qty) — يتطلب vendorId
     |===========================================================*/
    public function addtocart($id)
    {
        $vendorId = (int) request('user'); // إلزامي
        if (!$vendorId) {
            return redirect()->route('front.cart')->with('unsuccess', __('Missing vendor.'));
        }

        $prod = Product::where('id', $id)->first([
            'id','slug','sku','name','photo','size','size_qty','size_price','color',
            'price','stock','weight','type','file','link','license','license_qty',
            'measure','whole_sell_qty','whole_sell_discount','attributes','minimum_qty',
            'color_price','color_all','stock_check'
        ]);
        if (!$prod) {
            return redirect()->route('front.cart')->with('unsuccess', __('Product not found.'));
        }

        $mp = MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) {
            return redirect()->route('front.cart')->with('unsuccess', __('Vendor listing not found or inactive.'));
        }

        // inject vendor context
        $prod->user_id             = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        // inject vendor size attributes
        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);
        $prod->setAttribute('stock_check',         $mp->stock_check ?? $prod->stock_check);
        $prod->setAttribute('minimum_qty',         $mp->minimum_qty ?? $prod->minimum_qty);
        $prod->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? $prod->whole_sell_qty);
        $prod->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? $prod->whole_sell_discount);
        $prod->setAttribute('color_all',           $mp->color_all ?? $prod->color_all);

        // Attributes
        $keys = '';
        $values = '';
        if (!empty($prod->license_qty)) {
            $lcheck = 1;
            foreach ($prod->license_qty as $ttl => $dtl) {
                if ($dtl < 1) { $lcheck = 0; } else { $lcheck = 1; break; }
            }
            if ($lcheck == 0) {
                return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
            }
        }

        $size = '';
        if (!empty($prod->size)) {
            $size = trim($prod->size[0]);
        }

        $color = '';
        if (!empty($prod->color)) {
            $color = $prod->color[0];
            $color = str_replace('#', '', $color);
        }

        if (!empty($prod->attributes)) {
            $attrArr = json_decode($prod->attributes, true);
            if (!empty($attrArr)) {
                $count = count($attrArr);
                $j = 0;
                foreach ($attrArr as $attrKey => $attrVal) {
                    if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                        $keys .= $attrKey . ($j == $count - 1 ? '' : ','); $j++;
                        foreach ($attrVal['values'] as $optionKey => $optionVal) {
                            $values      .= $optionVal . ',';
                            $prod->price += $attrVal['prices'][$optionKey];
                            break;
                        }
                    }
                }
            }
        }
        $keys   = rtrim($keys, ',');
        $values = rtrim($values, ',');

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        // minimum_qty checks
        if (!empty($cart->items)) {
            if (!empty($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] < $minimum_qty) {
                    return redirect()->route('front.cart')->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            } else {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($prod->minimum_qty != null && 1 < $minimum_qty) {
                    return redirect()->route('front.cart')->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            }
        } else {
            $minimum_qty = (int) $prod->minimum_qty;
            if ($prod->minimum_qty != null && 1 < $minimum_qty) {
                return redirect()->route('front.cart')->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
            }
        }

        if ($cart->items != null && @$cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
            return redirect()->route('front.cart')->with('unsuccess', __('This item is already in the cart.'));
        }

        $cart->add($prod, $prod->id, $size, $color, $keys, $values);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {
            return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] >
                $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);
        return redirect()->route('front.cart');
    }

    /* ===========================================================
     |  إضافة بعدد معيّن (Ajax) — يتطلب vendorId
     |===========================================================*/
    public function addnumcart(Request $request)
    {
        $id         = (int) ($request->id ?? $_GET['id'] ?? 0);
        $qty        = isset($request->qty) ? (int)$request->qty : 1;
        $size       = isset($request->size) ? str_replace(' ', '-', $request->size) : '';
        $color      = isset($request->color) ? $request->color : '';
        $color_price = isset($request->color_price) ? (float) $_GET['color_price'] : 0;
        $size_qty   = isset($request->size_qty) ? $_GET['size_qty'] : '';
        $size_price = isset($request->size_price) ? (float) $_GET['size_price'] : 0;

        $size_key = isset($request->size_qty) ? $_GET['size_qty'] : '';
        $keys   = isset($request->keys)   ? $request->keys   : '';
        $values = isset($request->values) ? $request->values : '';
        $prices = isset($request->prices) ? $request->prices : 0;

        $affilate_user = isset($_GET['affilate_user']) ? $_GET['affilate_user'] : '0';
        $keys   = $keys   == "" ? '' : implode(',', (array)$keys);
        $values = $values == "" ? '' : implode(',', (array)$values);
        $curr   = $this->curr;

        $size_price  = ($size_price / $curr->value);
        $color_price = ($color_price / $curr->value);

        $prod = Product::where('id', $id)->first([
            'id','slug','sku','name','photo','size','size_qty','size_price','color',
            'price','stock','weight','type','file','link','license','license_qty',
            'measure','whole_sell_qty','whole_sell_discount','attributes','minimum_qty',
            'stock_check','color_all'
        ]);
        if (!$prod) { return 0; }
        if ($prod->type != 'Physical') { $qty = 1; }

        // vendorId: من الطلب أو من العنصر الموجود بالسلة (إن وُجد)
        $vendorId = (int) $request->input('user', 0);
        if (!$vendorId) {
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            if ($oldCart && isset($oldCart->items[$id . $size . str_replace('#','',$color) . str_replace(str_split(' ,'), '', $values)])) {
                $itm = $oldCart->items[$id . $size . str_replace('#','',$color) . str_replace(str_split(' ,'), '', $values)];
                $vendorId = (int) ($itm['item']['user_id'] ?? ($itm['item']->user_id ?? 0));
            }
        }
        if (!$vendorId) { return 0; }

        $mp = MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) { return 0; }

        // inject vendor context
        $prod->user_id             = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        // inject vendor size attributes
        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);
        $prod->setAttribute('stock_check',         $mp->stock_check ?? $prod->stock_check);
        $prod->setAttribute('minimum_qty',         $mp->minimum_qty ?? $prod->minimum_qty);
        $prod->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? $prod->whole_sell_qty);
        $prod->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? $prod->whole_sell_discount);
        $prod->setAttribute('color_all',           $mp->color_all ?? $prod->color_all);

        if (!empty($prices)) {
            foreach ((array)$prices as $data) {
                $prod->price += ((float)$data / $curr->value);
            }
        }

        if (!empty($prod->license_qty)) {
            $lcheck = 1;
            foreach ($prod->license_qty as $ttl => $dtl) {
                if ($dtl < 1) { $lcheck = 0; } else { $lcheck = 1; break; }
            }
            if ($lcheck == 0) { return 0; }
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
            if (!empty($prod->color)) {
                $color = $prod->color[0];
            }
        }
        $color = str_replace('#', '', $color);

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        if (!empty($cart->items)) {
            $key = $id . $size . $color . str_replace(str_split(' ,'), '', $values);
            if (!empty($cart->items[$key])) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($cart->items[$key]['qty'] < $minimum_qty) {
                    $data = []; $data[1] = true; $data[2] = $minimum_qty;
                    return response()->json($data);
                }
            } else if ($prod->minimum_qty != null) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($qty < $minimum_qty) {
                    $data = []; $data[1] = true; $data[2] = $minimum_qty;
                    return response()->json($data);
                }
            }
        } else if ($prod->minimum_qty != null) {
            $minimum_qty = (int) $prod->minimum_qty;
            if ($qty < $minimum_qty) {
                $data = []; $data[3] = true; $data[4] = $minimum_qty;
                return response()->json($data);
            }
        }

        if (isset($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)])) {
            if ($cart->items != null && $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
                return 'digital';
            }
        }

        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $affilate_user);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {
            return 0;
        }
        if ($prod->stock_check == 1) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] >
                    $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                    return 0;
                }
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);
        $data[0] = count($cart->items);
        $data[1] = $cart->totalPrice;
        $data[1] = \PriceHelper::showCurrencyPrice($data[1] * $curr->value);
        return response()->json($data);
    }

    /* ===========================================================
     |  إضافة بعدد معيّن (Navigate) — يتطلب vendorId
     |===========================================================*/
    public function addtonumcart(Request $request)
    {
        $id         = isset($request->id) ? (int)$request->id : 0;
        $qty        = isset($request->qty) ? (int)$request->qty : 1;
        $size       = isset($request->size) ? str_replace(' ', '-', $request->size) : '';
        $color      = isset($request->color) ? "#" . $request->color : '';
        $colorPrice = isset($request->color_price) ? (float) $_GET['color_price'] : 0;
        $size_qty   = isset($request->size_qty) ? $_GET['size_qty'] : '';
        $size_price = isset($request->size_price) ? (float) $_GET['size_price'] : 0;
        $size_key   = isset($request->size_qty) ? $_GET['size_qty'] : '';
        $keysArr    = isset($request->keys) ? explode(",", $request->keys) : '';
        $valsArr    = isset($request->values) ? explode(",", $request->values) : '';
        $pricesArr  = isset($request->prices) ? explode(",", $request->prices) : 0;
        $aff        = isset($_GET['affilate_user']) ? $_GET['affilate_user'] : '0';

        $keys   = !$keysArr ? '' : implode(',', $keysArr);
        $values = !$valsArr ? '' : implode(',', $valsArr);

        $curr = $this->curr;

        $size_price = ($size_price / $curr->value);
        $colorPrice = (float) $_GET['color_price'];

        $prod = Product::where('id', $id)->first([
            'id','slug','name','photo','size','size_qty','size_price','color',
            'price','stock','weight','type','file','link','license','license_qty',
            'measure','whole_sell_qty','whole_sell_discount','attributes','minimum_qty',
            'stock_check','color_price','color_all'
        ]);
        if (!$prod) {
            return redirect()->route('front.cart')->with('unsuccess', __('Product not found.'));
        }
        if ($prod->type != 'Physical') { $qty = 1; }

        // vendorId من الطلب أو من السلة
        $vendorId = (int) $request->input('user', 0);
        if (!$vendorId) {
            $keyGuess = $id . $size . str_replace('#','',$color) . str_replace(str_split(' ,'), '', $values);
            $oldCart  = Session::has('cart') ? Session::get('cart') : null;
            if ($oldCart && isset($oldCart->items[$keyGuess])) {
                $itm = $oldCart->items[$keyGuess];
                $vendorId = (int) ($itm['item']['user_id'] ?? ($itm['item']->user_id ?? 0));
            }
        }
        if (!$vendorId) {
            return redirect()->route('front.cart')->with('unsuccess', __('Missing vendor.'));
        }

        $mp = MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) {
            return redirect()->route('front.cart')->with('unsuccess', __('Vendor listing not found or inactive.'));
        }

        // inject vendor context
        $prod->user_id             = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        // inject vendor size attributes
        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);
        $prod->setAttribute('stock_check',         $mp->stock_check ?? $prod->stock_check);
        $prod->setAttribute('minimum_qty',         $mp->minimum_qty ?? $prod->minimum_qty);
        $prod->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? $prod->whole_sell_qty);
        $prod->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? $prod->whole_sell_discount);
        $prod->setAttribute('color_all',           $mp->color_all ?? $prod->color_all);

        if (!empty($pricesArr) && !empty($pricesArr[0])) {
            foreach ($pricesArr as $p) {
                $prod->price += ((float)$p / $curr->value);
            }
        }

        if (!empty($prod->license_qty)) {
            $lcheck = 1;
            foreach ($prod->license_qty as $ttl => $dtl) {
                if ($dtl < 1) { $lcheck = 0; } else { $lcheck = 1; break; }
            }
            if ($lcheck == 0) {
                return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
            }
        }

        if (empty($size)) {
            if (!empty($prod->size)) {
                $size = trim($prod->size[0]);
            }
            $size = str_replace(' ', '-', $size);
        }

        if ($size_qty == '0') {
            return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
        }

        if (empty($color)) {
            if (!empty($prod->color)) {
                $color = $prod->color[0];
            }
        }
        $color = str_replace('#', '', $color);

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        // minimum_qty checks
        if (!empty($cart->items)) {
            $key = $id . $size . $color . str_replace(str_split(' ,'), '', $values);
            if (!empty($cart->items[$key])) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($cart->items[$key]['qty'] < $minimum_qty) {
                    return redirect()->route('front.cart')->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            } else if ($prod->minimum_qty != null) {
                $minimum_qty = (int) $prod->minimum_qty;
                if ($qty < $minimum_qty) {
                    return redirect()->route('front.cart')->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
                }
            }
        } else if ($prod->minimum_qty != null) {
            $minimum_qty = (int) $prod->minimum_qty;
            if ($qty < $minimum_qty) {
                return redirect()->route('front.cart')->with('unsuccess', __('Minimum Quantity is:') . ' ' . $prod->minimum_qty);
            }
        }

        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $colorPrice, $size_key, $keys, $values, $aff);

        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['dp'] == 1) {
            return redirect()->route('front.cart')->with('unsuccess', __('This item is already in the cart.'));
        }
        if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['stock'] < 0) {
            return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
        }
        if ($prod->stock_check == 1) {
            if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                if ($cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['qty'] >
                    $cart->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)]['size_qty']) {
                    return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
                }
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);
        return redirect()->route('front.cart')->with('success', __('Successfully Added To Cart.'));
    }

    /* ===========================================================
     |  زيادة كمية عنصر واحد (Ajax) — يستخرج vendorId من العنصر
     |===========================================================*/
    public function addbyone()
    {
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }
        $curr      = $this->curr;
        $id        = $_GET['id'];
        $itemid    = $_GET['itemid'];
        $size_qty  = $_GET['size_qty'];
        $size_price = $_GET['size_price'];

        // المنتج (هوية فقط)
        $prod = Product::where('id', $id)->first([
            'id','slug','name','photo','size','size_qty','size_price','color','price','stock',
            'weight','type','file','link','license','license_qty','measure','whole_sell_qty',
            'whole_sell_discount','attributes','stock_check'
        ]);
        if (!$prod) {
            return 0;
        }

        // vendorId من السلة
        $oldCart  = Session::has('cart') ? Session::get('cart') : null;
        $vendorId = 0;
        if ($oldCart && isset($oldCart->items[$itemid])) {
            $itm      = $oldCart->items[$itemid];
            $vendorId = (int) ($itm['item']['user_id'] ?? ($itm['item']->user_id ?? 0));
        }
        if (!$vendorId) { return 0; }

        $mp = MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) { return 0; }

        // inject vendor context + خصائص المقاسات
        $prod->user_id             = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);

        // إضافة أسعار الخصائص الافتراضية كما في كودك
        if (!empty($prod->attributes)) {
            $attrArr = json_decode($prod->attributes, true);
            if (!empty($attrArr)) {
                foreach ($attrArr as $attrKey => $attrVal) {
                    if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                        foreach ($attrVal['values'] as $optionKey => $optionVal) {
                            $prod->price += $attrVal['prices'][$optionKey];
                            break;
                        }
                    }
                }
            }
        }

        $cart = new Cart($oldCart);
        $cart->adding($prod, $itemid, $size_qty, $size_price);

        if ($prod->stock_check == 1) {
            if ($cart->items[$itemid]['stock'] < 0) {
                return 0;
            }
            if (!empty($size_qty)) {
                if ($cart->items[$itemid]['qty'] > $cart->items[$itemid]['size_qty']) {
                    return 0;
                }
            }
        }

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);

        $data[0] = $cart->totalPrice;
        $data[3] = $data[0];
        $data[1] = $cart->items[$itemid]['qty'];
        $data[2] = $cart->items[$itemid]['price'];

        $data[0] = \PriceHelper::showCurrencyPrice($data[0] * $curr->value);
        $data[2] = \PriceHelper::showCurrencyPrice($data[2] * $curr->value);
        $data[3] = \PriceHelper::showCurrencyPrice($data[3] * $curr->value);
        $data[4] = $cart->items[$itemid]['discount'] == 0 ? '' : '(' . $cart->items[$itemid]['discount'] . '% ' . __('Off') . ')';

        return response()->json($data);
    }

    /* ===========================================================
     |  إنقاص كمية عنصر واحد (Ajax) — يستخرج vendorId من العنصر
     |===========================================================*/
    public function reducebyone()
    {
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }
        $curr      = $this->curr;
        $id        = $_GET['id'];
        $itemid    = $_GET['itemid'];
        $size_qty  = $_GET['size_qty'];
        $size_price = $_GET['size_price'];

        $prod = Product::where('id', $id)->first([
            'id','slug','name','photo','size','size_qty','size_price','color','price','stock',
            'weight','type','file','link','license','license_qty','measure','whole_sell_qty',
            'whole_sell_discount','attributes'
        ]);
        if (!$prod) {
            return 0;
        }

        $oldCart  = Session::has('cart') ? Session::get('cart') : null;
        $vendorId = 0;
        if ($oldCart && isset($oldCart->items[$itemid])) {
            $itm      = $oldCart->items[$itemid];
            $vendorId = (int) ($itm['item']['user_id'] ?? ($itm['item']->user_id ?? 0));
        }
        if (!$vendorId) { return 0; }

        $mp = MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) { return 0; }

        $prod->user_id             = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);

        if (!empty($prod->attributes)) {
            $attrArr = json_decode($prod->attributes, true);
            if (!empty($attrArr)) {
                foreach ($attrArr as $attrKey => $attrVal) {
                    if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                        foreach ($attrVal['values'] as $optionKey => $optionVal) {
                            $prod->price += $attrVal['prices'][$optionKey];
                            break;
                        }
                    }
                }
            }
        }

        $cart = new Cart($oldCart);
        $cart->reducing($prod, $itemid, $size_qty, $size_price);

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);

        $data[0] = $cart->totalPrice;
        $data[3] = $data[0];
        $data[1] = $cart->items[$itemid]['qty'];
        $data[2] = $cart->items[$itemid]['price'];

        $data[0] = \PriceHelper::showCurrencyPrice($data[0] * $curr->value);
        $data[2] = \PriceHelper::showCurrencyPrice($data[2] * $curr->value);
        $data[3] = \PriceHelper::showCurrencyPrice($data[3] * $curr->value);
        $data[4] = $cart->items[$itemid]['discount'] == 0 ? '' : '(' . $cart->items[$itemid]['discount'] . '% ' . __('Off') . ')';

        return response()->json($data);
    }

    /* ===========================================================
     |  إزالة عنصر من السلة
     |===========================================================*/
    public function removecart($id)
    {
        $curr    = $this->curr;
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        $cart->removeItem($id);
        Session::forget('cart');
        Session::forget('already');
        Session::forget('coupon');
        Session::forget('coupon_total');
        Session::forget('coupon_total1');
        Session::forget('coupon_percentage');

        Session::put('cart', $cart);
        if (count($cart->items) == 0) {
            Session::forget('cart');
        }

        return back()->with('success', __('Item has been removed from cart.'));
    }

    /* ===========================================================
     |  ضريبة الدولة / الولاية
     |===========================================================*/
    public function country_tax(Request $request)
    {
        if ($request->country_id) {
            if ($request->state_id != 0) {
                $state = State::findOrFail($request->state_id);
                $tax   = $state->tax;
                $data[11] = $state->id;
                $data[12] = 'state_tax';
            } else {
                $country  = Country::findOrFail($request->country_id);
                $tax      = $country->tax;
                $data[11] = $country->id;
                $data[12] = 'country_tax';
            }
        } else {
            $tax = 0;
        }

        Session::put('is_tax', $tax);

        $total  = (float) preg_replace('/[^0-9\.]/ui', '', $_GET['total']);
        $stotal = ($total * $tax) / 100;
        $sstotal = $stotal * $this->curr->value;
        Session::put('current_tax', $sstotal);

        $total = $total + $stotal;

        $data[0] = round($total, 2);
        $data[1] = $tax;

        if (Session::has('coupon')) {
            $data[0] = round($data[0] - Session::get('coupon'), 2);
        }

        return response()->json($data);
    }
}
