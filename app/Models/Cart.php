<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Session;

class Cart extends Model
{
    public $items = null;
    public $totalQty = 0;
    public $totalPrice = 0;

    public function __construct($oldCart = null)
    {
        if ($oldCart) {
            $this->items      = $oldCart->items;
            $this->totalQty   = $oldCart->totalQty;
            $this->totalPrice = $oldCart->totalPrice;
        }
    }

    /* ============================ أدوات مساعدة عامة ============================ */

    /**
     * استخرج vendorId من العنصر (يُحقن مسبقًا داخل المنتج في الكنترولر).
     */
    protected function vendorIdFromItem($item): int
    {
        return (int)($item->vendor_user_id ?? $item->user_id ?? 0);
    }

    /**
     * أنشئ مفتاح العنصر في السلة مع تمييز البائع.
     * الشكل: id : u{vendor} : {size_key|size} : {color} : {values-clean}
     */
    protected function makeKey($item, $size = '', $color = '', $values = '', $size_key = '')
    {
        $vendor = $this->vendorIdFromItem($item);
        $cleanValues = is_string($values) ? str_replace([' ', ','], '', $values) : (string)$values;
        $dim = ($size_key !== '' && $size_key !== null) ? $size_key : $size;

        return implode(':', [
            $item->id,
            'u' . $vendor,
            (string)$dim,
            (string)$color,
            (string)$cleanValues,
        ]);
    }

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
    private function pickAvailableSize(array $sizes, array $qtys): array
    {
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

    /* ============================ إضافة عنصر (1 وحدة) ============================ */

    public function add($item, $id, $size, $color, $keys, $values)
    {
        $storedItem = [
            'user_id'      => $this->vendorIdFromItem($item),
            'qty'          => 0,
            'size_key'     => 0,
            'size_qty'     => $item->size_qty,
            'size_price'   => $item->size_price,
            'size'         => $item->size,
            'color'        => $item->color,
            'stock'        => $item->stock,
            'price'        => $item->price,
            'item'         => $item,
            'license'      => '',
            'dp'           => '0',
            'keys'         => $keys,
            'values'       => $values,
            'item_price'   => $item->price,
            'discount'     => 0,
            'affilate_user'=> 0
        ];

        // مفتاح Vendor-aware
        $key = $this->makeKey($item, (string)$size, (string)$color, (string)$values, '');

        if ($item->type == 'Physical') {
            if ($this->items && array_key_exists($key, $this->items)) {
                $storedItem = $this->items[$key];
            }
        } else {
            if ($this->items && array_key_exists($key, $this->items)) {
                $storedItem = $this->items[$key];
            }
            $storedItem['dp'] = 1; // Digital
        }

        $storedItem['qty']++;

        if ($item->stock !== null && $item->stock !== '') {
            $storedItem['stock'] = ((int)$storedItem['stock']) - 1;
        }

        $sizes      = $this->toArrayValues($item->size);
        $sizeQtys   = $this->toArrayValues($item->size_qty);
        $sizePrices = $this->toArrayValues($item->size_price);
        $size_cost  = 0;

        if ($size === '' && !empty($sizes)) {
            [$picked, $_] = $this->pickAvailableSize($sizes, $sizeQtys);
            $size = $picked;
        }

        if ($size !== '') {
            $storedItem['size'] = $size;
        } elseif (!empty($sizes)) {
            $storedItem['size'] = $sizes[0];
        }

        $idx = $this->findSizeIndex($sizes, $storedItem['size']);
        if ($idx !== false) {
            $storedItem['size_qty']   = isset($sizeQtys[$idx])   ? (int)$sizeQtys[$idx] : null;
            $storedItem['size_price'] = isset($sizePrices[$idx]) ? (float)$sizePrices[$idx] : 0.0;
            $size_cost                = (float) $storedItem['size_price'];
        } else {
            $storedItem['size_qty']   = isset($sizeQtys[0])   ? (int)$sizeQtys[0] : null;
            $storedItem['size_price'] = isset($sizePrices[0]) ? (float)$sizePrices[0] : 0.0;
            $size_cost                = (float) $storedItem['size_price'];
        }

        if (!empty($color)) $storedItem['color'] = $color;
        if (!empty($keys))  $storedItem['keys']  = $keys;
        if (!empty($values))$storedItem['values']= $values;

        $item->price += $size_cost;
        $storedItem['item_price'] = $item->price;

        if (!empty($item->whole_sell_qty)) {
            $wsq = $this->toArrayValues($item->whole_sell_qty);
            $wsd = $this->toArrayValues($item->whole_sell_discount);

            if (!empty($wsq) && !empty($wsd)) {
                foreach (array_combine($wsq, $wsd) as $whole_sell_qty => $whole_sell_discount) {
                    if ($storedItem['qty'] == (int)$whole_sell_qty) {
                        $whole_discount[$key] = (float)$whole_sell_discount;
                        Session::put('current_discount', $whole_discount);
                        $storedItem['discount'] = (float)$whole_sell_discount;
                        break;
                    }
                }
                if (Session::has('current_discount')) {
                    $data = Session::get('current_discount');
                    if (array_key_exists($key, $data)) {
                        $discount = $item->price * ($data[$key] / 100);
                        $item->price = $item->price - $discount;
                    }
                }
            }
        }

        $storedItem['price'] = $item->price * $storedItem['qty'];
        $this->items[$key]   = $storedItem;

        // الزيادة بقطعة واحدة فقط
        $this->totalQty++;
    }

    /* ============================ إضافة عنصر (كمية) ============================ */

    public function addnum($item, $id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $affilate_user)
    {
        $size_cost  = 0;
        $color_cost = 0;

        $storedItem = [
            'user_id'      => $this->vendorIdFromItem($item),
            'qty'          => 0,
            'size_key'     => 0,
            'size_qty'     => $item->size_qty,
            'size_price'   => $item->size_price,
            'size'         => $item->size,
            'color'        => $item->color,
            'color_price'  => $color_price,
            'stock'        => $item->stock,
            'price'        => $item->price,
            'item'         => $item,
            'license'      => '',
            'dp'           => '0',
            'keys'         => $keys,
            'values'       => $values,
            'item_price'   => $item->price,
            'discount'     => 0,
            'affilate_user'=> 0
        ];

        // مفتاح Vendor-aware
        $key = $this->makeKey($item, (string)$size, (string)$color, (string)$values, (string)$size_key);

        if ($item->type == 'Physical') {
            if ($this->items && array_key_exists($key, $this->items)) {
                $storedItem = $this->items[$key];
            }
        } else {
            if ($this->items && array_key_exists($key, $this->items)) {
                $storedItem = $this->items[$key];
            }
            $storedItem['dp'] = 1;
        }

        $storedItem['affilate_user'] = $affilate_user;

        if (Auth::guard('admin')->check()) {
            $storedItem['qty'] = $qty;
        } else {
            $storedItem['qty'] = $storedItem['qty'] + $qty;
        }

        if ($item->stock !== null && $item->stock !== '') {
            $storedItem['stock'] = ((int)$storedItem['stock']) - $qty;
        }

        $sizes      = $this->toArrayValues($item->size);
        $sizeQtys   = $this->toArrayValues($item->size_qty);
        $sizePrices = $this->toArrayValues($item->size_price);

        if ($size === '' && !empty($sizes)) { [$size, $_] = $this->pickAvailableSize($sizes, $sizeQtys); }
        if ($size !== '') $storedItem['size'] = $size;
        elseif (!empty($sizes)) $storedItem['size'] = $sizes[0];

        if ($size_key !== '' && $size_key !== null) { $storedItem['size_key'] = $size_key; }

        if ($size_qty !== '' && $size_qty !== null) { $storedItem['size_qty'] = $size_qty; }
        else {
            $idx = $this->findSizeIndex($sizes, $storedItem['size']);
            $storedItem['size_qty'] = $idx !== false ? (int)($sizeQtys[$idx] ?? 0) : (int)($sizeQtys[0] ?? 0);
        }

        if ($size_price !== '' && $size_price !== null) {
            $storedItem['size_price'] = $size_price;
            $size_cost = (float)$size_price;
        } else {
            $idx = $this->findSizeIndex($sizes, $storedItem['size']);
            $storedItem['size_price'] = $idx !== false ? (float)($sizePrices[$idx] ?? 0) : (float)($sizePrices[0] ?? 0);
            $size_cost = (float) $storedItem['size_price'];
        }

        if ($color_price !== '' && $color_price !== null) {
            $storedItem['color_price'] = (float)$color_price;
            $color_cost = (float)$color_price;
        }

        // Get color from vendor colors (merchant_products.color_all)
        $vendorColors = $item->getVendorColors();
        if (!empty($vendorColors)) {
            $colors = $this->toArrayValues($vendorColors);
            if (!empty($colors)) $storedItem['color'] = $colors[0];
        }
        if (!empty($color)) $storedItem['color'] = $color;

        if (!empty($keys))   $storedItem['keys']   = $keys;
        if (!empty($values)) $storedItem['values'] = $values;

        $item->price += $size_cost + $color_cost;
        $storedItem['item_price'] = $item->price;

        if (!empty($item->whole_sell_qty)) {
            $wsq = $this->toArrayValues($item->whole_sell_qty);
            $wsd = $this->toArrayValues($item->whole_sell_discount);
            if (!empty($wsq) && !empty($wsd)) {
                foreach ($wsq as $keyIdx => $data) {
                    if (($keyIdx + 1) != count($wsq)) {
                        if (($storedItem['qty'] >= (int)$wsq[$keyIdx]) && ($storedItem['qty'] < (int)$wsq[$keyIdx + 1])) {
                            $whole_discount[$key] = (float)$wsd[$keyIdx];
                            Session::put('current_discount', $whole_discount);
                            $storedItem['discount'] = (float)$wsd[$keyIdx];
                            break;
                        }
                    } else {
                        if ($storedItem['qty'] >= (int)$wsq[$keyIdx]) {
                            $whole_discount[$key] = (float)$wsd[$keyIdx];
                            Session::put('current_discount', $whole_discount);
                            $storedItem['discount'] = (float)$wsd[$keyIdx];
                            break;
                        }
                    }
                }
                if (Session::has('current_discount')) {
                    $data = Session::get('current_discount');
                    if (array_key_exists($key, $data)) {
                        $discount = $item->price * ($data[$key] / 100);
                        $item->price = $item->price - $discount;
                    }
                }
            }
        }

        $storedItem['price'] = $item->price * $storedItem['qty'];
        $this->items[$key]   = $storedItem;

        // الزيادة بقدر الكمية المُضافة فقط
        $this->totalQty     += (int) $qty;
    }

    /* ============================ دوال دعم موجودة أصلًا ============================ */

    public function adding($item, $id, $size_qty, $size_price)
    {
        $storedItem = [
            'user_id'      => $this->vendorIdFromItem($item),
            'qty'          => 0,
            'size_key'     => 0,
            'size_qty'     => $item->size_qty,
            'size_price'   => $item->size_price,
            'size'         => $item->size,
            'color'        => $item->color,
            'stock'        => $item->stock,
            'price'        => $item->price,
            'item'         => $item,
            'license'      => '',
            'dp'           => '0',
            'keys'         => '',
            'values'       => '',
            'item_price'   => $item->price,
            'discount'     => 0,
            'affilate_user'=> 0
        ];
        if ($this->items && array_key_exists($id, $this->items)) {
            $storedItem = $this->items[$id];
        }

        // +1 فقط
        $storedItem['qty']++;
        if ($item->stock !== null && $item->stock !== '') $storedItem['stock']--;

        // لا نضيف size_price هنا لتجنب مضاعفة السعر (الكنترولر يمرر 0)
        $item->price += (float) $size_price;

        if (!empty($item->whole_sell_qty)) {
            foreach (array_combine($this->toArrayValues($item->whole_sell_qty), $this->toArrayValues($item->whole_sell_discount)) as $whole_sell_qty => $whole_sell_discount) {
                if ($storedItem['qty'] == (int)$whole_sell_qty) {
                    $whole_discount[$id] = (float)$whole_sell_discount;
                    Session::put('current_discount', $whole_discount);
                    $storedItem['discount'] = (float)$whole_sell_discount;
                    break;
                }
            }
            if (Session::has('current_discount')) {
                $data = Session::get('current_discount');
                if (array_key_exists($id, $data)) {
                    $discount = $item->price * ($data[$id] / 100);
                    $item->price = $item->price - $discount;
                }
            }
        }

        $storedItem['price'] = $item->price * $storedItem['qty'];
        $this->items[$id]    = $storedItem;

        // زِد الإجمالي بواحد فقط
        $this->totalQty++;
    }

    public function reducing($item, $id, $size_qty, $size_price)
    {
        $storedItem = [
            'user_id'      => $this->vendorIdFromItem($item),
            'qty'          => 0,
            'size_key'     => 0,
            'size_qty'     => $item->size_qty,
            'size_price'   => $item->size_price,
            'size'         => $item->size,
            'color'        => $item->color,
            'stock'        => $item->stock,
            'price'        => $item->price,
            'item'         => $item,
            'license'      => '',
            'dp'           => '0',
            'keys'         => '',
            'values'       => '',
            'item_price'   => $item->price,
            'discount'     => 0,
            'affilate_user'=> 0
        ];
        if ($this->items && array_key_exists($id, $this->items)) {
            $storedItem = $this->items[$id];
        }
        if ($storedItem['qty'] == 1) return;

        $storedItem['qty']--;
        if ($item->stock !== null && $item->stock !== '') $storedItem['stock']++;

        // لا نضيف size_price هنا لتجنب تغيير سعر الوحدة
        $item->price += (float) $size_price;

        if (!empty($item->whole_sell_qty)) {
            $wsq = $this->toArrayValues($item->whole_sell_qty);
            $wsd = $this->toArrayValues($item->whole_sell_discount);
            foreach ($wsq as $key => $data1) {
                if ($storedItem['qty'] < (int)$wsq[$key]) {
                    if ($storedItem['qty'] < (int)$wsq[0]) {
                        Session::forget('current_discount');
                        $storedItem['discount'] = 0;
                        break;
                    }
                    $whole_discount[$id] = (float)$wsd[$key - 1];
                    Session::put('current_discount', $whole_discount);
                    $storedItem['discount'] = (float)$wsd[$key - 1];
                    break;
                }
            }
            if (Session::has('current_discount')) {
                $data = Session::get('current_discount');
                if (array_key_exists($id, $data)) {
                    $discount = $item->price * ($data[$id] / 100);
                    $item->price = $item->price - $discount;
                }
            }
        }

        $storedItem['price'] = $item->price * $storedItem['qty'];
        $this->items[$id] = $storedItem;

        // أنقص الإجمالي بواحد
        $this->totalQty--;
    }

    public function MobileupdateLicense($id, $license) { $this->items[$id]['license'] = $license; }
    public function updateLicense($id, $license)      { $this->items[$id]['license'] = $license; }
    public function updateColor($item, $id, $color)   { $this->items[$id]['color']   = $color; }

    public function removeItem($id)
    {
        $this->totalQty   -= $this->items[$id]['qty'];
        $this->totalPrice -= $this->items[$id]['price'];
        unset($this->items[$id]);
        if (Session::has('current_discount')) {
            $data = Session::get('current_discount');
            if (array_key_exists($id, $data)) {
                unset($data[$id]);
                Session::put('current_discount', $data);
            }
        }
    }
}
