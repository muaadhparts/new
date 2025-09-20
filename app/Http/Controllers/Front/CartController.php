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
    /* ===================== Cart pages ===================== */

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
        if (is_null($mp->stock) || $mp->stock === '') return 999999;
        return (int)$mp->stock;
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
        $prod->vendor_user_id      = $mp->user_id;
        $prod->user_id             = $mp->user_id;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = method_exists($mp, 'vendorSizePrice') ? $mp->vendorSizePrice() : (float)$mp->price;
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

    private function recomputeTotals(Cart $cart): void
    {
        $totalQty = 0; $totalPrice = 0.0;
        if (is_array($cart->items)) {
            foreach ($cart->items as $row) {
                $totalQty   += (int)($row['qty'] ?? 0);
                $totalPrice += (float)($row['price'] ?? 0);
            }
        }
        $cart->totalQty   = $totalQty;
        $cart->totalPrice = $totalPrice;
    }

    private function findRowKeyInCart(Cart $cart, int $productId, ?int $vendorId, ?string $sizeKey, ?string $size, ?string $color, ?string $values): ?string
    {
        $valuesNorm = is_string($values) ? str_replace([' ', ','], '', $values) : null;
        foreach ((array)$cart->items as $k => $row) {
            $rowItem = $row['item'] ?? null;
            if (!$rowItem) continue;
            if ((int)($rowItem->id ?? 0) !== $productId) continue;
            if ($vendorId !== null) {
                $rowVendor = (int)($rowItem->vendor_user_id ?? $rowItem->user_id ?? 0);
                if ($rowVendor !== $vendorId) continue;
            }
            if ($sizeKey !== null && $sizeKey !== '') {
                if ((string)($row['size_key'] ?? '') !== (string)$sizeKey) continue;
            }
            if ($size !== null && $size !== '') {
                if (strcasecmp((string)($row['size'] ?? ''), (string)$size) !== 0) continue;
            }
            if ($color !== null && $color !== '') {
                if (strcasecmp((string)($row['color'] ?? ''), (string)$color) !== 0) continue;
            }
            if ($valuesNorm !== null && $valuesNorm !== '') {
                $rowValuesNorm = str_replace([' ', ','], '', (string)($row['values'] ?? ''));
                if ($rowValuesNorm !== $valuesNorm) continue;
            }
            return $k;
        }
        return null;
    }

    /* ===================== addcart / addtocart / addnumcart / addtonumcart ===================== */
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

        $this->recomputeTotals($cart);
        Session::put('cart', $cart);
        return response()->json([count($cart->items)]);
    }

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

        $this->recomputeTotals($cart);
        Session::put('cart', $cart);
        return redirect()->route('front.cart');
    }

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
        $cart->addnum(
            $prod, $prod->id, $qty, $size, $color,
            $size_qty, $size_price, $color_price, $size_key,
            $keys, $values, $request->input('affilate_user','0')
        );

        $this->recomputeTotals($cart);
        Session::put('cart', $cart);
        return response()->json([
            count($cart->items),
            \PriceHelper::showCurrencyPrice($cart->totalPrice * $curr->value),
        ]);
    }

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
        $curr   = $this->curr;

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
        $cart->addnum(
            $prod, $prod->id, $qty, $size, $color,
            $size_qty, $size_price, $colorPrice, $size_key,
            $keys, $values, $request->input('affilate_user','0')
        );

        $this->recomputeTotals($cart);
        Session::put('cart', $cart);
        return redirect()->route('front.cart')->with('success', __('Successfully Added To Cart.'));
    }

    /* ===================== زيادة/نقصان (الأسامي الجديدة) ===================== */

    public function increaseItem(Request $request)
    {
        if (!Session::has('cart')) return response()->json(['status'=>'error','msg'=>'No cart'], 400);

        $row = (string)$request->input('row', '');
        $oldCart = Session::get('cart');
        $cart    = new Cart($oldCart);

        if (!isset($cart->items[$row])) {
            return response()->json(['status'=>'error','msg'=>'Row not found'], 404);
        }

        $rowData  = $cart->items[$row];
        $item     = $rowData['item'];
        $productId= (int)($item->id ?? 0);
        $vendorId = (int)($item->vendor_user_id ?? $item->user_id ?? 0);
        $size     = (string)($rowData['size'] ?? '');
        $qtyNow   = (int)($rowData['qty'] ?? 0);

        $mp = MerchantProduct::where('product_id', $productId)
                ->where('user_id', $vendorId)->first();
        if (!$mp || (int)$mp->status !== 1) {
            return response()->json(['status'=>'error','msg'=>'Vendor listing invalid'], 400);
        }

        $avail = $this->effectiveStock($mp, $size);
        if ($qtyNow + 1 > $avail) {
            return response()->json(['status'=>'error','msg'=>__('Out Of Stock'), 'max'=>$avail], 422);
        }

        $cart->adding($item, $row, $rowData['size_qty'] ?? '', 0);
        $this->recomputeTotals($cart);
        Session::put('cart', $cart);

        return response()->json([
            'status'     => 'ok',
            'row'        => $row,
            'qty'        => $cart->items[$row]['qty'],
            'row_total'  => \PriceHelper::showCurrencyPrice($cart->items[$row]['price'] * $this->curr->value),
            'totalQty'   => $cart->totalQty,
            'totalPrice' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
        ]);
    }

    public function decreaseItem(Request $request)
    {
        if (!Session::has('cart')) return response()->json(['status'=>'error','msg'=>'No cart'], 400);

        $row = (string)$request->input('row', '');
        $oldCart = Session::get('cart');
        $cart    = new Cart($oldCart);

        if (!isset($cart->items[$row])) {
            return response()->json(['status'=>'error','msg'=>'Row not found'], 404);
        }

        $rowData = $cart->items[$row];
        $item    = $rowData['item'];
        $qtyNow  = (int)($rowData['qty'] ?? 0);
        if ($qtyNow <= 1) {
            return response()->json(['status'=>'error','msg'=>'Min qty reached'], 422);
        }

        $cart->reducing($item, $row, $rowData['size_qty'] ?? '', 0);
        $this->recomputeTotals($cart);
        Session::put('cart', $cart);

        return response()->json([
            'status'     => 'ok',
            'row'        => $row,
            'qty'        => $cart->items[$row]['qty'],
            'row_total'  => \PriceHelper::showCurrencyPrice($cart->items[$row]['price'] * $this->curr->value),
            'totalQty'   => $cart->totalQty,
            'totalPrice' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
        ]);
    }

    public function addbyone()
    {
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }

        $curr      = $this->curr;
        $id        = $_GET['id'];
        $itemid    = $_GET['itemid'];   // قد يأتي مختصرًا (مثلاً "221692")
        $size_qty  = $_GET['size_qty'];
        $size_price= $_GET['size_price'];

        // المنتج (هوية فقط)
        $prod = \App\Models\Product::where('id', $id)->first([
            'id','slug','name','photo','color',
            'weight','type','file','link','measure','attributes','stock_check'
        ]);
        if (!$prod) { return 0; }

        // السلة + تطبيع itemid إذا كان مختصرًا
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cartItems = $oldCart ? ($oldCart->items ?? []) : [];

        // إن لم نجد المفتاح كما أرسله الفرونت، جرّب أن تبحث عن المفتاح الحقيقي بصيغة id:u{vendor}:...
        if ($oldCart && !isset($cartItems[$itemid])) {
            // أولوية: مفاتيح تبدأ بـ "id:" (أي id:u...)
            foreach ($cartItems as $k => $v) {
                if (strpos($k, $id . ':') === 0) { $itemid = $k; break; }
            }
            // احتياط: أول مفتاح يبدأ بـ "id" (لو كان بناء مختلف)
            if (!isset($cartItems[$itemid])) {
                foreach ($cartItems as $k => $v) {
                    if (strpos($k, (string)$id) === 0) { $itemid = $k; break; }
                }
            }
        }
        if (!$oldCart || !isset($oldCart->items[$itemid])) {
            return 0;
        }

        // vendorId من عنصر السلة نفسه (يعتمد على وجود المفتاح الصحيح)
        $itm      = $oldCart->items[$itemid];
        $vendorId = (int) ($itm['item']['user_id'] ?? ($itm['item']->user_id ?? 0));
        if (!$vendorId) { return 0; }

        // سجل البائع
        $mp = \App\Models\MerchantProduct::where('product_id', $prod->id)
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
        if (!$mp) { return 0; }

        // inject سياق البائع + خصائص المقاسات
        $prod->user_id             = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $mp->vendorSizePrice();
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $mp->stock;

        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);

        // إضافة أسعار الخصائص الافتراضية (كما في كودك الحالي)
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

        // الزيادة
        $cart = new \App\Models\Cart($oldCart);
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

        Session::put('cart', $cart);

        $data = [];
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


    public function reducebyone()
    {
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }

        $curr      = $this->curr;
        $id        = $_GET['id'];
        $itemid    = $_GET['itemid'];  // قد يأتي مختصرًا
        $size_qty  = $_GET['size_qty'];
        $size_price= $_GET['size_price'];

        $prod = \App\Models\Product::where('id', $id)->first([
            'id','slug','name','photo','color',
            'weight','type','file','link','measure','attributes'
        ]);
        if (!$prod) { return 0; }

        // السلة + تطبيع itemid إذا كان مختصرًا
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cartItems = $oldCart ? ($oldCart->items ?? []) : [];

        if ($oldCart && !isset($cartItems[$itemid])) {
            foreach ($cartItems as $k => $v) {
                if (strpos($k, $id . ':') === 0) { $itemid = $k; break; }
            }
            if (!isset($cartItems[$itemid])) {
                foreach ($cartItems as $k => $v) {
                    if (strpos($k, (string)$id) === 0) { $itemid = $k; break; }
                }
            }
        }
        if (!$oldCart || !isset($oldCart->items[$itemid])) {
            return 0;
        }

        $itm      = $oldCart->items[$itemid];
        $vendorId = (int) ($itm['item']['user_id'] ?? ($itm['item']->user_id ?? 0));
        if (!$vendorId) { return 0; }

        $mp = \App\Models\MerchantProduct::where('product_id', $prod->id)
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

        $cart = new \App\Models\Cart($oldCart);
        $cart->reducing($prod, $itemid, $size_qty, $size_price);

        $cart->totalPrice = 0;
        foreach ($cart->items as $data) {
            $cart->totalPrice += $data['price'];
        }

        Session::put('cart', $cart);

        $data = [];
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


    /* ===================== remove ===================== */
    public function removecart(Request $request, $id)
    {
        if (!Session::has('cart')) return back();

        $oldCart = Session::get('cart');
        $cart    = new Cart($oldCart);

        $rowKey = $request->input('row', $id);
        if (!is_array($cart->items) || !array_key_exists($rowKey, $cart->items)) {
            $productId = (int) $id;
            $vendorId  = $request->has('user') ? (int)$request->input('user') : null;
            $sizeKey   = $request->input('size_key');
            $size      = $request->input('size');
            $color     = $request->input('color');
            $values    = $request->input('values');
            $rowKey = $this->findRowKeyInCart($cart, $productId, $vendorId, $sizeKey, $size, $color, $values);
            if (!$rowKey) {
                foreach ((array)$cart->items as $k => $row) {
                    if ((int)($row['item']->id ?? 0) === $productId) { $rowKey = $k; break; }
                }
            }
        }

        if ($rowKey && isset($cart->items[$rowKey])) {
            $cart->removeItem($rowKey);
        }

        foreach (['already','coupon','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            Session::forget($k);
        }
        $this->recomputeTotals($cart);
        Session::put('cart', $cart);
        if (empty($cart->items)) { Session::forget('cart'); }

        return back()->with('success', __('Item has been removed from cart.'));
    }

    /* ===================== tax ===================== */
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
