<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\NewCategory;
use App\Traits\NormalizesInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VehicleSearchApiController extends Controller
{
    use NormalizesInput;

    protected int $maxResults = 1000;

    /**
     * البحث بالاسم - اقتراحات
     */
    public function searchSuggestions(Request $request)
    {
        try {
            $query = trim($request->input('query', ''));
            $catalogCode = $request->input('catalog');
            $searchType = $request->input('type', 'number');

            if (mb_strlen($query, 'UTF-8') < 2) {
                return response()->json(['results' => []]);
            }

            $catalog = Catalog::where('code', $catalogCode)->first();
            if (!$catalog) {
                return response()->json(['error' => __('Catalog not found')], 404);
            }

            $allowedCodes = array_values(array_filter(
                array_map('strval', (array) session('preloaded_full_code', []))
            ));

            if (empty($allowedCodes)) {
                return response()->json(['results' => []]);
            }

            $results = $this->getLabelSuggestions($catalogCode, $query, $allowedCodes);

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Vehicle search suggestions error', ['error' => $e->getMessage()]);
            return response()->json(['error' => __('Search failed')], 500);
        }
    }

    /**
     * البحث الكامل - جلب النتائج
     */
    public function search(Request $request)
    {
        try {
            $query = trim($request->input('query', ''));
            $catalogCode = $request->input('catalog');
            $searchType = $request->input('type', 'number');

            $catalog = Catalog::with('brand')->where('code', $catalogCode)->first();
            if (!$catalog) {
                return response()->json(['error' => __('Catalog not found')], 404);
            }

            // التحقق من طول الاستعلام
            if ($searchType === 'number') {
                $cleanQuery = preg_replace('/[^0-9A-Za-z]+/', '', $query);
                if (strlen($cleanQuery) < 5) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Part number must be at least 5 characters')
                    ]);
                }
            } else {
                if (mb_strlen($query, 'UTF-8') < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Part name must be at least 2 characters')
                    ]);
                }
            }

            $allowedCodes = array_values(array_filter(
                array_map('strval', (array) session('preloaded_full_code', []))
            ));

            if (empty($allowedCodes)) {
                return response()->json([
                    'success' => false,
                    'message' => __('No allowed sections found')
                ]);
            }

            // جلب الكول آوتس
            $rows = $searchType === 'number'
                ? $this->fetchCalloutsByNumber($catalogCode, $query, $allowedCodes)
                : $this->fetchCalloutsByLabel($catalogCode, $query, $allowedCodes);

            // بناء الخيارات
            $calloutOptions = collect($rows)
                ->filter(fn($r) => !empty($r['part_callout']) && !empty($r['section_id']) && !empty($r['category_code']))
                ->map(fn($r) => [
                    'callout'       => $r['part_callout'],
                    'section_id'    => $r['section_id'],
                    'category_code' => $r['category_code'],
                    'label_ar'      => $r['part_label_ar'] ?? null,
                    'label_en'      => $r['part_label_en'] ?? null,
                    'qty'           => $r['part_qty'] ?? null,
                    'part_number'   => $r['part_number'] ?? null,
                ])
                ->unique(fn($o) => $o['callout'].'|'.$o['section_id'].'|'.$o['category_code'])
                ->filter(fn($o) => in_array($o['category_code'], $allowedCodes, true))
                ->values()
                ->all();

            // إثراء بالمفاتيح
            $calloutOptions = $this->enrichCalloutOptionsWithKeys($calloutOptions, $catalog);

            $count = count($calloutOptions);

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => $searchType === 'number'
                        ? __('No matching callout found for this part number')
                        : __('No matching callout found for this part name')
                ]);
            }

            // إذا نتيجة واحدة، أرجع الرابط مباشرة
            if ($count === 1) {
                $opt = $calloutOptions[0];
                if (!empty($opt['key1']) && !empty($opt['key2']) && !empty($opt['key3'])) {
                    // Return clean URL + callout info for sessionStorage approach
                    $cleanUrl = route('illustrations', [
                        'brand'   => $catalog->brand->name,
                        'catalog' => $catalog->code,
                        'key1'    => $opt['key1'],
                        'key2'    => $opt['key2'],
                        'key3'    => $opt['key3'],
                        'vin'     => session('vin'),
                    ]);

                    return response()->json([
                        'success' => true,
                        'single' => true,
                        'redirect_url' => $cleanUrl,
                        'callout_info' => [
                            'callout'       => $opt['callout'],
                            'section_id'    => $opt['section_id'],
                            'category_id'   => $opt['category_id'] ?? null,
                            'category_code' => $opt['category_code'],
                        ]
                    ]);
                }
            }

            // عدة نتائج - أرجعها للعرض
            $resultsWithUrls = array_map(function($opt) use ($catalog) {
                $url = null;
                if (!empty($opt['key1']) && !empty($opt['key2']) && !empty($opt['key3'])) {
                    $url = route('illustrations', [
                        'brand'         => $catalog->brand->name,
                        'catalog'       => $catalog->code,
                        'key1'          => $opt['key1'],
                        'key2'          => $opt['key2'],
                        'key3'          => $opt['key3'],
                        'vin'           => session('vin'),
                        'callout'       => $opt['callout'],
                        'auto_open'     => 1,
                        'section_id'    => $opt['section_id'],
                        'category_code' => $opt['category_code'],
                        'catalog_code'  => $catalog->code,
                        'category_id'   => $opt['category_id'] ?? null,
                    ]);
                }
                return array_merge($opt, ['url' => $url]);
            }, $calloutOptions);

            return response()->json([
                'success' => true,
                'single' => false,
                'count' => $count,
                'results' => $resultsWithUrls
            ]);

        } catch (\Exception $e) {
            Log::error('Vehicle search error', ['error' => $e->getMessage()]);
            return response()->json(['error' => __('Search failed')], 500);
        }
    }

    /**
     * جلب الاقتراحات بالاسم
     *
     * ✅ محسّن: استخدام LIKE prefix حيث ممكن + limit مبكر
     */
    protected function getLabelSuggestions(string $catalogCode, string $query, array $allowedCodes): array
    {
        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        $isArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $query);
        $targetColumn = $isArabic ? 'label_ar' : 'label_en';

        $normalized = $this->normalizeArabic($query);
        $words = array_values(array_filter(preg_split('/\s+/', trim($normalized))));
        if (empty($words)) {
            return [];
        }

        $base = DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes);

        // ✅ استخدام LIKE prefix للكلمة الأولى للاستفادة من الفهرس
        $firstWord = array_shift($words);
        $base->where(function ($q) use ($firstWord) {
            $q->where('p.label_en', 'like', "{$firstWord}%")
              ->orWhere('p.label_ar', 'like', "{$firstWord}%");
        });

        // ✅ باقي الكلمات تستخدم LIKE كامل
        foreach ($words as $w) {
            $like = "%{$w}%";
            $base->where(function ($q) use ($like) {
                $q->where('p.label_en', 'like', $like)
                  ->orWhere('p.label_ar', 'like', $like);
            });
        }

        return $base
            ->distinct()
            ->limit($this->maxResults)
            ->pluck("p.$targetColumn")
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * جلب الكول آوتس برقم القطعة
     *
     * ✅ محسّن: استعلام واحد مجمع بدلاً من استعلامين
     */
    protected function fetchCalloutsByNumber(string $catalogCode, string $query, array $allowedCodes): array
    {
        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $cleanQuery = preg_replace('/[^0-9A-Za-z]+/', '', $query ?? '');

        if (empty($cleanQuery) || empty($allowedCodes)) return [];

        // ✅ استعلام واحد مجمع: البحث + الفلترة في نفس الوقت
        return DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes)
            ->where(function ($q) use ($cleanQuery) {
                // استخدام LIKE prefix للاستفادة من الفهرس
                $q->where('p.part_number', 'like', "{$cleanQuery}%")
                  ->orWhere('p.callout', 'like', "{$cleanQuery}%");
            })
            ->select(
                'p.id as part_id',
                'p.part_number',
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.callout as part_callout',
                'p.qty as part_qty',
                's.id as section_id',
                's.full_code as category_code'
            )
            ->limit($this->maxResults * 5)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    /**
     * جلب الكول آوتس بالاسم
     *
     * ✅ محسّن: استعلام واحد مجمع بدلاً من استعلامين
     */
    protected function fetchCalloutsByLabel(string $catalogCode, string $query, array $allowedCodes): array
    {
        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        if (empty($query) || empty($allowedCodes)) return [];

        // ✅ استعلام واحد مجمع: البحث + الفلترة في نفس الوقت
        return DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes)
            ->where(function ($q) use ($query) {
                // استخدام LIKE prefix للاستفادة من الفهرس
                $q->where('p.label_en', 'like', "{$query}%")
                  ->orWhere('p.label_ar', 'like', "{$query}%");
            })
            ->select(
                'p.id as part_id',
                'p.part_number',
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.callout as part_callout',
                'p.qty as part_qty',
                's.id as section_id',
                's.full_code as category_code'
            )
            ->limit($this->maxResults * 5)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    /**
     * إثراء النتائج بالمفاتيح
     *
     * ✅ محسّن: استعلامات محسنة مع select محدد
     */
    protected function enrichCalloutOptionsWithKeys(array $options, $catalog): array
    {
        if (empty($options)) return [];

        $codes = collect($options)->pluck('category_code')->filter()->unique()->values()->all();

        if (empty($codes)) return $options;

        // ✅ استعلام محسّن مع select محدد
        $cats = NewCategory::query()
            ->where('catalog_id', $catalog->id)
            ->whereIn('full_code', $codes)
            ->select('id', 'full_code', 'parents_key', 'spec_key', 'Applicability')
            ->get()
            ->keyBy('full_code');

        $catIds = $cats->pluck('id')->filter()->unique()->values()->all();

        // ✅ استعلام محسّن للفترات
        $periods = collect();
        if (!empty($catIds)) {
            $periods = DB::table('category_periods')
                ->whereIn('category_id', $catIds)
                ->select(
                    'category_id',
                    DB::raw('MIN(begin_date) as begin_date'),
                    DB::raw('MAX(end_date) as end_date')
                )
                ->groupBy('category_id')
                ->get()
                ->keyBy('category_id');
        }

        return array_map(function (array $o) use ($cats, $periods) {
            $cat = $cats[$o['category_code']] ?? null;
            $catId = $cat->id ?? null;
            $p = $catId ? ($periods[$catId] ?? null) : null;

            return [
                ...$o,
                'key1'          => $cat->parents_key ?? null,
                'key2'          => $cat->spec_key ?? null,
                'key3'          => $o['category_code'],
                'category_id'   => $catId,
                'applicability' => $cat->Applicability ?? null,
                'cat_begin'     => $p->begin_date ?? null,
                'cat_end'       => $p->end_date ?? null,
            ];
        }, $options);
    }

    /**
     * بناء اسم الجدول الديناميكي
     */
    protected function dyn(string $base, string $catalogCode): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $catalogCode)) {
            throw new \Exception('Invalid catalog code');
        }
        return strtolower("{$base}_{$catalogCode}");
    }
}
