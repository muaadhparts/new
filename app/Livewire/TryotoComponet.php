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
    public array $products = [];
    public array $deliveryCompany = [];

    public int $vendorId = 0;

    protected $token;
    protected $weight = 100;
    protected string $url;
    protected string $_token;
    protected string $_webhook;

    public function mount(array $products, int $vendorId = 0)
    {
        $this->products = $products;
        $this->vendorId = $vendorId;

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
    }

    public function render()
    {
        return view('livewire.tryoto-componet');
    }

    public function getWeight(): void
    {
        $this->weight = 0;
        foreach ($this->products as $index => $product) {
            $product['weight_total'] = $product['qty'] * $product['item']['weight'];
            $this->products[$index] = $product;
            $this->weight += $product['weight_total'];
        }
    }

    protected function authorize(): void
    {
        $response = Http::post($this->url . '/rest/v2/refreshToken', [
            'refresh_token' => env('TRYOTO_REFRESH_TOKEN'),
        ]);

        if ($response->successful()) {
            $token = $response->json()['access_token'];
            Cache::put('tryoto-token', $token, now()->addMinutes($response->json()['expires_in']));
            $this->token = $token;
        }
    }

    public function selectedOption(string $value): void
    {
        // value = "deliveryOptionId#CompanyName#price"
        $parts = explode('#', $value);
        $company = $parts[1] ?? '';
        $price   = (float)($parts[2] ?? 0);

        // بثّ الحدث للواجهة
        $this->emit('shipping-updated', ['vendorId' => $this->vendorId]);
    }

    protected function checkOTODeliveryFee(): void
    {
        $response = Http::withToken($this->token)->post($this->url . '/rest/v2/checkOTODeliveryFee', [
            "originCity"      => "Riyadh",
            "destinationCity" => "Riyadh",
            "weight"          => $this->weight,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Unable to get shipping token');
        }

        $this->deliveryCompany = $response->json()['deliveryCompany'] ?? [];

        // إعلان مبدئي لتحديث نص الشحن على أول خيار
        $this->emit('shipping-updated', ['vendorId' => $this->vendorId]);
    }
}
