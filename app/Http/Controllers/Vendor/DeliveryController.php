<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{
    Models\Order
};
use App\Helpers\PriceHelper;
use App\Models\City;
use App\Models\DeliveryRider;
use App\Models\Package;
use App\Models\Rider;
use App\Models\RiderServiceArea;
use App\Models\Shipping;
use App\Models\ShipmentStatusLog;
use App\Models\Generalsetting;
use App\Services\TryotoService;
use Datatables;
use Illuminate\Support\Facades\Log;

class DeliveryController extends VendorBaseController
{
    public function index()
    {

        $user = $this->user;
        $datas = Order::orderby('id', 'desc')->with(array('vendororders' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }))->get()->reject(function ($item) use ($user) {
            if ($item->vendororders()->where('user_id', '=', $user->id)->count() == 0) {
                return true;
            }
            return false;
        });


        return view('vendor.delivery.index', compact('datas'));
    }

    //*** JSON Request
    public function datatables()
    {
        $user = $this->user;
        $datas = Order::orderby('id', 'desc')->with(array('vendororders' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }))->get()->reject(function ($item) use ($user) {
            if ($item->vendororders()->where('user_id', '=', $user->id)->count() == 0) {
                return true;
            }
            return false;
        });


        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('totalQty', function (Order $data) {
                return $data->vendororders()->where('user_id', '=', $this->user->id)->sum('qty');
            })
            ->editColumn('customer_info', function (Order $data) {
                $info = '<strong>' . __('Name') . ':</strong> ' . $data->customer_name . '<br>' .
                    '<strong>' . __('Email') . ':</strong> ' . $data->customer_email . '<br>' .
                    '<strong>' . __('Phone') . ':</strong> ' . $data->customer_phone . '<br>' .
                    '<strong>' . __('Country') . ':</strong> ' . $data->customer_country . '<br>' .
                    '<strong>' . __('City') . ':</strong> ' . $data->customer_city . '<br>' .
                    '<strong>' . __('Postal Code') . ':</strong> ' . $data->customer_zip . '<br>' .
                    '<strong>' . __('Address') . ':</strong> ' . $data->customer_address . '<br>' .
                    '<strong>' . __('Order Date') . ':</strong> ' . $data->created_at->diffForHumans() . '<br>';
                return $info;
            })


            ->editColumn('riders', function (Order $data) {
                $delivery =  DeliveryRider::where('order_id', $data->id)->whereVendorId(auth()->id())->first();

                if ($delivery) {
                    $message = '<strong class="display-5">Rider : ' . $delivery->rider->name . ' </br>Delivery Cost : ' . PriceHelper::showAdminCurrencyPrice($delivery->servicearea->price) . '</br> 
                    Pickup Point : ' . $delivery->pickup->location . '</br>
                    Status : 
                    <span class="badge badge-dark p-1">' . $delivery->status . '</span>
                    </strong>';
                    return $message;
                } else {
                    $message = '<span class="badge badge-danger p-1">' . __('Not Assigned') . '</span>';
                    return $message;
                }
            })

            ->editColumn('pay_amount', function (Order $data) {

                $order = Order::findOrFail($data->id);
                $user = $this->user;

                $price = $order->vendororders()->where('user_id', '=', $user->id)->sum('price');
                if ($order->is_shipping == 1) {
                    $vendor_shipping = json_decode($order->vendor_shipping_id);
                    $user_id = auth()->id();
                    // shipping cost
                    $shipping_id = $vendor_shipping->$user_id;
                    $shipping = Shipping::findOrFail($shipping_id);
                    if ($shipping) {
                        $price = $price + round($shipping->price * $order->currency_value, 2);
                    }

                    // packaging cost
                    $vendor_packing_id = json_decode($order->vendor_packing_id);
                    $packing_id = $vendor_packing_id->$user_id;
                    $packaging = Package::findOrFail($packing_id);
                    if ($packaging) {
                        $price = $price + round($packaging->price * $order->currency_value, 2);
                    }
                }


                return \PriceHelper::showOrderCurrencyPrice(($price), $data->currency_sign);
            })


            ->addColumn('action', function (Order $data) {
                $delevery = DeliveryRider::where('vendor_id', auth()->id())->where('order_id', $data->id)->first();
                if ($delevery && $delevery->status == 'delivered') {
                    $auction = '<div class="action-list">
                    <a href="' . route('vendor-order-show', $data->order_number) . '" class="btn btn-outline-primary btn-sm"><i class="fa fa-eye"></i> ' . __('Order View') . '</a>
                    </div>';
                } else {
                    $auction = '<div class="action-list">
                    <button data-toggle="modal" data-target="#riderList" customer-city="' . $data->customer_city . '" order_id="' . $data->id . '" class="mybtn1 searchDeliveryRider">
                    <i class="fa fa-user"></i>  ' . __("Assign Rider") . ' </button>
                    </div>';
                }


                return $auction;
            })
            ->rawColumns(['id', 'customer_info', 'riders', 'action','pay_amount'])
            ->toJson(); //--- Returning Json Data To Client Side

    }


    public function findReider(Request $request)
    {
        // البحث عن المدينة بالاسم أو بالـ ID
        $city = City::where('id', $request->city)
            ->orWhere('city_name', $request->city)
            ->orWhere('city_name_ar', $request->city)
            ->first();

        if (!$city) {
            return response()->json(['riders' => '<option value="">' . __('No riders available for this city') . '</option>']);
        }

        $areas = RiderServiceArea::where('city_id', $city->id)
            ->whereHas('rider', function($q) {
                $q->where('status', 1);
            })
            ->get();

        $ridersData = '<option value="">' . __('Select Rider') . '</option>';

        foreach ($areas as $area) {
            if ($area->rider) {
                $ridersData .= '<option riderName="' . $area->rider->name . '" area="' . $city->city_name . '" riderCost="' . PriceHelper::showAdminCurrencyPrice($area->price) . '" value="' . $area->id . '">' . $area->rider->name . ' - ' . PriceHelper::showAdminCurrencyPrice($area->price) . '</option>';
            }
        }

        return response()->json(['riders' => $ridersData]);
    }


    public function findReiderSubmit(Request $request)
    {
        $service_area = RiderServiceArea::find($request->rider_id);

        if (!$service_area) {
            return redirect()->back()->with('error', __('Invalid rider selection'));
        }

        $delivery = DeliveryRider::where('order_id', $request->order_id)
            ->where('vendor_id', auth()->id())
            ->first();

        if ($delivery) {
            $delivery->rider_id = $service_area->rider_id;
            $delivery->service_area_id = $service_area->id;
            $delivery->pickup_point_id = $request->pickup_point_id;
            $delivery->status = 'pending';
            $delivery->save();
        } else {
            $delivery = new DeliveryRider();
            $delivery->order_id = $request->order_id;
            $delivery->vendor_id = auth()->id();
            $delivery->rider_id = $service_area->rider_id;
            $delivery->service_area_id = $service_area->id;
            $delivery->pickup_point_id = $request->pickup_point_id;
            $delivery->status = 'pending';
            $delivery->save();
        }

        return redirect()->back()->with('success', __('Rider Assigned Successfully'));
    }

    /**
     * عرض خيارات الشحن من Tryoto
     */
    public function getShippingOptions(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['success' => false, 'error' => __('Order not found')]);
        }

        $vendor = $this->user;

        // الحصول على مدينة الأصل من البائع
        $originCity = $this->resolveCityName($vendor->city_id, $vendor->warehouse_city ?? $vendor->shop_city);

        if (!$originCity) {
            return response()->json(['success' => false, 'error' => __('Vendor city not configured')]);
        }

        // الحصول على مدينة الوجهة من الطلب
        $destinationCity = $this->resolveCityName(
            $order->shipping_city_id ?? $order->customer_city_id,
            $order->shipping_city ?: $order->customer_city
        );

        if (!$destinationCity) {
            return response()->json(['success' => false, 'error' => __('Customer city not specified')]);
        }

        // حساب الوزن والأبعاد من السلة
        $dimensions = $this->calculateOrderDimensions($order);
        $weight = $dimensions['weight'];

        // حساب مبلغ COD إذا كان الدفع عند الاستلام
        $codAmount = in_array($order->method, ['cod', 'Cash On Delivery']) ? (float)$order->pay_amount : 0;

        Log::info('Vendor Delivery: Getting shipping options', [
            'order_id' => $order->id,
            'origin' => $originCity,
            'destination' => $destinationCity,
            'weight' => $weight
        ]);

        $tryotoService = new TryotoService();
        $result = $tryotoService->getDeliveryOptions($originCity, $destinationCity, $weight, $codAmount, $dimensions);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? __('Failed to get shipping options')
            ]);
        }

        $options = $result['options'] ?? [];
        $html = '<option value="">' . __('Select Shipping Company') . '</option>';

        foreach ($options as $option) {
            $price = $option['price'] ?? 0;
            $company = $option['company'] ?? 'Unknown';
            $deliveryOptionId = $option['deliveryOptionId'] ?? '';
            $estimatedDays = $option['estimatedDeliveryDays'] ?? '';
            $logo = $option['logo'] ?? '';
            $serviceType = $option['serviceType'] ?? '';

            $displayPrice = PriceHelper::showAdminCurrencyPrice($price);
            $label = $company . ' - ' . $displayPrice;
            if ($estimatedDays) {
                $label .= ' (' . $estimatedDays . ' ' . __('days') . ')';
            }

            $html .= '<option value="' . $deliveryOptionId . '"
                        data-company="' . htmlspecialchars($company) . '"
                        data-price="' . $price . '"
                        data-display-price="' . $displayPrice . '"
                        data-days="' . $estimatedDays . '"
                        data-logo="' . htmlspecialchars($logo) . '"
                        data-service-type="' . htmlspecialchars($serviceType) . '">' . $label . '</option>';
        }

        return response()->json([
            'success' => true,
            'options' => $html,
            'options_count' => count($options),
            'origin' => $originCity,
            'destination' => $destinationCity
        ]);
    }

    /**
     * تحويل city ID إلى city name
     */
    private function resolveCityName($cityId, $fallbackName = null): ?string
    {
        // إذا كان لدينا ID، نبحث عن الاسم
        if ($cityId && is_numeric($cityId)) {
            $city = City::find($cityId);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        // إذا كان الاسم نصاً وليس رقماً، نستخدمه مباشرة
        if ($fallbackName && !is_numeric($fallbackName)) {
            return $fallbackName;
        }

        // آخر محاولة: إذا كان الـ fallback رقماً أيضاً
        if ($fallbackName && is_numeric($fallbackName)) {
            $city = City::find($fallbackName);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        return null;
    }

    /**
     * حساب الأبعاد والوزن من السلة
     */
    private function calculateOrderDimensions(Order $order): array
    {
        $cart = is_string($order->cart) ? json_decode($order->cart, true) : $order->cart;
        $items = $cart['items'] ?? $cart ?? [];

        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($items as $item) {
            $qty = (int)($item['qty'] ?? 1);
            $itemData = $item['item'] ?? $item;
            $weight = (float)($itemData['weight'] ?? 1);
            $totalWeight += $weight * $qty;

            // Estimate volume per item (default 30x30x30 = 27000 cm³)
            $itemVolume = 27000 * $qty;
            $totalVolume += $itemVolume;
        }

        // Calculate cubic dimensions from total volume
        $cubicRoot = pow($totalVolume, 1/3);
        $dimension = max(30, ceil($cubicRoot));

        return [
            'weight' => max(0.5, $totalWeight),
            'length' => $dimension,
            'height' => $dimension,
            'width' => $dimension
        ];
    }

    /**
     * إرسال الطلب لـ Tryoto
     */
    public function sendToTryoto(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_option_id' => 'required|string',
            'company' => 'required|string',
            'price' => 'required|numeric',
            'service_type' => 'nullable|string',
        ]);

        $order = Order::find($request->order_id);
        $vendorId = $this->user->id;

        // التحقق من أن هذا الطلب يخص هذا البائع
        $vendorOrder = $order->vendororders()->where('user_id', $vendorId)->first();
        if (!$vendorOrder) {
            return redirect()->back()->with('error', __('This order does not belong to you'));
        }

        // التحقق من عدم وجود شحنة سابقة لهذا الطلب من هذا البائع
        $existingShipment = ShipmentStatusLog::where('order_id', $order->id)
            ->where('vendor_id', $vendorId)
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->first();

        if ($existingShipment) {
            return redirect()->back()->with('error', __('A shipment already exists for this order. Tracking: ') . $existingShipment->tracking_number);
        }

        $tryotoService = new TryotoService();
        $result = $tryotoService->createShipment(
            $order,
            $vendorId,
            $request->delivery_option_id,
            $request->company,
            $request->price,
            $request->service_type ?? 'express'
        );

        if ($result['success']) {
            // تحديث حالة vendor_order إلى processing
            $vendorOrder->status = 'processing';
            $vendorOrder->save();

            return redirect()->back()->with('success', __('Shipment created successfully. Tracking Number: ') . $result['tracking_number']);
        }

        Log::error('Tryoto shipment failed', [
            'order_id' => $order->id,
            'vendor_id' => $vendorId,
            'error' => $result['error'] ?? 'Unknown error'
        ]);

        return redirect()->back()->with('error', __('Failed to create shipment: ') . ($result['error'] ?? __('Unknown error')));
    }

    /**
     * تتبع الشحنة
     */
    public function trackShipment(Request $request)
    {
        $trackingNumber = $request->tracking_number;

        if (!$trackingNumber) {
            return response()->json(['success' => false, 'error' => __('Tracking number is required')]);
        }

        $tryotoService = new TryotoService();
        $result = $tryotoService->trackShipment($trackingNumber);

        return response()->json($result);
    }

    /**
     * عرض سجل الشحنات للبائع
     */
    public function shipmentHistory($orderId)
    {
        $vendorId = $this->user->id;

        $logs = ShipmentStatusLog::where('order_id', $orderId)
            ->where('vendor_id', $vendorId)
            ->orderBy('status_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * إلغاء الشحنة
     */
    public function cancelShipment(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string',
            'reason' => 'nullable|string|max:500'
        ]);

        $vendorId = $this->user->id;

        // التحقق من أن الشحنة تخص هذا البائع
        $shipment = ShipmentStatusLog::where('tracking_number', $request->tracking_number)
            ->where('vendor_id', $vendorId)
            ->first();

        if (!$shipment) {
            return redirect()->back()->with('error', __('Shipment not found or does not belong to you'));
        }

        // التحقق من أن الشحنة قابلة للإلغاء
        $nonCancellableStatuses = ['delivered', 'out_for_delivery', 'cancelled'];
        if (in_array($shipment->status, $nonCancellableStatuses)) {
            return redirect()->back()->with('error', __('This shipment cannot be cancelled'));
        }

        $tryotoService = new TryotoService();
        $result = $tryotoService->cancelShipment($request->tracking_number, $request->reason ?? '');

        if ($result['success']) {
            return redirect()->back()->with('success', __('Shipment cancelled successfully'));
        }

        return redirect()->back()->with('error', __('Failed to cancel shipment: ') . ($result['error'] ?? __('Unknown error')));
    }

    /**
     * تحديث حالة الطلب من البائع (جاهز للاستلام)
     */
    public function markReadyForPickup(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::find($request->order_id);
        $vendorId = $this->user->id;

        $vendorOrder = $order->vendororders()->where('user_id', $vendorId)->first();
        if (!$vendorOrder) {
            return redirect()->back()->with('error', __('This order does not belong to you'));
        }

        // تحديث حالة الطلب
        $vendorOrder->status = 'ready_for_pickup';
        $vendorOrder->save();

        // إضافة تتبع
        $order->tracks()->create([
            'title' => __('Ready for Pickup'),
            'text' => __('Vendor :vendor has marked the order as ready for pickup', ['vendor' => $this->user->shop_name])
        ]);

        return redirect()->back()->with('success', __('Order marked as ready for pickup'));
    }

    /**
     * عرض إحصائيات الشحن للبائع
     */
    public function shippingStats()
    {
        $vendorId = $this->user->id;

        $tryotoService = new TryotoService();
        $stats = $tryotoService->getVendorStatistics($vendorId);

        return view('vendor.delivery.stats', compact('stats'));
    }

    /**
     * الحصول على حالة الشحنة للطلب
     */
    public function getOrderShipmentStatus($orderId)
    {
        $vendorId = $this->user->id;

        $latestStatus = ShipmentStatusLog::where('order_id', $orderId)
            ->where('vendor_id', $vendorId)
            ->orderBy('status_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestStatus) {
            return response()->json([
                'success' => true,
                'has_shipment' => false
            ]);
        }

        return response()->json([
            'success' => true,
            'has_shipment' => true,
            'tracking_number' => $latestStatus->tracking_number,
            'company' => $latestStatus->company_name,
            'status' => $latestStatus->status,
            'status_ar' => $latestStatus->status_ar,
            'status_date' => $latestStatus->status_date?->format('Y-m-d H:i'),
            'message' => $latestStatus->message_ar ?? $latestStatus->message
        ]);
    }
}
