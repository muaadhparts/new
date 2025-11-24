<?php

namespace App\Livewire;

use App\Models\Cart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class TryotoComponet extends Component
{
    /** منتجات هذا البائع داخل المودال */
    public array $products = [];

    /** خيارات شركات الشحن من Tryoto */
    public array $deliveryCompany = [];

    /** رقم البائع لربط الإشارة وتحديث نص الشحن أعلى المودال */
    public int $vendorId = 0;

    /** توكن OTO */
    protected $token;

    /** الوزن الإجمالي */
    protected $weight = 100;

    /** إعدادات API */
    protected string $url;
    protected string $_token;
    protected string $_webhook;

    /** Cached general settings */
    protected $generalSettings;

    public function mount(array $products, int $vendorId = 0)
    {
        $this->products = $products;
        $this->vendorId = $vendorId;

        // Cache general settings to avoid repeated DB calls
        $this->generalSettings = cache()->remember('generalsettings', now()->addMinutes(30), function () {
            return \DB::table('generalsettings')->first();
        });

        if (!config('services.tryoto.sandbox')) {
            $this->url = config('services.tryoto.live.url');
            $this->_token = config('services.tryoto.live.token');
            $this->_webhook = route('tryoto.callback');
        } else {
            $this->url = config('services.tryoto.test.url');
            $this->_token = config('services.tryoto.test.token');
            $this->_webhook = 'https://request-dinleyici-url-buraya-yazilmali';
        }

        $this->authenticateTryoto();
        $this->getWeight();
        $this->checkOTODeliveryFee();

        // // dd($this->deliveryCompany); // فحص سريع لو احتجت
    }

    public function render()
    {
        return view('livewire.tryoto-componet');
    }

    /**
     * حساب الوزن الإجمالي والأبعاد
     */
    public function getWeight(): void
    {
        // Use PriceHelper to calculate dimensions and weight
        $dimensions = \App\Helpers\PriceHelper::calculateShippingDimensions($this->products);

        $this->weight = $dimensions['weight'];

        // Update products array with calculated weights for display
        foreach ($this->products as $index => $product) {
            $product['weight_total'] = $product['qty'] * $product['item']['weight'];
            $this->products[$index] = $product;
        }
    }

    /**
     * جلب توكن Tryoto
     */
    protected function authenticateTryoto(): void
    {
        // First try to get cached token
        $cachedToken = Cache::get('tryoto-token');
        if ($cachedToken) {
            $this->token = $cachedToken;
            return;
        }

        // Get refresh token based on environment with multiple fallbacks
        $refreshToken = config('services.tryoto.sandbox')
            ? (config('services.tryoto.test.token') ?? config('services.tryoto.test.refresh_token') ?? env('TRYOTO_TEST_REFRESH_TOKEN'))
            : (config('services.tryoto.live.token') ?? config('services.tryoto.live.refresh_token') ?? env('TRYOTO_REFRESH_TOKEN'));

        if (empty($refreshToken)) {
            \Log::error('Tryoto API Error - No refresh token configured', [
                'sandbox' => config('services.tryoto.sandbox'),
                'config_keys_checked' => [
                    'test.token',
                    'test.refresh_token',
                    'live.token',
                    'live.refresh_token'
                ]
            ]);
            throw new \Exception('Tryoto refresh token is not configured. Please check your config/services.php or .env file.');
        }

        $response = Http::post($this->url . '/rest/v2/refreshToken', [
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            $token = $response->json()['access_token'];
            $expiresIn = (int)($response->json()['expires_in'] ?? 3600);
            $ttl = now()->addSeconds(max(300, $expiresIn - 60));
            Cache::put('tryoto-token', $token, $ttl);
            // dd(['__fn__' => __FUNCTION__, 'ttl' => $ttl->diffInSeconds(), 'sandbox' => config('services.tryoto.sandbox')]); // Quick Check
            $this->token = $token;
        } else {
            \Log::error('Tryoto API Error - Failed to get access token', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $this->url,
                'sandbox' => config('services.tryoto.sandbox'),
            ]);
            throw new \Exception('Failed to get Tryoto access token. Status: ' . $response->status() . '. Error: ' . $response->body());
        }
    }

    /**
     * يُستدعى عند تغيير الراديو في جدول Tryoto
     * value = "deliveryOptionId#CompanyName#price"
     */
    public function selectedOption(string $value): void
    {
        // $parts   = explode('#', $value);
        // $company = $parts[1] ?? '';
        // $price   = (float)($parts[2] ?? 0);
        // // dd($company, $price); // فحص سريع لو احتجت

        // نبث حدثًا للواجهة لتحدّث نص "الشحن:" والسعر
        $this->dispatch('shipping-updated', vendorId: $this->vendorId);
        // // dd('dispatch:shipping-updated', $this->vendorId); // فحص
    }

    /**
     * جلب خيارات الشحن من Tryoto
     */
    protected function checkOTODeliveryFee(): void
    {
        // Calculate dimensions using PriceHelper
        $dimensions = \App\Helpers\PriceHelper::calculateShippingDimensions($this->products);

        // Get cities from session data
        $originCity = $this->getOriginCity();
        $destinationCity = $this->getDestinationCity();

        $requestData = [
            "originCity"      => $originCity,
            "destinationCity" => $destinationCity,
            "weight"          => $dimensions['weight'],
            "xlength"         => max(30, $dimensions['length']), // Minimum 30cm or calculated length
            "xheight"         => max(30, $dimensions['height']), // Minimum 30cm or calculated height
            "xwidth"          => max(30, $dimensions['width']),  // Minimum 30cm or calculated width
        ];

        $response = Http::withToken($this->token)->post($this->url . '/rest/v2/checkOTODeliveryFee', $requestData);

        if (!$response->successful()) {
            // Log detailed error information
            \Log::error('Tryoto API Error - checkOTODeliveryFee', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request' => $requestData,
                'token_exists' => !empty($this->token),
                'url' => $this->url,
            ]);

            // Throw exception with more details
            throw new \Exception('Unable to get shipping fee from Tryoto. Status: ' . $response->status() . '. Error: ' . $response->body());
        }

        $this->deliveryCompany = $response->json()['deliveryCompany'] ?? [];

        // إعلان مبدئي لتحديث نص الشحن الافتراضي (أول خيار)
        $this->dispatch('shipping-updated', vendorId: $this->vendorId);
    }

    /**
     * Get origin city (vendor/warehouse city)
     * Only uses city_id from cities table - no fallbacks
     */
    protected function getOriginCity(): string
    {
        // Vendor must be specified
        if ($this->vendorId <= 0) {
            \Log::error('TryotoComponent: No vendor ID specified for origin city');
            throw new \Exception('Vendor ID is required to determine origin city for shipping calculation.');
        }

        $vendor = \App\Models\User::find($this->vendorId);

        if (!$vendor) {
            \Log::error('TryotoComponent: Vendor not found', [
                'vendor_id' => $this->vendorId,
            ]);
            throw new \Exception("Vendor with ID {$this->vendorId} not found.");
        }

        // city_id must exist
        if (empty($vendor->city_id)) {
            \Log::error('TryotoComponent: Vendor has no city_id set', [
                'vendor_id' => $this->vendorId,
                'vendor_name' => $vendor->name,
            ]);
            throw new \Exception("Vendor '{$vendor->name}' (ID: {$this->vendorId}) does not have a city assigned. Please set the vendor's city in their profile.");
        }

        // City must exist in cities table
        $city = \App\Models\City::find($vendor->city_id);

        if (!$city || empty($city->city_name)) {
            \Log::error('TryotoComponent: City not found in database', [
                'vendor_id' => $this->vendorId,
                'city_id' => $vendor->city_id,
            ]);
            throw new \Exception("City with ID {$vendor->city_id} not found in database. Please contact administrator.");
        }

        \Log::info('TryotoComponent: Origin city resolved from vendor city_id', [
            'vendor_id' => $this->vendorId,
            'vendor_name' => $vendor->name,
            'city_id' => $vendor->city_id,
            'city_name' => $city->city_name,
        ]);

        return $this->normalizeCityName($city->city_name);
    }

    /**
     * Get destination city from customer data in session ONLY
     *
     * CRITICAL REQUIREMENT:
     * - Uses ONLY city_id from checkout form (session step1 or vendor_step1_{vendor_id})
     * - NO fallback to user profile city
     * - NO fallback to saved addresses
     * - NO fallback to address book
     * - If city not provided in checkout form: throw exception
     *
     * This ensures shipping calculation uses only current checkout data,
     * not any saved/historical data from user profile.
     *
     * Supports both:
     * - Regular checkout: session('step1')
     * - Vendor checkout: session('vendor_step1_{vendor_id}')
     */
    // protected function getDestinationCity(): string
    // {
    //     $cityId = null;
    //     $sessionKey = null;

    //     // Try vendor-specific checkout first (if vendorId is set)
    //     if ($this->vendorId > 0) {
    //         $vendorSessionKey = 'vendor_step1_' . $this->vendorId;
    //         if (\Session::has($vendorSessionKey)) {
    //             $sessionKey = $vendorSessionKey;
    //             $step1 = \Session::get($vendorSessionKey);

    //             // Check if customer_city exists and is numeric (city_id)
    //             if (!empty($step1['customer_city']) && is_numeric($step1['customer_city'])) {
    //                 $cityId = $step1['customer_city'];
    //             }
    //         }
    //     }

    //     // If not vendor checkout OR vendor session not found, try regular checkout
    //     if (!$cityId && \Session::has('step1')) {
    //         $sessionKey = 'step1';
    //         $step1 = \Session::get('step1');

    //         // Check if customer_city exists and is numeric (city_id)
    //         if (!empty($step1['customer_city']) && is_numeric($step1['customer_city'])) {
    //             $cityId = $step1['customer_city'];
    //         }
    //     }

    //     // city_id must exist from checkout form
    //     // NO FALLBACK to user profile or any other source
    //     if (!$cityId) {
    //         \Log::error('TryotoComponent: No destination city_id found in checkout form', [
    //             'vendor_id' => $this->vendorId,
    //             'has_session_step1' => \Session::has('step1'),
    //             'has_vendor_session' => $this->vendorId > 0 ? \Session::has('vendor_step1_' . $this->vendorId) : false,
    //             'session_key_checked' => $sessionKey,
    //             'session_customer_city' => $sessionKey ? \Session::get($sessionKey . '.customer_city') : null,
    //         ]);
    //         throw new \Exception('Customer destination city is required for shipping calculation. Please select a city in the checkout form.');
    //     }

    //     // City must exist in cities table
    //     $city = \App\Models\City::find($cityId);

    //     if (!$city || empty($city->city_name)) {
    //         \Log::error('TryotoComponent: Destination city not found in database', [
    //             'city_id' => $cityId,
    //         ]);
    //         throw new \Exception("Destination city with ID {$cityId} not found in database. Please contact administrator.");
    //     }

    //     \Log::info('TryotoComponent: Destination city resolved from checkout form', [
    //         'vendor_id' => $this->vendorId,
    //         'city_id' => $cityId,
    //         'city_name' => $city->city_name,
    //         'source' => 'checkout_form_only',
    //         'session_key' => $sessionKey,
    //     ]);

    //     return $this->normalizeCityName($city->city_name);
    // }

    // /**
    //  * Normalize city name for Tryoto API
    //  * Only cleans the name - removes diacritics and special characters
    //  * NO automatic mapping or substitution
    //  */

    protected function getDestinationCity(): string
    {
        // Vendor checkout only — لا يوجد checkout عادي في هذا الفرع
        $sessionKey = 'vendor_step1_' . $this->vendorId;

        // التحقق من وجود بيانات step1 الخاصة بالمنتج لدى هذا البائع
        if (!Session::has($sessionKey)) {
            \Log::error('TryotoComponent: Vendor step1 session missing', [
                'vendor_id' => $this->vendorId,
                'session_keys'=> array_keys(Session::all())
            ]);
            throw new \Exception('Customer destination city is required for shipping calculation. Please complete Step 1 and select a city.');
        }

        $step1 = Session::get($sessionKey);

        // التحقق من وجود customer_city
        if (empty($step1['customer_city']) || !is_numeric($step1['customer_city'])) {
            \Log::error('TryotoComponent: Invalid or missing customer_city', [
                'vendor_id' => $this->vendorId,
                'customer_city' => $step1['customer_city'] ?? null
            ]);
            throw new \Exception('Customer destination city is required for shipping calculation. Please select a city.');
        }

        $cityId = $step1['customer_city'];

        // التحقق من أن المدينة موجودة فعلياً في قاعدة البيانات
        $city = \App\Models\City::find($cityId);

        if (!$city || empty($city->city_name)) {
            \Log::error('TryotoComponent: City not found in DB', [
                'vendor_id' => $this->vendorId,
                'city_id' => $cityId,
            ]);
            throw new \Exception("Destination city not found. (ID: {$cityId})");
        }

        \Log::info('TryotoComponent: Destination city resolved', [
            'vendor_id' => $this->vendorId,
            'city_id' => $cityId,
            'city_name' => $city->city_name,
            'session_key' => $sessionKey,
        ]);

        return $this->normalizeCityName($city->city_name);
    }

    protected function normalizeCityName(string $cityName): string
    {
        // Characters to remove/replace
        $charsToReplace = ['ā', 'ī', 'ū', 'ē', 'ō', 'Ā', 'Ī', 'Ū', 'Ē', 'Ō'];
        $replacements = ['a', 'i', 'u', 'e', 'o', 'A', 'I', 'U', 'E', 'O'];

        // Remove diacritics and special characters
        $normalized = str_replace($charsToReplace, $replacements, $cityName);

        // Remove apostrophes
        $normalized = str_replace("'", '', $normalized);

        // Trim whitespace
        $normalized = trim($normalized);

        // Log the normalization
        if ($normalized !== $cityName) {
            \Log::info('TryotoComponent: City name normalized', [
                'original' => $cityName,
                'normalized' => $normalized,
            ]);
        }

        return $normalized;
    }
}
