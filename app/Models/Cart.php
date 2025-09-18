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

    /* ============================== Helpers ============================== */

    /** حوّل قيمة إلى مصفوفة (CSV → array) */
    private function toArrayValues($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') return array_map('trim', explode(',', $v));
        return [];
    }

    /** ابحث عن فهرس المقاس المختار داخل مصفوفة المقاسات */
    private function findSizeIndex(array $sizes, string $size)
    {
        if ($size === '') return false;
        return array_search(trim($size), array_map('trim', $sizes), true);
    }

    /** اختر أول مقاس كميته > 0 وإلا أول مقاس إن لم يوجد متاح */
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

    /* ============================== ADD (1) ============================== */

    public function add($item, $id, $size, $color, $keys, $values)
    {
        // نحضّر عنصر التخزين
        $storedItem = [
            'user_id'      => $item->user_id,
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
            'affilate_user'=> 0,
        ];

        // فيزيائي أم لا + دمج مع عنصر موجود
        if ($item->type == 'Physical') {
            if ($this->items) {
                $k = $id . $size . $color . str_replace(str_split(' ,'), '', $values);
                if (array_key_exists($k, $this->items)) {
                    $storedItem = $this->items[$k];
                }
            }
        } else {
            if ($this->items) {
                $k = $id . $size . $color . str_replace(str_split(' ,'), '', $values);
                if (array_key_exists($k, $this->items)) {
                    $storedItem = $this->items[$k];
                }
            }
            $storedItem['dp'] = 1; // Digital
        }

        // زِد الكمية
        $storedItem['qty']++;

        // خفّض stock العام إن كان معرفاً (معلومة تقريبية للعرض)
        if ($item->stock !== null && $item->stock !== '') {
            $storedItem['stock'] = ((int)$storedItem['stock']) - 1;
        }

        // --- معالجة المقاسات: اربط size_qty/size_price بالمقاس المختار ---
        $sizes      = $this->toArrayValues($item->size);
        $sizeQtys   = $this->toArrayValues($item->size_qty);
        $sizePrices = $this->toArrayValues($item->size_price);
        $size_cost  = 0;

        // إن لم يصل مقاس من الواجهة، اختر أول متاح > 0
        if ($size === '' && !empty($sizes)) {
            [$picked, $_q] = $this->pickAvailableSize($sizes, $sizeQtys);
            $size = $picked;
        }

        // عين المقاس المختار
        if ($size !== '') {
            $storedItem['size'] = $size;
        } elseif (!empty($sizes)) {
            $storedItem['size'] = $sizes[0];
        }

        // اربط size_qty/size_price حسب الفهرس الصحيح
        $idx = $this->findSizeIndex($sizes, $storedItem['size']);
        if ($idx !== false) {
            $storedItem['size_qty']   = isset($sizeQtys[$idx])   ? (int)$sizeQtys[$idx]   : null;
            $storedItem['size_price'] = isset($sizePrices[$idx]) ? (float)$sizePrices[$idx] : 0.0;
            $size_cost                = (float) $storedItem['size_price'];
        } else {
            // لا يوجد فهرس مطابق — استخدم أول قيمة إن وجدت
            $storedItem['size_qty']   = isset($sizeQtys[0])   ? (int)$sizeQtys[0]   : null;
            $storedItem['size_price'] = isset($sizePrices[0]) ? (float)$sizePrices[0] : 0.0;
            $size_cost                = (float) $storedItem['size_price'];
        }

        // اللون
        if (!empty($color)) {
            $storedItem['color'] = $color;
        }

        // keys/values
        if (!empty($keys))   $storedItem['keys']   = $keys;
        if (!empty($values)) $storedItem['values'] = $values;

        // احسب سعر العنصر (أضف فرق المقاس)
        $item->price += $size_cost;
        $storedItem['item_price'] = $item->price;

        // خصم الجملة (نفس منطقك القديم)
        if (!empty($item->whole_sell_qty)) {
            // قد تأتي كسلاسل CSV من mp — نحولها لصفوف
            $wsq = $this->toArrayValues($item->whole_sell_qty);
            $wsd = $this->toArrayValues($item->whole_sell_discount);

            if (!empty($wsq) && !empty($wsd)) {
                foreach (array_combine($wsq, $wsd) as $whole_sell_qty => $whole_sell_discount) {
                    if ($storedItem['qty'] == (int)$whole_sell_qty) {
                        $key = $id . $size . $color . str_replace(str_split(' ,'), '', $values);
                        $whole_discount[$key] = (float)$whole_sell_discount;
                        Session::put('current_discount', $whole_discount);
                        $storedItem['discount'] = (float)$whole_sell_discount;
                        break;
                    }
                }
                if (Session::has('current_discount')) {
                    $key = $id . $size . $color . str_replace(str_split(' ,'), '', $values);
                    $data = Session::get('current_discount');
                    if (array_key_exists($key, $data)) {
                        $discount = $item->price * ($data[$key] / 100);
                        $item->price = $item->price - $discount;
                    }
                }
            }
        }

        $storedItem['price'] = $item->price * $storedItem['qty'];
        $this->items[$id . $size . $color . str_replace(str_split(' ,'), '', $values)] = $storedItem;
        $this->totalQty++;
    }

    /* ============================ ADD MULTIPLE ============================ */

    public function addnum($item, $id, $qty, $size, $color, $size_qty, $size_price, $color_price, $size_key, $keys, $values, $affilate_user)
    {
        $size_cost  = 0;
        $color_cost = 0;

        $storedItem = [
            'user_id'      => $item->user_id,
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

        $key = $id . $size . $color . str_replace(str_split(' ,'), '', $values);

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

        // --- ربط المقاس المختار بالقيم الصحيحة ---
        $sizes      = $this->toArrayValues($item->size);
        $sizeQtys   = $this->toArrayValues($item->size_qty);
        $sizePrices = $this->toArrayValues($item->size_price);

        // لو ما وصل مقاس، اختر أول متاح
        if ($size === '' && !empty($sizes)) {
            [$picked, $_q] = $this->pickAvailableSize($sizes, $sizeQtys);
            $size = $picked;
        }

        if ($size !== '') {
            $storedItem['size'] = $size;
        } elseif (!empty($sizes)) {
            $storedItem['size'] = $sizes[0];
        }

        // size_key (اختياري)
        if ($size_key !== '' && $size_key !== null) {
            $storedItem['size_key'] = $size_key;
        }

        // لو size_qty وصل من الواجهة حتى لو "0" نأخذه (بدون empty)
        if ($size_qty !== '' && $size_qty !== null) {
            $storedItem['size_qty'] = $size_qty;
        } else {
            // استخرج من المصفوفات بحسب المقاس المختار
            $idx = $this->findSizeIndex($sizes, $storedItem['size']);
            if ($idx !== false) {
                $storedItem['size_qty'] = isset($sizeQtys[$idx]) ? (int)$sizeQtys[$idx] : null;
            } else {
                $storedItem['size_qty'] = isset($sizeQtys[0]) ? (int)$sizeQtys[0] : null;
            }
        }

        // size_price من الواجهة أو من المصفوفات
        if ($size_price !== '' && $size_price !== null) {
            $storedItem['size_price'] = $size_price;
            $size_cost = (float)$size_price;
        } else {
            $idx = $this->findSizeIndex($sizes, $storedItem['size']);
            if ($idx !== false) {
                $storedItem['size_price'] = isset($sizePrices[$idx]) ? (float)$sizePrices[$idx] : 0.0;
            } else {
                $storedItem['size_price'] = isset($sizePrices[0]) ? (float)$sizePrices[0] : 0.0;
            }
            $size_cost = (float)$storedItem['size_price'];
        }

        // color_price
        if ($color_price !== '' && $color_price !== null) {
            $storedItem['color_price'] = (float)$color_price;
            $color_cost = (float)$color_price;
        }

        // اللون
        if (!empty($item->color)) {
            $colors = $this->toArrayValues($item->color);
            if (!empty($colors)) $storedItem['color'] = $colors[0];
        }
        if (!empty($color)) $storedItem['color'] = $color;

        if (!empty($keys))   $storedItem['keys']   = $keys;
        if (!empty($values)) $storedItem['values'] = $values;

        // الأسعار النهائية
        $item->price += $size_cost;
        $item->price += $color_cost;
        $storedItem['item_price'] = $item->price;

        // خصم الجملة (نفس منطقك القديم - مع تحويل إلى مصفوفات)
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
        $this->totalQty     += $storedItem['qty'];
    }

    /* ============================== ADDING ============================== */

    public function adding($item, $id, $size_qty, $size_price)
    {
        $storedItem = [
            'user_id'      => $item->user_id,
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

        $storedItem['qty']++;

        if ($item->stock !== null && $item->stock !== '') {
            $storedItem['stock']--;
        }

        // سعر المقاس (يزاد)
        $item->price += (float) $size_price;

        // خصم الجملة (نفس منطقك القديم)
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
        $this->totalQty     += $storedItem['qty'];
    }

    /* ============================== REDUCING ============================== */

    public function reducing($item, $id, $size_qty, $size_price)
    {
        $storedItem = [
            'user_id'      => $item->user_id,
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

        if ($storedItem['qty'] == 1) {
            return;
        }

        $storedItem['qty']--;

        if ($item->stock !== null && $item->stock !== '') {
            $storedItem['stock']++;
        }

        // سعر المقاس (يزاد/ينقص على نفس نهجك)
        $item->price += (float) $size_price;

        if (!empty($item->whole_sell_qty)) {
            $wsq = $this->toArrayValues($item->whole_sell_qty);
            $wsd = $this->toArrayValues($item->whole_sell_discount);
            $len = count($wsq);
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
        $this->items[$id]    = $storedItem;
        $this->totalQty--;
    }

    /* ============================== MISC ============================== */

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
