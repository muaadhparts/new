<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class SearchBoxvin extends Component
{
    public string $query = '';
    public bool $isLoading = false;
    public bool $notFound = false;
    public string $userMessage = '';
    public bool $is_vin = false;
    public array $results = [];

    public function submitSearch()
    {
        // dd(['component' => 'SearchBoxvin', 'query' => $this->query]); // debug
        return $this->searchByVin();
    }

    /**
     * نفّذ البحث برقم الهيكل ثم وجّه مباشرة عند النجاح.
     */
    public function searchByVin()
    {
        $rawQuery = $this->query;
        $cleanVin = $this->cleanInput($rawQuery);

        if (strlen($cleanVin) >= 10) {
            $this->isLoading = true;
            $this->notFound = false;
            $this->userMessage = '';
            $this->is_vin = false;

            $result = $this->searchByVinQuery($cleanVin);

            if (!empty($result)) {
                // سيقوم هذا بإعادة التوجيه إلى الشجرة بعد حفظ الجلسة
                $this->isLoading = false;
                // dd(['redirecting_with' => $result]); // debug
                return $this->selectedVin(json_encode($result));
            } else {
                $this->isLoading = false;
                $this->notFound = true;
                $this->userMessage = __('VIN must be at least 14 characters and valid.');
                return null;
            }
        } else {
            $this->userMessage = 'رقم الهيكل يجب أن يحتوي على 14 خانات على الأقل ويكون صحيحًا.';
            $this->notFound = true;
            return null;
        }
    }

    public function selectedVin(string $value)
    {
        $data = json_decode($value);

        // ✅ تصفية رقم الهيكل قبل استخدامه
        $vin = $this->cleanInput($data->vin ?? null);

        if (!$vin) {
            abort(400, __('Invalid VIN value.'));
        }

        $vinData = DB::table('vin_decoded_cache')->where('vin', $vin)->first();

        if (!$vinData) {
            abort(404, __('VIN not found in cache.'));
        }

        $brandName = DB::table('brands')->where('id', $vinData->brand_id)->value('name');

        Session::put('vin', $vinData->vin);
        Session::put('current_catalog', json_decode(json_encode($vinData), true));

        // Livewire v2: emit | v3: dispatch. أبقينا emit كما كان لديك.
        $this->emit('vinSelected');

        return redirect()->route('tree.level1', [
            'id'   => $brandName,
            'data' => $vinData->catalogCode,
            'vin'  => $vinData->vin,
        ]);
    }

    public function searchByVinQuery(string $query): array
    {
        $vin = $this->cleanInput($query);
        $this->userMessage = '';
        $this->notFound = false;

        $vinData = $this->getVinAttributes($vin);

        if ($vinData) {
            $this->is_vin = true;
            $out = [
                'vin'         => $vin,
                'label_en'    => $vinData['shortName'],
                'data'        => $vinData['catalogCode'],
                'brand_name'  => DB::table('brands')->where('id', $vinData['brand_id'])->value('name') ?? '',
            ];
            // dd($out); // debug
            return $out;
        } else {
            $this->notFound = true;
            $this->userMessage = __('Please enter a valid VIN, or your vehicle may not be compatible with our specifications.');
            return [];
        }
    }

    private function cleanInput(?string $input): string
    {
        return strtoupper(preg_replace('/[\s\-.,]+/', '', trim((string) $input)));
    }

    /**
     * يعيد بيانات الـ VIN المهيكلة ويحفظها في الجلسة عند النجاح.
     * @return array|null
     */
    public function getVinAttributes(string $vin): ?array
    {
        // تحقق إن كان موجودًا مسبقًا
        $cached = DB::table('vin_decoded_cache')->where('vin', $vin)->first();

        if (!$cached) {
            $tokenModel = \App\Models\Token::valid() ?? \App\Services\NissanTokenService::refresh();

            if (!$tokenModel || !$tokenModel->accessToken) {
                abort(403, __('Unable to fetch valid Nissan access token.'));
            }

            $token = $tokenModel->accessToken;
            $cookie = \App\Models\NissanCredential::first()->cookie ?? '';

            $headers = [
                'Authorization'     => $token,
                'cookie'            => $cookie,
                'x-ifm-sid'         => config('nissan.sid'),
                'x-ifm-franchise'   => config('nissan.franchise'),
                'referer'           => config('nissan.referer'),
                'user-agent'        => config('nissan.user_agent'),
            ];

            // dd([
            //     'Authorization' => $headers['Authorization'],
            //     'cookie' => $headers['cookie'],
            //     'x-ifm-sid' => $headers['x-ifm-sid'],
            //     'x-ifm-franchise' => $headers['x-ifm-franchise'],
            //     'referer' => $headers['referer'],
            //     'user-agent' => $headers['user-agent'],
            // ]);

            $response = Http::withHeaders($headers)->get(
                'https://microcat-apac.superservice.com/ver/microcat/epc-html/v3/vehicle/search',
                [
                    'searchTerm'   => $vin,
                    'market'       => 'SA',
                    'language'     => 'en',
                    'dataLanguage' => 'en',
                ]
            );

            // dd([
            //     'status' => $response->status(),
            //     'body' => $response->body(),
            //     'json' => $response->json(),
            // ]);

            // dd($response->json());
            if ($response->successful() && $data = $response->json('vehicles.0')) {
                $brand        = $data['catalogBrand'] ?? $data['brand'];
                $catalogCode  = $data['catalogCode'];
                $modelCode    = $data['model']['code'] ?? null;
                $buildDate    = $this->normalizeDate($data['buildDate'] ?? null);
                $beginDate    = $this->normalizeDate($data['model']['beginDate'] ?? null);
                $endDate      = $this->normalizeDate($data['model']['endDate'] ?? null);
                $shortName    = $data['shortName'];
                $catalogType  = $data['catalogType'];
                $dataRegion   = $data['dataRegion'] ?? null;
                $market       = $data['catMarket'] ?? null;
                $vehicleType  = $data['nmc_vehicleType'] ?? null;
                $majorAttributes = $data['majorAttributes'] ?? [];

                $brand_id   = DB::table('brands')->where('name', $brand)->value('id');
                $region_id  = DB::table('brand_regions')->where('brand_id', $brand_id)->where('code', $dataRegion)->value('id');
                $catalog_id = DB::table('catalogs')->where('code', $catalogCode)->value('id');

                $vin_model_id = null;
                if (!empty($majorAttributes['MODEL'])) {
                    $modelCodeAttr = $majorAttributes['MODEL'];
                    DB::table('vin_models')->updateOrInsert(['model_code' => $modelCodeAttr], ['description' => $modelCodeAttr]);
                    $vin_model_id = DB::table('vin_models')->where('model_code', $modelCodeAttr)->value('id');
                }

                DB::table('vin_decoded_cache')->updateOrInsert(['vin' => $vin], [
                    'brand_id'         => $brand_id,
                    'brand_region_id'  => $region_id,
                    'catalog_id'       => $catalog_id,
                    'catalogCode'      => $catalogCode,
                    'modelCode'        => $modelCode,
                    'buildDate'        => $buildDate,
                    'modelBeginDate'   => $beginDate,
                    'modelEndDate'     => $endDate,
                    'shortName'        => $shortName,
                    'catalogType'      => $catalogType,
                    'dataRegion'       => $dataRegion,
                    'catMarket'        => $market,
                    'nmc_vehicleType'  => $vehicleType,
                    'vin_model_id'     => $vin_model_id,
                    'raw_json'         => json_encode($data),
                ]);

                $vin_id = DB::table('vin_decoded_cache')->where('vin', $vin)->value('id');

                foreach ($majorAttributes as $attr_code => $attr_value) {
                    DB::table('vin_spec_attributes')->insertOrIgnore([
                        'vin' => $vin,
                        'attribute_code' => $attr_code,
                        'attribute_value' => $attr_value
                    ]);

                    $spec_id = DB::table('specifications')->where('name', $attr_code)->value('id');
                    if ($spec_id && $catalog_id) {
                        $spec_item_id = DB::table('specification_items')
                            ->where('specification_id', $spec_id)
                            ->where('catalog_id', $catalog_id)
                            ->whereRaw('TRIM(value_id) = ?', [trim($attr_value)])
                            ->value('id');

                        if ($spec_item_id) {
                            DB::table('vin_spec_mapped')->updateOrInsert(
                                ['vin_id' => $vin_id, 'specification_id' => $spec_id],
                                ['specification_item_id' => $spec_item_id]
                            );
                        }
                    }
                }

                // 🟢 تحميل البيانات الجديدة من قاعدة البيانات بعد الحفظ
                $cached = DB::table('vin_decoded_cache')->where('vin', $vin)->first();
            }
        }

        if ($cached) {
            $attributes = DB::table('vin_spec_mapped as vsm')
                ->join('specifications as s', 's.id', '=', 'vsm.specification_id')
                ->join('specification_items as si', 'si.id', '=', 'vsm.specification_item_id')
                ->where('vsm.vin_id', $cached->id)
                ->pluck('si.value_id', 's.name')
                ->toArray();

            $structured = [];
            foreach ($attributes as $key => $value) {
                $structured[$key] = [
                    'value_id' => $value,
                    'source' => 'vin'
                ];
            }

            if (!empty($cached->buildDate)) {
                [$year, $month] = explode('-', $cached->buildDate);
                $structured['year'] = ['value_id' => $year, 'source' => 'vin'];
                $structured['month'] = ['value_id' => str_pad($month, 2, '0', STR_PAD_LEFT), 'source' => 'vin'];
            }

            Session::put('vin', $vin);
            Session::put('selected_filters', $structured);
            Session::put('current_catalog', json_decode(json_encode($cached), true));

            return [
                'source'      => 'api',
                'vin'         => $vin,
                'brand_id'    => $cached->brand_id,
                'catalogCode' => $cached->catalogCode,
                'shortName'   => $cached->shortName,
                'modelCode'   => $cached->modelCode,
                'attributes'  => $structured,
            ];
        }

        return null;
    }

    private function normalizeDate($date)
    {
        if (!$date) return null;
        if (preg_match('/^\d{8}$/', $date)) {
            return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        } elseif (preg_match('/^\d{6}$/', $date)) {
            return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-01';
        }
        return null;
    }

    public function render()
    {
        // dd('render: SearchBoxvin'); // debug
        return view('livewire.search-boxvin');
    }
}
