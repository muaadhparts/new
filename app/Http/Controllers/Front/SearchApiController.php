<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CatalogSessionManager;
use App\Traits\NormalizesInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SearchApiController extends Controller
{
    use NormalizesInput;

    protected CatalogSessionManager $sessionManager;

    public function __construct(CatalogSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * البحث برقم القطعة أو اسمها
     */
    public function searchPart(Request $request)
    {
        $query = trim($request->input('query', ''));

        if (strlen($query) < 2) {
            return response()->json(['results' => [], 'message' => __('Enter at least 2 characters')]);
        }

        $results = $this->searchByPartQuery($query);

        return response()->json([
            'success' => true,
            'results' => $results,
            'count' => count($results)
        ]);
    }

    /**
     * البحث برقم الهيكل VIN
     */
    public function searchVin(Request $request)
    {
        $query = trim($request->input('query', ''));
        $cleanVin = $this->cleanInput($query);

        if (strlen($cleanVin) < 10) {
            return response()->json([
                'success' => false,
                'message' => __('VIN must be at least 10 characters.')
            ]);
        }

        $result = $this->searchByVinQuery($cleanVin);

        if (!empty($result)) {
            return response()->json([
                'success' => true,
                'result' => $result,
                'is_vin' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('VIN not found. Please check and try again.')
        ]);
    }

    /**
     * اختيار VIN والتوجيه
     */
    public function selectVin(Request $request)
    {
        $vin = $this->cleanInput($request->input('vin', ''));

        if (!$vin) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid VIN value.')
            ], 400);
        }

        $vinData = DB::table('vin_decoded_cache')->where('vin', $vin)->first();

        if (!$vinData) {
            return response()->json([
                'success' => false,
                'message' => __('VIN not found in cache.')
            ], 404);
        }

        $brandName = DB::table('brands')->where('id', $vinData->brand_id)->value('name');

        if (!$brandName || !$vinData->catalogCode) {
            return response()->json([
                'success' => false,
                'message' => __('Vehicle brand or catalog not found.')
            ], 404);
        }

        // حفظ في الجلسة
        $this->sessionManager->setVin($vinData->vin);
        $this->sessionManager->setCurrentCatalog(json_decode(json_encode($vinData), true));

        // بناء رابط التوجيه
        $redirectUrl = route('tree.level1', [
            'brand'   => $brandName,
            'catalog' => $vinData->catalogCode,
            'vin'     => $vinData->vin,
        ]);

        return response()->json([
            'success' => true,
            'redirect_url' => $redirectUrl
        ]);
    }

    /**
     * البحث في جدول المنتجات
     */
    protected function searchByPartQuery(string $query): array
    {
        $sku = $this->cleanInput($query);

        // البحث بالرقم (prefix-only)
        $results = Product::where('sku', 'like', "{$sku}%")
            ->select('id', 'sku', 'label_en', 'label_ar')
            ->limit(50)
            ->get();

        // fallback → البحث بالاسم
        if ($results->isEmpty()) {
            $results = $this->searchByPartNameQuery($query);
        }

        return $results->toArray();
    }

    /**
     * البحث بالاسم
     */
    private function searchByPartNameQuery(string $query)
    {
        $normalized = $this->normalizeArabic($query);
        $words = array_filter(preg_split('/\s+/', trim($normalized)));

        if (empty($words)) {
            return collect();
        }

        for ($i = count($words); $i > 0; $i--) {
            $subset = array_slice($words, 0, $i);

            $results = Product::query()
                ->where(function ($q) use ($subset) {
                    foreach ($subset as $word) {
                        $word = trim($word);
                        if ($word === '') continue;

                        $q->where(function ($sub) use ($word) {
                            $sub->where('label_ar', 'like', "%{$word}%")
                                ->orWhere('label_en', 'like', "%{$word}%");
                        });
                    }
                })
                ->select('id', 'sku', 'label_en', 'label_ar')
                ->limit(50)
                ->get();

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }

    /**
     * البحث عن VIN
     */
    public function searchByVinQuery(string $query): array
    {
        $vin = $this->cleanInput($query);

        $vinData = $this->getVinAttributes($vin);

        if ($vinData) {
            return [
                'vin'        => $vin,
                'label_en'   => $vinData['shortName'],
                'data'       => $vinData['catalogCode'],
                'brand_name' => DB::table('brands')->where('id', $vinData['brand_id'])->value('name') ?? '',
            ];
        }

        return [];
    }

    /**
     * جلب بيانات VIN من الكاش أو API
     */
    public function getVinAttributes(string $vin): ?array
    {
        $cached = DB::table('vin_decoded_cache')->where('vin', $vin)->first();

        if (!$cached) {
            // جلب من Nissan API
            $cached = $this->fetchFromNissanApi($vin);
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
                $parts = explode('-', $cached->buildDate);
                if (count($parts) >= 2) {
                    $structured['year'] = ['value_id' => $parts[0], 'source' => 'vin'];
                    $structured['month'] = ['value_id' => str_pad($parts[1], 2, '0', STR_PAD_LEFT), 'source' => 'vin'];
                }
            }

            $this->sessionManager->setVin($vin);
            $this->sessionManager->setSelectedFilters($structured);
            $this->sessionManager->setCurrentCatalog(json_decode(json_encode($cached), true));

            return [
                'source'      => 'cache',
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

    /**
     * جلب من Nissan API
     */
    protected function fetchFromNissanApi(string $vin)
    {
        try {
            $tokenModel = \App\Models\Token::valid() ?? \App\Services\NissanTokenService::refresh();

            if (!$tokenModel || !$tokenModel->accessToken) {
                return null;
            }

            $token = $tokenModel->accessToken;
            $cookie = \App\Models\NissanCredential::first()->cookie ?? '';

            $headers = [
                'Authorization'   => $token,
                'cookie'          => $cookie,
                'x-ifm-sid'       => config('nissan.sid'),
                'x-ifm-franchise' => config('nissan.franchise'),
                'referer'         => config('nissan.referer'),
                'user-agent'      => config('nissan.user_agent'),
            ];

            $response = Http::withHeaders($headers)->get(
                'https://microcat-apac.superservice.com/ver/microcat/epc-html/v3/vehicle/search',
                [
                    'searchTerm'   => $vin,
                    'market'       => 'SA',
                    'language'     => 'en',
                    'dataLanguage' => 'en',
                ]
            );

            if ($response->successful() && $data = $response->json('vehicles.0')) {
                return $this->saveVinToCache($vin, $data);
            }
        } catch (\Exception $e) {
            \Log::error('Nissan API Error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * حفظ VIN في الكاش
     */
    protected function saveVinToCache(string $vin, array $data)
    {
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

        return DB::table('vin_decoded_cache')->where('vin', $vin)->first();
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
}
