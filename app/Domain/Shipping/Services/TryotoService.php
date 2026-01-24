<?php

namespace App\Domain\Shipping\Services;

use App\Models\Purchase;
use App\Models\ShipmentTracking;
use App\Models\User;
use App\Models\City;
use App\Models\UserCatalogEvent;
use App\Models\MerchantItem;
use App\Helpers\PriceHelper;
use App\Services\ShippingCalculatorService;
use App\Services\MerchantCredentialService;
use App\Services\MonetaryUnitService;
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
 * - دعم credentials لكل تاجر (merchant_credentials)
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
    private MerchantCredentialService $merchantCredentialService;
    private ?int $merchantId = null;

    /**
     * POLICY: All Tryoto operations REQUIRE explicit merchant context.
     * Credentials are ONLY from merchant_credentials table.
     * NO fallback to api_credentials or .env
     *
     * @param MerchantCredentialService|null $merchantCredentialService For merchant-specific credentials
     * @param int|null $merchantId Merchant ID (REQUIRED for shipping operations)
     */
    public function __construct(
        ?MerchantCredentialService $merchantCredentialService = null,
        ?int $merchantId = null
    ) {
        $this->merchantCredentialService = $merchantCredentialService ?? app(MerchantCredentialService::class);
        $this->merchantId = $merchantId;
        $this->isSandbox = (bool) config('services.tryoto.sandbox', false);
        $this->baseUrl = $this->isSandbox
            ? config('services.tryoto.test.url', 'https://staging-api.tryoto.com')
            : config('services.tryoto.live.url', 'https://api.tryoto.com');
    }

    /**
     * Set merchant ID for merchant-specific credentials
     */
    public function forMerchant(int $merchantId): self
    {
        $this->merchantId = $merchantId;
        return $this;
    }

    /**
     * الحصول على مفتاح الـ Cache الموحد
     * يشمل merchant_id إذا كان موجوداً لفصل tokens التجار
     */
    public function getCacheKey(): string
    {
        $env = $this->isSandbox ? 'sandbox' : 'live';
        if ($this->merchantId) {
            return self::CACHE_KEY_PREFIX . "merchant-{$this->merchantId}-{$env}";
        }
        return self::CACHE_KEY_PREFIX . $env;
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
     * POLICY: ALL Tryoto operations REQUIRE explicit merchant context.
     * Credentials are ONLY from merchant_credentials table.
     * NO fallback to api_credentials or .env
     *
     * @return string|null
     * @throws \Exception
     */
    private function doRefreshToken(): ?string
    {
        // POLICY: Merchant ID is REQUIRED - no system-level Tryoto operations
        if (!$this->merchantId) {
            throw new \Exception(
                "Tryoto operations require explicit merchant context. " .
                "Use ->forMerchant(\$merchantId) before making API calls."
            );
        }

        // Get merchant credentials - NO FALLBACK
        $refreshToken = $this->merchantCredentialService->getTryotoRefreshToken($this->merchantId);

        if (empty($refreshToken)) {
            throw new \Exception(
                "Tryoto shipping credentials not configured for merchant #{$this->merchantId}. " .
                "Each merchant must have their own shipping credentials in merchant_credentials table. " .
                "Configure via Operator Panel > Merchant Credentials or Merchant Dashboard."
            );
        }

        Log::debug('Tryoto: Using merchant-specific refresh token', ['merchant_id' => $this->merchantId]);

        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
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

                Log::debug('Tryoto: Token refreshed successfully', [
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
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
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
     * Get warehouses from Tryoto API
     */
    public function getWarehouses(): array
    {
        $result = $this->makeApiRequest('GET', '/rest/v2/warehouses');

        if ($result['success']) {
            return $result;
        }

        // Try alternative endpoint
        $result2 = $this->makeApiRequest('GET', '/rest/v2/getWarehouses');
        if ($result2['success']) {
            return $result2;
        }

        return $result;
    }

    /**
     * Create or update warehouse in Tryoto
     */
    public function createWarehouse(array $warehouseData): array
    {
        $payload = [
            'warehouseCode' => $warehouseData['code'] ?? null,
            'warehouseName' => $warehouseData['name'] ?? null,
            'address' => $warehouseData['address'] ?? null,
            'city' => $warehouseData['city'] ?? null,
            'country' => $warehouseData['country'] ?? 'SA',
            'phone' => $warehouseData['phone'] ?? null,
            'email' => $warehouseData['email'] ?? null,
        ];

        return $this->makeApiRequest('POST', '/rest/v2/warehouse', $payload);
    }

    /**
     * Get delivery options for a route using checkOTODeliveryFee endpoint
     *
     * المبدأ الأساسي:
     * - الوزن مطلوب (من catalogItems.weight × الكمية)
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

        // ═══════════════════════════════════════════════════════════════════
        // IMPORTANT:
        // Tryoto API accepts weight-only requests.
        // Do NOT auto-calculate dimensions.
        // Dimensions are sent only if explicitly provided from catalog_items.
        // ═══════════════════════════════════════════════════════════════════
        $requestData = [
            'originCity' => $originCity,
            'destinationCity' => $destinationCity,
            'weight' => $weight,
        ];

        // Only add dimensions if explicitly provided (no auto-calculation)
        if ($length !== null && $length > 0) {
            $requestData['xlength'] = $length;
        }
        if ($height !== null && $height > 0) {
            $requestData['xheight'] = $height;
        }
        if ($width !== null && $width > 0) {
            $requestData['xwidth'] = $width;
        }

        Log::debug('Tryoto: Request data', [
            'has_dimensions' => isset($requestData['xlength']),
            'request' => $requestData,
        ]);

        Log::debug('Tryoto: Requesting delivery options', $requestData);

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

        Log::debug('Tryoto: Got ' . count($options) . ' delivery options');

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
     * @param Purchase $purchase
     * @param int $merchantId
     * @param string $deliveryOptionId
     * @param string $company
     * @param float $price
     * @param string $serviceType
     * @param array|null $merchantShippingData بيانات الشحن المحسوبة مسبقاً (من MerchantCartManager)
     * @param int|null $merchantBranchId معرف فرع الاستلام المحدد (من merchant_branches)
     * @return array
     */
    public function createShipment(Purchase $purchase, int $merchantId, string $deliveryOptionId, string $company, float $price, string $serviceType = '', ?array $merchantShippingData = null, ?int $merchantBranchId = null): array
    {
        // Set merchant ID for merchant-specific credentials
        $this->merchantId = $merchantId;

        // Get merchant info using ShippingCalculatorService
        $merchantCityData = ShippingCalculatorService::getMerchantCity($merchantId);
        $merchant = User::find($merchantId);

        if (!$merchant) {
            return [
                'success' => false,
                'error' => 'Merchant not found',
                'error_code' => 'MERCHANT_NOT_FOUND'
            ];
        }

        // ✅ STRICT: merchant_branch_id is REQUIRED - NO fallback, NO auto-select
        if (!$merchantBranchId) {
            Log::error('Tryoto: createShipment - merchant_branch_id is REQUIRED', [
                'merchant_id' => $merchantId,
                'purchase_id' => $purchase->id,
            ]);
            return [
                'success' => false,
                'error' => 'Branch ID is required. Please select a pickup branch.',
                'error_code' => 'BRANCH_ID_REQUIRED'
            ];
        }

        $merchantBranch = \DB::table('merchant_branches')
            ->where('id', $merchantBranchId)
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->first();

        if (!$merchantBranch) {
            Log::error('Tryoto: createShipment - Invalid or inactive branch', [
                'merchant_id' => $merchantId,
                'merchant_branch_id' => $merchantBranchId,
            ]);
            return [
                'success' => false,
                'error' => 'Invalid branch selected or branch is not active.',
                'error_code' => 'INVALID_BRANCH'
            ];
        }

        // ✅ pickupLocationCode - يجب أن يتطابق مع code المستودع في Tryoto
        // الأولوية: tryoto_warehouse_code → warehouse_name
        $pickupLocationCode = $merchantBranch ? ($merchantBranch->tryoto_warehouse_code ?: $merchantBranch->warehouse_name) : null;

        if (!$pickupLocationCode) {
            Log::error('Tryoto: createShipment - pickupLocationCode missing', [
                'merchant_id' => $merchantId,
                'merchant_branch_id' => $merchantBranchId,
                'tip' => 'Set tryoto_warehouse_code in merchant_branches to match the code in Tryoto dashboard'
            ]);
            return [
                'success' => false,
                'error' => 'Pickup Location Code is missing. Please configure Tryoto Warehouse Code in settings.',
                'error_code' => 'PICKUP_LOCATION_CODE_MISSING'
            ];
        }

        // استخدام مدينة التاجر من ShippingCalculatorService - بدون fallback
        $originCity = $merchantCityData['city_name'] ?? null;

        if (!$originCity) {
            Log::error('Tryoto: createShipment - merchant city missing', ['merchant_id' => $merchantId]);
            return [
                'success' => false,
                'error' => 'Merchant city is not configured',
                'error_code' => 'MERCHANT_CITY_MISSING'
            ];
        }

        // ✅ العنوان من merchant_branches فقط (المصدر الوحيد)
        if (!$merchantBranch || !$merchantBranch->location) {
            Log::error('Tryoto: createShipment - merchant branch not configured', [
                'merchant_id' => $merchantId,
                'tip' => 'Configure merchant branch in merchant_branches table'
            ]);
            return [
                'success' => false,
                'error' => 'Merchant warehouse location is not configured',
                'error_code' => 'MERCHANT_BRANCH_MISSING'
            ];
        }
        $originAddress = $merchantBranch->location;

        // مدينة العميل من الطلب
        $destinationCityValue = $purchase->customer_city;
        $destinationCity = $this->resolveCityName($destinationCityValue);

        if (!$destinationCity) {
            Log::error('Tryoto: createShipment - customer city missing', ['purchase_id' => $purchase->id]);
            return [
                'success' => false,
                'error' => 'Customer city is not configured',
                'error_code' => 'CUSTOMER_CITY_MISSING'
            ];
        }

        // Calculate dimensions from merchant shipping data or purchase cart
        $dims = $merchantShippingData ? [
            'weight' => $merchantShippingData['chargeable_weight'] ?? $merchantShippingData['actual_weight'] ?? null,
            'length' => $merchantShippingData['dimensions']['length'] ?? null,
            'width' => $merchantShippingData['dimensions']['width'] ?? null,
            'height' => $merchantShippingData['dimensions']['height'] ?? null,
        ] : $this->calculateDimensionsFromPurchase($purchase, $merchantId);

        // ✅ التحقق من الوزن فقط - المقاسات اختيارية
        if (!$dims['weight'] || $dims['weight'] <= 0) {
            Log::error('Tryoto: createShipment - weight is required', [
                'purchase_id' => $purchase->id,
                'merchant_id' => $merchantId,
                'dims' => $dims
            ]);
            return [
                'success' => false,
                'error' => 'CatalogItem weight is required for shipping',
                'error_code' => 'WEIGHT_MISSING',
                'missing_fields' => ['weight']
            ];
        }

        // ═══════════════════════════════════════════════════════════════════
        // IMPORTANT:
        // Do NOT auto-calculate dimensions.
        // Dimensions are sent only if explicitly provided from catalog_items.
        // ═══════════════════════════════════════════════════════════════════
        $weight = $dims['weight'];
        $length = $dims['length'] && $dims['length'] > 0 ? $dims['length'] : null;
        $width = $dims['width'] && $dims['width'] > 0 ? $dims['width'] : null;
        $height = $dims['height'] && $dims['height'] > 0 ? $dims['height'] : null;

        Log::debug('Tryoto: createShipment - dimensions from catalog', [
            'purchase_id' => $purchase->id,
            'weight' => $weight,
            'has_dimensions' => ($length !== null && $width !== null && $height !== null),
        ]);

        // Determine COD amount
        $isCOD = in_array($purchase->method, ['cod', 'Cash On Delivery']);
        $codAmount = $isCOD ? (float)$purchase->pay_amount : 0.0;

        // Prepare receiver info
        $receiverName = $purchase->customer_name;
        $receiverPhone = $this->cleanPhoneNumber($purchase->customer_phone ?: '');
        $receiverEmail = $purchase->customer_email ?: null;
        $receiverAddress = $purchase->customer_address;
        $receiverZip = $purchase->customer_zip ?: null;
        $receiverDistrict = $purchase->customer_state ?? '';

        // Validate required receiver info
        if (!$receiverName || !$receiverPhone || !$receiverAddress) {
            Log::error('Tryoto: createShipment - incomplete receiver info', [
                'purchase_id' => $purchase->id,
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
        $senderPhone = $this->cleanPhoneNumber($merchant->phone ?? '');

        // Prepare cart items - use model method that handles legacy data
        $items = $purchase->getCartItems();
        $purchaseItems = [];
        $itemCount = 0;

        foreach ($items as $item) {
            $itemData = $item['item'] ?? $item;
            $qty = (int)($item['qty'] ?? 1);
            $itemCount += $qty;
            $purchaseItems[] = [
                'productId' => (string)($itemData['id'] ?? '0'),
                'name' => $itemData['name'] ?? null,
                'price' => (float)($itemData['price'] ?? 0),
                'rowTotal' => (float)($itemData['price'] ?? 0) * $qty,
                'taxAmount' => 0,
                'quantity' => $qty,
                'serialnumber' => '',
                'part_number' => $itemData['part_number'] ?? null,
                'image' => $itemData['photo'] ?? '',
            ];
        }

        // Generate unique purchase ID for Tryoto
        $tryotoOrderId = $purchase->purchase_number . '-M' . $merchantId . '-' . time();

        // Build createOrder payload - using real data only
        $payload = [
            'orderId' => $tryotoOrderId,
            'ref1' => 'REF-' . $purchase->id . '-' . $merchantId,
            'deliveryOptionId' => $deliveryOptionId,
            'serviceType' => $serviceType,
            'createShipment' => true,
            // ✅ pickupLocationCode - الحقل الصحيح الذي يتوقعه Tryoto API
            // يجب أن يتطابق مع code المستودع المسجل في لوحة تحكم Tryoto
            'pickupLocationCode' => $pickupLocationCode,
            'storeName' => $merchant->shop_name ?? $merchant->name ?? null,
            'payment_method' => $isCOD ? 'cod' : 'paid',
            'amount' => (float)$purchase->pay_amount,
            'amount_due' => $isCOD ? (float)$purchase->pay_amount : 0,
            'shippingAmount' => $price,
            'subtotal' => (float)$purchase->pay_amount,
            'currency' => MonetaryUnitService::BASE_MONETARY_UNIT,
            'shippingNotes' => 'Purchase #' . $purchase->purchase_number,
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
            'senderName' => $merchant->shop_name ?? $merchant->name,
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
            'items' => $purchaseItems
        ];

        $result = $this->makeApiRequest('POST', '/rest/v2/createOrder', $payload);

        if ($result['success']) {
            $data = $result['data'];
            $otoId = $data['otoId'] ?? null;
            $trackingNumber = $data['trackingNumber'] ?? null;

            // If no tracking number yet, use otoId as reference
            $trackingRef = $trackingNumber ?? ('OTO-' . $otoId);

            // Save to shipment_trackings
            $this->createInitialLog($purchase, $merchantId, $trackingRef, (string)$otoId, $company, $originCity, $data);

            // Send notification to merchant
            $this->notifyMerchant($merchantId, $purchase, 'shipment_created', $trackingRef);

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

        Log::error('Tryoto: createShipment failed', [
            'purchase_id' => $purchase->id,
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
     * Handles both real tracking numbers and OTO- prefixed temporary IDs.
     * For OTO- IDs, uses orderDetails API instead of trackShipment.
     *
     * @param string $trackingNumber
     * @param string|null $companyName
     * @return array
     */
    public function trackShipment(string $trackingNumber, ?string $companyName = null): array
    {
        // ✅ Handle OTO- prefixed temporary IDs
        // When Tryoto hasn't assigned a real tracking number yet, we use OTO-{otoId} format
        // The trackShipment API doesn't recognize this - we need to use orderDetails instead
        if (str_starts_with($trackingNumber, 'OTO-')) {
            $otoId = substr($trackingNumber, 4); // Extract the ID after "OTO-"

            $orderResult = $this->getOrderDetails($otoId);

            if (!$orderResult['success']) {
                return ['success' => false, 'error' => $orderResult['error']];
            }

            // If a real tracking number is now available, update local record
            $realTrackingNumber = $orderResult['tracking_number'] ?? null;
            if ($realTrackingNumber && !str_starts_with($realTrackingNumber, 'OTO-')) {
                // Update the shipment tracking record with the real tracking number
                $shipment = ShipmentTracking::getLatestByTracking($trackingNumber);
                if ($shipment) {
                    try {
                        $trackingService = app(ShipmentTrackingService::class);
                        $trackingService->createTrackingRecord(
                            $shipment->purchase_id,
                            $shipment->merchant_id,
                            $orderResult['status'],
                            [
                                'shipping_id' => $shipment->shipping_id,
                                'integration_type' => ShipmentTracking::INTEGRATION_API,
                                'provider' => 'tryoto',
                                'tracking_number' => $realTrackingNumber,
                                'external_shipment_id' => $orderResult['oto_id'],
                                'company_name' => $orderResult['company_name'] ?? $shipment->company_name,
                                'status_ar' => $orderResult['status_ar'],
                                'message' => 'Tracking number assigned: ' . $realTrackingNumber,
                                'message_ar' => 'تم تعيين رقم التتبع: ' . $realTrackingNumber,
                                'source' => ShipmentTracking::SOURCE_API,
                                'raw_payload' => $orderResult['raw'] ?? [],
                                'awb_url' => $orderResult['awb_url'] ?? null,
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::warning('Tryoto: Failed to update tracking number', ['error' => $e->getMessage()]);
                    }
                }

                $orderResult['tracking_updated'] = true;
                $orderResult['old_tracking'] = $trackingNumber;
                $orderResult['tracking_number'] = $realTrackingNumber;
            }

            // Return in the same format as regular trackShipment
            return [
                'success' => true,
                'status' => $orderResult['status'] ?? 'unknown',
                'status_ar' => $orderResult['status_ar'] ?? $this->getStatusArabic($orderResult['status'] ?? 'unknown'),
                'location' => null,
                'events' => [],
                'estimated_delivery' => $orderResult['estimated_delivery'] ?? null,
                'tracking_number' => $orderResult['tracking_number'] ?? $trackingNumber,
                'tracking_updated' => $orderResult['tracking_updated'] ?? false,
                'oto_id' => $orderResult['oto_id'] ?? $otoId,
                'awb_url' => $orderResult['awb_url'] ?? null,
                'raw' => $orderResult['raw'] ?? []
            ];
        }

        // ✅ Standard tracking for real tracking numbers
        if (!$companyName) {
            $shipment = ShipmentTracking::getLatestByTracking($trackingNumber);
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

        // Update local tracking if there's new status
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
            // Update local tracking using new system
            $latest = ShipmentTracking::getLatestByTracking($trackingNumber);
            if ($latest) {
                $trackingService = app(ShipmentTrackingService::class);
                $trackingService->cancelShipment(
                    $latest->purchase_id,
                    $latest->merchant_id,
                    ShipmentTracking::SOURCE_API,
                    $reason ?: 'Cancelled via Tryoto API'
                );

                // Notify merchant
                $purchase = Purchase::find($latest->purchase_id);
                if ($purchase) {
                    $this->notifyMerchant($latest->merchant_id, $purchase, 'shipment_cancelled', $trackingNumber);
                }
            }

            return ['success' => true, 'message' => 'Shipment cancelled successfully'];
        }

        return ['success' => false, 'error' => $result['error'], 'details' => $result['raw'] ?? null];
    }

    /**
     * Get purchase details from Tryoto
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

        Log::debug('Tryoto: orderDetails success', [
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
        $shipment = ShipmentTracking::getLatestByTracking($trackingNumber);

        if (!$shipment) {
            return ['success' => false, 'error' => 'Shipment not found in local database'];
        }

        $rawData = $shipment->raw_payload;
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
            // Create tracking record with new tracking number
            $trackingService = app(ShipmentTrackingService::class);
            $trackingService->createTrackingRecord(
                $shipment->purchase_id,
                $shipment->merchant_id,
                $details['status'],
                [
                    'shipping_id' => $shipment->shipping_id,
                    'integration_type' => ShipmentTracking::INTEGRATION_API,
                    'provider' => 'tryoto',
                    'tracking_number' => $newTrackingNumber,
                    'external_shipment_id' => $details['oto_id'],
                    'company_name' => $details['company_name'] ?? $shipment->company_name,
                    'status_ar' => $details['status_ar'],
                    'message' => 'Tracking number assigned: ' . $newTrackingNumber,
                    'message_ar' => 'تم تعيين رقم التتبع: ' . $newTrackingNumber,
                    'source' => ShipmentTracking::SOURCE_API,
                    'raw_payload' => $details['raw'],
                    'awb_url' => $details['awb_url'] ?? null,
                ]
            );

            $purchase = Purchase::find($shipment->purchase_id);
            if ($purchase) {
                $this->notifyMerchant($shipment->merchant_id, $purchase, 'tracking_assigned', $newTrackingNumber);
            }

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

        Log::debug('Tryoto: verifyCitySupport', [
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
     * Get shipment history for a purchase
     * يستخدم النظام الجديد shipment_trackings
     *
     * @param int $purchaseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getShipmentHistory(int $purchaseId)
    {
        return ShipmentTracking::getHistoryForPurchase($purchaseId);
    }

    /**
     * Get merchant shipments
     * يستخدم النظام الجديد shipment_trackings
     *
     * @param int $merchantId
     * @param string|null $status
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMerchantShipments(int $merchantId, ?string $status = null, int $limit = 50)
    {
        // Get latest status for each unique purchase+merchant combination
        $subQuery = ShipmentTracking::where('merchant_id', $merchantId)
            ->selectRaw('MAX(id) as max_id')
            ->groupBy('purchase_id', 'merchant_id');

        $query = ShipmentTracking::whereIn('id', $subQuery)
            ->select('tracking_number', 'purchase_id', 'company_name', 'status', 'status_ar', 'occurred_at')
            ->orderBy('occurred_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get shipping statistics for merchant
     * يستخدم ShipmentTrackingService
     *
     * @param int $merchantId
     * @return array
     */
    public function getMerchantStatistics(int $merchantId): array
    {
        $trackingService = app(ShipmentTrackingService::class);
        $stats = $trackingService->getMerchantStats($merchantId);

        return [
            'total' => $stats['total'] ?? 0,
            'delivered' => $stats['delivered'] ?? 0,
            'in_transit' => $stats['in_transit'] ?? 0,
            'out_for_delivery' => 0, // Included in in_transit
            'failed' => $stats['failed'] ?? 0,
            'returned' => $stats['returned'] ?? 0,
            'success_rate' => $stats['total'] > 0
                ? round(($stats['delivered'] / $stats['total']) * 100, 1)
                : 0
        ];
    }

    /**
     * Get admin statistics
     * يستخدم ShipmentTrackingService
     *
     * @return array
     */
    public function getAdminStatistics(): array
    {
        $trackingService = app(ShipmentTrackingService::class);
        $stats = $trackingService->getOperatorStats();

        $byCompany = ShipmentTracking::selectRaw('company_name, COUNT(DISTINCT CONCAT(purchase_id, "-", merchant_id)) as count')
            ->groupBy('company_name')
            ->get()
            ->pluck('count', 'company_name')
            ->toArray();

        return [
            'total' => $stats['total'] ?? 0,
            'delivered' => $stats['by_status']['delivered'] ?? 0,
            'in_transit' => ($stats['by_status']['in_transit'] ?? 0) + ($stats['by_status']['out_for_delivery'] ?? 0),
            'pending' => ($stats['by_status']['created'] ?? 0) + ($stats['by_status']['picked_up'] ?? 0),
            'failed' => $stats['by_status']['failed'] ?? 0,
            'returned' => $stats['by_status']['returned'] ?? 0,
            'by_company' => $byCompany,
            'success_rate' => ($stats['total'] ?? 0) > 0
                ? round((($stats['by_status']['delivered'] ?? 0) / $stats['total']) * 100, 1)
                : 0
        ];
    }

    // ========================
    // Private Helper Methods
    // ========================

    /**
     * Calculate shipping dimensions from purchase cart using MerchantCartManager
     * بدون قيم ثابتة - يرجع null للقيم الناقصة
     *
     * @param Purchase $purchase
     * @param int|null $merchantId Filter items by merchant (optional)
     * @return array
     */
    private function calculateDimensionsFromPurchase(Purchase $purchase, ?int $merchantId = null): array
    {
        // Use model method that handles legacy double-encoded data
        $items = $purchase->getCartItems();
        $itemsForCalculation = [];

        foreach ($items as $key => $ci) {
            $item = $ci['item'] ?? $ci;

            // Filter by merchant if specified
            if ($merchantId !== null) {
                // FAIL-FAST: merchant_id must exist
                if (!isset($ci['merchant_id']) && !isset($ci['user_id']) && !isset($item['merchant_id'])) {
                    throw new \RuntimeException(
                        "Cart item '{$key}' missing required field: merchant_id. " .
                        "Purchase ID: {$purchase->id}"
                    );
                }
                $itemMerchantId = (int)($ci['merchant_id'] ?? $ci['user_id'] ?? $item['merchant_id']);
                if ($itemMerchantId <= 0) {
                    throw new \RuntimeException(
                        "Cart item '{$key}' has invalid merchant_id: {$itemMerchantId}. " .
                        "Purchase ID: {$purchase->id}"
                    );
                }
                if ($itemMerchantId !== $merchantId) {
                    continue;
                }
            }

            // FAIL-FAST: Required fields must exist
            if (!isset($ci['qty']) && !isset($ci['quantity'])) {
                throw new \RuntimeException(
                    "Cart item '{$key}' missing required field: qty. " .
                    "Purchase ID: {$purchase->id}"
                );
            }
            if (!isset($ci['merchant_item_id']) && !isset($item['merchant_item_id'])) {
                throw new \RuntimeException(
                    "Cart item '{$key}' missing required field: merchant_item_id. " .
                    "Purchase ID: {$purchase->id}"
                );
            }

            $qty = (int)($ci['qty'] ?? $ci['quantity']);
            if ($qty <= 0) {
                throw new \RuntimeException(
                    "Cart item '{$key}' has invalid qty: {$qty}. " .
                    "Purchase ID: {$purchase->id}"
                );
            }

            $mpId = (int)($ci['merchant_item_id'] ?? $item['merchant_item_id']);
            if ($mpId <= 0) {
                throw new \RuntimeException(
                    "Cart item '{$key}' has invalid merchant_item_id: {$mpId}. " .
                    "Purchase ID: {$purchase->id}"
                );
            }

            // الحصول على الأبعاد من MerchantItem/CatalogItem
            $dimensions = $this->getMerchantItemDimensions($mpId);
            $itemsForCalculation[] = [
                'qty' => $qty,
                'weight' => $dimensions['weight'],
                'length' => $dimensions['length'],
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
            ];
        }

        if (empty($itemsForCalculation)) {
            throw new \RuntimeException(
                "No valid items found for shipping calculation. " .
                "Purchase ID: {$purchase->id}, Merchant ID: " . ($merchantId ?? 'all')
            );
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
     * Calculate shipping dimensions from purchase cart
     * FAIL-FAST: No legacy fallbacks
     */
    private function calculateDimensions(Purchase $purchase): array
    {
        return $this->calculateDimensionsFromPurchase($purchase);
    }

    /**
     * Get dimensions for a merchant item
     * FAIL-FAST: No fallbacks
     */
    private function getMerchantItemDimensions(int $merchantItemId): array
    {
        $mp = MerchantItem::with('catalogItem')->find($merchantItemId);

        if (!$mp) {
            throw new \InvalidArgumentException("MerchantItem {$merchantItemId} not found");
        }

        if (!$mp->catalogItem) {
            throw new \InvalidArgumentException("MerchantItem {$merchantItemId} has no associated CatalogItem");
        }

        $catalogItem = $mp->catalogItem;

        // Priority: merchant_items -> catalog_items (NO fallbacks to hardcoded values)
        $weight = $mp->weight ?? $catalogItem->weight ?? null;
        $length = $mp->length ?? $catalogItem->length ?? null;
        $width = $mp->width ?? $catalogItem->width ?? null;
        $height = $mp->height ?? $catalogItem->height ?? null;

        return [
            'weight' => $weight !== null ? (float) $weight : null,
            'length' => $length !== null ? (float) $length : null,
            'width' => $width !== null ? (float) $width : null,
            'height' => $height !== null ? (float) $height : null,
        ];
    }

    /**
     * Create initial shipment tracking record
     * يستخدم النظام الجديد shipment_trackings
     */
    private function createInitialLog(Purchase $purchase, int $merchantId, ?string $trackingNumber, ?string $shipmentId, string $company, string $originCity, array $rawData, ?int $shippingId = null, float $shippingCost = 0, float $codAmount = 0): void
    {
        if (!$trackingNumber) return;

        try {
            $trackingService = app(ShipmentTrackingService::class);
            $trackingService->createApiShipment(
                purchaseId: $purchase->id,
                merchantId: $merchantId,
                shippingId: $shippingId ?? 0,
                provider: 'tryoto',
                trackingNumber: $trackingNumber,
                externalShipmentId: $shipmentId ?? '',
                companyName: $company,
                shippingCost: $shippingCost,
                codAmount: $codAmount,
                awbUrl: $rawData['awbUrl'] ?? null,
                rawPayload: $rawData
            );
        } catch (\Exception $e) {
            Log::error('Tryoto: Failed to create tracking record', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync tracking status from API response
     * يستخدم النظام الجديد shipment_trackings
     */
    private function syncTrackingStatus(string $trackingNumber, array $data): void
    {
        $currentStatus = $data['status'] ?? null;
        if (!$currentStatus) return;

        try {
            $trackingService = app(ShipmentTrackingService::class);
            $result = $trackingService->updateFromApi($trackingNumber, $currentStatus, [
                'location' => $data['location'] ?? null,
                'message' => $data['message'] ?? null,
                'message_ar' => $this->getMessageArabic($currentStatus, $data['location'] ?? null),
                'status_ar' => $this->getStatusArabic($currentStatus),
                'status_en' => ShipmentTracking::getStatusTranslationEn($currentStatus),
                'occurred_at' => $data['statusDate'] ?? now(),
                'raw_payload' => $data,
            ]);

            // Notify merchant on important status changes
            if ($result && in_array($currentStatus, ['picked_up', 'delivered', 'failed', 'returned'])) {
                $purchase = Purchase::find($result->purchase_id);
                if ($purchase) {
                    $this->notifyMerchant($result->merchant_id, $purchase, 'status_' . $currentStatus, $trackingNumber);
                }
            }
        } catch (\Exception $e) {
            Log::error('Tryoto: Failed to sync tracking status', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification to merchant
     */
    private function notifyMerchant(int $merchantId, $purchase, string $type, ?string $trackingNumber): void
    {
        if (!$merchantId) return;

        try {
            $notification = new UserCatalogEvent();
            $notification->user_id = $merchantId;
            $notification->purchase_number = $purchase->purchase_number ?? ($purchase->purchase_number ?? 'N/A');

            if (\Schema::hasColumn('user_catalog_events', 'purchase_id')) {
                $notification->purchase_id = $purchase->id ?? ($purchase->id ?? null);
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
     * Check if Tryoto service is properly configured for a merchant
     *
     * POLICY: Merchant ID is REQUIRED - all Tryoto operations need merchant context
     * Credentials are ONLY from merchant_credentials table - NO fallback
     *
     * @param int $merchantId Merchant ID (REQUIRED)
     * @return array
     */
    public function checkConfiguration(int $merchantId): array
    {
        $issues = [];

        $this->merchantId = $merchantId;
        $merchantToken = $this->merchantCredentialService->getTryotoRefreshToken($merchantId);
        $hasMerchantCredential = !empty($merchantToken);

        if (!$hasMerchantCredential) {
            $issues[] = "REQUIRED: Merchant #{$merchantId} must have Tryoto credentials in merchant_credentials table. " .
                       "Configure via Operator Panel > Merchant Credentials or Merchant Dashboard.";
        }

        if (empty($this->baseUrl)) {
            $issues[] = 'API URL is not configured in config/services.php';
        }

        return [
            'configured' => empty($issues),
            'sandbox' => $this->isSandbox,
            'base_url' => $this->baseUrl,
            'merchant_id' => $merchantId,
            'has_merchant_credential' => $hasMerchantCredential,
            'policy' => 'ALL Tryoto operations require merchant_credentials - NO system fallback',
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
        Log::debug('Tryoto: Cached token cleared', ['cache_key' => $this->getCacheKey()]);
    }
}
