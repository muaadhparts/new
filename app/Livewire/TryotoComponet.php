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

        $this->authorize();
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
    protected function authorize(): void
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
        $this->emit('shipping-updated', ['vendorId' => $this->vendorId]);
        // // dd('emit:shipping-updated', $this->vendorId); // فحص
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
        $this->emit('shipping-updated', ['vendorId' => $this->vendorId]);
    }

    /**
     * Get origin city (vendor/warehouse city)
     */
    protected function getOriginCity(): string
    {
        // Default to Riyadh if no vendor-specific city is found
        $defaultCity = 'Riyadh';

        // If vendorId is available, try to get vendor's warehouse city
        if ($this->vendorId > 0) {
            $vendor = \App\Models\User::find($this->vendorId);

            // أولاً: نحاول الحصول على مدينة المستودع
            if ($vendor && !empty($vendor->warehouse_city)) {
                return $vendor->warehouse_city;
            }

            // ثانياً: إذا لم توجد مدينة المستودع، نحاول الحصول على مدينة المتجر من city_id
            if ($vendor && !empty($vendor->city_id)) {
                $city = \App\Models\City::find($vendor->city_id);
                if ($city && !empty($city->city_name)) {
                    return $city->city_name;
                }
            }
        }

        // Get from cached general settings or default
        return $this->generalSettings->shop_city ?? $defaultCity;
    }

    /**
     * Get destination city from customer data in session
     */
    protected function getDestinationCity(): string
    {
        // Default to Riyadh if no customer city is found
        $defaultCity = 'Riyadh';

        // Try to get customer city from step1 session data
        if (\Session::has('step1')) {
            $step1 = \Session::get('step1');

            // Check if customer_city exists in step1 data
            if (!empty($step1['customer_city'])) {
                $city = $step1['customer_city'];

                // If customer_city is a numeric ID, get the city name from database
                if (is_numeric($city)) {
                    $cityModel = \App\Models\City::find($city);
                    if ($cityModel && !empty($cityModel->city_name)) {
                        return $cityModel->city_name;
                    }
                }

                return $city;
            }
        }

        // If authenticated user, try to get from user profile
        if (\Auth::check()) {
            $user = \Auth::user();
            if (!empty($user->city)) {
                return $user->city;
            }
        }

        return $defaultCity;
    }
}
