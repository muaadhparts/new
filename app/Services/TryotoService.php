<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShipmentStatusLog;
use App\Models\User;
use App\Models\City;
use App\Models\UserNotification;
use App\Helpers\PriceHelper;
use App\Services\ShippingCalculatorService;
use App\Services\VendorCartService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * TryotoService - Enterprise-Level Shipping Integration
 *
 * خدمة مركزية موحدة للتعامل مع Tryoto API
 * جميع الاتصالات مع Tryoto تمر عبر هذه الخدمة فقط
 *
 * الميزات:
 * - إدارة التوكن موحدة (تجديد تلقائي + retry)
 * - إنشاء الشحنات
 * - تتبع الشحنات
 * - إلغاء الشحنات
 * - جلب أسعار الشحن
 * - جلب المدن والمواقع
 */
class TryotoService
{
    /**
     * مفتاح الـ Cache الموحد للتوكن
     * يجب استخدام هذا المفتاح فقط في جميع أنحاء المشروع
     */
    private const CACHE_KEY_PREFIX = 'tryoto-token-';

    /**
     * عدد محاولات إعادة المحاولة عند فشل الاتصال
     */
    private const MAX_RETRIES = 3;

    /**
     * الانتظار بين المحاولات (بالثواني)
     */
    private const RETRY_DELAY = 2;

    /**
     * timeout للطلبات (بالثواني)
     */
    private const REQUEST_TIMEOUT = 30;

    private string $baseUrl;
    private bool $isSandbox;
    private ?string $token = null;

    public function __construct()
    {
        $this->isSandbox = (bool) config('services.tryoto.sandbox', false);
        $this->baseUrl = $this->isSandbox
            ? config('services.tryoto.test.url', 'https://staging-api.tryoto.com')
            : config('services.tryoto.live.url', 'https://api.tryoto.com');
    }

    /**
     * الحصول على مفتاح الـ Cache الموحد
     * هذا هو المفتاح الوحيد المستخدم في المشروع
     */
    public function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . ($this->isSandbox ? 'sandbox' : 'live');
    }

    /**
     * الحصول على التوكن (من الكاش أو تجديد)
     *
     * @param bool $forceRefresh إجبار تجديد التوكن
     * @return string|null
     */
    public function getToken(bool $forceRefresh = false): ?string
    {
        $cacheKey = $this->getCacheKey();

        // إذا لم يكن هناك إجبار للتجديد، نحاول من الكاش أولاً
        if (!$forceRefresh) {
            $cachedToken = Cache::get($cacheKey);
            if ($cachedToken) {
                $this->token = $cachedToken;
                return $cachedToken;
            }
        }

        // تجديد التوكن مع إعادة المحاولة
        return $this->refreshTokenWithRetry();
    }

    /**
     * تجديد التوكن مع آلية إعادة المحاولة
     *
     * @return string|null
     */
    private function refreshTokenWithRetry(): ?string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result = $this->doRefreshToken();
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("Tryoto: Token refresh attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_retries' => self::MAX_RETRIES
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY * $attempt); // exponential backoff
                }
            }
        }

        Log::error('Tryoto: All token refresh attempts failed', [
            'attempts' => self::MAX_RETRIES,
            'last_error' => $lastException?->getMessage()
        ]);

        return null;
    }

    /**
     * تنفيذ تجديد التوكن الفعلي
     *
     * @return string|null
     * @throws \Exception
     */
    private function doRefreshToken(): ?string
    {
        $refreshToken = $this->isSandbox
            ? config('services.tryoto.test.token')
            : config('services.tryoto.live.token');

        if (empty($refreshToken)) {
            throw new \Exception('Tryoto refresh token is not configured. Check .env file.');
        }

        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->post($this->baseUrl . '/rest/v2/refreshToken', [
                'refresh_token' => $refreshToken
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'] ?? null;
            $expiresIn = (int)($data['expires_in'] ?? 3600);

            if ($token) {
                $cacheKey = $this->getCacheKey();
                // نحفظ التوكن لمدة أقل من انتهاء صلاحيته بـ 60 ثانية
                $cacheTtl = max(300, $expiresIn - 60);
                Cache::put($cacheKey, $token, now()->addSeconds($cacheTtl));

                $this->token = $token;

                Log::info('Tryoto: Token refreshed successfully', [
                    'expires_in' => $expiresIn,
                    'cache_ttl' => $cacheTtl,
                    'sandbox' => $this->isSandbox
                ]);

                return $token;
            }
        }

        $errorBody = $response->body();
        Log::error('Tryoto: Token refresh failed', [
            'status' => $response->status(),
            'body' => $errorBody,
            'sandbox' => $this->isSandbox
        ]);

        throw new \Exception("Token refresh failed: HTTP {$response->status()} - {$errorBody}");
    }

    /**
     * تنفيذ طلب API مع التوكن وإعادة المحاولة التلقائية
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param bool $retryOnAuthError إعادة المحاولة عند خطأ 401
     * @return array
     */
    public function makeApiRequest(string $method, string $endpoint, array $data = [], bool $retryOnAuthError = true): array
    {
        $token = $this->getToken();

        if (!$token) {
            return [
                'success' => false,
                'error' => 'Unable to get access token',
                'error_code' => 'TOKEN_ERROR'
            ];
        }

        try {
            $url = $this->baseUrl . $endpoint;

            $request = Http::timeout(self::REQUEST_TIMEOUT)
                ->withToken($token)
                ->withHeaders(['Accept' => 'application/json']);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => $request->post($url, $data),
            };

            // إذا كان الخطأ 401 (غير مصرح)، نجدد التوكن ونعيد المحاولة
            if ($response->status() === 401 && $retryOnAuthError) {
                Log::warning('Tryoto: Got 401, refreshing token and retrying...');

                // إجبار تجديد التوكن
                $newToken = $this->getToken(forceRefresh: true);

                if ($newToken) {
                    // إعادة المحاولة مرة واحدة فقط
                    return $this->makeApiRequest($method, $endpoint, $data, retryOnAuthError: false);
                }

                return [
                    'success' => false,
                    'error' => 'Authentication failed after token refresh',
                    'error_code' => 'AUTH_ERROR'
                ];
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status' => $response->status()
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['errorMsg'] ?? $errorData['otoErrorMessage'] ?? $response->body(),
                'error_code' => $errorData['errorCode'] ?? 'API_ERROR',
                'status' => $response->status(),
                'raw' => $errorData
            ];

        } catch (\Exception $e) {
            Log::error('Tryoto: API request exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Get delivery options for a route using checkOTODeliveryFee endpoint
     *
     * المبدأ الأساسي:
     * - الوزن مطلوب (من products.weight × الكمية)
     * - المقاسات اختيارية - إذا غير متوفرة، نستخدم قيم محسوبة من الوزن
     *
     * @param string $originCity المدينة المصدر
     * @param string $destinationCity المدينة الوجهة
     * @param float $weight الوزن بالكيلو (مطلوب)
     * @param float $codAmount مبلغ الدفع عند الاستلام (غير مستخدم في هذا الـ endpoint)
     * @param array $dimensions الأبعاد [length, height, width] - اختيارية
     * @return array
     */
    public function getDeliveryOptions(string $originCity, string $destinationCity, float $weight = 0, float $codAmount = 0, array $dimensions = []): array
    {
        // التحقق من البيانات الأساسية
        $errors = [];

        if (empty($originCity)) {
            $errors[] = 'origin_city_missing';
        }
        if (empty($destinationCity)) {
            $errors[] = 'destination_city_missing';
        }
        if ($weight <= 0) {
            $errors[] = 'weight_missing_or_invalid';
        }

        // إذا كانت البيانات الأساسية ناقصة، رفض الطلب
        if (!empty($errors)) {
            Log::warning('Tryoto: getDeliveryOptions - missing required data', [
                'errors' => $errors,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
            ]);

            return [
                'success' => false,
                'error' => 'Incomplete shipping data',
                'error_code' => 'INCOMPLETE_DATA',
                'missing_fields' => $errors
            ];
        }

        // ✅ المقاسات - استخدام القيم المتوفرة أو حساب من الوزن
        $length = $dimensions['length'] ?? null;
        $height = $dimensions['height'] ?? null;
        $width = $dimensions['width'] ?? null;

        // إذا المقاسات غير متوفرة، نحسب أبعاد تقريبية من الوزن
        // المعادلة: حجم الصندوق = الوزن × 5000 (عكس معادلة الوزن الحجمي)
        // ثم نوزع الحجم بالتساوي على الأبعاد الثلاثة
        if ($length === null || $height === null || $width === null || $length <= 0 || $height <= 0 || $width <= 0) {
            // حساب حجم تقريبي من الوزن
            // افتراض: كثافة المنتج = 200 كجم/متر مكعب (متوسط للمنتجات العادية)
            $volumeCm3 = ($weight / 0.0002); // الوزن / الكثافة = الحجم
            $sideCm = pow($volumeCm3, 1/3); // الجذر التكعيبي للحصول على طول الضلع
            $sideCm = max(10, min(100, round($sideCm))); // حد أدنى 10سم، أقصى 100سم

            $length = $length ?? $sideCm;
            $height = $height ?? $sideCm;
            $width = $width ?? $sideCm;

            Log::info('Tryoto: Using calculated dimensions from weight', [
                'weight' => $weight,
                'calculated_side' => $sideCm,
                'dimensions' => ['length' => $length, 'height' => $height, 'width' => $width]
            ]);
        }

        $requestData = [
            'originCity' => $originCity,
            'destinationCity' => $destinationCity,
            'weight' => $weight,
            'xlength' => $length,
            'xheight' => $height,
            'xwidth' => $width,
        ];

        Log::info('Tryoto: Requesting delivery options', $requestData);

        $result = $this->makeApiRequest('POST', '/rest/v2/checkOTODeliveryFee', $requestData);

        if (!$result['success']) {
            Log::warning('Tryoto: checkOTODeliveryFee failed', [
                'origin' => $originCity,
                'destination' => $destinationCity,
                'error' => $result['error']
            ]);
            return ['success' => false, 'error' => $result['error']];
        }

        $data = $result['data'];
        $companies = $data['deliveryCompany'] ?? [];

        // Transform to unified format
        $options = [];
        foreach ($companies as $company) {
            $options[] = [
                'deliveryOptionId' => (string)($company['deliveryOptionId'] ?? ''),
                'company' => $company['deliveryOptionName'] ?? $company['deliveryCompanyName'] ?? null,
                'companyCode' => $company['deliveryCompanyName'] ?? '',
                'price' => (float)($company['price'] ?? 0),
                'estimatedDeliveryDays' => $this->parseDeliveryTime($company['avgDeliveryTime'] ?? ''),
                'avgDeliveryTime' => $company['avgDeliveryTime'] ?? '',
                'serviceType' => $company['serviceType'] ?? '',
                'deliveryType' => $company['deliveryType'] ?? '',
                'logo' => $company['logo'] ?? '',
                'codCharge' => (float)($company['codCharge'] ?? 0),
                'returnFee' => (float)($company['returnFee'] ?? 0),
                'maxCODValue' => (float)($company['maxCODValue'] ?? 0),
                'maxOrderValue' => (float)($company['maxOrderValue'] ?? 0),
            ];
        }

        Log::info('Tryoto: Got ' . count($options) . ' delivery options');

        return [
            'success' => true,
            'options' => $options,
            'raw' => $data
        ];
    }

    /**
     * Parse delivery time string to number of days
     */
    private function parseDeliveryTime(string $time): string
    {
        if (empty($time)) return '';

        if (stripos($time, 'same') !== false) return '0';
        if (stripos($time, 'next') !== false) return '1';

        if (preg_match('/(\d+)to(\d+)/i', $time, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        if (preg_match('/(\d+)/', $time, $matches)) {
            return $matches[1];
        }

        return $time;
    }

    /**
     * تحويل city ID أو اسم إلى اسم المدينة الصحيح لـ Tryoto
     *
     * المبدأ الأساسي: بدون fallback
     * إذا كانت المدينة غير موجودة، نرجع null
     *
     * @param mixed $cityValue City ID or name
     * @return string|null City name or null if not found
     */
    public function resolveCityName($cityValue): ?string
    {
        if (empty($cityValue)) {
            Log::warning('Tryoto: resolveCityName - empty city value provided');
            return null;
        }

        // إذا كان رقم، نبحث في قاعدة البيانات
        if (is_numeric($cityValue)) {
            $city = City::find($cityValue);
            if ($city) {
                // جلب الاسم من الأعمدة المتاحة
                return $city->city_name ?? $city->name ?? null;
            }
            Log::warning('Tryoto: resolveCityName - city not found', ['city_id' => $cityValue]);
            return null;
        }

        // إذا كان نص، نستخدمه مباشرة
        if (is_string($cityValue)) {
            return $cityValue;
        }

        return null;
    }

    /**
     * Create a shipment using createOrder API with createShipment=true
     *
     * المبدأ الأساسي: بدون قيم ثابتة
     * - مدينة التاجر من user.city_id فقط
     * - مدينة العميل من الطلب فقط
     * - الأبعاد من ShippingCalculatorService
     *
     * @param Order $order
     * @param int $vendorId
     * @param string $deliveryOptionId
     * @param string $company
     * @param float $price
     * @param string $serviceType
     * @param array|null $vendorShippingData بيانات الشحن المحسوبة مسبقاً (من VendorCartService)
     * @return array
     */
    public function createShipment(Order $order, int $vendorId, string $deliveryOptionId, string $company, float $price, string $serviceType = '', ?array $vendorShippingData = null): array
    {
        // Get vendor info using ShippingCalculatorService
        $vendorCityData = ShippingCalculatorService::getVendorCity($vendorId);
        $vendor = User::find($vendorId);

        if (!$vendor) {
            return [
                'success' => false,
                'error' => 'Vendor not found',
                'error_code' => 'VENDOR_NOT_FOUND'
            ];
        }

        // استخدام مدينة التاجر من ShippingCalculatorService - بدون fallback
        $originCity = $vendorCityData['city_name'] ?? null;

        if (!$originCity) {
            Log::error('Tryoto: createShipment - vendor city missing', ['vendor_id' => $vendorId]);
            return [
                'success' => false,
                'error' => 'Vendor city is not configured',
                'error_code' => 'VENDOR_CITY_MISSING'
            ];
        }

        $originAddress = $vendor->warehouse_address ?? $vendor->shop_address ?? $originCity;

        // مدينة العميل من الطلب - بدون fallback
        $destinationCityValue = $order->shipping_city ?: $order->customer_city;
        $destinationCity = $this->resolveCityName($destinationCityValue);

        if (!$destinationCity) {
            Log::error('Tryoto: createShipment - customer city missing', ['order_id' => $order->id]);
            return [
                'success' => false,
                'error' => 'Customer city is not configured',
                'error_code' => 'CUSTOMER_CITY_MISSING'
            ];
        }

        // Calculate dimensions from vendor shipping data or order cart
        $dims = $vendorShippingData ? [
            'weight' => $vendorShippingData['chargeable_weight'] ?? $vendorShippingData['actual_weight'] ?? null,
            'length' => $vendorShippingData['dimensions']['length'] ?? null,
            'width' => $vendorShippingData['dimensions']['width'] ?? null,
            'height' => $vendorShippingData['dimensions']['height'] ?? null,
        ] : $this->calculateDimensionsFromOrder($order, $vendorId);

        // ✅ التحقق من الوزن فقط - المقاسات اختيارية
        if (!$dims['weight'] || $dims['weight'] <= 0) {
            Log::error('Tryoto: createShipment - weight is required', [
                'order_id' => $order->id,
                'vendor_id' => $vendorId,
                'dims' => $dims
            ]);
            return [
                'success' => false,
                'error' => 'Product weight is required for shipping',
                'error_code' => 'WEIGHT_MISSING',
                'missing_fields' => ['weight']
            ];
        }

        // ✅ إذا المقاسات غير متوفرة، نحسبها من الوزن
        $weight = $dims['weight'];
        $length = $dims['length'];
        $width = $dims['width'];
        $height = $dims['height'];

        if (!$length || $length <= 0 || !$width || $width <= 0 || !$height || $height <= 0) {
            // حساب حجم تقريبي من الوزن
            $volumeCm3 = ($weight / 0.0002);
            $sideCm = pow($volumeCm3, 1/3);
            $sideCm = max(10, min(100, round($sideCm)));

            $length = $length && $length > 0 ? $length : $sideCm;
            $width = $width && $width > 0 ? $width : $sideCm;
            $height = $height && $height > 0 ? $height : $sideCm;

            Log::info('Tryoto: createShipment - calculated dimensions from weight', [
                'order_id' => $order->id,
                'weight' => $weight,
                'calculated_side' => $sideCm
            ]);

            // تحديث dims للاستخدام لاحقاً
            $dims['length'] = $length;
            $dims['width'] = $width;
            $dims['height'] = $height;
        }

        // Determine COD amount
        $isCOD = in_array($order->method, ['cod', 'Cash On Delivery']);
        $codAmount = $isCOD ? (float)$order->pay_amount : 0.0;

        // Prepare receiver info - allow null for missing data
        $receiverName = $order->shipping_name ?: $order->customer_name;
        $receiverPhone = $this->cleanPhoneNumber($order->shipping_phone ?: $order->customer_phone ?: '');
        $receiverEmail = $order->shipping_email ?: $order->customer_email ?: null;
        $receiverAddress = $order->shipping_address ?: $order->customer_address;
        $receiverZip = $order->shipping_zip ?: $order->customer_zip ?: null;
        $receiverDistrict = $order->shipping_state ?? $order->customer_state ?? '';

        // Validate required receiver info
        if (!$receiverName || !$receiverPhone || !$receiverAddress) {
            Log::error('Tryoto: createShipment - incomplete receiver info', [
                'order_id' => $order->id,
                'has_name' => !empty($receiverName),
                'has_phone' => !empty($receiverPhone),
                'has_address' => !empty($receiverAddress)
            ]);
            return [
                'success' => false,
                'error' => 'Incomplete receiver information',
                'error_code' => 'INCOMPLETE_RECEIVER_INFO'
            ];
        }

        // Prepare sender phone - allow null
        $senderPhone = $this->cleanPhoneNumber($vendor->phone ?? '');

        // Prepare cart items
        $cart = is_string($order->cart) ? json_decode($order->cart, true) : $order->cart;
        $items = $cart['items'] ?? $cart ?? [];
        $orderItems = [];
        $itemCount = 0;

        foreach ($items as $item) {
            $itemData = $item['item'] ?? $item;
            $qty = (int)($item['qty'] ?? 1);
            $itemCount += $qty;
            $orderItems[] = [
                'productId' => (string)($itemData['id'] ?? '0'),
                'name' => $itemData['name'] ?? null,
                'price' => (float)($itemData['price'] ?? 0),
                'rowTotal' => (float)($itemData['price'] ?? 0) * $qty,
                'taxAmount' => 0,
                'quantity' => $qty,
                'serialnumber' => '',
                'sku' => $itemData['sku'] ?? null,
                'image' => $itemData['photo'] ?? '',
            ];
        }

        // Generate unique order ID for Tryoto
        $tryotoOrderId = $order->order_number . '-V' . $vendorId . '-' . time();

        // Build createOrder payload - using real data only
        $payload = [
            'orderId' => $tryotoOrderId,
            'ref1' => 'REF-' . $order->id . '-' . $vendorId,
            'deliveryOptionId' => $deliveryOptionId,
            'serviceType' => $serviceType,
            'createShipment' => true,
            'storeName' => $vendor->shop_name ?? $vendor->name ?? null,
            'payment_method' => $isCOD ? 'cod' : 'paid',
            'amount' => (float)$order->pay_amount,
            'amount_due' => $isCOD ? (float)$order->pay_amount : 0,
            'shippingAmount' => $price,
            'subtotal' => (float)$order->pay_amount,
            'currency' => 'SAR',
            'shippingNotes' => 'Order #' . $order->order_number,
            'packageSize' => 'medium',
            'packageCount' => max(1, $itemCount),
            'packageWeight' => $dims['weight'],
            'boxWidth' => $dims['width'],
            'boxLength' => $dims['length'],
            'boxHeight' => $dims['height'],
            'orderDate' => date('d/m/Y H:i'),
            'deliverySlotDate' => date('d/m/Y', strtotime('+2 days')),
            'deliverySlotTo' => '6:00pm',
            'deliverySlotFrom' => '9:00am',
            'senderName' => $vendor->shop_name ?? $vendor->name,
            'senderPhone' => $senderPhone ?: null,
            'senderCity' => $originCity,
            'senderAddress' => $originAddress,
            'customer' => [
                'name' => $receiverName,
                'email' => $receiverEmail,
                'mobile' => $receiverPhone,
                'address' => $receiverAddress,
                'district' => $receiverDistrict,
                'city' => $destinationCity,
                'country' => 'SA',
                'postcode' => $receiverZip,
                'lat' => '',
                'lon' => '',
                'refID' => '',
                'W3WAddress' => ''
            ],
            'items' => $orderItems
        ];

        Log::info('Tryoto: Creating order with shipment', [
            'order_id' => $order->id,
            'tryoto_order_id' => $tryotoOrderId,
            'origin' => $originCity,
            'destination' => $destinationCity,
            'company' => $company,
            'dimensions' => $dims,
        ]);

        $result = $this->makeApiRequest('POST', '/rest/v2/createOrder', $payload);

        if ($result['success']) {
            $data = $result['data'];
            $otoId = $data['otoId'] ?? null;
            $trackingNumber = $data['trackingNumber'] ?? null;

            // If no tracking number yet, use otoId as reference
            $trackingRef = $trackingNumber ?? ('OTO-' . $otoId);

            // Save to shipment_status_logs
            $this->createInitialLog($order, $vendorId, $trackingRef, (string)$otoId, $company, $originCity, $data);

            // Send notification to vendor
            $this->notifyVendor($vendorId, $order, 'shipment_created', $trackingRef);

            Log::info('Tryoto: Order created successfully', [
                'order_id' => $order->id,
                'oto_id' => $otoId,
                'tracking_number' => $trackingNumber,
            ]);

            return [
                'success' => true,
                'shipment_id' => (string)$otoId,
                'tracking_number' => $trackingRef,
                'oto_id' => $otoId,
                'company' => $company,
                'price' => $price,
                'raw' => $data
            ];
        }

        Log::error('Tryoto: createOrder failed', [
            'order_id' => $order->id,
            'error' => $result['error'],
            'error_code' => $result['error_code'] ?? null
        ]);

        return [
            'success' => false,
            'error' => $result['error'],
            'error_code' => $result['error_code'] ?? null,
            'details' => $result['raw'] ?? null
        ];
    }

    /**
     * Clean and format phone number for Saudi Arabia
     */
    private function cleanPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = ltrim($phone, '0');

        if (strpos($phone, '966') === 0) {
            $phone = substr($phone, 3);
        }

        if (strlen($phone) < 9) {
            $phone = '5' . str_pad($phone, 8, '0', STR_PAD_LEFT);
        }

        return substr($phone, 0, 9);
    }

    /**
     * Track a shipment
     *
     * @param string $trackingNumber
     * @param string|null $companyName
     * @return array
     */
    public function trackShipment(string $trackingNumber, ?string $companyName = null): array
    {
        if (!$companyName) {
            $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)->first();
            $companyName = $shipment?->company_name;
        }

        $payload = ['trackingNumber' => $trackingNumber];
        if ($companyName) {
            $payload['deliveryCompanyName'] = $companyName;
        }

        $result = $this->makeApiRequest('POST', '/rest/v2/trackShipment', $payload);

        if (!$result['success']) {
            Log::warning('Tryoto: trackShipment failed', [
                'tracking' => $trackingNumber,
                'error' => $result['error']
            ]);
            return ['success' => false, 'error' => $result['error']];
        }

        $data = $result['data'];

        // Update local log if there's new status
        $this->syncTrackingStatus($trackingNumber, $data);

        return [
            'success' => true,
            'status' => $data['status'] ?? 'unknown',
            'status_ar' => $this->getStatusArabic($data['status'] ?? 'unknown'),
            'location' => $data['location'] ?? null,
            'events' => $data['events'] ?? [],
            'estimated_delivery' => $data['estimatedDelivery'] ?? null,
            'raw' => $data
        ];
    }

    /**
     * Cancel a shipment
     *
     * @param string $trackingNumber
     * @param string $reason
     * @return array
     */
    public function cancelShipment(string $trackingNumber, string $reason = ''): array
    {
        $result = $this->makeApiRequest('POST', '/rest/v2/cancelShipment', [
            'trackingNumber' => $trackingNumber,
            'reason' => $reason ?: 'Cancelled by merchant'
        ]);

        if ($result['success']) {
            // Update local status
            $log = ShipmentStatusLog::where('tracking_number', $trackingNumber)->latest()->first();
            if ($log) {
                ShipmentStatusLog::create([
                    'order_id' => $log->order_id,
                    'vendor_id' => $log->vendor_id,
                    'tracking_number' => $trackingNumber,
                    'shipment_id' => $log->shipment_id,
                    'company_name' => $log->company_name,
                    'status' => 'cancelled',
                    'status_ar' => 'ملغي',
                    'message' => 'Shipment cancelled: ' . $reason,
                    'message_ar' => 'تم إلغاء الشحنة: ' . $reason,
                    'status_date' => now(),
                ]);

                // Notify vendor
                $this->notifyVendor($log->vendor_id, $log->order, 'shipment_cancelled', $trackingNumber);
            }

            return ['success' => true, 'message' => 'Shipment cancelled successfully'];
        }

        return ['success' => false, 'error' => $result['error'], 'details' => $result['raw'] ?? null];
    }

    /**
     * Get order details from Tryoto
     *
     * @param string $orderId
     * @return array
     */
    public function getOrderDetails(string $orderId): array
    {
        $result = $this->makeApiRequest('POST', '/rest/v2/orderDetails', ['orderId' => $orderId]);

        if (!$result['success']) {
            Log::warning('Tryoto: orderDetails failed', [
                'order_id' => $orderId,
                'error' => $result['error']
            ]);
            return ['success' => false, 'error' => $result['error'], 'error_code' => $result['error_code'] ?? null];
        }

        $data = $result['data'];

        Log::info('Tryoto: orderDetails success', [
            'order_id' => $orderId,
            'status' => $data['status'] ?? 'unknown',
            'tracking' => $data['trackingNumber'] ?? null,
        ]);

        return [
            'success' => true,
            'order_id' => $orderId,
            'oto_id' => $data['otoId'] ?? null,
            'tracking_number' => $data['trackingNumber'] ?? null,
            'status' => $data['status'] ?? 'unknown',
            'status_ar' => $this->getStatusArabic($data['status'] ?? 'unknown'),
            'shipment_status' => $data['shipmentStatus'] ?? null,
            'company_name' => $data['companyName'] ?? null,
            'picked_up_date' => $data['pickedUpDate'] ?? null,
            'delivered_date' => $data['deliveredDate'] ?? null,
            'estimated_delivery' => $data['estimatedDelivery'] ?? null,
            'awb_url' => $data['awbUrl'] ?? null,
            'pod_url' => $data['podUrl'] ?? null,
            'raw' => $data
        ];
    }

    /**
     * Refresh shipment status using orderDetails API
     *
     * @param string $trackingNumber
     * @return array
     */
    public function refreshShipmentStatus(string $trackingNumber): array
    {
        $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->latest('created_at')
            ->first();

        if (!$shipment) {
            return ['success' => false, 'error' => 'Shipment not found in local database'];
        }

        $rawData = $shipment->raw_data;
        $orderId = null;

        if (is_array($rawData)) {
            $orderId = $rawData['orderId'] ?? $rawData['otoId'] ?? null;
        } elseif (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            $orderId = $decoded['orderId'] ?? $decoded['otoId'] ?? null;
        }

        if (!$orderId) {
            return $this->trackShipment($trackingNumber);
        }

        $details = $this->getOrderDetails($orderId);

        if (!$details['success']) {
            return $this->trackShipment($trackingNumber);
        }

        $newTrackingNumber = $details['tracking_number'];
        if ($newTrackingNumber && $newTrackingNumber !== $trackingNumber && !str_starts_with($newTrackingNumber, 'OTO-')) {
            ShipmentStatusLog::create([
                'order_id' => $shipment->order_id,
                'vendor_id' => $shipment->vendor_id,
                'tracking_number' => $newTrackingNumber,
                'shipment_id' => $details['oto_id'],
                'company_name' => $details['company_name'] ?? $shipment->company_name,
                'status' => $details['status'],
                'status_ar' => $details['status_ar'],
                'message' => 'Tracking number assigned',
                'message_ar' => 'تم تعيين رقم التتبع: ' . $newTrackingNumber,
                'status_date' => now(),
                'raw_data' => $details['raw'],
            ]);

            $this->notifyVendor($shipment->vendor_id, $shipment->order, 'tracking_assigned', $newTrackingNumber);

            $details['tracking_updated'] = true;
            $details['old_tracking'] = $trackingNumber;
        }

        $this->syncTrackingStatus($newTrackingNumber ?? $trackingNumber, [
            'status' => $details['status'],
            'location' => null,
            'statusDate' => $details['picked_up_date'] ?? $details['delivered_date'] ?? now(),
        ]);

        return $details;
    }

    /**
     * Get all available cities
     *
     * @return array
     */
    public function getCities(): array
    {
        $cacheKey = 'tryoto-cities-' . ($this->isSandbox ? 'sandbox' : 'live');

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            $result = $this->makeApiRequest('GET', '/rest/v2/getCities');

            if ($result['success']) {
                return $result['data']['cities'] ?? [];
            }

            return [];
        });
    }

    /**
     * Verify if a city/state/governorate is supported by Tryoto
     *
     * Enhanced version that returns all available information for location resolution
     *
     * @param string $locationName City, State, or Governorate name
     * @param string $testDestination Destination to test against (default: Riyadh)
     * @return array [
     *   'supported' => bool,
     *   'companies' => array,
     *   'company_count' => int,
     *   'region' => string|null (Tryoto's internal region name),
     *   'origin_city' => string|null (Tryoto's resolved city name),
     *   'destination_city' => string|null,
     *   'cheapest_price' => float|null,
     *   'fastest_delivery' => string|null,
     *   'error' => string|null
     * ]
     */
    public function verifyCitySupport(string $locationName, string $testDestination = 'Riyadh'): array
    {
        // Cache key for repeated checks
        $cacheKey = 'tryoto-verify-' . md5($locationName . '-' . $testDestination);

        // Check cache first (5 minutes TTL for verification results)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $requestData = [
            'originCity' => $locationName,
            'destinationCity' => $testDestination,
            'weight' => 1,
            'xlength' => 30,
            'xheight' => 30,
            'xwidth' => 30,
        ];

        $result = $this->makeApiRequest('POST', '/rest/v2/checkOTODeliveryFee', $requestData);

        if (!$result['success']) {
            $response = [
                'supported' => false,
                'error' => $result['error'],
                'error_code' => $result['error_code'] ?? null,
                'location_name' => $locationName
            ];
            // Cache negative results for 5 minutes
            Cache::put($cacheKey, $response, now()->addMinutes(5));
            return $response;
        }

        $data = $result['data'];
        $companies = $data['deliveryCompany'] ?? [];

        // Extract all useful information from Tryoto response
        $region = $data['originRegion'] ?? $data['region'] ?? null;
        $originCity = $data['originCity'] ?? $data['origin'] ?? null;
        $destCity = $data['destinationCity'] ?? $data['destination'] ?? null;

        // Find cheapest and fastest options
        $cheapestPrice = null;
        $fastestDelivery = null;

        foreach ($companies as $company) {
            $price = (float)($company['price'] ?? 0);
            $deliveryTime = $company['avgDeliveryTime'] ?? '';

            if ($cheapestPrice === null || $price < $cheapestPrice) {
                $cheapestPrice = $price;
            }

            if ($deliveryTime && ($fastestDelivery === null || $this->compareDeliveryTime($deliveryTime, $fastestDelivery) < 0)) {
                $fastestDelivery = $deliveryTime;
            }
        }

        $response = [
            'supported' => !empty($companies),
            'companies' => $companies,
            'company_count' => count($companies),
            'region' => $region,
            'origin_city' => $originCity,
            'destination_city' => $destCity,
            'cheapest_price' => $cheapestPrice,
            'fastest_delivery' => $fastestDelivery,
            'location_name' => $locationName,
            'full_response' => $data
        ];

        // Cache positive results for 30 minutes
        Cache::put($cacheKey, $response, now()->addMinutes(30));

        Log::info('Tryoto: verifyCitySupport', [
            'location' => $locationName,
            'supported' => $response['supported'],
            'region' => $region,
            'companies' => $response['company_count']
        ]);

        return $response;
    }

    /**
     * Compare two delivery time strings
     * Returns negative if $a is faster, positive if $b is faster
     */
    private function compareDeliveryTime(string $a, string $b): int
    {
        $daysA = $this->extractDays($a);
        $daysB = $this->extractDays($b);
        return $daysA - $daysB;
    }

    /**
     * Extract minimum days from delivery time string
     */
    private function extractDays(string $time): int
    {
        if (stripos($time, 'same') !== false) return 0;
        if (stripos($time, 'next') !== false) return 1;

        if (preg_match('/(\d+)/', $time, $matches)) {
            return (int)$matches[1];
        }

        return 999; // Unknown, assume very slow
    }

    /**
     * Verify multiple locations at once (batch verification)
     * Useful for finding nearest supported location
     *
     * @param array $locationNames Array of location names to verify
     * @param string $testDestination Destination to test against
     * @return array Array of verification results keyed by location name
     */
    public function verifyMultipleLocations(array $locationNames, string $testDestination = 'Riyadh'): array
    {
        $results = [];

        foreach ($locationNames as $location) {
            $results[$location] = $this->verifyCitySupport($location, $testDestination);

            // Small delay to avoid rate limiting
            if (count($locationNames) > 5) {
                usleep(100000); // 100ms delay
            }
        }

        return $results;
    }

    /**
     * Find supported locations from a list
     * Returns only locations that are supported by Tryoto
     *
     * @param array $locationNames Array of location names
     * @param string $testDestination Destination to test against
     * @return array Array of supported locations with their details
     */
    public function findSupportedLocations(array $locationNames, string $testDestination = 'Riyadh'): array
    {
        $supported = [];

        foreach ($locationNames as $location) {
            $result = $this->verifyCitySupport($location, $testDestination);

            if ($result['supported']) {
                $supported[] = [
                    'name' => $location,
                    'region' => $result['region'],
                    'origin_city' => $result['origin_city'],
                    'company_count' => $result['company_count'],
                    'cheapest_price' => $result['cheapest_price'],
                ];
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 100ms delay
        }

        return $supported;
    }

    /**
     * Get shipment history for an order
     *
     * @param int $orderId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getShipmentHistory(int $orderId)
    {
        return ShipmentStatusLog::where('order_id', $orderId)
            ->orderBy('status_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get vendor shipments
     *
     * @param int $vendorId
     * @param string|null $status
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVendorShipments(int $vendorId, ?string $status = null, int $limit = 50)
    {
        $query = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->select('tracking_number', 'order_id', 'company_name', 'status', 'status_ar', 'status_date')
            ->groupBy('tracking_number')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get shipping statistics for vendor
     *
     * @param int $vendorId
     * @return array
     */
    public function getVendorStatistics(int $vendorId): array
    {
        $stats = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->selectRaw('
                COUNT(DISTINCT tracking_number) as total_shipments,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status = "out_for_delivery" THEN 1 ELSE 0 END) as out_for_delivery,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned
            ')
            ->first();

        return [
            'total' => $stats->total_shipments ?? 0,
            'delivered' => $stats->delivered ?? 0,
            'in_transit' => $stats->in_transit ?? 0,
            'out_for_delivery' => $stats->out_for_delivery ?? 0,
            'failed' => $stats->failed ?? 0,
            'returned' => $stats->returned ?? 0,
            'success_rate' => $stats->total_shipments > 0
                ? round(($stats->delivered / $stats->total_shipments) * 100, 1)
                : 0
        ];
    }

    /**
     * Get admin statistics
     *
     * @return array
     */
    public function getAdminStatistics(): array
    {
        $stats = ShipmentStatusLog::selectRaw('
                COUNT(DISTINCT tracking_number) as total_shipments,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status = "pending" OR status = "created" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned
            ')
            ->first();

        $byCompany = ShipmentStatusLog::selectRaw('company_name, COUNT(DISTINCT tracking_number) as count')
            ->groupBy('company_name')
            ->get()
            ->pluck('count', 'company_name')
            ->toArray();

        return [
            'total' => $stats->total_shipments ?? 0,
            'delivered' => $stats->delivered ?? 0,
            'in_transit' => $stats->in_transit ?? 0,
            'pending' => $stats->pending ?? 0,
            'failed' => $stats->failed ?? 0,
            'returned' => $stats->returned ?? 0,
            'by_company' => $byCompany,
            'success_rate' => $stats->total_shipments > 0
                ? round(($stats->delivered / $stats->total_shipments) * 100, 1)
                : 0
        ];
    }

    // ========================
    // Private Helper Methods
    // ========================

    /**
     * Calculate shipping dimensions from order cart using VendorCartService
     * بدون قيم ثابتة - يرجع null للقيم الناقصة
     *
     * @param Order $order
     * @param int|null $vendorId Filter items by vendor (optional)
     * @return array
     */
    private function calculateDimensionsFromOrder(Order $order, ?int $vendorId = null): array
    {
        $cartRaw = $order->cart;
        $cartArr = is_string($cartRaw) ? (json_decode($cartRaw, true) ?: []) : (is_array($cartRaw) ? $cartRaw : []);

        $items = $cartArr['items'] ?? $cartArr;
        $itemsForCalculation = [];

        foreach ($items as $ci) {
            $item = $ci['item'] ?? $ci;

            // Filter by vendor if specified
            if ($vendorId !== null) {
                $itemVendorId = (int)($ci['user_id'] ?? $item['user_id'] ?? $item['vendor_user_id'] ?? 0);
                if ($itemVendorId !== $vendorId) {
                    continue;
                }
            }

            $qty = (int)($ci['qty'] ?? $ci['quantity'] ?? 1);
            $mpId = (int)($ci['merchant_product_id'] ?? $item['merchant_product_id'] ?? 0);

            if ($mpId > 0) {
                // استخدام VendorCartService للحصول على الأبعاد الحقيقية
                $dimensions = VendorCartService::getProductDimensions($mpId);
                $itemsForCalculation[] = [
                    'qty' => max(1, $qty),
                    'weight' => $dimensions['weight'],
                    'length' => $dimensions['length'],
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                ];
            } else {
                // fallback للبيانات المخزنة في السلة
                $itemsForCalculation[] = [
                    'qty' => max(1, $qty),
                    'weight' => $item['weight'] ?? null,
                    'length' => $item['length'] ?? null,
                    'width' => $item['width'] ?? null,
                    'height' => $item['height'] ?? null,
                ];
            }
        }

        if (empty($itemsForCalculation)) {
            return [
                'weight' => null,
                'length' => null,
                'width' => null,
                'height' => null
            ];
        }

        // استخدام ShippingCalculatorService لحساب الأبعاد النهائية
        $calculated = ShippingCalculatorService::calculatePackageDimensions($itemsForCalculation);

        return [
            'weight' => $calculated['chargeable_weight'],
            'length' => $calculated['dimensions']['length'],
            'width' => $calculated['dimensions']['width'],
            'height' => $calculated['dimensions']['height'],
        ];
    }

    /**
     * Calculate shipping dimensions from order cart (legacy support)
     * @deprecated Use calculateDimensionsFromOrder instead
     */
    private function calculateDimensions(Order $order): array
    {
        $result = $this->calculateDimensionsFromOrder($order);

        // للتوافق مع الكود القديم، نرجع قيم افتراضية فقط إذا كانت كل القيم null
        // هذا سيتم إزالته لاحقاً
        if ($result['weight'] === null && $result['length'] === null) {
            Log::warning('Tryoto: calculateDimensions - all values null, using legacy fallback', [
                'order_id' => $order->id
            ]);
            return PriceHelper::calculateShippingDimensions([]);
        }

        return $result;
    }

    /**
     * Create initial shipment log
     */
    private function createInitialLog(Order $order, int $vendorId, ?string $trackingNumber, ?string $shipmentId, string $company, string $originCity, array $rawData): void
    {
        if (!$trackingNumber) return;

        try {
            DB::table('shipment_status_logs')->insert([
                'order_id' => $order->id,
                'vendor_id' => $vendorId,
                'tracking_number' => $trackingNumber,
                'shipment_id' => $shipmentId,
                'company_name' => $company,
                'status' => 'created',
                'status_ar' => 'تم إنشاء الشحنة',
                'message' => 'Shipment created successfully',
                'message_ar' => 'تم إنشاء الشحنة بنجاح. في انتظار استلام السائق من المستودع.',
                'location' => $originCity,
                'status_date' => now(),
                'raw_data' => json_encode($rawData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Tryoto: Failed to create initial log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync tracking status from API response
     */
    private function syncTrackingStatus(string $trackingNumber, array $data): void
    {
        $existingLog = ShipmentStatusLog::where('tracking_number', $trackingNumber)->latest()->first();

        if (!$existingLog) return;

        $currentStatus = $data['status'] ?? null;

        if ($currentStatus && $currentStatus !== $existingLog->status) {
            ShipmentStatusLog::create([
                'order_id' => $existingLog->order_id,
                'vendor_id' => $existingLog->vendor_id,
                'tracking_number' => $trackingNumber,
                'shipment_id' => $existingLog->shipment_id,
                'company_name' => $existingLog->company_name,
                'status' => $currentStatus,
                'status_ar' => $this->getStatusArabic($currentStatus),
                'message' => $data['message'] ?? null,
                'message_ar' => $this->getMessageArabic($currentStatus, $data['location'] ?? null),
                'location' => $data['location'] ?? null,
                'status_date' => $data['statusDate'] ?? now(),
                'raw_data' => $data,
            ]);

            // Update order status if delivered
            if ($currentStatus === 'delivered') {
                $order = Order::find($existingLog->order_id);
                if ($order && $order->status !== 'completed') {
                    $order->status = 'completed';
                    $order->save();
                    $order->tracks()->create([
                        'title' => 'Completed',
                        'text' => 'Order delivered - Tracking: ' . $trackingNumber,
                    ]);
                }
            }

            // Notify vendor on important status changes
            if (in_array($currentStatus, ['picked_up', 'delivered', 'failed', 'returned'])) {
                $this->notifyVendor($existingLog->vendor_id, $existingLog->order, 'status_' . $currentStatus, $trackingNumber);
            }
        }
    }

    /**
     * Send notification to vendor
     */
    private function notifyVendor(int $vendorId, $order, string $type, ?string $trackingNumber): void
    {
        if (!$vendorId) return;

        try {
            $notification = new UserNotification();
            $notification->user_id = $vendorId;
            $notification->order_number = $order->order_number ?? ($order->order_number ?? 'N/A');

            if (\Schema::hasColumn('user_notifications', 'order_id')) {
                $notification->order_id = $order->id ?? ($order->id ?? null);
            }

            $notification->is_read = 0;
            $notification->save();
        } catch (\Exception $e) {
            Log::warning('Tryoto: Notification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get Arabic status translation
     */
    public function getStatusArabic(string $status): string
    {
        $translations = [
            'created' => 'تم إنشاء الشحنة',
            'picked_up' => 'تم الاستلام من المستودع',
            'in_transit' => 'في الطريق',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل التوصيل',
            'returned' => 'مرتجع',
            'cancelled' => 'ملغي',
        ];

        return $translations[$status] ?? $status;
    }

    /**
     * Get Arabic message based on status
     */
    private function getMessageArabic(string $status, ?string $location = null): string
    {
        $messages = [
            'created' => 'تم إنشاء الشحنة بنجاح',
            'picked_up' => 'تم استلام الشحنة من المستودع بنجاح',
            'in_transit' => $location ? "الشحنة في الطريق - الموقع الحالي: {$location}" : 'الشحنة في الطريق',
            'out_for_delivery' => 'الشحنة خرجت للتوصيل - سيصل السائق قريباً',
            'delivered' => 'تم تسليم الشحنة بنجاح للعميل',
            'failed' => 'فشل محاولة التوصيل - سيتم إعادة المحاولة',
            'returned' => 'تم إرجاع الشحنة إلى المستودع',
            'cancelled' => 'تم إلغاء الشحنة',
        ];

        return $messages[$status] ?? 'تم تحديث حالة الشحنة';
    }

    /**
     * Check if Tryoto service is properly configured
     *
     * @return array
     */
    public function checkConfiguration(): array
    {
        $issues = [];

        $refreshToken = $this->isSandbox
            ? config('services.tryoto.test.token')
            : config('services.tryoto.live.token');

        if (empty($refreshToken)) {
            $issues[] = 'Refresh token is not configured in .env';
        }

        if (empty($this->baseUrl)) {
            $issues[] = 'API URL is not configured';
        }

        return [
            'configured' => empty($issues),
            'sandbox' => $this->isSandbox,
            'base_url' => $this->baseUrl,
            'issues' => $issues,
            'cache_key' => $this->getCacheKey(),
            'has_cached_token' => Cache::has($this->getCacheKey())
        ];
    }

    /**
     * Clear cached token (useful for debugging)
     */
    public function clearCachedToken(): void
    {
        Cache::forget($this->getCacheKey());
        $this->token = null;
        Log::info('Tryoto: Cached token cleared', ['cache_key' => $this->getCacheKey()]);
    }
}
