<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{
    Models\Purchase
};
use App\Helpers\PriceHelper;
use App\Models\City;
use App\Models\DeliveryCourier;
use App\Models\Package;
use App\Models\Courier;
use App\Models\CourierServiceArea;
use App\Models\Shipping;
use App\Models\ShipmentStatusLog;
use App\Models\Muaadhsetting;
use App\Services\TryotoService;
use Datatables;
use Illuminate\Support\Facades\Log;

class DeliveryController extends MerchantBaseController
{
    public function index()
    {
        $user = $this->user;

        // ✅ FIX: Use explicit query instead of silent reject
        // Get purchases that have merchant_purchases for this merchant
        $datas = Purchase::orderby('id', 'desc')
            ->whereHas('merchantPurchases', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['merchantPurchases' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // ✅ Log for debugging if no purchases found
        if ($datas->isEmpty()) {
            Log::info('Merchant Delivery: No purchases found for merchant', [
                'merchant_id' => $user->id,
                'merchant_name' => $user->shop_name ?? $user->name,
                'tip' => 'Check if merchant_purchases table has records with this user_id'
            ]);
        } else {
            Log::debug('Merchant Delivery: Found purchases', [
                'merchant_id' => $user->id,
                'purchase_count' => $datas->count()
            ]);
        }

        // ✅ Check Tryoto configuration status
        $tryotoStatus = $this->checkTryotoStatus();

        return view('merchant.delivery.index', compact('datas', 'tryotoStatus'));
    }

    /**
     * Check Tryoto configuration status for display
     */
    private function checkTryotoStatus(): array
    {
        $tryotoService = new TryotoService();
        $config = $tryotoService->checkConfiguration($this->user->id);

        $status = [
            'available' => $config['configured'],
            'sandbox' => $config['sandbox'],
            'has_token' => $config['has_cached_token'],
            'issues' => $config['issues'] ?? [],
            'message' => null
        ];

        if (!$config['configured']) {
            $status['message'] = __('Smart Shipping (Tryoto) is not configured');
            Log::warning('Merchant Delivery: Tryoto not configured', $config);
        }

        return $status;
    }

    //*** JSON Request
    public function datatables()
    {
        $user = $this->user;

        // ✅ FIX: Use whereHas instead of silent reject
        $datas = Purchase::orderby('id', 'desc')
            ->whereHas('merchantPurchases', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['merchantPurchases' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();


        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('totalQty', function (Purchase $data) {
                return $data->merchantPurchases()->where('user_id', '=', $this->user->id)->sum('qty');
            })
            ->editColumn('customer_info', function (Purchase $data) {
                $info = '<strong>' . __('Name') . ':</strong> ' . $data->customer_name . '<br>' .
                    '<strong>' . __('Email') . ':</strong> ' . $data->customer_email . '<br>' .
                    '<strong>' . __('Phone') . ':</strong> ' . $data->customer_phone . '<br>' .
                    '<strong>' . __('Country') . ':</strong> ' . $data->customer_country . '<br>' .
                    '<strong>' . __('City') . ':</strong> ' . $data->customer_city . '<br>' .
                    '<strong>' . __('Postal Code') . ':</strong> ' . $data->customer_zip . '<br>' .
                    '<strong>' . __('Address') . ':</strong> ' . $data->customer_address . '<br>' .
                    '<strong>' . __('Purchase Date') . ':</strong> ' . $data->created_at->diffForHumans() . '<br>';
                return $info;
            })


            ->editColumn('couriers', function (Purchase $data) {
                $delivery =  DeliveryCourier::where('purchase_id', $data->id)->where('merchant_id', auth()->id())->first();

                if ($delivery) {
                    $message = '<strong class="display-5">Courier : ' . $delivery->courier->name . ' </br>Delivery Cost : ' . PriceHelper::showAdminCurrencyPrice($delivery->servicearea->price) . '</br>
                    Warehouse Location : ' . $delivery->merchantLocation->location . '</br>
                    Status :
                    <span class="badge badge-dark p-1">' . $delivery->status . '</span>
                    </strong>';
                    return $message;
                } else {
                    $message = '<span class="badge badge-danger p-1">' . __('Not Assigned') . '</span>';
                    return $message;
                }
            })

            ->editColumn('pay_amount', function (Purchase $data) {

                $purchase = Purchase::findOrFail($data->id);
                $user = $this->user;

                $price = $purchase->merchantPurchases()->where('user_id', '=', $user->id)->sum('price');


                return \PriceHelper::showOrderCurrencyPrice(($price), $data->currency_sign);
            })


            ->addColumn('action', function (Purchase $data) {
                $delevery = DeliveryCourier::where('merchant_id', auth()->id())->where('purchase_id', $data->id)->first();
                if ($delevery && $delevery->status == DeliveryCourier::STATUS_DELIVERED) {
                    $auction = '<div class="action-list">
                    <a href="' . route('merchant-purchase-show', $data->purchase_number) . '" class="btn btn-outline-primary btn-sm"><i class="fa fa-eye"></i> ' . __('Purchase View') . '</a>
                    </div>';
                } else {
                    $auction = '<div class="action-list">
                    <button data-bs-toggle="modal" data-bs-target="#courierList" customer-city="' . $data->customer_city . '" purchase_id="' . $data->id . '" class="mybtn1 searchDeliveryCourier">
                    <i class="fa fa-user"></i>  ' . __("Assign Courier") . ' </button>
                    </div>';
                }


                return $auction;
            })
            ->rawColumns(['id', 'customer_info', 'couriers', 'action','pay_amount'])
            ->toJson(); //--- Returning Json Data To Client Side

    }


    public function findCourier(Request $request)
    {
        // البحث عن المدينة بالاسم أو بالـ ID (الاسم إنجليزي فقط - لا يوجد city_name_ar)
        $city = City::where('id', $request->city)
            ->orWhere('city_name', $request->city)
            ->first();

        if (!$city) {
            return response()->json(['couriers' => '<option value="">' . __('No couriers available for this city') . '</option>']);
        }

        $areas = CourierServiceArea::where('city_id', $city->id)
            ->whereHas('courier', function($q) {
                $q->where('status', 1);
            })
            ->get();

        $couriersData = '<option value="">' . __('Select Courier') . '</option>';

        foreach ($areas as $area) {
            if ($area->courier) {
                $couriersData .= '<option courierName="' . $area->courier->name . '" area="' . $city->city_name . '" courierCost="' . PriceHelper::showAdminCurrencyPrice($area->price) . '" value="' . $area->id . '">' . $area->courier->name . ' - ' . PriceHelper::showAdminCurrencyPrice($area->price) . '</option>';
            }
        }

        return response()->json(['couriers' => $couriersData]);
    }


    /**
     * Assign courier to purchase
     * NEW WORKFLOW: Creates delivery with pending_approval status
     */
    public function findCourierSubmit(Request $request)
    {
        $service_area = CourierServiceArea::find($request->courier_id);

        if (!$service_area) {
            return redirect()->back()->with('error', __('Invalid courier selection'));
        }

        $purchase = Purchase::find($request->purchase_id);
        if (!$purchase) {
            return redirect()->back()->with('error', __('Purchase not found'));
        }

        // Calculate amounts
        $merchantOrder = $purchase->merchantPurchases()->where('user_id', auth()->id())->first();
        $purchaseAmount = $merchantOrder ? $merchantOrder->price : 0;
        $deliveryFee = $service_area->price ?? 0;

        // Determine payment method
        $paymentMethod = in_array($purchase->method, ['cod', 'Cash On Delivery'])
            ? DeliveryCourier::PAYMENT_COD
            : DeliveryCourier::PAYMENT_ONLINE;

        // Check for existing delivery record (reassignment case)
        $delivery = DeliveryCourier::where('purchase_id', $request->purchase_id)
            ->where('merchant_id', auth()->id())
            ->first();

        if ($delivery) {
            // Reassign courier (e.g., after rejection)
            $delivery->initializeAssignment(
                courierId: $service_area->courier_id,
                serviceAreaId: $service_area->id,
                merchantLocationId: $request->merchant_location_id,
                deliveryFee: $deliveryFee,
                purchaseAmount: $purchaseAmount,
                paymentMethod: $paymentMethod
            );
        } else {
            // Create new delivery record
            $delivery = DeliveryCourier::createForPurchase(
                purchaseId: $request->purchase_id,
                merchantId: auth()->id(),
                courierId: $service_area->courier_id,
                serviceAreaId: $service_area->id,
                merchantLocationId: $request->merchant_location_id,
                deliveryFee: $deliveryFee,
                purchaseAmount: $purchaseAmount,
                paymentMethod: $paymentMethod
            );
        }

        Log::info('Courier assigned to delivery', [
            'delivery_id' => $delivery->id,
            'purchase_id' => $request->purchase_id,
            'courier_id' => $service_area->courier_id,
            'status' => DeliveryCourier::STATUS_PENDING_APPROVAL,
        ]);

        return redirect()->back()->with('success', __('Courier assigned! Waiting for courier approval.'));
    }

    /**
     * عرض خيارات الشحن من Tryoto
     * ✅ محسّن: معالجة أخطاء أفضل + تحديد المدن من المصادر الصحيحة
     */
    public function getShippingOptions(Request $request)
    {
        try {
            $purchase = Purchase::find($request->purchase_id);

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'error' => __('Purchase not found'),
                    'error_code' => 'PURCHASE_NOT_FOUND'
                ]);
            }

            $merchant = $this->user;

            // ✅ مدينة التاجر من جدول users (city_id)
            $originCity = $this->resolveMerchantCity($merchant);

            if (!$originCity) {
                Log::warning('Merchant Delivery: Merchant city not configured', [
                    'merchant_id' => $merchant->id,
                    'city_id' => $merchant->city_id,
                    'shop_city' => $merchant->shop_city ?? null
                ]);
                return response()->json([
                    'success' => false,
                    'error' => __('Please configure your city in merchant settings'),
                    'error_code' => 'MERCHANT_CITY_MISSING',
                    'show_settings_link' => true
                ]);
            }

            // ✅ مدينة العميل من الخريطة/العنوان في الطلب
            $destinationCity = $this->resolveCustomerCity($purchase);

            if (!$destinationCity) {
                Log::warning('Merchant Delivery: Customer city not found in purchase', [
                    'purchase_id' => $purchase->id,
                    'customer_city' => $purchase->customer_city,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => __('Customer city not specified in purchase'),
                    'error_code' => 'CUSTOMER_CITY_MISSING'
                ]);
            }

            // حساب الوزن والأبعاد من السلة
            $dimensions = $this->calculatePurchaseDimensions($purchase);
            $weight = $dimensions['weight'];

            // حساب مبلغ COD إذا كان الدفع عند الاستلام
            $codAmount = in_array($purchase->method, ['cod', 'Cash On Delivery']) ? (float)$purchase->pay_amount : 0;

            Log::debug('Merchant Delivery: Getting shipping options', [
                'purchase_id' => $purchase->id,
                'merchant_id' => $merchant->id,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight
            ]);

            // ✅ Use merchant-specific credentials
            $tryotoService = (new TryotoService())->forMerchant($merchant->id);

            // ✅ التحقق من إعدادات Tryoto للتاجر
            $config = $tryotoService->checkConfiguration($merchant->id);
            if (!$config['configured']) {
                Log::error('Merchant Delivery: Tryoto not configured for merchant', $config);
                return response()->json([
                    'success' => false,
                    'error' => __('Smart Shipping is temporarily unavailable'),
                    'error_code' => 'TRYOTO_NOT_CONFIGURED',
                    'details' => $config['issues']
                ]);
            }

            $result = $tryotoService->getDeliveryOptions($originCity, $destinationCity, $weight, $codAmount, $dimensions);

            if (!$result['success']) {
                // ✅ معالجة أخطاء محددة
                $errorCode = $result['error_code'] ?? 'UNKNOWN';
                $userFriendlyError = $this->getShippingErrorMessage($errorCode, $result['error'] ?? '');

                Log::warning('Merchant Delivery: Tryoto API failed', [
                    'purchase_id' => $purchase->id,
                    'origin' => $originCity,
                    'destination' => $destinationCity,
                    'error' => $result['error'],
                    'error_code' => $errorCode
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $userFriendlyError,
                    'error_code' => $errorCode,
                    'technical_error' => $result['error'] ?? null
                ]);
            }

            $options = $result['options'] ?? [];

            if (empty($options)) {
                return response()->json([
                    'success' => false,
                    'error' => __('No shipping options available for this route'),
                    'error_code' => 'NO_OPTIONS',
                    'origin' => $originCity,
                    'destination' => $destinationCity
                ]);
            }

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

        } catch (\Exception $e) {
            // ✅ لا نُسقط الصفحة - نرجع رسالة خطأ ودية
            Log::error('Merchant Delivery: Exception in getShippingOptions', [
                'purchase_id' => $request->purchase_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => __('Shipping service temporarily unavailable. Please try again later.'),
                'error_code' => 'EXCEPTION'
            ]);
        }
    }

    /**
     * ✅ مدينة التاجر من جدول users (city_id أو shop_city)
     */
    private function resolveMerchantCity($merchant): ?string
    {
        // المحاولة الأولى: city_id (الأفضل - مرتبط بجدول المدن)
        if ($merchant->city_id) {
            $city = City::find($merchant->city_id);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        // المحاولة الثانية: shop_city (نص مباشر)
        if (!empty($merchant->shop_city)) {
            // إذا كان رقماً، نبحث عن المدينة
            if (is_numeric($merchant->shop_city)) {
                $city = City::find($merchant->shop_city);
                if ($city && $city->city_name) {
                    return $city->city_name;
                }
            }
            // إذا كان نصاً، نستخدمه مباشرة
            return $merchant->shop_city;
        }

        // المحاولة الثالثة: warehouse_city
        if (!empty($merchant->warehouse_city)) {
            if (is_numeric($merchant->warehouse_city)) {
                $city = City::find($merchant->warehouse_city);
                if ($city && $city->city_name) {
                    return $city->city_name;
                }
            }
            return $merchant->warehouse_city;
        }

        Log::warning('Merchant has no city configured', [
            'merchant_id' => $merchant->id,
            'merchant_name' => $merchant->name,
            'city_id' => $merchant->city_id,
            'shop_city' => $merchant->shop_city ?? null
        ]);

        return null;
    }

    /**
     * ✅ مدينة العميل من الطلب
     * customer_city يخزن ID المدينة (من الخريطة)
     */
    private function resolveCustomerCity(Purchase $purchase): ?string
    {
        // customer_city يحتوي على ID المدينة
        $cityValue = $purchase->customer_city;

        if (!$cityValue) {
            Log::warning('Purchase has no customer_city', [
                'purchase_id' => $purchase->id
            ]);
            return null;
        }

        // إذا كان رقماً (ID)، نبحث عن المدينة
        if (is_numeric($cityValue)) {
            $city = City::find($cityValue);
            if ($city && $city->city_name) {
                return $city->city_name;
            }

            Log::warning('City not found for purchase', [
                'purchase_id' => $purchase->id,
                'customer_city' => $cityValue
            ]);
            return null;
        }

        // إذا كان نصاً، نستخدمه مباشرة (حالة قديمة)
        return $cityValue;
    }

    /**
     * ✅ رسائل خطأ ودية للمستخدم
     */
    private function getShippingErrorMessage(string $errorCode, string $technicalError = ''): string
    {
        $messages = [
            'TOKEN_ERROR' => __('Shipping service authentication failed. Please try again.'),
            'AUTH_ERROR' => __('Shipping service authentication failed. Please try again.'),
            'INCOMPLETE_DATA' => __('Missing shipping information. Please check purchase details.'),
            'API_ERROR' => __('Shipping service temporarily unavailable.'),
            'EXCEPTION' => __('Shipping service temporarily unavailable. Please try again later.'),
        ];

        return $messages[$errorCode] ?? __('Failed to get shipping options. Please try again.');
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
    private function calculatePurchaseDimensions(Purchase $purchase): array
    {
        $cart = $purchase->cart; // Model cast handles decoding
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
            'purchase_id' => 'required|exists:purchases,id',
            'delivery_option_id' => 'required|string',
            'company' => 'required|string',
            'price' => 'required|numeric',
            'service_type' => 'nullable|string',
        ]);

        $purchase = Purchase::find($request->purchase_id);
        $merchantId = $this->user->id;

        // التحقق من أن هذا الطلب يخص هذا التاجر
        $merchantOrder = $purchase->merchantPurchases()->where('user_id', $merchantId)->first();
        if (!$merchantOrder) {
            return redirect()->back()->with('error', __('This purchase does not belong to you'));
        }

        // التحقق من عدم وجود شحنة سابقة لهذا الطلب من هذا التاجر
        $existingShipment = ShipmentStatusLog::where('purchase_id', $purchase->id)
            ->where('merchant_id', $merchantId)
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->first();

        if ($existingShipment) {
            return redirect()->back()->with('error', __('A shipment already exists for this purchase. Tracking: ') . $existingShipment->tracking_number);
        }

        // ✅ Use merchant-specific credentials
        $tryotoService = (new TryotoService())->forMerchant($merchantId);
        $result = $tryotoService->createShipment(
            $purchase,
            $merchantId,
            $request->delivery_option_id,
            $request->company,
            $request->price,
            $request->service_type ?? 'express'
        );

        if ($result['success']) {
            // تحديث حالة merchant_purchase إلى processing
            $merchantOrder->status = 'processing';
            $merchantOrder->save();

            return redirect()->back()->with('success', __('Shipment created successfully. Tracking Number: ') . $result['tracking_number']);
        }

        Log::error('Tryoto shipment failed', [
            'purchase_id' => $purchase->id,
            'merchant_id' => $merchantId,
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
     * عرض سجل الشحنات للتاجر
     */
    public function shipmentHistory($purchaseId)
    {
        $merchantId = $this->user->id;

        $logs = ShipmentStatusLog::where('purchase_id', $purchaseId)
            ->where('merchant_id', $merchantId)
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

        $merchantId = $this->user->id;

        // التحقق من أن الشحنة تخص هذا التاجر
        $shipment = ShipmentStatusLog::where('tracking_number', $request->tracking_number)
            ->where('merchant_id', $merchantId)
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
     * STEP 2: Merchant marks order ready for pickup
     * NEW WORKFLOW: approved -> ready_for_pickup
     */
    public function markReadyForCourierCollection(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id'
        ]);

        $purchase = Purchase::find($request->purchase_id);
        $merchantId = $this->user->id;

        $merchantOrder = $purchase->merchantPurchases()->where('user_id', $merchantId)->first();
        if (!$merchantOrder) {
            return redirect()->back()->with('error', __('This purchase does not belong to you'));
        }

        // Get DeliveryCourier record
        $deliveryCourier = DeliveryCourier::where('purchase_id', $purchase->id)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$deliveryCourier) {
            return redirect()->back()->with('error', __('No courier assigned to this purchase'));
        }

        // Check if courier has approved (status should be 'approved')
        if (!$deliveryCourier->isApproved()) {
            return redirect()->back()->with('error', __('Courier has not approved this delivery yet. Current status: ') . $deliveryCourier->status_label);
        }

        // Transition to ready_for_pickup
        try {
            $deliveryCourier->markReadyForPickup();

            Log::info('Merchant marked order ready for pickup', [
                'delivery_courier_id' => $deliveryCourier->id,
                'purchase_id' => $purchase->id,
                'courier_id' => $deliveryCourier->courier_id,
            ]);

            // Add tracking entry
            $purchase->tracks()->create([
                'title' => __('Ready for Courier Pickup'),
                'text' => __('Merchant :merchant has prepared the order and is waiting for courier pickup', ['merchant' => $this->user->shop_name])
            ]);

            return redirect()->back()->with('success', __('Order marked as ready! Courier will pick it up soon.'));
        } catch (\Exception $e) {
            Log::error('Failed to mark ready for pickup', [
                'error' => $e->getMessage(),
                'delivery_id' => $deliveryCourier->id
            ]);
            return redirect()->back()->with('error', __('Failed to update status: ') . $e->getMessage());
        }
    }

    /**
     * STEP 3: Merchant confirms handover to courier
     * NEW WORKFLOW: ready_for_pickup -> picked_up
     */
    public function confirmHandoverToCourier(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id'
        ]);

        $purchase = Purchase::find($request->purchase_id);
        $merchantId = $this->user->id;

        $merchantOrder = $purchase->merchantPurchases()->where('user_id', $merchantId)->first();
        if (!$merchantOrder) {
            return redirect()->back()->with('error', __('This purchase does not belong to you'));
        }

        // Get DeliveryCourier record
        $deliveryCourier = DeliveryCourier::where('purchase_id', $purchase->id)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$deliveryCourier) {
            return redirect()->back()->with('error', __('No courier assigned to this purchase'));
        }

        // Check if order is ready for pickup
        if (!$deliveryCourier->isReadyForPickup()) {
            return redirect()->back()->with('error', __('Order is not ready for pickup. Current status: ') . $deliveryCourier->status_label);
        }

        // Transition to picked_up
        try {
            $deliveryCourier->confirmHandoverToCourier();

            // Update merchant purchase status
            $merchantOrder->status = 'processing';
            $merchantOrder->save();

            Log::info('Merchant confirmed handover to courier', [
                'delivery_courier_id' => $deliveryCourier->id,
                'purchase_id' => $purchase->id,
                'courier_id' => $deliveryCourier->courier_id,
                'courier_name' => $deliveryCourier->courier->name ?? 'N/A',
            ]);

            // Add tracking entry
            $purchase->tracks()->create([
                'title' => __('Picked Up by Courier'),
                'text' => __('Order has been handed over to courier :courier for delivery', ['courier' => $deliveryCourier->courier->name ?? 'Courier'])
            ]);

            return redirect()->back()->with('success', __('Order handed over to courier! They will deliver it to the customer.'));
        } catch (\Exception $e) {
            Log::error('Failed to confirm handover', [
                'error' => $e->getMessage(),
                'delivery_id' => $deliveryCourier->id
            ]);
            return redirect()->back()->with('error', __('Failed to update status: ') . $e->getMessage());
        }
    }

    /**
     * عرض إحصائيات الشحن للتاجر
     */
    public function shippingStats()
    {
        $merchantId = $this->user->id;

        $tryotoService = new TryotoService();
        $stats = $tryotoService->getMerchantStatistics($merchantId);

        return view('merchant.delivery.stats', compact('stats'));
    }

    /**
     * الحصول على حالة الشحنة للطلب
     */
    public function getOrderShipmentStatus($purchaseId)
    {
        $merchantId = $this->user->id;

        $latestStatus = ShipmentStatusLog::where('purchase_id', $purchaseId)
            ->where('merchant_id', $merchantId)
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
