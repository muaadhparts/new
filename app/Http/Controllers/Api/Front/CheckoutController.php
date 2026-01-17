<?php
namespace App\Http\Controllers\Api\Front;

use App\Models\MerchantItem;
// // dd('use MerchantItem injected'); // اختباري
use App\Classes\MuaadhMailer;
use App\Helpers\PurchaseHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseDetailsResource;
use App\Models\MerchantCart;
use App\Models\Country;
use App\Models\DiscountCode;
use App\Models\MonetaryUnit;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\PurchaseTimeline;
use App\Models\Package;
use App\Models\FrontendSetting;
use App\Models\CatalogItem;
use App\Models\Shipping;
use App\Models\User;
use App\Models\MerchantCommission;
use App\Models\MerchantPurchase;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        try {

            $input = $request->all();
            $items = $input['items'];

            if (gettype($items) == 'string') {
                $items = json_decode($items, true);
            }

            $new_cart = new MerchantCart(null);

            foreach ($items as $key => $item) {
                if (isset($item['id'], $item['qty'])) {
                    $this->addtocart(
                        $new_cart,
                        $input['currency_code'] ?? null,
                        $item['id'],
                        $item['qty'],
                        $item['size'] ?? '',
                        $item['color'] ?? '',
                        $item['size_qty'] ?? '',
                        $item['size_price'] ?? 0,
                        $item['size_key'] ?? '',
                        $item['keys'] ?? '',
                        $item['values'] ?? '',
                        $item['prices'] ?? 0,
                        $input['affilate_user'] ?? null,
                        $item['user'] ?? $item['merchant_id'] ?? null // merchantId إلزامي
                    );
                    // // dd(['api_merchant' => $item['user'] ?? $item['merchant_id'] ?? null]); // اختباري
                }
            }

            $cart = new MerchantCart($new_cart);

            $gs = Muaadhsetting::find(1);

            $currency_code = $input['currency_code'] ?? null;
            if (!empty($currency_code)) {
                $curr = MonetaryUnit::where('name', '=', $currency_code)->first();
                if (empty($curr)) {
                    $curr = monetaryUnit()->getDefault();
                }
            } else {
                $curr = monetaryUnit()->getDefault();
            }

            // حفظ نسخة خفيفة من السلة داخل الطلب
            $t_cart = new MerchantCart($cart);
            $cart_payload = [
                'totalQty'   => $t_cart->totalQty,
                'totalPrice' => $t_cart->totalPrice,
                'items'      => $t_cart->items,
            ];
            // Model cast handles encoding - pass array directly

            // أفلييت
            $temp_affilate_users = PurchaseHelper::item_affilate_check($cart);
            $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

            $t_cart = new MerchantCart($cart);
            $purchaseCalculate = PriceHelper::getPurchaseTotal($input, $t_cart);

            if (isset($purchaseCalculate['success']) && $purchaseCalculate['success'] == false) {
                return redirect()->back()->with('unsuccess', $purchaseCalculate['message']);
            }

            if ($gs->multiple_shipping == 0) {
                $purchaseTotal          = $purchaseCalculate['total_amount'];
                $shipping            = $purchaseCalculate['shipping'];
                $packeing            = $purchaseCalculate['packeing'];
                $merchant_shipping_ids = $purchaseCalculate['merchant_shipping_ids'];
                $merchant_packing_ids  = $purchaseCalculate['merchant_packing_ids'];
                $merchant_ids          = $purchaseCalculate['merchant_ids'];

                $input['shipping_name']     = @$shipping->name;
                $input['merchant_shipping_id'] = @$shipping->id;
                $input['packing_name']      = @$packeing->name;
                $input['merchant_packing_id']  = @$packeing->id;
                $input['shipping_cost']      = @$shipping->price ?? 0;
                $input['packing_cost']       = @$packeing->price ?? 0;
                $input['merchant_shipping_ids']= $merchant_shipping_ids;
                $input['merchant_packing_ids'] = $merchant_packing_ids;
                $input['merchant_ids']         = $merchant_ids;
            } else {
                // multi shipping
                $purchaseTotal          = $purchaseCalculate['total_amount'];
                $shipping            = $purchaseCalculate['shipping'];
                $packeing            = $purchaseCalculate['packeing'];
                $merchant_shipping_ids = $purchaseCalculate['merchant_shipping_ids'];
                $merchant_packing_ids  = $purchaseCalculate['merchant_packing_ids'];
                $merchant_ids          = $purchaseCalculate['merchant_ids'];
                $shipping_cost       = $purchaseCalculate['shipping_cost'];
                $packing_cost        = $purchaseCalculate['packing_cost'];

                $input['shipping_name']     = $merchant_shipping_ids;
                $input['merchant_shipping_id'] = $merchant_shipping_ids;
                $input['packing_name']      = $merchant_packing_ids;
                $input['merchant_packing_id']  = $merchant_packing_ids;
                $input['shipping_cost']      = $shipping_cost;
                $input['packing_cost']       = $packing_cost;
                $input['merchant_shipping_ids']= $merchant_shipping_ids;
                $input['merchant_packing_ids'] = $merchant_packing_ids;
                $input['merchant_ids']         = $merchant_ids;
                unset($input['shipping'], $input['packeging']);
            }

            // إنشاء الطلب (Purchase)
            $purchase = new Purchase;

            $input['user_id']        = $request->user_id ? $request->user_id : null;
            $input['cart']           = $cart_payload; // Model cast handles encoding
            $input['affilate_users'] = $affilate_users;
            $input['currency_name']  = $curr->name;
            $input['currency_sign']  = $curr->sign;
            $input['currency_value'] = $curr->value;
            $input['pay_amount']     = $purchaseTotal / $curr->value;
            $input['purchase_number']   = Str::random(4) . time();

            // Tax location is now just city or country name (from frontend)
            if (!empty($input['tax_location'])) {
                $input['tax_location'] = $input['tax_location']; // Keep as sent from frontend
            }

            // Tax amount from frontend calculation
            $input['tax'] = $input['tax'] ?? 0;

            if (Session::has('affilate')) {
                $val = ($request->total ?? 0) / $curr->value;
                $val = $val / 100;
                $sub = $val * $gs->affilate_charge;
                if ($temp_affilate_users != null) {
                    $t_sub = 0;
                    foreach ($temp_affilate_users as $t_cost) {
                        $t_sub += $t_cost['charge'];
                    }
                    $sub = $sub - $t_sub;
                }
                if ($sub > 0) {
                    $user = PurchaseHelper::affilate_check(Session::get('affilate'), $sub, 0);
                    $input['affilate_user']   = Session::get('affilate');
                    $input['affilate_charge'] = $sub;
                }
            }

            // ✅ حفظ إحداثيات العميل
            // المصدر 1: مباشرة من الـ request (إذا أرسلها الفرونت)
            // المصدر 2: من الـ session (إذا تم حفظها في خطوة سابقة)
            // ملاحظة: الفرونت قد يرسل latitude/longitude أو customer_latitude/customer_longitude
            $reqLat = $input['customer_latitude'] ?? $input['latitude'] ?? null;
            $reqLng = $input['customer_longitude'] ?? $input['longitude'] ?? null;

            if (!empty($reqLat) && !empty($reqLng)) {
                // الإحداثيات جاءت مباشرة من الـ request
                $input['customer_latitude'] = (float) $reqLat;
                $input['customer_longitude'] = (float) $reqLng;
            } else {
                // جرب الحصول عليها من الـ session
                $merchantIdsArray = $merchant_ids ? explode(',', $merchant_ids) : [];
                $firstMerchantId = $merchantIdsArray[0] ?? null;

                if ($firstMerchantId) {
                    $addressData = Session::get('checkout.merchant.' . $firstMerchantId . '.address')
                        ?? Session::get('merchant_step1_' . $firstMerchantId)
                        ?? Session::get('step1')
                        ?? [];

                    if (!empty($addressData['latitude']) && !empty($addressData['longitude'])) {
                        $input['customer_latitude'] = (float) $addressData['latitude'];
                        $input['customer_longitude'] = (float) $addressData['longitude'];
                    }
                }
            }

            $purchase->fill($input)->save();

            // Create an OTO shipment (doesn't break the purchase on failure)
            $this->createOtoShipments($purchase, $input);

            $purchase->tracks()->create(['name' => 'Pending', 'text' => 'You have successfully placed your purchase.']);
            $purchase->notifications()->create();

            // بدّل منطق الخصم: خصم من merchant_items بدل PurchaseHelper على catalogItems
            // PurchaseHelper::size_qty_check($cart);
            // PurchaseHelper::stock_check($cart);
            $this->decrementMerchantStockAndSizes($cart);
            // // dd('mp-stock decremented'); // اختباري

            PurchaseHelper::merchant_purchase_check($cart, $purchase);

            // Email للمشتري
            $data = [
                'to'      => $purchase->customer_email,
                'type'    => "purchase_confirmed",
                'cname'   => $purchase->customer_name,
                'oamount' => "",
                'aname'   => "",
                'aemail'  => "",
                'wname'  => "",
                'onumber' => $purchase->purchase_number,
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendAutoPurchaseMail($data, $purchase->id);

            // Email للأدمن
            $ps = FrontendSetting::find(1);
            $data = [
                'to'      => $ps->contact_email,
                'subject' => "New Purchase Recieved!!",
                'body'    => "Hello Operator!<br>Your store has received a new purchase.<br>Purchase Number is " . $purchase->purchase_number . ".Please login to your panel to check. <br>Thank you.",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);

            unset($purchase['cart']);
            return response()->json([
                'status' => true,
                'data'   => route('front.payment.success', $purchase->purchase_number),
                'error'  => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data'   => [],
                'error'  => ['message' => $e->getMessage()]
            ]);
        }
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        try {
            //--- Logic Section
            $purchase = Purchase::find($id);
            $input = $request->all();

            if ($purchase->status == "completed") {
                // Then Save Without Changing it.
                $input['status'] = "completed";
                $purchase->update($input);
                return response()->json(['status' => true, 'data' => $purchase, 'error' => []]);
            } else {
                if ($input['status'] == "completed") {

                    if (User::where('id', $purchase->affilate_user)->exists()) {
                        $auser = User::where('id', $purchase->affilate_user)->first();
                        $auser->affilate_income += $purchase->affilate_charge;
                        $auser->update();
                    }

                    $gs = Muaadhsetting::find(1);
                    if ($gs->is_smtp == 1) {
                        $maildata = [
                            'to'      => $purchase->customer_email,
                            'subject' => 'Your purchase ' . $purchase->purchase_number . ' is Confirmed!',
                            'body'    => "Hello " . $purchase->customer_name . "," . "\n Thank you for shopping with us. We are looking forward to your next visit.",
                        ];
                        $mailer = new MuaadhMailer();
                        $mailer->sendCustomMail($maildata);
                    } else {
                        $to = $purchase->customer_email;
                        $subject = 'Your purchase ' . $purchase->purchase_number . ' is Confirmed!';
                        $msg = "Hello " . $purchase->customer_name . "," . "\n Thank you for shopping with us. We are looking forward to your next visit.";
                        $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
                        mail($to, $subject, $msg, $headers);
                    }
                }

                if ($input['status'] == "declined") {

                    // استرجاع مخزون/مقاسات MerchantItem بدل catalogItems
                    $this->restoreMerchantStockAndSizesFromPurchase($purchase);
                    // // dd('mp stock restored'); // اختباري

                    $gs = Muaadhsetting::find(1);
                    if ($gs->is_smtp == 1) {
                        $maildata = [
                            'to'      => $purchase->customer_email,
                            'subject' => 'Your purchase ' . $purchase->purchase_number . ' is Declined!',
                            'body'    => "Hello " . $purchase->customer_name . "," . "\n We are sorry for the inconvenience caused. We are looking forward to your next visit.",
                        ];
                        $mailer = new MuaadhMailer();
                        $mailer->sendCustomMail($maildata);
                    } else {
                        $to = $purchase->customer_email;
                        $subject = 'Your purchase ' . $purchase->purchase_number . ' is Declined!';
                        $msg = "Hello " . $purchase->customer_name . "," . "\n We are sorry for the inconvenience caused. We are looking forward to your next visit.";
                        $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
                        mail($to, $subject, $msg, $headers);
                    }
                }

                $purchase->update($input);

                if ($request->track_text) {
                    $name = ucwords($request->status);
                    $ck = PurchaseTimeline::where('purchase_id', '=', $id)->where('name', '=', $name)->first();
                    if ($ck) {
                        $ck->purchase_id = $id;
                        $ck->name    = $name;
                        $ck->text     = $request->track_text;
                        $ck->update();
                    } else {
                        $ot = new PurchaseTimeline;
                        $ot->purchase_id = $id;
                        $ot->name    = $name;
                        $ot->text     = $request->track_text;
                        $ot->save();
                    }
                }

                MerchantPurchase::where('purchase_id', '=', $id)->update(['status' => $input['status']]);

                return response()->json(['status' => true, 'data' => $purchase, 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    //*** POST Request
    public function delete($id)
    {
        try {
            $purchase = Purchase::find($id);
            if ($purchase) {
                $purchase->delete();
                return response()->json(['status' => true, 'data' => 'Purchase Deleted Successfully', 'error' => []]);
            } else {
                return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'Purchase Not Found']]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    //*** GET Request
    public function purchaseDetails(Request $request)
    {
        try {
            if ($request->has('purchase_number')) {
                $purchase_number = $request->purchase_number;
                $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
                return response()->json(['status' => true, 'data' => new PurchaseDetailsResource($purchase), 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    /**
     * إضافة للسلة — Merchant-aware: يعتمد على MerchantItem فقط
     */
    protected function addtocart($cart, $currency_code, $p_id, $p_qty, $p_size, $p_color, $p_size_qty, $p_size_price, $p_size_key, $p_keys, $p_values, $p_prices, $affilate_user, $merchantId)
    {
        try {
            // // dd(func_get_args()); // اختباري

            $id        = (int)$p_id;
            $qty       = (int)$p_qty;
            $size      = str_replace(' ', '-', (string)$p_size);
            $color     = (string)$p_color;
            $size_qty  = $p_size_qty;
            $size_price= (float)$p_size_price;
            $size_key  = $p_size_key;

            $keys   = $p_keys;
            $keys   = $keys === '' ? [] : explode(",", (string)$keys);
            $values = $p_values;
            $values = $values === '' ? [] : explode(",", (string)$values);

            $prices = $p_prices;
            if (!empty($prices)) {
                $prices = explode(",", (string)$prices);
            }

            $keys   = $keys == "" ? '' : implode(',', $keys);
            $values = $values == "" ? '' : implode(',', $values);

            if (!empty($currency_code)) {
                $curr = MonetaryUnit::where('name', '=', $currency_code)->first();
                if (empty($curr)) {
                    $curr = monetaryUnit()->getDefault();
                }
            } else {
                $curr = monetaryUnit()->getDefault();
            }

            $size_price = ($size_price / $curr->value);

            // هوية العنصر فقط
            $cartItem = CatalogItem::where('id', '=', $id)->first([
                'id','slug','name','photo','color','part_number','weight','type','file','link','measure','attributes','color_all','color_price'
            ]);
            if (!$cartItem) {
                return false;
            }

            // عرض البائع
            $merchantId = (int)$merchantId;
            if ($merchantId <= 0) {
                return false;
            }

            $mp = MerchantItem::where('catalog_item_id', $cartItem->id)
                ->where('user_id', $merchantId)
                ->where('status', 1)
                ->first();

            if (!$mp) {
                return false;
            }

            // حقن سياق البائع بالقيم الصحيحة
            $cartItem->user_id              = $merchantId;             // inject merchant context
            $cartItem->merchant_item_id  = $mp->id;

            // سعر أساسي + عمولة + سعر مقاس إن وجد (per-merchant)
            $basePrice = (float)$mp->price;
            $commission = MerchantCommission::getOrCreateForMerchant($merchantId);
            $withCommission = $commission->getPriceWithCommission($basePrice);

            // إن كان لديك آلية size_price متقدمة على MP، طبّقها هنا
            if (!empty($mp->size_price) && $size !== '' && $size !== null) {
                // إن كانت size_price رقمًا موحدًا:
                if (is_numeric($mp->size_price)) {
                    $withCommission += (float)$mp->size_price;
                }
                // لو تخزينك للـ size_price مصفوفة/CSV، عدّل هذا القسم لانتقاء السعر المناسب
            }

            $cartItem->price          = round($withCommission, 2);
            $cartItem->previous_price = $mp->previous_price;
            $cartItem->stock          = $mp->stock;

            // مقاسات/خصائص من MP
            $cartItem->setAttribute('size',       $mp->size);
            $cartItem->setAttribute('size_qty',   $mp->size_qty);
            $cartItem->setAttribute('size_price', $mp->size_price);
            $cartItem->setAttribute('stock_check',         $mp->stock_check ?? null);
            $cartItem->setAttribute('minimum_qty',         $mp->minimum_qty ?? null);
            $cartItem->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? null);
            $cartItem->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? null);
            $cartItem->setAttribute('color_all',           $mp->color_all ?? null);

            // أسعار خصائص إضافية (إن أرسلت)
            if (!empty($prices) && !empty($prices[0])) {
                foreach ($prices as $data) {
                    $cartItem->price += ((float)$data / $curr->value);
                }
            }

            // default size / color
            if ($size === '' && !empty($cartItem->size)) {
                // قد تكون مصفوفة/CSV — هنا نفترض أول قيمة
                if (is_array($cartItem->size)) {
                    $size = str_replace(' ', '-', trim($cartItem->size[0]));
                } else {
                    $size = str_replace(' ', '-', trim((string)$cartItem->size));
                }
            }

            if ($color === '' && !empty($cartItem->color)) {
                // color من هوية المنتج كما كان
                if (is_array($cartItem->color)) {
                    $color = $cartItem->color[0];
                } else {
                    $color = (string)$cartItem->color;
                }
            }
            $color = str_replace('#', '', (string)$color);

            $cart->addnum($cartItem, $cartItem->id, $qty, $size, $color, $size_qty, $size_price, $size_key, $keys, $values, $affilate_user);
            $cart->totalPrice = 0;

            foreach ($cart->items as $data) {
                $cart->totalPrice += $data['price'];
            }

            return $cart->items;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDiscountCode(Request $request)
    {
        $code = $request->code ?? $request->discount_code;
        $discountCode = DiscountCode::where('code', '=', $code)->where('status', 1)->first();

        if ($discountCode) {
            $today = date('Y-m-d');
            $from  = date('Y-m-d', strtotime($discountCode->start_date));
            $to    = date('Y-m-d', strtotime($discountCode->end_date));

            if ($from <= $today && $to >= $today) {
                return response()->json(['status' => true, 'data' => $discountCode, 'error' => []]);
            } else {
                return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Discount Code']);
            }
        } else {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Discount Code Not Found']);
        }
    }

    public function getShippingPackaging()
    {
        $shipping  = Shipping::whereUserId(0)->get();
        $packaging = Package::whereUserId(0)->get();
        return response()->json(['status' => true, 'data' => ['shipping' => $shipping, 'packaging' => $packaging], 'error' => []]);
    }

    public function MerchantWisegetShippingPackaging(Request $request)
    {
        $explode = explode(',', $request->merchant_ids);
        foreach ($explode as $key => $value) {
            $shipping[$value]  = Shipping::forMerchant($value)->get();
            $packaging[$value] = Package::where('user_id', $value)->get();
        }
        return response()->json(['status' => true, 'data' => ['shipping' => $shipping, 'packaging' => $packaging], 'error' => []]);
    }

    public function countries()
    {
        $countries = Country::with('cities')->get();
        return response()->json(['status' => true, 'data' => $countries, 'error' => []]);
    }

    /**
     * خصم المخزون/المقاس من MerchantItem بدل CatalogItem
     */
    protected function decrementMerchantStockAndSizes(\App\Models\Cart $cart): void
    {
        // // dd(['items' => array_keys($cart->items ?? [])]); // اختباري

        foreach ($cart->items as $cartItem) {
            $catalogItemId = (int)($cartItem['item']['id'] ?? 0);
            $merchantUserId  = (int)($cartItem['item']['user_id'] ?? 0);
            $qty       = (int)($cartItem['qty'] ?? 0);
            $sizeKey   = $cartItem['size_key'] ?? null;
            $sizeQty   = $cartItem['size_qty'] ?? null; // قد تم تمريره من العميل (قيمة جديدة/مباشرة)

            if (!$catalogItemId || !$merchantUserId || $qty <= 0) {
                continue;
            }

            $mp = MerchantItem::where('catalog_item_id', $catalogItemId)
                ->where('user_id', $merchantUserId)
                ->first();

            if (!$mp) {
                continue;
            }

            // خصم المخزون العام
            if (!is_null($mp->stock)) {
                $mp->stock = max(0, (int)$mp->stock - $qty);
            }

            // تحديث size_qty للفهرس المحدد - نتبع نفس نمط النظام القديم (يتم تمرير القيمة الجديدة مباشرة من العميل)
            if ($sizeQty !== null && $sizeKey !== null && $mp->size_qty) {
                $arr = is_array($mp->size_qty) ? $mp->size_qty : explode(',', (string)$mp->size_qty);
                $idx = (int)$sizeKey;
                $arr[$idx] = (int)$sizeQty;
                $mp->size_qty = implode(',', $arr);
            }

            $mp->save();
        }
    }

    /**
     * استرجاع مخزون/مقاسات MerchantItem عند رفض الطلب
     */
    protected function restoreMerchantStockAndSizesFromPurchase(\App\Models\Purchase $purchase): void
    {
        $cart = unserialize(bzdecompress(utf8_decode($purchase->cart)));
        if (!$cart || empty($cart->items)) {
            return;
        }

        foreach ($cart->items as $cartItem) {
            $catalogItemId = (int)($cartItem['item']['id'] ?? 0);
            $merchantUserId  = (int)($cartItem['item']['user_id'] ?? 0);
            $qty       = (int)($cartItem['qty'] ?? 0);
            $sizeKey   = $cartItem['size_key'] ?? null;
            $sizeQty   = $cartItem['size_qty'] ?? null;

            if (!$catalogItemId || !$merchantUserId || $qty <= 0) {
                continue;
            }

            $mp = MerchantItem::where('catalog_item_id', $catalogItemId)
                ->where('user_id', $merchantUserId)
                ->first();

            if (!$mp) {
                continue;
            }

            // إعادة المخزون العام
            if (!is_null($mp->stock)) {
                $mp->stock = (int)$mp->stock + $qty;
            }

            // إعادة size_qty للفهرس المحدد بالقيمة المخزّنة في السلة
            if ($sizeQty !== null && $sizeKey !== null && $mp->size_qty) {
                $arr = is_array($mp->size_qty) ? $mp->size_qty : explode(',', (string)$mp->size_qty);
                $idx = (int)$sizeKey;
                $arr[$idx] = (int)$sizeQty;
                $mp->size_qty = implode(',', $arr);
            }

            $mp->save();
        }
    }

    /**
     * Create an OTO shipment(s) after the purchase is successfully created.
     * Store the results in merchant_shipping_id as JSON, and update the shipping/shipping_name for the view.
     *
     * يستخدم TryotoService الموحد لإدارة التوكن وإنشاء الشحنات
     */
    private function createOtoShipments(\App\Models\Purchase $purchase, array $input): void
    {
        // Check shipping selection — supports array (multi-merchant) or single-value scenarios
        $shippingInput = $input['shipping'] ?? null;
        if (!$shippingInput) {
            return;
        }
        $selections = is_array($shippingInput) ? $shippingInput : [0 => $shippingInput];

        $otoPayloads = [];
        foreach ($selections as $merchantId => $value) {
            // OTO option is in the form: deliveryOptionId#Company#price
            if (!is_string($value) || strpos($value, '#') === false) {
                continue; // Not OTO, could be an internal shipping ID
            }
            [$deliveryOptionId, $company, $price] = explode('#', $value);

            // Use merchant-specific credentials for each merchant
            $tryotoService = app(\App\Services\TryotoService::class)->forMerchant((int)$merchantId);
            $result = $tryotoService->createShipment(
                $purchase,
                (int)$merchantId ?: 0,
                $deliveryOptionId,
                $company,
                (float)$price,
                'express'
            );

            if ($result['success']) {
                $otoPayloads[] = [
                    'merchant_id' => (string)$merchantId,
                    'company' => $company,
                    'price' => (float)$price,
                    'deliveryOptionId'=> $deliveryOptionId,
                    'shipmentId' => $result['shipment_id'] ?? null,
                    'trackingNumber' => $result['tracking_number'] ?? null,
                ];
            } else {
                Log::error('Tryoto createShipment failed via TryotoService', [
                    'purchase_id' => $purchase->id,
                    'merchant_id' => $merchantId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        }

        if ($otoPayloads) {
            // 1) Store the details in merchant_shipping_id as JSON text (no migration required)
            $purchase->merchant_shipping_id = json_encode(['oto' => $otoPayloads], JSON_UNESCAPED_UNICODE);

            // 2) Quick display and explanation
            $first = $otoPayloads[0];
            $purchase->shipping_name = 'Tryoto - ' . ($first['company'] ?? 'N/A') . ' (Tracking: ' . ($first['trackingNumber'] ?? 'N/A') . ')';

            // إذا كانت shipping فارغة أو غير محددة، نضع 'shipto' كقيمة افتراضية
            if (empty($purchase->shipping) || $purchase->shipping !== 'shipto') {
                $purchase->shipping = 'shipto';
            }

            $purchase->save();
        }
    }
}
