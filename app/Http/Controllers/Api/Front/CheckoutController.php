<?php
namespace App\Http\Controllers\Api\Front;

use App\Models\MerchantProduct;
// // dd('use MerchantProduct injected'); // اختباري
use App\Classes\MuaadhMailer;
use App\Helpers\OrderHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailsResource;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Order;
use App\Models\OrderTrack;
use App\Models\Package;
use App\Models\Pagesetting;
use App\Models\Product;
use App\Models\Reward;
use App\Models\Shipping;
use App\Models\User;
use App\Models\VendorOrder;
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

            $new_cart = new Cart(null);

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
                        $item['user'] ?? $item['vendor_id'] ?? null // vendorId إلزامي
                    );
                    // // dd(['api_vendor' => $item['user'] ?? $item['vendor_id'] ?? null]); // اختباري
                }
            }

            $cart = new Cart($new_cart);

            $gs = Generalsetting::find(1);

            $currency_code = $input['currency_code'] ?? null;
            if (!empty($currency_code)) {
                $curr = Currency::where('name', '=', $currency_code)->first();
                if (empty($curr)) {
                    $curr = Currency::where('is_default', '=', 1)->first();
                }
            } else {
                $curr = Currency::where('is_default', '=', 1)->first();
            }

            // تحقق رخص (لا تُنقص هنا)
            OrderHelper::license_check($cart);

            // حفظ نسخة خفيفة من السلة داخل الطلب
            $t_cart = new Cart($cart);
            $cart_payload = [
                'totalQty'   => $t_cart->totalQty,
                'totalPrice' => $t_cart->totalPrice,
                'items'      => $t_cart->items,
            ];
            $new_cart_json = json_encode($cart_payload);

            // أفلييت
            $temp_affilate_users = OrderHelper::product_affilate_check($cart);
            $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

            // إنقاص رخص MP (بدل products) وتعيين الترخيص بالسلة
            foreach ($cart->items as $key => $prod) {
                if (!empty($prod['item']['license']) && !empty($prod['item']['license_qty'])) {
                    $vendorId = (int)($prod['item']['user_id'] ?? 0);
                    if (!$vendorId) {
                        continue;
                    }

                    $mp = MerchantProduct::where('product_id', $prod['item']['id'])
                        ->where('user_id', $vendorId)
                        ->first();

                    if ($mp && !empty($mp->license_qty)) {
                        $arr = is_array($mp->license_qty) ? $mp->license_qty : explode(',', (string)$mp->license_qty);
                        foreach ($arr as $ttl => $dtl) {
                            $dtl = (int)$dtl;
                            if ($dtl > 0) {
                                $dtl--;
                                $arr[$ttl] = $dtl;
                                $mp->license_qty = implode(',', $arr);
                                $mp->save();

                                // حدّث قيمة الترخيص في عنصر السلة كما كان سابقًا
                                $licenses = is_array($mp->license) ? $mp->license : explode(',,', (string)$mp->license);
                                $license  = $licenses[$ttl] ?? null;
                                if ($license) {
                                    $cart->MobileupdateLicense($key, $license);
                                }
                                break;
                            }
                        }
                    }
                }
            }

            $t_cart = new Cart($cart);
            $orderCalculate = PriceHelper::getOrderTotal($input, $t_cart);

            if (isset($orderCalculate['success']) && $orderCalculate['success'] == false) {
                return redirect()->back()->with('unsuccess', $orderCalculate['message']);
            }

            if ($gs->multiple_shipping == 0) {
                $orderTotal          = $orderCalculate['total_amount'];
                $shipping            = $orderCalculate['shipping'];
                $packeing            = $orderCalculate['packeing'];
                $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
                $vendor_packing_ids  = $orderCalculate['vendor_packing_ids'];
                $vendor_ids          = $orderCalculate['vendor_ids'];

                $input['shipping_title']     = @$shipping->title;
                $input['vendor_shipping_id'] = @$shipping->id;
                $input['packing_title']      = @$packeing->title;
                $input['vendor_packing_id']  = @$packeing->id;
                $input['shipping_cost']      = @$shipping->price ?? 0;
                $input['packing_cost']       = @$packeing->price ?? 0;
                $input['vendor_shipping_ids']= $vendor_shipping_ids;
                $input['vendor_packing_ids'] = $vendor_packing_ids;
                $input['vendor_ids']         = $vendor_ids;
            } else {
                // multi shipping
                $orderTotal          = $orderCalculate['total_amount'];
                $shipping            = $orderCalculate['shipping'];
                $packeing            = $orderCalculate['packeing'];
                $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
                $vendor_packing_ids  = $orderCalculate['vendor_packing_ids'];
                $vendor_ids          = $orderCalculate['vendor_ids'];
                $shipping_cost       = $orderCalculate['shipping_cost'];
                $packing_cost        = $orderCalculate['packing_cost'];

                $input['shipping_title']     = $vendor_shipping_ids;
                $input['vendor_shipping_id'] = $vendor_shipping_ids;
                $input['packing_title']      = $vendor_packing_ids;
                $input['vendor_packing_id']  = $vendor_packing_ids;
                $input['shipping_cost']      = $shipping_cost;
                $input['packing_cost']       = $packing_cost;
                $input['vendor_shipping_ids']= $vendor_shipping_ids;
                $input['vendor_packing_ids'] = $vendor_packing_ids;
                $input['vendor_ids']         = $vendor_ids;
                unset($input['shipping'], $input['packeging']);
            }

            // إنشاء الطلب
            $order = new Order;

            $input['user_id']        = $request->user_id ? $request->user_id : null;
            $input['cart']           = $new_cart_json;
            $input['affilate_users'] = $affilate_users;
            $input['currency_name']  = $curr->name;
            $input['currency_sign']  = $curr->sign;
            $input['currency_value'] = $curr->value;
            $input['pay_amount']     = $orderTotal / $curr->value;
            $input['order_number']   = Str::random(4) . time();
            $input['wallet_price']   = ($request->wallet_price ?? 0) / $curr->value;

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
                    $user = OrderHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']);
                    $input['affilate_user']   = Session::get('affilate');
                    $input['affilate_charge'] = $sub;
                }
            }

            $order->fill($input)->save();

            // Create an OTO shipment (doesn't break the order on failure)
            $this->createOtoShipments($order, $input);

            $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
            $order->notifications()->create();

            if (Auth::guard('api')->check()) {
                if ($gs->is_reward == 1) {
                    $num = $order->pay_amount;
                    $rewards = Reward::get();
                    foreach ($rewards as $i) {
                        $smallest[$i->order_amount] = abs($i->order_amount - $num);
                    }
                    asort($smallest);
                    $final_reword = Reward::where('order_amount', key($smallest))->first();
                    if ($final_reword) {
                        Auth::guard('api')->user()->update([
                            'reward' => (Auth::guard('api')->user()->reward + $final_reword->reward)
                        ]);
                    }
                }
            }

            if (Auth::guard('api')->check()) {
                Auth::guard('api')->user()->update([
                    'balance' => (Auth::guard('api')->user()->balance - $order->wallet_price)
                ]);
            }

            // بدّل منطق الخصم: خصم من merchant_products بدل OrderHelper على products
            // OrderHelper::size_qty_check($cart);
            // OrderHelper::stock_check($cart);
            $this->decrementMerchantStockAndSizes($cart);
            // // dd('mp-stock decremented'); // اختباري

            OrderHelper::vendor_order_check($cart, $order);

            if ($order->user_id != 0 && $order->wallet_price != 0) {
                OrderHelper::add_to_transaction($order, $order->wallet_price); // Store To Transactions
            }

            // Email للمشتري
            $data = [
                'to'      => $order->customer_email,
                'type'    => "new_order",
                'cname'   => $order->customer_name,
                'oamount' => "",
                'aname'   => "",
                'aemail'  => "",
                'wtitle'  => "",
                'onumber' => $order->order_number,
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendAutoOrderMail($data, $order->id);

            // Email للأدمن
            $ps = Pagesetting::find(1);
            $data = [
                'to'      => $ps->contact_email,
                'subject' => "New Order Recieved!!",
                'body'    => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);

            unset($order['cart']);
            return response()->json([
                'status' => true,
                'data'   => route('payment.checkout') . '?order_number=' . $order->order_number,
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
            $data = Order::find($id);
            $input = $request->all();

            if ($data->status == "completed") {
                // Then Save Without Changing it.
                $input['status'] = "completed";
                $data->update($input);
                return response()->json(['status' => true, 'data' => $data, 'error' => []]);
            } else {
                if ($input['status'] == "completed") {

                    foreach ($data->vendororders as $vorder) {
                        $uprice = User::find($vorder->user_id);
                        $uprice->current_balance = $uprice->current_balance + $vorder->price;
                        $uprice->update();
                    }

                    if (User::where('id', $data->affilate_user)->exists()) {
                        $auser = User::where('id', $data->affilate_user)->first();
                        $auser->affilate_income += $data->affilate_charge;
                        $auser->update();
                    }

                    $gs = Generalsetting::find(1);
                    if ($gs->is_smtp == 1) {
                        $maildata = [
                            'to'      => $data->customer_email,
                            'subject' => 'Your order ' . $data->order_number . ' is Confirmed!',
                            'body'    => "Hello " . $data->customer_name . "," . "\n Thank you for shopping with us. We are looking forward to your next visit.",
                        ];
                        $mailer = new MuaadhMailer();
                        $mailer->sendCustomMail($maildata);
                    } else {
                        $to = $data->customer_email;
                        $subject = 'Your order ' . $data->order_number . ' is Confirmed!';
                        $msg = "Hello " . $data->customer_name . "," . "\n Thank you for shopping with us. We are looking forward to your next visit.";
                        $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
                        mail($to, $subject, $msg, $headers);
                    }
                }

                if ($input['status'] == "declined") {

                    if ($data->user_id != 0) {
                        if ($data->wallet_price != 0) {
                            $user = User::find($data->user_id);
                            if ($user) {
                                $user->balance = $user->balance + $data->wallet_price;
                                $user->save();
                            }
                        }
                    }

                    // استرجاع مخزون/مقاسات MerchantProduct بدل products
                    $this->restoreMerchantStockAndSizesFromOrder($data);
                    // // dd('mp stock restored'); // اختباري

                    $gs = Generalsetting::find(1);
                    if ($gs->is_smtp == 1) {
                        $maildata = [
                            'to'      => $data->customer_email,
                            'subject' => 'Your order ' . $data->order_number . ' is Declined!',
                            'body'    => "Hello " . $data->customer_name . "," . "\n We are sorry for the inconvenience caused. We are looking forward to your next visit.",
                        ];
                        $mailer = new MuaadhMailer();
                        $mailer->sendCustomMail($maildata);
                    } else {
                        $to = $data->customer_email;
                        $subject = 'Your order ' . $data->order_number . ' is Declined!';
                        $msg = "Hello " . $data->customer_name . "," . "\n We are sorry for the inconvenience caused. We are looking forward to your next visit.";
                        $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
                        mail($to, $subject, $msg, $headers);
                    }
                }

                $data->update($input);

                if ($request->track_text) {
                    $title = ucwords($request->status);
                    $ck = OrderTrack::where('order_id', '=', $id)->where('title', '=', $title)->first();
                    if ($ck) {
                        $ck->order_id = $id;
                        $ck->title    = $title;
                        $ck->text     = $request->track_text;
                        $ck->update();
                    } else {
                        $ot = new OrderTrack;
                        $ot->order_id = $id;
                        $ot->title    = $title;
                        $ot->text     = $request->track_text;
                        $ot->save();
                    }
                }

                VendorOrder::where('order_id', '=', $id)->update(['status' => $input['status']]);

                return response()->json(['status' => true, 'data' => $data, 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    //*** POST Request
    public function delete($id)
    {
        try {
            $data = Order::find($id);
            if ($data) {
                $data->delete();
                return response()->json(['status' => true, 'data' => 'Order Deleted Successfully', 'error' => []]);
            } else {
                return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'Order Not Found']]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    //*** GET Request
    public function orderDetails(Request $request)
    {
        try {
            if ($request->has('order_number')) {
                $order_number = $request->order_number;
                $order = Order::where('order_number', $order_number)->firstOrFail();
                return response()->json(['status' => true, 'data' => new OrderDetailsResource($order), 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    /**
     * إضافة للسلة — Vendor-aware: يعتمد على MerchantProduct فقط
     */
    protected function addtocart($cart, $currency_code, $p_id, $p_qty, $p_size, $p_color, $p_size_qty, $p_size_price, $p_size_key, $p_keys, $p_values, $p_prices, $affilate_user, $vendorId)
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
                $curr = Currency::where('name', '=', $currency_code)->first();
                if (empty($curr)) {
                    $curr = Currency::where('is_default', '=', 1)->first();
                }
            } else {
                $curr = Currency::where('is_default', '=', 1)->first();
            }

            $size_price = ($size_price / $curr->value);

            // هوية المنتج فقط
            $prod = Product::where('id', '=', $id)->first([
                'id','slug','name','photo','color','sku','weight','type','file','link','measure','attributes','color_all','color_price'
            ]);
            if (!$prod) {
                return false;
            }

            // عرض البائع
            $vendorId = (int)$vendorId;
            if ($vendorId <= 0) {
                return false;
            }

            $mp = MerchantProduct::where('product_id', $prod->id)
                ->where('user_id', $vendorId)
                ->where('status', 1)
                ->first();

            if (!$mp) {
                return false;
            }

            // حقن سياق البائع بالقيم الصحيحة
            $prod->user_id              = $vendorId;             // inject vendor context
            $prod->merchant_product_id  = $mp->id;

            // سعر أساسي + عمولة + سعر مقاس إن وجد
            $gs  = Generalsetting::find(1);
            $basePrice = (float)$mp->price;
            $withCommission = $basePrice + (float)$gs->fixed_commission + ($basePrice * (float)$gs->percentage_commission / 100);

            // إن كان لديك آلية size_price متقدمة على MP، طبّقها هنا
            if (!empty($mp->size_price) && $size !== '' && $size !== null) {
                // إن كانت size_price رقمًا موحدًا:
                if (is_numeric($mp->size_price)) {
                    $withCommission += (float)$mp->size_price;
                }
                // لو تخزينك للـ size_price مصفوفة/CSV، عدّل هذا القسم لانتقاء السعر المناسب
            }

            $prod->price          = round($withCommission, 2);
            $prod->previous_price = $mp->previous_price;
            $prod->stock          = $mp->stock;

            // مقاسات/خصائص من MP
            $prod->setAttribute('size',       $mp->size);
            $prod->setAttribute('size_qty',   $mp->size_qty);
            $prod->setAttribute('size_price', $mp->size_price);
            $prod->setAttribute('stock_check',         $mp->stock_check ?? null);
            $prod->setAttribute('minimum_qty',         $mp->minimum_qty ?? null);
            $prod->setAttribute('whole_sell_qty',      $mp->whole_sell_qty ?? null);
            $prod->setAttribute('whole_sell_discount', $mp->whole_sell_discount ?? null);
            $prod->setAttribute('color_all',           $mp->color_all ?? null);

            // أسعار خصائص إضافية (إن أرسلت)
            if (!empty($prices) && !empty($prices[0])) {
                foreach ($prices as $data) {
                    $prod->price += ((float)$data / $curr->value);
                }
            }

            // default size / color
            if ($size === '' && !empty($prod->size)) {
                // قد تكون مصفوفة/CSV — هنا نفترض أول قيمة
                if (is_array($prod->size)) {
                    $size = str_replace(' ', '-', trim($prod->size[0]));
                } else {
                    $size = str_replace(' ', '-', trim((string)$prod->size));
                }
            }

            if ($color === '' && !empty($prod->color)) {
                // color من هوية المنتج كما كان
                if (is_array($prod->color)) {
                    $color = $prod->color[0];
                } else {
                    $color = (string)$prod->color;
                }
            }
            $color = str_replace('#', '', (string)$color);

            $cart->addnum($prod, $prod->id, $qty, $size, $color, $size_qty, $size_price, $size_key, $keys, $values, $affilate_user);
            $cart->totalPrice = 0;

            foreach ($cart->items as $data) {
                $cart->totalPrice += $data['price'];
            }

            return $cart->items;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCoupon(Request $request)
    {
        $code = $request->coupon;
        $coupon = Coupon::where('code', '=', $code)->where('status', 1)->first();

        if ($coupon) {
            $today = date('Y-m-d');
            $from  = date('Y-m-d', strtotime($coupon->start_date));
            $to    = date('Y-m-d', strtotime($coupon->end_date));

            if ($from <= $today && $to >= $today) {
                return response()->json(['status' => true, 'data' => $coupon, 'error' => []]);
            } else {
                return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Coupon']);
            }
        } else {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Coupon Not Found']);
        }
    }

    public function getShippingPackaging()
    {
        $shipping  = Shipping::whereUserId(0)->get();
        $packaging = Package::whereUserId(0)->get();
        return response()->json(['status' => true, 'data' => ['shipping' => $shipping, 'packaging' => $packaging], 'error' => []]);
    }

    public function VendorWisegetShippingPackaging(Request $request)
    {
        $explode = explode(',', $request->vendor_ids);
        foreach ($explode as $key => $value) {
            $shipping[$value]  = Shipping::forVendor($value)->get();
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
     * خصم المخزون/المقاس من MerchantProduct بدل Product
     */
    protected function decrementMerchantStockAndSizes(\App\Models\Cart $cart): void
    {
        // // dd(['items' => array_keys($cart->items ?? [])]); // اختباري

        foreach ($cart->items as $prod) {
            $productId = (int)($prod['item']['id'] ?? 0);
            $vendorId  = (int)($prod['item']['user_id'] ?? 0);
            $qty       = (int)($prod['qty'] ?? 0);
            $sizeKey   = $prod['size_key'] ?? null;
            $sizeQty   = $prod['size_qty'] ?? null; // قد تم تمريره من العميل (قيمة جديدة/مباشرة)

            if (!$productId || !$vendorId || $qty <= 0) {
                continue;
            }

            $mp = MerchantProduct::where('product_id', $productId)
                ->where('user_id', $vendorId)
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
     * استرجاع مخزون/مقاسات MerchantProduct عند رفض الطلب
     */
    protected function restoreMerchantStockAndSizesFromOrder(\App\Models\Order $data): void
    {
        $cart = unserialize(bzdecompress(utf8_decode($data->cart)));
        if (!$cart || empty($cart->items)) {
            return;
        }

        foreach ($cart->items as $prod) {
            $productId = (int)($prod['item']['id'] ?? 0);
            $vendorId  = (int)($prod['item']['user_id'] ?? 0);
            $qty       = (int)($prod['qty'] ?? 0);
            $sizeKey   = $prod['size_key'] ?? null;
            $sizeQty   = $prod['size_qty'] ?? null;

            if (!$productId || !$vendorId || $qty <= 0) {
                continue;
            }

            $mp = MerchantProduct::where('product_id', $productId)
                ->where('user_id', $vendorId)
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
     * Create an OTO shipment(s) after the order is successfully created.
     * Store the results in vendor_shipping_id as JSON, and update the shipping/shipping_title for the view.
     *
     * يستخدم TryotoService الموحد لإدارة التوكن وإنشاء الشحنات
     */
    private function createOtoShipments(\App\Models\Order $order, array $input): void
    {
        // Check shipping selection — supports array (multi-vendor) or single-value scenarios
        $shippingInput = $input['shipping'] ?? null;
        if (!$shippingInput) {
            return;
        }
        $selections = is_array($shippingInput) ? $shippingInput : [0 => $shippingInput];

        // استخدام TryotoService الموحد
        $tryotoService = app(\App\Services\TryotoService::class);

        $otoPayloads = [];
        foreach ($selections as $vendorId => $value) {
            // OTO option is in the form: deliveryOptionId#Company#price
            if (!is_string($value) || strpos($value, '#') === false) {
                continue; // Not OTO, could be an internal shipping ID
            }
            [$deliveryOptionId, $company, $price] = explode('#', $value);

            // استخدام createShipment من TryotoService
            $result = $tryotoService->createShipment(
                $order,
                (int)$vendorId ?: 0,
                $deliveryOptionId,
                $company,
                (float)$price,
                'express'
            );

            if ($result['success']) {
                $otoPayloads[] = [
                    'vendor_id' => (string)$vendorId,
                    'company' => $company,
                    'price' => (float)$price,
                    'deliveryOptionId'=> $deliveryOptionId,
                    'shipmentId' => $result['shipment_id'] ?? null,
                    'trackingNumber' => $result['tracking_number'] ?? null,
                ];
            } else {
                Log::error('Tryoto createShipment failed via TryotoService', [
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        }

        if ($otoPayloads) {
            // 1) Store the details in vendor_shipping_id as JSON text (no migration required)
            $order->vendor_shipping_id = json_encode(['oto' => $otoPayloads], JSON_UNESCAPED_UNICODE);

            // 2) Quick display and explanation
            $first = $otoPayloads[0];
            $order->shipping_title = 'Tryoto - ' . ($first['company'] ?? 'N/A') . ' (Tracking: ' . ($first['trackingNumber'] ?? 'N/A') . ')';

            // إذا كانت shipping فارغة أو غير محددة، نضع 'shipto' كقيمة افتراضية
            if (empty($order->shipping) || !in_array($order->shipping, ['shipto', 'pickup'])) {
                $order->shipping = 'shipto';
            }

            $order->save();
        }
    }
}
