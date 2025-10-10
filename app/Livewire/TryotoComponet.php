<?php

// namespace App\Livewire;

// use App\Models\Cart;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Session;
// use Livewire\Component;

// class TryotoComponet extends Component
// {

//     public array $products;
//     protected $token ;
//     protected $weight =100 ;
//     public array $deliveryCompany;
// //    protected $url ;
//     public function mount()
//     {
//         if (!config('services.tryoto.sandbox')) {
//             $this->url = config('services.tryoto.live.url');
//             $this->_token = config('services.tryoto.live.token');
//             $this->_webhook = route('tryoto.callback'); // comes from package route.
//         }
//         else {
//             $this->url = config('services.tryoto.test.url');
//             $this->_token = config('services.tryoto.test.token');
//             $this->_webhook = 'https://request-dinleyici-url-buraya-yazilmali';
//         }


// //        $this->token = "Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6ImE3MWI1MTU1MmI0ODA5OWNkMGFkN2Y5YmZlNGViODZiMDM5NmUxZDEiLCJ0eXAiOiJKV1QifQ.eyJjb21wYW55SWQiOiIzMjk4MiIsImNsaWVudFR5cGUiOiJGcmVlUGFja2FnZSIsIm1hcmtldFBsYWNlTmFtZSI6Im90b2FwaSIsInVzYWdlTW9kZSI6InJlYWwiLCJzdG9yZU5hbWUiOiJwYXJ0c3RvcmUuc2EiLCJ1c2VyVHlwZSI6InNhbGVzQ2hhbm5lbCIsInNjY0lkIjoiMTAzMjkiLCJlbWFpbCI6IjMyOTgyLTEwMzI5LW90b2FwaUB0cnlvdG8uY29tIiwiaXNzIjoiaHR0cHM6Ly9zZWN1cmV0b2tlbi5nb29nbGUuY29tL290by1yZXN0LWFwaSIsImF1ZCI6Im90by1yZXN0LWFwaSIsImF1dGhfdGltZSI6MTcwNjg0OTg5OSwidXNlcl9pZCI6ImVZNTViTkdsRFpTb281dUxkeVBZMTF6b0tSYzIiLCJzdWIiOiJlWTU1Yk5HbERaU29vNXVMZHlQWTExem9LUmMyIiwiaWF0IjoxNzM1MDgyMDEyLCJleHAiOjE3MzUwODU2MTIsImVtYWlsX3ZlcmlmaWVkIjpmYWxzZSwiZmlyZWJhc2UiOnsiaWRlbnRpdGllcyI6eyJlbWFpbCI6WyIzMjk4Mi0xMDMyOS1vdG9hcGlAdHJ5b3RvLmNvbSJdfSwic2lnbl9pbl9wcm92aWRlciI6InBhc3N3b3JkIn19.qmP6GpPaNDYym2lGSmdIG0OMQcp3h7MCUgWOcIcWSe5enT0y4VG6O1CKdUcS9n0aEZJXp-2f0gp-a8CJm1ZGj5PlA7b-0ts1DaROfavmmczNbJTeuyh4kWnwc2l5nmhuqKRX3976_wq72g8iWNsZ6kUpDi3JFahD14IgAJAG02ruiojd2NKBbYPFNlp2ArDKE_8MZ5EVw8Y4jbemEEAxRKheJgVbIyPdnHCr_u0HiSe_TmsD4anLyXWf7lfPmOCnCAD4FUwRiFMbA1cI0-nSS23dvIRXlE7QDKr9kFdsJuCsQm2Rrlg5E59IBIcGwCgZMPhBy6csHLXwIxSiEfZUVw";
//         $this->authorize();
//         $this->getWeight();
//         $this->checkOTODeliveryFee();
// //        dump($this,$this->token);
//     }

//     public function render()
//     {
// //        $this->authorize();
// //        $this->checkOTODeliveryFee();

// //        dd($this ,$this->products ,collect($this->products) );

//         return view('livewire.tryoto-componet');
//     }

//     /**
//      * @return void
//      */
//     public function getWeight(): void
//     {
//         $this->weight = 0;

//         // Calculate weight_total for each product and update total weight
//         foreach ($this->products as $index => $product) {
//             $product['weight_total'] = $product['qty'] * $product['item']['weight'];
//             $this->products[$index] = $product;
//             $this->weight += $product['weight_total'];
//         }

//     }

//     protected function authorize()
//     {


// //    dd($this->url );
//         $response = Http::post($this->url . '/rest/v2/refreshToken', [
//             'refresh_token' => env('TRYOTO_REFRESH_TOKEN'),
//         ]);

//         if($response->successful()) {
//             $token =$response->json()['access_token'];

//             Cache::put('tryoto-token', $token, now()->addMinutes($response->json()['expires_in']));

//             $this->token = $token;
// //            dd($this->token);
// //            return $token;

//         }


//     }

//     public function selectedOption($price)
//     {
//         $shipping = explode('#',$price);
//         $price = $shipping[0];
//         $shipping_company  = $shipping[1];
//         $oldCart = Session::get('cart');
// //        $oldCart->totalPrice = $oldCart->totalPrice + $price;
// //        $oldCart->shipping_name = $shipping_company;
// //        $oldCart->shipping_cost = $price;
// //        $cart = new Cart($oldCart);
// //        $cart = Session::get('cart');

// //        $shipping

// //            Session::get('cart') ;
// //        dd(explode('#',$price) ,$cart ,Session::get('cart') );
//     }
//     protected function checkOTODeliveryFee()
//     {
// //        dd($this->token);
//         $response = Http::withToken($this->token)->post($this->url .'/rest/v2/checkOTODeliveryFee', [
//              "originCity"=> "Riyadh",
//              "destinationCity"=> "Riyadh",
//              "weight"=> $this->weight,
// //            "xheight"=> 30,
// //            "xwidth" => 30,
// //            "xlength"=> 30

//         ]);

// //        dd($response ,$response->json());

//         if(!$response->successful()) {

//             throw new \Exception('Unable to get shipping token');

//         }

//             $this->deliveryCompany = $response->json()['deliveryCompany'];
// //            $token = 'Bearer ' . $response->json()['access_token'];

//     }

// }

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
            throw new \Exception('Failed to get Tryoto access token: ' . $response->body());
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

        $response = Http::withToken($this->token)->post($this->url . '/rest/v2/checkOTODeliveryFee', [
            "originCity"      => $originCity,
            "destinationCity" => $destinationCity,
            "weight"          => $dimensions['weight'],
            "xlength"         => max(30, $dimensions['length']), // Minimum 30cm or calculated length
            "xheight"         => max(30, $dimensions['height']), // Minimum 30cm or calculated height
            "xwidth"          => max(30, $dimensions['width']),  // Minimum 30cm or calculated width
        ]);

        if (!$response->successful()) {
            throw new \Exception('Unable to get shipping token');
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
                return $step1['customer_city'];
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
