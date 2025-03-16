<?php

namespace App\Livewire;

use App\Jobs\getCatalogModelJob;
use App\Models\Catalog;
use App\Models\CatalogModel;
use App\Models\Product;
use App\Models\Token;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Component;

class SearchBox extends Component
{

    public $query = '';
    public bool  $is_vin = false;
    public $results = [];

    public function updatedQuery()
    {
        $this->results = $this->search($this->query);
    }

    private function convertLettersToNumbers($input)
    {
        // خريطة التحويل
        $mapping = [
            'M' => '0', 'A' => '1', 'B' => '2', 'C' => '3',
            'D' => '4', 'E' => '5', 'F' => '6', 'G' => '7',
            'H' => '8', 'K' => '9'
        ];

        // تقسيم الإدخال إلى أول 5 أحرف والباقي
        $firstFive = substr($input, 0, 5);
        $remaining = substr($input, 5);

        // استبدال الحروف في أول 5 أحرف
        $converted = strtr($firstFive, $mapping);

        // دمج الأجزاء
        return $converted . $remaining;
    }



    private function cleanInput($input)
    {
        // إزالة المسافات الزائدة
        $input = trim($input);


        // إزالة الفواصل والمسافات الزائدة داخل النص
        $input = preg_replace('/\s+/', ' ', $input);

        // تحويل الأرقام العربية إلى إنجليزية
//        $input = strtr($input, [
//            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
//            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'
//        ]);

        return   $this->convertLettersToNumbers($input);
        // تحويل النصوص إلى أحرف صغيرة للتوحيد
//        return Str::lower($input);
    }

    public function xgetVinDecodec($slug)
    {

//
// Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJFNFI3NWp0N1ZGbzUzZ3pzdTF3a1M3aUxqYngzSTJZTiIsInZmX2lmbSI6dHJ1ZSwiaXNzIjoiaHR0cHM6Ly9hcGkuc3VwZXJzZXJ2aWNlLmNvbSIsIlgtSUZNLVVJRCI6ImF0d2pyeS50QGdtYWlsLmNvbSIsIlgtSUZNLUNPVU5UUlkiOiJTQSIsImV4cCI6MTczNTUwODMxNywiaWF0IjoxNzM1NDM2MzE3fQ.J9L_6qC3xNY44fIm_p6iTtBNKnsVVTk988o4nGP0MV8
//  "refreshToken": "d78EPXIldn4GvuAEfNU5Ri4rm3R4GrhiJkGdw3jsK1oLKk1363HFcfr3ma9Umprm",
//  "expiresInSeconds": 72000
        // if (Str::length($slug) != 17) {
        //     Toastr::error(__('common.erroer'), __('common.vin must be 17 letters'));
        //     return redirect()->back();
        // }
        Session::forget('CatalogModel');
        getCatalogModelJob::dispatchSync($slug);
//        $response = decodeVin($slug);

        $CatalogModel = Session::get('CatalogModel');
//        dd($CatalogModel);
        if (!$CatalogModel) {

            Toastr::error(__('common.notfound'), __('common.notfound'));
            return redirect()->back();
        }

//        dd($CatalogModel);
        $catlog = Catalog::findOrFail($CatalogModel->catlog_id);
        return redirect()->route('catlog.vin.show', $catlog->id);


        // return view(theme('pages.parent_category'), compact('categories', 'vin_disp'));

    }


    public function search($query)
    {

        $slug = str_replace('-', '',$query);
        $slug = Str::upper($slug);
        $length = Str::length($slug);

//        dd($length);

        if ($length > 14) {

           $query =   $this->getVinDecode($slug);

            $this->is_vin = true;
            $CatalogModel = Session::get('CatalogModel');
//            to_route('tree.level1',['slug'=>$data['slug']]);
            return   $query;
//            return    redirect()->route('tree.level1',$query);
//            dd($query ,$slug,$CatalogModel);
//            redirect()->route('search.result', ['sku' => $value]);

//            dd($query ,$slug);

        }
        $query = $this->cleanInput($query);



        $results = \App\Models\Product::where('sku', 'like', "{$query}%")
            ->orWhere('name', 'like', "{$query}%")
            ->orWhere('label_en', 'like', "{$query}%")
            ->orWhere('label_ar', 'like', "{$query}%")
            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
            ->limit(50)
            ->get();

        // إذا لم تكن هناك نتائج، البحث داخل النص
        if ($results->isEmpty()) {
            $results = \App\Models\Product::where('sku', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%")
                ->orWhere('label_en', 'like', "%{$query}%")
                ->orWhere('label_ar', 'like', "%{$query}%")
                ->select('id', 'sku', 'name', 'label_en', 'label_ar')
                ->limit(50)
                ->get();
        }

        return $results;
//        return \App\Models\Product::where('sku', 'like', "{$query}%")
//            ->orWhere('name', 'like', "{$query}%")
//            ->orWhere('label_en', 'like', "{$query}%")
//            ->orWhere('label_ar', 'like', "{$query}%")
//            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
//            ->limit(50)
//            ->get();

//        return \App\Models\Product::Where('sku', 'like', "%{$query}%")
//            ->orwhere('name', 'like', "%{$query}%")
//            ->orwhere('label_en', 'like', "%{$query}%")
//            ->orwhere('label_ar', 'like', "%{$query}%")
//            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
//            ->limit(50)
//            ->get();
//            ->limit(10)
//            ->get(['sku as value', 'name as key'])
//            ->toArray();
    }




    public function selectItem($value)
    {
//        dd($value);
        redirect()->route('search.result', ['sku' => $value]);
//        $this->query = $value;

//        $this->results = [];
    }


    public function selectedVin($value)
    {

        $data = json_decode($value);
//            dd($data);
        $CatalogModel =  Session::get('CatalogModel');
//        dd($data ,$CatalogModel ,['id'=> $CatalogModel->brand->name ,'data'=> $data->data , 'vin'  =>$CatalogModel->vin ]);
          redirect()->route('tree.level1',['id'=> $CatalogModel->brand->name ,'data'=> $data->data  , 'vin'  =>$CatalogModel->vin ]);

//      return  redirect()->route('tree.level1',$data);


//        redirect()->route('search.result', ['sku' => $value]);
//
    }



    protected static function getVinDecode(String $vin)
    {
        $token = Token::latest()->first();


//    dd($vin ,$token ,$catlog);
        $Cookie = "languages=en-US,en; ifm-device=2788b770-9fdc-43e0-8383-ef467c0e8310; language=languageCode=en-US; browserSessionId=843b471c-fa77-4e45-a865-2d3f71c9ef0d; AWSELB=F3C7258D1EC172B06C97FDA8FA581AFF2D5D2F8413AE42C8FC6207D1F7AFBD6F74EE2A2AAE5EAD76B1B8E3292DCA86ADB9931D3047B71B329BA0A6969239074C99BFB08B11; AWSELBCORS=F3C7258D1EC172B06C97FDA8FA581AFF2D5D2F8413AE42C8FC6207D1F7AFBD6F74EE2A2AAE5EAD76B1B8E3292DCA86ADB9931D3047B71B329BA0A6969239074C99BFB08B11; Authorization=Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJFNFI3NWp0N1ZGbzUzZ3pzdTF3a1M3aUxqYngzSTJZTiIsInZmX2lmbSI6dHJ1ZSwiaXNzIjoiaHR0cHM6Ly9hcGkuc3VwZXJzZXJ2aWNlLmNvbSIsIlgtSUZNLVVJRCI6ImF0d2pyeS50QGdtYWlsLmNvbSIsIlgtSUZNLUNPVU5UUlkiOiJTQSIsImV4cCI6MTczNTUwODI0MSwiaWF0IjoxNzM1NDM2MjQxfQ.1A4Z4u-uWeUq_-Z8dr85JXSVTAEW32KgXl11LiRtbWc";

        $response =   Http::withHeaders([
            'authority' => 'microcat-apac.superservice.com',
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en-US,en;q=0.9',
            'authorization' => $token->accessToken ?? '',
//            'authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJFNFI3NWp0N1ZGbzUzZ3pzdTF3a1M3aUxqYngzSTJZTiIsInZmX2lmbSI6dHJ1ZSwiaXNzIjoiaHR0cHM6Ly9hcGkuc3VwZXJzZXJ2aWNlLmNvbSIsIlgtSUZNLVVJRCI6ImF0d2pyeS5zQGdtYWlsLmNvbSIsIlgtSUZNLUNPVU5UUlkiOiJTQSIsImV4cCI6MTcwNzE3MzYyMiwiaWF0IjoxNzA3MTAxNjIyfQ.qB7VBJ-u44R90UqOzuvHvI_AjiXJ6mZt-HwSO3h4f8I',
            'content-type' => 'application/x-www-form-urlencoded',
            'cookie' => $Cookie,
//            'cookie' => '_ga=GA1.2.505264364.1706580959; browserSessionId=54832a21-d5cc-4a2e-9093-988f612ddc42; ifm-device=1d6fce60-72b4-4ac4-a554-faab46c70095; settings.new-grid=no; _gid=GA1.2.1440260172.1707101611; _gat=1; _ga_N5E6KFM1GP=GS1.2.1707101612.19.1.1707101613.0.0.0; Authorization=Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJFNFI3NWp0N1ZGbzUzZ3pzdTF3a1M3aUxqYngzSTJZTiIsInZmX2lmbSI6dHJ1ZSwiaXNzIjoiaHR0cHM6Ly9hcGkuc3VwZXJzZXJ2aWNlLmNvbSIsIlgtSUZNLVVJRCI6ImF0d2pyeS5zQGdtYWlsLmNvbSIsIlgtSUZNLUNPVU5UUlkiOiJTQSIsImV4cCI6MTcwNzE3MzYyMiwiaWF0IjoxNzA3MTAxNjIyfQ.qB7VBJ-u44R90UqOzuvHvI_AjiXJ6mZt-HwSO3h4f8I',
            'newrelic' => 'eyJ2IjpbMCwxXSwiZCI6eyJ0eSI6IkJyb3dzZXIiLCJhYyI6Ijc0NTI0IiwiYXAiOiIzMzQ2ODM4MSIsImlkIjoiOTk3NjI2NzA3YTBlNzM5YSIsInRyIjoiNWVjMjFmYWRlOTg3MjRlMmUyNTY5NGI2YzQwMzg2YTMiLCJ0aSI6MTczNTQzNjI0NjIyM319',
            'referer' => 'https://microcat-apac.superservice.com/content/microcat-epc/',
            'sec-ch-ua' => '"Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"macOS"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'traceparent' => '00-5ec21fade98724e2e25694b6c40386a3-997626707a0e739a-01',
            'tracestate' => '74524@nr=0-1-74524-33468381-997626707a0e739a----1735436246223',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:133.0) Gecko/20100101 Firefox/133.0',
            'x-ifm-encoding' => 'gzip, deflate, br',
            'x-ifm-franchise' => 'NMC',
            'x-ifm-sid' => 'DYN000000001207CF2',
        ])
            ->get('https://microcat-apac.superservice.com/ver/microcat/epc-html/v3/vehicle/search', [
//                'searchTerm' => 'JNKAY1AP7BM200155',
//                'searchTerm' => 'MDHBN7AD7DG021302',
                'searchTerm' =>$vin,
                'market' => 'SA',
                'language' => 'en',
                'dataLanguage' => 'en',
            ]);


        if($response->successful() && !empty($response->json())  ) {

//            dd($response->json()  ,count($response->json()['vehicles']));
            if(count($response->json()['vehicles']) >0){




                $data = $response->json()['vehicles'][0];
                $catalog = Catalog::where('data',$data['catalogCode'])->first();
//            dd($data ,$catalog);

                $CatalogModel = CatalogModel::with('brand')->firstOrCreate(['vin' => $data['vin']], [

                    'catlog_id' => $catalog->id,
                    'brand_id' => $catalog->brand_id,
                    'name' => $data['shortName'],
                    'public_id' => $data['id'],
//                'vin' => $data['vin'],

                    'model_code' => $data['model']['code'],
                    'beginDate' => $data['model']['beginDate']??'',
                    'endDate' => $data['model']['endDate'] ?? '',
                    'year' => $data['model']['year'] ?? '',
                    'majorAttributes' => $data['majorAttributes'],
                    'vehicleDescription' => $data['vehicleDescription'],


                    'meta' => $data

                ]);

                Session::put('CatalogModel',$CatalogModel);
            }

//            Session::get('CatalogModel')
//            to_route('tree.level1',['slug'=>$data['slug']]);
            return  ['id'=> $CatalogModel->brand->name ,'data'=> $catalog->data ,
                'vin'  =>$CatalogModel->vin ,
                'sku'  =>$CatalogModel->vin ,
                'label_en' => $CatalogModel->name
            ];
//            return route('tree.level1',['id'=> $CatalogModel->brand->name ,'data'=> $catalog->data , 'vin'  =>$CatalogModel->vin ]);
//            dd($CatalogModel ,Session::get('CatalogModel'));
//            return redirect()->back();
        }

//        dd($response->json() ,$response);


//        "https://microcat-apac.superservice.com/ver/microcat/epc-html/v3/vehicle/search?searchTerm=JNKAY1AP7BM200155&market=SA&language=en&dataLanguage=en"

    }

    public function render()
    {
        return view('livewire.search-box');
    }
}
