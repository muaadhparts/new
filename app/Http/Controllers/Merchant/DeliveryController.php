<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{
    Models\Purchase
};
use App\Helpers\PriceHelper;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryCourier;
use App\Models\Package;
use App\Models\Courier;
use App\Models\CourierServiceArea;
use App\Models\Shipping;
use App\Models\ShipmentTracking;
use App\Models\Muaadhsetting;
use App\Services\TryotoService;
use App\Services\ShipmentTrackingService;
use Datatables;
use Illuminate\Support\Facades\Log;

class DeliveryController extends MerchantBaseController
{
    public function index()
    {
        $user = $this->user;
        $merchantId = $user->id;

        // ✅ FIX: Use explicit query with eager loading
        // Get purchases that have merchant_purchases for this merchant
        $datas = Purchase::orderby('id', 'desc')
            ->whereHas('merchantPurchases', function ($query) use ($merchantId) {
                $query->where('user_id', $merchantId);
            })
            ->with([
                // ✅ Eager load merchant purchases for this merchant
                'merchantPurchases' => function ($query) use ($merchantId) {
                    $query->where('user_id', $merchantId);
                },
                // ✅ Eager load delivery couriers for this merchant
                'deliveryCouriers' => function ($query) use ($merchantId) {
                    $query->where('merchant_id', $merchantId)->with('courier');
                },
                // ✅ Eager load shipment trackings for this merchant
                'shipmentTrackings' => function ($query) use ($merchantId) {
                    $query->where('merchant_id', $merchantId)
                          ->orderBy('occurred_at', 'desc');
                },
            ])
            ->get();

        // ✅ تحضير البيانات لكل طلب في الـ Controller بدلاً من الـ View
        // هذا يتبع مبدأ "لا استعلامات في العروض"
        $purchaseData = [];
        foreach ($datas as $purchase) {
            // Get delivery courier for this merchant (already eager loaded)
            $delivery = $purchase->deliveryCouriers->first();

            // Get latest shipment tracking for this merchant (already eager loaded)
            $shipment = $purchase->shipmentTrackings->first();

            // Get customer's shipping choice from model accessor (no query, uses stored JSON)
            $customerChoice = $purchase->getCustomerShippingChoice($merchantId);

            // Calculate price from eager-loaded merchantPurchases
            $price = $purchase->merchantPurchases->sum('price');

            $purchaseData[$purchase->id] = [
                'delivery' => $delivery,
                'shipment' => $shipment,
                'customerChoice' => $customerChoice,
                'price' => $price,
            ];
        }

        // ✅ Log for debugging if no purchases found
        if ($datas->isEmpty()) {
            Log::info('Merchant Delivery: No purchases found for merchant', [
                'merchant_id' => $merchantId,
                'merchant_name' => $user->shop_name ?? $user->name,
                'tip' => 'Check if merchant_purchases table has records with this user_id'
            ]);
        } else {
            Log::debug('Merchant Delivery: Found purchases', [
                'merchant_id' => $merchantId,
                'purchase_count' => $datas->count()
            ]);
        }

        // ✅ Check Tryoto configuration status
        $tryotoStatus = $this->checkTryotoStatus();

        return view('merchant.delivery.index', compact('datas', 'tryotoStatus', 'purchaseData'));
    }

    /**
     * Check Tryoto configuration status for display
     */
    private function checkTryotoStatus(): array
    {
        $tryotoService = (new TryotoService())->forMerchant($this->user->id);
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

            // ✅ مدينة التاجر من merchant_locations فقط
            $originCity = $this->resolveMerchantCity($merchant);

            if (!$originCity) {
                Log::warning('Merchant Delivery: Merchant city not configured in merchant_locations', [
                    'merchant_id' => $merchant->id,
                    'tip' => 'Add merchant location in merchant_locations table'
                ]);
                return response()->json([
                    'success' => false,
                    'error' => __('Please configure your warehouse location in merchant settings'),
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

            // ✅ استخدام الوزن/الأبعاد من الطلب أو من المدخلات (للبحث مجدداً)
            $useCustomDimensions = $request->has('weight') && $request->input('weight') > 0;

            if ($useCustomDimensions) {
                // المستخدم أدخل قيم مخصصة للبحث مجدداً
                $dimensions = [
                    'weight' => (float) $request->input('weight', 1),
                    'length' => (float) $request->input('length', 30),
                    'width' => (float) $request->input('width', 30),
                    'height' => (float) $request->input('height', 30),
                ];
                $weight = $dimensions['weight'];
            } else {
                // حساب الوزن والأبعاد من السلة المحفوظة
                $dimensions = $this->calculatePurchaseDimensions($purchase);
                $weight = $dimensions['weight'];
            }

            // حساب مبلغ COD إذا كان الدفع عند الاستلام
            $codAmount = in_array($purchase->method, ['cod', 'Cash On Delivery']) ? (float)$purchase->pay_amount : 0;

            // ✅ جلب اختيار العميل للشحن
            $customerChoice = $purchase->customer_shipping_choice[$merchant->id] ?? null;

            Log::debug('Merchant Delivery: Getting shipping options', [
                'purchase_id' => $purchase->id,
                'merchant_id' => $merchant->id,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions,
                'custom_search' => $useCustomDimensions,
                'customer_choice' => $customerChoice
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
            $options = $result['success'] ? ($result['options'] ?? []) : [];
            $usedNearestCity = false;
            $originalCustomerCity = $destinationCity; // ✅ حفظ المدينة الأصلية

            // ✅ إذا فشلت المدينة الأصلية، جرب أقرب مدينة مدعومة باستخدام الإحداثيات
            if (empty($options) && $purchase->customer_latitude && $purchase->customer_longitude) {
                $nearestCity = $this->findNearestSupportedCity($purchase);

                if ($nearestCity && $nearestCity !== $destinationCity) {
                    Log::debug('Merchant Delivery: Trying nearest city', [
                        'purchase_id' => $purchase->id,
                        'original_city' => $destinationCity,
                        'nearest_city' => $nearestCity
                    ]);

                    $retryResult = $tryotoService->getDeliveryOptions($originCity, $nearestCity, $weight, $codAmount, $dimensions);

                    if ($retryResult['success'] && !empty($retryResult['options'])) {
                        $result = $retryResult;
                        $options = $result['options'];
                        $usedNearestCity = true;
                        $destinationCity = $nearestCity; // ✅ تحديث المدينة المستخدمة
                    }
                }
            }

            if (!$result['success'] && empty($options)) {
                // ✅ معالجة أخطاء محددة
                $errorCode = $result['error_code'] ?? 'UNKNOWN';
                $userFriendlyError = $this->getShippingErrorMessage($errorCode, $result['error'] ?? '');

                Log::warning('Merchant Delivery: Tryoto API failed', [
                    'purchase_id' => $purchase->id,
                    'origin' => $originCity,
                    'destination' => $destinationCity,
                    'error' => $result['error'] ?? 'No options',
                    'error_code' => $errorCode
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $userFriendlyError,
                    'error_code' => $errorCode,
                    'technical_error' => $result['error'] ?? null
                ]);
            }

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
                'destination' => $destinationCity,
                // ✅ المدينة الأصلية (قبل استخدام أقرب مدينة)
                'original_city' => $originalCustomerCity,
                'used_nearest_city' => $usedNearestCity,
                // ✅ بيانات الشحنة للتعديل والبحث مجدداً
                'dimensions' => [
                    'weight' => $dimensions['weight'],
                    'length' => $dimensions['length'],
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                ],
                // ✅ اختيار العميل للاختيار التلقائي
                'customer_choice' => $customerChoice,
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
     * ✅ مدينة التاجر من merchant_locations فقط (المصدر الوحيد)
     */
    private function resolveMerchantCity($merchant): ?string
    {
        // ✅ merchant_locations هو المصدر الوحيد لعنوان التاجر
        $merchantLocation = \DB::table('merchant_locations')
            ->where('user_id', $merchant->id)
            ->where('status', 1)
            ->first();

        if ($merchantLocation && $merchantLocation->city_id) {
            $city = City::find($merchantLocation->city_id);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        Log::warning('Merchant has no location configured in merchant_locations', [
            'merchant_id' => $merchant->id,
            'merchant_name' => $merchant->name,
            'tip' => 'Add merchant location in merchant_locations table'
        ]);

        return null;
    }

    /**
     * ✅ مدينة العميل من الطلب المحفوظ
     *
     * الأولوية:
     * 1. shipping_city من customer_shipping_choice (المدينة المدعومة التي تم تحديدها أثناء الـ checkout)
     * 2. customer_city (المدينة الأصلية من الخريطة)
     *
     * هذا يضمن استخدام نفس المدينة التي عُرضت بها خيارات الشحن للعميل
     */
    private function resolveCustomerCity(Purchase $purchase): ?string
    {
        $merchantId = $this->user->id;

        // ✅ أولاً: التحقق من shipping_city في customer_shipping_choice
        // هذه المدينة تم تحديدها أثناء الـ checkout وهي المدينة المدعومة فعلياً
        $customerShippingChoice = $purchase->customer_shipping_choice;
        if (is_array($customerShippingChoice) && isset($customerShippingChoice[$merchantId])) {
            $merchantChoice = $customerShippingChoice[$merchantId];
            if (!empty($merchantChoice['shipping_city'])) {
                Log::debug('resolveCustomerCity: Using shipping_city from customer_shipping_choice', [
                    'purchase_id' => $purchase->id,
                    'shipping_city' => $merchantChoice['shipping_city'],
                    'original_customer_city' => $purchase->customer_city
                ]);
                return $merchantChoice['shipping_city'];
            }
        }

        // ✅ Fallback: استخدام customer_city من الطلب
        $cityValue = $purchase->customer_city;

        if (!$cityValue) {
            Log::warning('Purchase has no customer_city', [
                'purchase_id' => $purchase->id
            ]);
            return null;
        }

        // إذا كان رقماً (ID)، نحوله لاسم المدينة
        if (is_numeric($cityValue)) {
            $city = City::find($cityValue);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        // إرجاع المدينة كما هي محفوظة
        return $cityValue;
    }

    /**
     * ✅ إيجاد أقرب مدينة مدعومة مختلفة عن المدينة الأصلية
     *
     * المشكلة: أحياناً المدينة موجودة في DB كـ tryoto_supported لكن Tryoto API لا يخدمها فعلياً
     * الحل: ابحث عن أقرب مدينة مختلفة باستخدام Haversine formula
     */
    private function findNearestSupportedCity(Purchase $purchase): ?string
    {
        $lat = $purchase->customer_latitude;
        $lng = $purchase->customer_longitude;
        $originalCity = $purchase->customer_city;

        if (!$lat || !$lng) {
            Log::debug('findNearestSupportedCity: No coordinates in purchase', [
                'purchase_id' => $purchase->id
            ]);
            return null;
        }

        try {
            // إيجاد الدولة
            $country = Country::where('country_name', 'like', '%' . ($purchase->customer_country ?? 'Saudi') . '%')
                ->orWhere('country_name_ar', 'like', '%' . ($purchase->customer_country ?? 'سعودي') . '%')
                ->first();

            if (!$country) {
                Log::debug('findNearestSupportedCity: Country not found');
                return null;
            }

            // صيغة Haversine لحساب المسافة بالكيلومتر
            $haversine = "(6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            ))";

            // البحث عن أقرب مدينة مختلفة عن المدينة الأصلية (ضمن 100 كم)
            $nearestCity = City::where('country_id', $country->id)
                ->where('tryoto_supported', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('city_name', '!=', $originalCity) // ✅ استبعاد المدينة الأصلية
                ->selectRaw("city_name, {$haversine} as distance_km", [(float)$lat, (float)$lng, (float)$lat])
                ->havingRaw('distance_km <= ?', [100]) // حد أقصى 100 كم
                ->orderBy('distance_km', 'asc')
                ->first();

            if ($nearestCity) {
                Log::debug('findNearestSupportedCity: Found different city', [
                    'purchase_id' => $purchase->id,
                    'original_city' => $originalCity,
                    'nearest_city' => $nearestCity->city_name,
                    'distance_km' => round($nearestCity->distance_km, 2)
                ]);
                return $nearestCity->city_name;
            }

            Log::debug('findNearestSupportedCity: No different city found within 100km');

        } catch (\Exception $e) {
            Log::warning('findNearestSupportedCity: Failed', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
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
            'merchant_location_id' => 'nullable|exists:merchant_locations,id',
        ]);

        $purchase = Purchase::find($request->purchase_id);
        $merchantId = $this->user->id;

        // التحقق من أن هذا الطلب يخص هذا التاجر
        $merchantOrder = $purchase->merchantPurchases()->where('user_id', $merchantId)->first();
        if (!$merchantOrder) {
            return redirect()->back()->with('error', __('This purchase does not belong to you'));
        }

        // التحقق من عدم وجود شحنة سابقة لهذا الطلب من هذا التاجر
        $existingShipment = ShipmentTracking::getLatestForPurchase($purchase->id, $merchantId);

        if ($existingShipment && !$existingShipment->is_final) {
            return redirect()->back()->with('error', __('A shipment already exists for this purchase. Tracking: ') . $existingShipment->tracking_number);
        }

        // ✅ Validate merchant_location_id - NO silent fallback if multiple locations
        $merchantLocationId = $request->merchant_location_id;
        $activeLocations = \DB::table('merchant_locations')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->get();

        if ($activeLocations->isEmpty()) {
            return redirect()->back()->with('error', __('No warehouse location configured. Please add a location in settings.'));
        }

        if (!$merchantLocationId) {
            if ($activeLocations->count() === 1) {
                // Safe: only one location, use it
                $merchantLocationId = $activeLocations->first()->id;
            } else {
                // Unsafe: multiple locations, cannot guess
                return redirect()->back()->with('error', __('Please select a pickup location. You have multiple warehouses configured.'));
            }
        }

        // ✅ Use merchant-specific credentials
        $tryotoService = (new TryotoService())->forMerchant($merchantId);
        $result = $tryotoService->createShipment(
            $purchase,
            $merchantId,
            $request->delivery_option_id,
            $request->company,
            $request->price,
            $request->service_type ?? 'express',
            null, // merchantShippingData
            $merchantLocationId // ✅ Pass merchant_location_id
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

        $tryotoService = (new TryotoService())->forMerchant($this->user->id);
        $result = $tryotoService->trackShipment($trackingNumber);

        return response()->json($result);
    }

    /**
     * عرض سجل الشحنات للتاجر
     */
    public function shipmentHistory($purchaseId)
    {
        $merchantId = $this->user->id;

        $trackingService = app(ShipmentTrackingService::class);
        $history = $trackingService->getTrackingHistory($purchaseId, $merchantId);

        return response()->json([
            'success' => true,
            'logs' => $history
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
        $shipment = ShipmentTracking::getLatestByTracking($request->tracking_number);

        if (!$shipment || $shipment->merchant_id != $merchantId) {
            return redirect()->back()->with('error', __('Shipment not found or does not belong to you'));
        }

        // التحقق من أن الشحنة قابلة للإلغاء
        $nonCancellableStatuses = [
            ShipmentTracking::STATUS_DELIVERED,
            ShipmentTracking::STATUS_OUT_FOR_DELIVERY,
            ShipmentTracking::STATUS_CANCELLED
        ];
        if (in_array($shipment->status, $nonCancellableStatuses)) {
            return redirect()->back()->with('error', __('This shipment cannot be cancelled'));
        }

        // API shipments: call Tryoto API to cancel
        if ($shipment->integration_type === ShipmentTracking::INTEGRATION_API) {
            $tryotoService = (new TryotoService())->forMerchant($merchantId);
            $result = $tryotoService->cancelShipment($request->tracking_number, $request->reason ?? '');

            if ($result['success']) {
                return redirect()->back()->with('success', __('Shipment cancelled successfully'));
            }

            return redirect()->back()->with('error', __('Failed to cancel shipment: ') . ($result['error'] ?? __('Unknown error')));
        }

        // Manual shipments: use tracking service to cancel
        $trackingService = app(ShipmentTrackingService::class);
        $result = $trackingService->cancelShipment(
            $shipment->purchase_id,
            $merchantId,
            $request->reason ?? 'Cancelled by merchant'
        );

        if ($result) {
            return redirect()->back()->with('success', __('Shipment cancelled successfully'));
        }

        return redirect()->back()->with('error', __('Failed to cancel shipment'));
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
                'name' => __('Ready for Courier Pickup'),
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
                'name' => __('Picked Up by Courier'),
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

        $tryotoService = (new TryotoService())->forMerchant($merchantId);
        $stats = $tryotoService->getMerchantStatistics($merchantId);

        return view('merchant.delivery.stats', compact('stats'));
    }

    /**
     * الحصول على حالة الشحنة للطلب
     */
    public function getOrderShipmentStatus($purchaseId)
    {
        $merchantId = $this->user->id;

        $latestStatus = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

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
            'occurred_at' => $latestStatus->occurred_at?->format('Y-m-d H:i'),
            'message' => $latestStatus->message_ar ?? $latestStatus->message
        ]);
    }

    /**
     * ✅ Get Available Shipping Providers (Dynamic Tabs)
     * Returns distinct providers from shippings table for this merchant
     */
    public function getShippingProviders(Request $request)
    {
        try {
            $merchantId = $this->user->id;

            // Get distinct providers for this merchant (merchant's + platform/operator default)
            // Note: Don't use forMerchant scope here as it adds ORDER BY which conflicts with DISTINCT
            // user_id=0 → Operator/Platform (متاح للجميع)
            // user_id=$merchantId → شحنات التاجر الخاصة
            $providers = Shipping::whereIn('user_id', [0, $merchantId])
                ->whereNotNull('provider')
                ->where('provider', '!=', '')
                ->select('provider')
                ->distinct()
                ->pluck('provider')
                ->toArray();

            // Provider display names (can be extended)
            $providerLabels = [
                'tryoto' => __('Smart Shipping (Tryoto)'),
                'manual' => __('Manual Shipping'),
                'Saudi' => __('Saudi Post'),
                'debts' => __('Debts Shipping'),
            ];

            // Provider icons
            $providerIcons = [
                'tryoto' => 'fas fa-shipping-fast',
                'manual' => 'fas fa-truck',
                'Saudi' => 'fas fa-mail-bulk',
                'debts' => 'fas fa-file-invoice-dollar',
            ];

            $result = [];
            foreach ($providers as $provider) {
                $result[] = [
                    'key' => $provider,
                    'label' => $providerLabels[$provider] ?? ucfirst($provider),
                    'icon' => $providerIcons[$provider] ?? 'fas fa-box',
                    'has_api' => ($provider === 'tryoto'), // Only tryoto has API currently
                ];
            }

            return response()->json([
                'success' => true,
                'providers' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get Shipping Providers Error', [
                'merchant_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => __('Failed to load shipping providers'),
                'providers' => []
            ]);
        }
    }

    /**
     * ✅ Get Shipping Options for a specific Provider
     * Returns shipping methods for the specified provider
     */
    public function getProviderShippingOptions(Request $request)
    {
        try {
            $merchantId = $this->user->id;
            $provider = $request->input('provider');

            if (empty($provider)) {
                return response()->json([
                    'success' => false,
                    'error' => __('Provider is required'),
                    'options' => []
                ]);
            }

            // For tryoto, return empty (options come from API via getShippingOptions)
            if ($provider === 'tryoto') {
                return response()->json([
                    'success' => true,
                    'has_api' => true,
                    'message' => __('Use Tryoto API to get options'),
                    'options' => []
                ]);
            }

            // Get shipping methods for this provider
            $shippings = Shipping::forMerchant($merchantId)
                ->where('provider', $provider)
                ->orderBy('price', 'asc')
                ->get();

            if ($shippings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => __('No shipping methods found for this provider'),
                    'options' => []
                ]);
            }

            $options = $shippings->map(function ($shipping) {
                return [
                    'id' => $shipping->id,
                    'name' => $shipping->name,
                    'subname' => $shipping->subname,
                    'price' => (float) $shipping->price,
                    'display_price' => PriceHelper::showAdminCurrencyPrice($shipping->price),
                    'free_above' => $shipping->free_above,
                    'provider' => $shipping->provider,
                ];
            });

            return response()->json([
                'success' => true,
                'has_api' => false,
                'options' => $options
            ]);

        } catch (\Exception $e) {
            Log::error('Provider Shipping Options Error', [
                'merchant_id' => $this->user->id,
                'provider' => $request->input('provider'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => __('Failed to load shipping methods'),
                'options' => []
            ]);
        }
    }

    /**
     * ✅ Assign Provider Shipping to Purchase (Dynamic - works for any provider except tryoto)
     * Creates a shipment record with the specified provider
     */
    public function sendProviderShipping(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'shipping_id' => 'required|exists:shippings,id',
            'tracking_number' => 'nullable|string|max:100',
            'merchant_location_id' => 'nullable|exists:merchant_locations,id',
        ]);

        $purchase = Purchase::find($request->purchase_id);
        $merchantId = $this->user->id;

        // Verify this purchase belongs to this merchant
        $merchantOrder = $purchase->merchantPurchases()->where('user_id', $merchantId)->first();
        if (!$merchantOrder) {
            return redirect()->back()->with('error', __('This purchase does not belong to you'));
        }

        // Check for existing shipment
        $existingShipment = ShipmentTracking::getLatestForPurchase($purchase->id, $merchantId);

        if ($existingShipment && !$existingShipment->is_final) {
            return redirect()->back()->with('error', __('A shipment already exists for this purchase. Tracking: ') . $existingShipment->tracking_number);
        }

        // Get the selected shipping method
        $shipping = Shipping::find($request->shipping_id);
        if (!$shipping) {
            return redirect()->back()->with('error', __('Invalid shipping method'));
        }

        // ✅ Validate merchant_location_id - NO silent fallback if multiple locations
        $merchantLocationId = $request->merchant_location_id;
        $activeLocations = \DB::table('merchant_locations')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->get();

        if ($activeLocations->isEmpty()) {
            return redirect()->back()->with('error', __('No warehouse location configured. Please add a location in settings.'));
        }

        if (!$merchantLocationId) {
            if ($activeLocations->count() === 1) {
                // Safe: only one location, use it
                $merchantLocationId = $activeLocations->first()->id;
            } else {
                // Unsafe: multiple locations, cannot guess
                return redirect()->back()->with('error', __('Please select a pickup location. You have multiple warehouses configured.'));
            }
        }

        // Use tracking service to create manual shipment
        $trackingService = app(ShipmentTrackingService::class);
        $trackingService->createManualShipment(
            purchaseId: $purchase->id,
            merchantId: $merchantId,
            shippingId: $shipping->id,
            provider: $shipping->provider ?? 'manual',
            trackingNumber: $request->tracking_number,
            companyName: $shipping->name,
            shippingCost: $shipping->price,
            merchantLocationId: $merchantLocationId // ✅ Pass merchant_location_id
        );

        // Update merchant purchase status
        $merchantOrder->status = 'processing';
        $merchantOrder->save();

        // Get the created tracking to get the tracking number
        $tracking = ShipmentTracking::getLatestForPurchase($purchase->id, $merchantId);
        $trackingNumber = $tracking->tracking_number ?? 'N/A';

        // Add tracking entry to purchase
        $purchase->tracks()->create([
            'name' => __('Shipping Assigned'),
            'text' => __('Shipment assigned to :company (:provider). Tracking: :tracking', [
                'company' => $shipping->name,
                'provider' => $shipping->provider ?? 'manual',
                'tracking' => $trackingNumber
            ])
        ]);

        Log::info('Provider shipping assigned', [
            'purchase_id' => $purchase->id,
            'merchant_id' => $merchantId,
            'shipping_id' => $shipping->id,
            'shipping_name' => $shipping->name,
            'provider' => $shipping->provider,
            'tracking_number' => $trackingNumber,
        ]);

        return redirect()->back()->with('success', __('Shipping assigned successfully. Tracking Number: ') . $trackingNumber);
    }

    /**
     * ✅ Get Merchant Locations (Warehouses/Pickup Points)
     * Returns all active locations for the current merchant
     */
    public function getMerchantLocations(Request $request)
    {
        try {
            $merchantId = $this->user->id;

            $locations = \DB::table('merchant_locations')
                ->where('user_id', $merchantId)
                ->where('status', 1)
                ->select('id', 'warehouse_name', 'tryoto_warehouse_code', 'location', 'city_id')
                ->get();

            if ($locations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => __('No warehouse locations configured. Please add a location in settings.'),
                    'locations' => [],
                    'show_settings_link' => true
                ]);
            }

            // Get city names for each location
            $cityIds = $locations->pluck('city_id')->filter()->unique()->toArray();
            $cities = City::whereIn('id', $cityIds)->pluck('city_name', 'id')->toArray();

            $result = $locations->map(function ($loc) use ($cities) {
                $cityName = $cities[$loc->city_id] ?? '';
                $displayName = $loc->warehouse_name ?: __('Warehouse');

                // Add Tryoto code if available (helps user verify)
                $tryotoCode = $loc->tryoto_warehouse_code;
                if ($tryotoCode) {
                    $displayName .= ' [' . $tryotoCode . ']';
                }

                // Add city name if available
                if ($cityName) {
                    $displayName .= ' - ' . $cityName;
                }

                // Warning if no Tryoto code configured
                $hasTryotoCode = !empty($tryotoCode);

                return [
                    'id' => $loc->id,
                    'warehouse_name' => $loc->warehouse_name,
                    'tryoto_code' => $tryotoCode,
                    'has_tryoto_code' => $hasTryotoCode,
                    'location' => $loc->location,
                    'city_name' => $cityName,
                    'display_name' => $displayName,
                ];
            });

            return response()->json([
                'success' => true,
                'locations' => $result,
                'default_id' => $locations->first()->id ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Get Merchant Locations Error', [
                'merchant_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => __('Failed to load warehouse locations'),
                'locations' => []
            ]);
        }
    }
}
