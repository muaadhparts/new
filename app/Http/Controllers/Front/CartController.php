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
    public function cart(Request $request)
    {
        if (!Session::has('cart')) {
            return view('frontend.cart');
        }
        foreach (['already','coupon','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            if (Session::has($k)) Session::forget($k);
        }

        $oldCart    = Session::get('cart');
        $cart       = new Cart($oldCart);
        $products   = $cart->items;
        $totalPrice = $cart->totalPrice;
        $mainTotal  = $totalPrice;

        if ($request->ajax()) {
            return view('frontend.ajax.cart-page', compact('products', 'totalPrice', 'mainTotal'));
        }
        return view('frontend.cart', compact('products', 'totalPrice', 'mainTotal'));
    }

    public function cartview() { return view('load.cart'); }

    public function view_cart()
    {
        if (!Session::has('cart')) { return view('frontend.cart'); }
        foreach (['already','coupon','coupon_code','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            if (Session::has($k)) Session::forget($k);
        }
        $oldCart    = Session::get('cart');
        $cart       = new Cart($oldCart);
        $products   = $cart->items;
        $totalPrice = $cart->totalPrice;
        $mainTotal  = $totalPrice;
        return view('frontend.ajax.cart-page', compact('products', 'totalPrice', 'mainTotal'));
    }

    /* ===================== Utilities ===================== */

    private function toArrayValues($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') return array_map('trim', explode(',', $v));
        return [];
    }
    private function findSizeIndex(array $sizes, string $size)
    {
        if ($size === '') return false;
        return array_search(trim($size), array_map('trim', $sizes), true);
    }
    private function pickAvailableSize(?string $sizeCsv, ?string $qtyCsv): array
    {
        $sizes = $this->toArrayValues($sizeCsv ?? '');
        $qtys  = $this->toArrayValues($qtyCsv  ?? '');
        $picked = '';
        $pickedQty = null;
        foreach ($sizes as $i => $sz) {
            $q = (int)($qtys[$i] ?? 0);
            if ($q > 0) { $picked = $sz; $pickedQty = $q; break; }
        }
        if ($picked === '' && !empty($sizes)) {
            $picked    = $sizes[0];
            $pickedQty = (int)($qtys[0] ?? 0);
        }
        return [$picked, $pickedQty];
    }
    private function pickDefaultListing(int $productId): ?MerchantProduct
    {
        return MerchantProduct::where('product_id', $productId)
            ->where('status', 1)
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock=0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price', 'ASC')
            ->first();
    }
    private function effectiveStock(MerchantProduct $mp, string $size = ''): int
    {
        if (!empty($mp->size) && !empty($mp->size_qty) && $size !== '') {
            $sizes = $this->toArrayValues($mp->size);
            $qtys  = $this->toArrayValues($mp->size_qty);
            $idx   = $this->findSizeIndex($sizes, $size);
            if ($idx !== false && isset($qtys[$idx]) && $qtys[$idx] !== '') return (int)$qtys[$idx];
            return 0;
        }
        return (int)($mp->stock ?? 0);
    }
    private function fetchIdentity(int $id): ?Product
    {
        return Product::query()->select([
            'id','slug','sku','name','photo','color',
            'weight','type','file','link','measure','attributes','color_all','cross_products'
        ])->find($id);
    }
    private function fetchListingOrFallback(Product $prod, ?int $vendorId): ?MerchantProduct
    {
        if ($vendorId) {
            $mp = MerchantProduct::where('product_id', $prod->id)
                ->where('user_id', $vendorId)
                ->where('status', 1)
                ->first();
            if ($mp) return $mp;
        }
        return $this->pickDefaultListing($prod->id);
    }
    private function injectMerchantContext(Product $prod, MerchantProduct $mp): void
    {
        $prod->user_id             = $mp->user_id;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);
        $prod->setAttribute('stock_check',         $mp->stock_check ?? null);
        $prod->setAttribute('minimum_qty',         $mp->minimum_qty ?? null);
        $prod->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? null);
        $prod->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? null);
    }
    private function normNum($v, $default = 0.0) { return is_numeric($v) ? (float)$v : (float)$default; }

    /* ===================== addcart ===================== */
    public function addcart($id)
    {
        $prod = $this->fetchIdentity($id); if (!$prod) return 0;
        $vendorId = (int) request('user');
        $mp = $this->fetchListingOrFallback($prod, $vendorId); if (!$mp) return 0;

        $size = (string) request('size', '');
        if ($size === '') { [$size, $_] = $this->pickAvailableSize($mp->size, $mp->size_qty); }

        $color = '';
        if (!empty($prod->color)) {
            $colors = $this->toArrayValues($prod->color);
            if (!empty($colors)) $color = $colors[0];
        }

        $effStock = $this->effectiveStock($mp, $size);
        if ($effStock <= 0) return 0;

        $this->injectMerchantContext($prod, $mp);
        $keys   = (string) request('keys','');
        $values = (string) request('values','');

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        $cart->add($prod, $prod->id, $size, $color, $keys, $values);

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) $cart->totalPrice += $data['price'];

        Session::put('cart', $cart);
        return response()->json([count($cart->items)]);
    }

    /* ===================== addtocart ===================== */
    public function addtocart($id)
    {
        $prod = $this->fetchIdentity($id);
        if (!$prod) return redirect()->route('front.cart')->with('unsuccess', __('Product not found.'));

        $vendorId = (int) request('user', 0);
        $mp = $this->fetchListingOrFallback($prod, $vendorId);
        if (!$mp) return redirect()->route('front.cart')->with('unsuccess', __('Vendor listing not found or inactive.'));

        $size = (string) request('size','');
        if ($size === '') { [$size, $_] = $this->pickAvailableSize($mp->size, $mp->size_qty); }

        $color = '';
        if (!empty($prod->color)) {
            $colors = $this->toArrayValues($prod->color);
            if (!empty($colors)) $color = $colors[0];
        }

        $effStock = $this->effectiveStock($mp, $size);
        if ($effStock <= 0) return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));

        $this->injectMerchantContext($prod, $mp);
        $keys   = (string) request('keys','');
        $values = (string) request('values','');

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        $cart->add($prod, $prod->id, $size, $color, $keys, $values);

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) $cart->totalPrice += $data['price'];

        Session::put('cart', $cart);
        return redirect()->route('front.cart');
    }

    /* ===================== addnumcart ===================== */
    public function addnumcart(Request $request)
    {
        $id   = (int) ($request->id ?? $_GET['id'] ?? 0);
        $qty  = (int) ($request->qty ?? 1);
        $size = (string) ($request->size ?? '');
        $color= (string) ($request->color ?? '');

        $size_qty   = (string) ($request->size_qty   ?? '');
        $size_price = $this->normNum($request->size_price ?? 0);
        $color_price= $this->normNum($request->color_price ?? 0);

        $size_key = (string) ($request->size_qty ?? '');
        $keys     = (string) $request->input('keys','');
        $values   = (string) $request->input('values','');
        $prices   = $request->input('prices', 0);
        $curr     = $this->curr;

        $prod = $this->fetchIdentity($id); if (!$prod) return 0;
        if ($prod->type != 'Physical') { $qty = 1; }

        $vendorId = (int) $request->input('user', 0);
        $mp = $this->fetchListingOrFallback($prod, $vendorId); if (!$mp) return 0;

        if ($size === '') { [$size, $_] = $this->pickAvailableSize($mp->size, $mp->size_qty); }
        $effStock = $this->effectiveStock($mp, $size);
        if ($effStock <= 0 || $qty > $effStock) return 0;

        $this->injectMerchantContext($prod, $mp);
        if (!empty($prices)) foreach ((array)$prices as $p) $prod->price += ((float)$p / $curr->value);

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $request->input('affilate_user','0'));

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) $cart->totalPrice += $data['price'];

        Session::put('cart', $cart);
        return response()->json([
            count($cart->items),
            \PriceHelper::showCurrencyPrice($cart->totalPrice * $curr->value),
        ]);
    }

    /* ===================== addtonumcart ===================== */
    public function addtonumcart(Request $request)
    {
        $id   = (int) ($request->id ?? 0);
        $qty  = (int) ($request->qty ?? 1);
        $size = (string) ($request->size ?? '');
        $color= (string) ($request->color ?? '');

        $size_qty   = (string) ($request->size_qty   ?? '');
        $size_price = $this->normNum($request->size_price ?? 0);
        $colorPrice = $this->normNum($request->color_price ?? 0);

        $size_key   = (string) ($request->size_qty ?? '');
        $keysArr    = $request->input('keys')   ? explode(',', $request->input('keys'))   : '';
        $valsArr    = $request->input('values') ? explode(',', $request->input('values')) : '';
        $pricesArr  = $request->input('prices') ? explode(',', $request->input('prices')) : 0;
        $keys   = !$keysArr ? '' : implode(',', $keysArr);
        $values = !$valsArr ? '' : implode(',', $valsArr);
        $curr = $this->curr;

        $prod = $this->fetchIdentity($id);
        if (!$prod) return redirect()->route('front.cart')->with('unsuccess', __('Product not found.'));
        if ($prod->type != 'Physical') { $qty = 1; }

        $vendorId = (int) $request->input('user', 0);
        $mp = $this->fetchListingOrFallback($prod, $vendorId);
        if (!$mp) return redirect()->route('front.cart')->with('unsuccess', __('Vendor listing not found or inactive.'));

        if ($size === '') { [$size, $_] = $this->pickAvailableSize($mp->size, $mp->size_qty); }
        $effStock = $this->effectiveStock($mp, $size);
        if ($effStock <= 0 || $qty > $effStock) {
            return redirect()->route('front.cart')->with('unsuccess', __('Out Of Stock.'));
        }

        $this->injectMerchantContext($prod, $mp);
        if (!empty($pricesArr) && !empty($pricesArr[0])) {
            foreach ($pricesArr as $p) $prod->price += ((float)$p / $curr->value);
        }

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $colorPrice, $size_key, $keys, $values, $request->input('affilate_user','0'));

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) $cart->totalPrice += $data['price'];

        Session::put('cart', $cart);
        return redirect()->route('front.cart')->with('success', __('Successfully Added To Cart.'));
    }

    public function removecart($id)
    {
        $curr    = $this->curr;
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        $cart->removeItem($id);
        foreach (['cart','already','coupon','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            Session::forget($k);
        }
        Session::put('cart', $cart);
        if (empty($cart->items)) { Session::forget('cart'); }

        return back()->with('success', __('Item has been removed from cart.'));
    }

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
        } else { $tax = 0; }

        Session::put('is_tax', $tax);

        $total  = (float) preg_replace('/[^0-9\.]/ui', '', $_GET['total'] ?? 0);
        $stotal = ($total * $tax) / 100;
        $sstotal= $stotal * $this->curr->value;
        Session::put('current_tax', $sstotal);

        $total = $total + $stotal;
        $data[0] = round(Session::has('coupon') ? $total - (float) Session::get('coupon') : $total, 2);
        $data[1] = $tax;
        return response()->json($data);
    }
}
