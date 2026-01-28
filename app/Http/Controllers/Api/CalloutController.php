<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Illustration;
use App\Domain\Catalog\Models\Section;
use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CalloutController extends Controller
{
    /**
     * Helper: اسم الجدول الديناميكي بناءً على كود الكتالوج.
     */
    protected function dyn(string $base, string $catalogCode): string
    {
        return strtolower("{$base}_{$catalogCode}");
    }

    /**
     * ✅ جلب معلومات Callouts الأساسية فقط (coordinates + types)
     * يستخدم من JS لبناء landmarks بدون تحميل بيانات كاملة
     *
     * محسّن: استخدام استعلام واحد مع select محدد + batch lookup للـ section callouts
     */
    public function metadata(Request $request)
    {
        $sectionId   = (int) $request->query('section_id');
        $categoryId  = (int) $request->query('category_id');
        $catalogCode = (string) $request->query('catalog_code');

        if (!$sectionId || !$categoryId || !$catalogCode) {
            return response()->json([
                'ok'       => false,
                'error'    => 'Missing required parameters',
                'callouts' => [],
            ], 422);
        }

        try {
            // ✅ استعلام محسّن: جلب الـ callouts مباشرة مع select محدد
            $illustration = Illustration::select('id', 'section_id')
                ->with(['callouts' => function ($q) {
                    $q->select(
                        'id', 'illustration_id', 'callout_key', 'callout_type',
                        'applicable', 'selective_fit',
                        'rectangle_left', 'rectangle_top', 'rectangle_right', 'rectangle_bottom'
                    );
                }])
                ->where('section_id', $sectionId)
                ->first();

            if (!$illustration || $illustration->callouts->isEmpty()) {
                return response()->json(['ok' => true, 'callouts' => []]);
            }

            // ✅ جمع section callouts للبحث batch واحد بدلاً من N queries
            $sectionCalloutKeys = $illustration->callouts
                ->filter(fn($c) => ($c->callout_type ?? 'part') === 'section' && !empty($c->callout_key))
                ->pluck('callout_key')
                ->unique()
                ->values()
                ->toArray();

            // ✅ استعلام batch واحد لجميع parents_key
            $parentsKeyMap = [];
            if (!empty($sectionCalloutKeys)) {
                // بناء CASE WHEN للبحث الأمثل
                $parentsKeyMap = Category::where('level', 3)
                    ->where(function ($q) use ($sectionCalloutKeys) {
                        foreach ($sectionCalloutKeys as $key) {
                            $q->orWhere('full_code', 'LIKE', $key . '%');
                        }
                    })
                    ->orderBy('id')
                    ->get(['full_code', 'parents_key'])
                    ->groupBy(function ($cat) use ($sectionCalloutKeys) {
                        foreach ($sectionCalloutKeys as $key) {
                            if (str_starts_with($cat->full_code, $key)) {
                                return $key;
                            }
                        }
                        return null;
                    })
                    ->map(fn($group) => $group->first()->parents_key)
                    ->filter()
                    ->toArray();
            }

            // تحويل callouts إلى الصيغة المطلوبة للـ JS
            $callouts = $illustration->callouts->map(function ($c) use ($parentsKeyMap) {
                $width = ($c->rectangle_right ?? 0) - ($c->rectangle_left ?? 0);
                $height = ($c->rectangle_bottom ?? 0) - ($c->rectangle_top ?? 0);
                $calloutKey = $c->callout_key ?? '';

                $data = [
                    'id'               => $c->id,
                    'callout_key'      => $calloutKey,
                    'callout_type'     => $c->callout_type ?? 'part',
                    'applicable'       => $c->applicable ?? null,
                    'selective_fit'    => $c->selective_fit ?? null,
                    'rectangle_left'   => $c->rectangle_left ?? 0,
                    'rectangle_top'    => $c->rectangle_top ?? 0,
                    'rectangle_width'  => $width > 0 ? $width : 150,
                    'rectangle_height' => $height > 0 ? $height : 30,
                ];

                // ✅ استخدام الـ map المحسوب مسبقاً
                if (($c->callout_type ?? 'part') === 'section' && !empty($calloutKey)) {
                    $parentsKey = $parentsKeyMap[$calloutKey] ?? null;
                    if (!empty($parentsKey)) {
                        $data['parents_key'] = $parentsKey;
                    }
                }

                return $data;
            })->values();

            return response()->json([
                'ok'       => true,
                'callouts' => $callouts,
            ]);

        } catch (\Exception $e) {
            \Log::error('CalloutController metadata error', [
                'section_id'   => $sectionId,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'ok'       => false,
                'error'    => 'Failed to fetch callout metadata',
                'callouts' => [],
            ], 500);
        }
    }

    /**
     * ✅ إرجاع القطع للـ callout مع احترام الفلترة والقروبات
     *
     * محسّن: استعلام مجمّع + فلترة ذكية
     */
    public function show(Request $request)
    {
        $t0 = microtime(true);

        $sectionId   = (int) $request->query('section_id');
        $categoryId  = (int) $request->query('category_id');
        $catalogCode = (string) $request->query('catalog_code');
        $calloutKey  = (string) $request->query('callout');

        $page     = max(1, (int) $request->query('page', 1));
        $perPage  = min(100, max(10, (int) $request->query('per_page', 50)));

        if (!$sectionId || !$categoryId || !$catalogCode || !$calloutKey) {
            return response()->json([
                'ok'    => false,
                'error' => 'Missing required parameters',
            ], 422);
        }

        $category = Category::select('id', 'catalog_id')->find($categoryId);
        if (!$category) {
            return response()->json(['ok' => false, 'error' => 'Invalid category'], 404);
        }

        $catalogId = $category->catalog_id;

        // مواصفات المستخدم من السيشن
        $specs     = Session::get('selected_filters', []);
        $yearMonth = $this->extractYearMonth($specs);

        // ✅ جلب القطع مع القروبات في استعلام مجمّع واحد
        $partsWithGroups = $this->fetchPartsWithGroupsOptimized($sectionId, $calloutKey, $catalogCode, $catalogId);

        if (empty($partsWithGroups)) {
            return response()->json([
                'ok'         => true,
                'elapsed_ms' => (int) round((microtime(true) - $t0) * 1000),
                'catalogItems'   => [],
                'pagination' => ['total' => 0, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => 0],
                'rawResults' => [],
            ]);
        }

        // ✅ بناء expectedSet
        if (!empty($specs)) {
            $expectedSet = collect($specs)->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
        } else {
            $categorySpecs = $this->fetchCategorySpecsOptimized($categoryId, $catalogId);
            $expectedSet   = $categorySpecs;
        }

        // ✅ تطبيق الفلترة على القروبات
        $matchedParts = $this->filterPartsBySpecs($partsWithGroups, $expectedSet, $yearMonth);

        // ترتيب
        usort($matchedParts, function ($a, $b) {
            $byCount = count($b['match_value_ids']) <=> count($a['match_value_ids']);
            return $byCount !== 0 ? $byCount : strcmp($a['part_number'], $b['part_number']);
        });

        // Pagination
        $total = count($matchedParts);
        $offset = ($page - 1) * $perPage;
        $paginatedParts = array_slice($matchedParts, $offset, $perPage);

        // ✅ إضافة عدد العروض لكل قطعة
        $paginatedParts = $this->appendOffersCount($paginatedParts);

        $elapsed = (int) round((microtime(true) - $t0) * 1000);

        return response()->json([
            'ok'         => true,
            'elapsed_ms' => $elapsed,
            'catalogItems'   => $paginatedParts,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
            'rawResults' => $paginatedParts,
        ]);
    }

    /**
     * ✅ إضافة عدد العروض لكل قطعة (القطعة + بدائلها) مع التفريق بينهما
     * يحسب:
     * - self_offers: عروض الصنف نفسه
     * - alt_offers: عروض البدائل
     * - offers_count: الإجمالي
     */
    protected function appendOffersCount(array $parts): array
    {
        if (empty($parts)) return [];

        // جمع أرقام القطع
        $partNumbers = array_filter(array_column($parts, 'part_number'));
        if (empty($partNumbers)) return $parts;

        // 1. جلب group_id لكل part_number من sku_alternatives
        $groupMap = DB::table('sku_alternatives')
            ->whereIn('part_number', $partNumbers)
            ->pluck('group_id', 'part_number')
            ->toArray();

        // 2. جلب كل group_ids الفريدة
        $groupIds = array_unique(array_filter(array_values($groupMap)));

        // 3. جلب كل part_numbers في كل group (الصنف + كل بدائله)
        $groupPartNumbers = [];
        if (!empty($groupIds)) {
            $allGroupParts = DB::table('sku_alternatives')
                ->whereIn('group_id', $groupIds)
                ->select('group_id', 'part_number')
                ->get();

            foreach ($allGroupParts as $row) {
                $groupPartNumbers[$row->group_id][] = $row->part_number;
            }
        }

        // 4. تجميع كل أرقام القطع المطلوبة (الأصلية + البدائل)
        $allRelatedPartNumbers = $partNumbers;
        foreach ($groupPartNumbers as $groupParts) {
            $allRelatedPartNumbers = array_merge($allRelatedPartNumbers, $groupParts);
        }
        $allRelatedPartNumbers = array_unique($allRelatedPartNumbers);

        // 5. جلب catalog_item_ids لكل part_number
        $catalogItemMap = DB::table('catalog_items')
            ->whereIn('part_number', $allRelatedPartNumbers)
            ->pluck('id', 'part_number')
            ->toArray();

        // 6. جلب عدد العروض النشطة لكل catalog_item_id
        $catalogItemIds = array_values($catalogItemMap);
        $offersCountMap = [];
        if (!empty($catalogItemIds)) {
            $offersCountMap = DB::table('merchant_items')
                ->whereIn('catalog_item_id', $catalogItemIds)
                ->where('status', 1)
                ->groupBy('catalog_item_id')
                ->select('catalog_item_id', DB::raw('COUNT(*) as cnt'))
                ->pluck('cnt', 'catalog_item_id')
                ->toArray();
        }

        // 7. حساب العروض لكل part_number مع التفريق بين عروضه وعروض بدائله
        $offersDataMap = [];
        foreach ($partNumbers as $pn) {
            $groupId = $groupMap[$pn] ?? null;

            // عروض الصنف نفسه
            $selfCatalogItemId = $catalogItemMap[$pn] ?? null;
            $selfOffers = $selfCatalogItemId ? ($offersCountMap[$selfCatalogItemId] ?? 0) : 0;

            // عروض البدائل
            $altOffers = 0;
            if ($groupId && isset($groupPartNumbers[$groupId])) {
                foreach ($groupPartNumbers[$groupId] as $relatedPn) {
                    // استثناء الصنف نفسه
                    if ($relatedPn === $pn) continue;

                    $relatedCatalogItemId = $catalogItemMap[$relatedPn] ?? null;
                    if ($relatedCatalogItemId) {
                        $altOffers += $offersCountMap[$relatedCatalogItemId] ?? 0;
                    }
                }
            }

            $offersDataMap[$pn] = [
                'self_offers' => $selfOffers,
                'alt_offers' => $altOffers,
                'total' => $selfOffers + $altOffers,
            ];
        }

        // 8. جلب fitment brands لكل catalog_item
        $fitmentBrandsMap = $this->fetchFitmentBrands($catalogItemIds);

        // 9. دمج عدد العروض و catalog_item_id و fitment brands مع القطع
        foreach ($parts as &$part) {
            $pn = $part['part_number'] ?? '';
            $data = $offersDataMap[$pn] ?? ['self_offers' => 0, 'alt_offers' => 0, 'total' => 0];
            $part['self_offers'] = $data['self_offers'];
            $part['alt_offers'] = $data['alt_offers'];
            $part['offers_count'] = $data['total'];

            $catalogItemId = $catalogItemMap[$pn] ?? null;
            $part['catalog_item_id'] = $catalogItemId;

            // Fitment brands
            $fitmentBrands = $catalogItemId ? ($fitmentBrandsMap[$catalogItemId] ?? []) : [];
            $part['fitment_brands'] = $fitmentBrands;
            $part['fitment_count'] = count($fitmentBrands);
        }

        return $parts;
    }

    /**
     * ✅ جلب fitment brands لمجموعة من catalog_item_ids
     * يرجع map: catalog_item_id => [brands array]
     */
    protected function fetchFitmentBrands(array $catalogItemIds): array
    {
        if (empty($catalogItemIds)) return [];

        $locale = app()->getLocale();
        $isArabic = str_starts_with($locale, 'ar');

        // جلب fitments مع brands
        $fitments = DB::table('catalog_item_fitments as cif')
            ->join('brands as b', 'b.id', '=', 'cif.brand_id')
            ->whereIn('cif.catalog_item_id', $catalogItemIds)
            ->select(
                'cif.catalog_item_id',
                'b.id as brand_id',
                'b.name as brand_name_en',
                'b.name_ar as brand_name_ar',
                'b.slug as brand_slug',
                'b.photo as brand_photo'
            )
            ->get();

        // تجميع حسب catalog_item_id
        $result = [];
        foreach ($fitments as $row) {
            $catalogItemId = $row->catalog_item_id;
            $brandId = $row->brand_id;

            if (!isset($result[$catalogItemId])) {
                $result[$catalogItemId] = [];
            }

            // تجنب التكرار
            $exists = false;
            foreach ($result[$catalogItemId] as $existing) {
                if ($existing['id'] == $brandId) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $brandName = $isArabic
                    ? ($row->brand_name_ar ?: $row->brand_name_en)
                    : ($row->brand_name_en ?: $row->brand_name_ar);

                // Build logo URL matching Brand model's photo_url accessor
                $logo = null;
                if ($row->brand_photo) {
                    if (filter_var($row->brand_photo, FILTER_VALIDATE_URL)) {
                        $logo = $row->brand_photo;
                    } elseif (file_exists(public_path('assets/images/brand/' . $row->brand_photo))) {
                        $logo = asset('assets/images/brand/' . $row->brand_photo);
                    } elseif (\Storage::disk('public')->exists('brands/' . $row->brand_photo)) {
                        $logo = \Storage::url('brands/' . $row->brand_photo);
                    } else {
                        $logo = asset('assets/images/brand/' . $row->brand_photo);
                    }
                }

                $result[$catalogItemId][] = [
                    'id' => $brandId,
                    'name' => $brandName ?: 'Unknown',
                    'slug' => $row->brand_slug,
                    'logo' => $logo,
                ];
            }
        }

        return $result;
    }

    /**
     * ✅ جلب القطع مع القروبات - استعلام SQL واحد مع JOINs
     */
    protected function fetchPartsWithGroupsOptimized(int $sectionId, string $calloutKey, string $catalogCode, int $catalogId): array
    {
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $groupTable        = $this->dyn('part_spec_groups', $catalogCode);
        $itemTable         = $this->dyn('part_spec_group_items', $catalogCode);
        $periodTable       = $this->dyn('part_periods', $catalogCode);

        // ✅ استعلام واحد مع كل الـ JOINs
        // LIMIT 5000 لمنع timeout
        // ✅ FORCE INDEX لإجبار MySQL على استخدام الفهرس على part_spec_group_items
        $indexName = "idx_psgi_group_id_{$catalogCode}";
        $sql = "
            SELECT
                p.id as part_id,
                p.part_number,
                p.label_en as part_label_en,
                p.label_ar as part_label_ar,
                p.qty as part_qty,
                p.callout as part_callout,
                g.id as group_id,
                g.group_index,
                pp.begin_date,
                pp.end_date,
                si.value_id
            FROM {$partsTable} p
            INNER JOIN {$sectionPartsTable} sp ON sp.part_id = p.id AND sp.section_id = ?
            LEFT JOIN {$groupTable} g ON g.part_id = p.id AND g.section_id = ? AND g.catalog_id = ?
            LEFT JOIN {$periodTable} pp ON pp.id = g.part_period_id
            LEFT JOIN {$itemTable} gi FORCE INDEX ({$indexName}) ON gi.group_id = g.id
            LEFT JOIN specification_items si ON si.id = gi.specification_item_id
            WHERE p.callout = ?
            LIMIT 5000
        ";

        $rows = DB::select($sql, [$sectionId, $sectionId, $catalogId, $calloutKey]);

        if (empty($rows)) return [];

        // تجميع النتائج
        $partsMap = [];
        $groupsMap = [];

        foreach ($rows as $row) {
            $partId = $row->part_id;

            // إضافة القطعة
            if (!isset($partsMap[$partId])) {
                $partsMap[$partId] = [
                    'part_id'       => (int) $partId,
                    'part_number'   => $row->part_number,
                    'part_label_en' => $row->part_label_en,
                    'part_label_ar' => $row->part_label_ar,
                    'part_qty'      => $row->part_qty,
                    'part_callout'  => $row->part_callout,
                    'groups'        => [],
                ];
                $groupsMap[$partId] = [];
            }

            // إضافة القروب (إن وجد)
            if ($row->group_id) {
                $groupId = $row->group_id;

                if (!isset($groupsMap[$partId][$groupId])) {
                    $groupsMap[$partId][$groupId] = [
                        'group_id'    => (int) $groupId,
                        'group_index' => (int) $row->group_index,
                        'begin_date'  => $row->begin_date,
                        'end_date'    => $row->end_date,
                        'spec_items'  => [],
                    ];
                }

                // إضافة value_id
                if ($row->value_id) {
                    $groupsMap[$partId][$groupId]['spec_items'][] = ['value_id' => $row->value_id];
                }
            }
        }

        // دمج القروبات مع القطع
        foreach ($partsMap as $partId => &$part) {
            $part['groups'] = array_values($groupsMap[$partId] ?? []);
        }

        return array_values($partsMap);
    }

    /**
     * ✅ فلترة القطع حسب المواصفات
     */
    protected function filterPartsBySpecs(array $parts, array $expectedSet, ?string $yearMonth): array
    {
        $matched = [];

        foreach ($parts as $part) {
            $matchedGroupIds = [];
            $unionValues     = [];
            $partBegin       = null;
            $partEnd         = null;
            $hasAnyMatch     = false;

            foreach ($part['groups'] as $group) {
                $gIndex = (int) ($group['group_index'] ?? 0);
                $values = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();

                // (A) القروب العام: group_index=0 بدون قيم
                if ($gIndex === 0 && empty($values)) {
                    $hasAnyMatch = true;
                    $matchedGroupIds[] = $group['group_id'];
                    $this->updateDateRange($group, $partBegin, $partEnd);
                    continue;
                }

                // (B) قروبات ذات قيم: ⊆ expectedSet
                if (!empty($values) && !empty($expectedSet)) {
                    if (count(array_diff($values, $expectedSet)) !== 0) continue;

                    // فحص التاريخ
                    if ($yearMonth) {
                        $begin = $group['begin_date'] ?? null;
                        $end   = $group['end_date'] ?? null;
                        if (($begin && $yearMonth < $begin) || ($end && $yearMonth > $end)) continue;
                    }

                    $hasAnyMatch = true;
                    $matchedGroupIds[] = $group['group_id'];
                    $unionValues = array_merge($unionValues, $values);
                    $this->updateDateRange($group, $partBegin, $partEnd);
                }
            }

            if ($hasAnyMatch && !empty($matchedGroupIds)) {
                $matched[] = [
                    'part_id'           => $part['part_id'],
                    'part_number'       => $part['part_number'],
                    'part_label_ar'     => $part['part_label_ar'],
                    'part_label_en'     => $part['part_label_en'],
                    'part_qty'          => $part['part_qty'],
                    'part_callout'      => $part['part_callout'],
                    'part_begin'        => $partBegin,
                    'part_end'          => $partEnd,
                    'match_values'      => array_values(array_unique($unionValues)),
                    'details'           => array_values(array_unique($unionValues)),
                    'extensions'        => [],
                    'match_count'       => count(array_unique($unionValues)),
                    'difference_count'  => 0,
                    'match_value_ids'   => array_values(array_unique($unionValues)),
                ];
            }
        }

        return $matched;
    }

    /**
     * تحديث نطاق التاريخ
     */
    protected function updateDateRange(array $group, &$partBegin, &$partEnd): void
    {
        $gBegin = $group['begin_date'] ?? null;
        $gEnd   = $group['end_date'] ?? null;

        if ($gBegin && (is_null($partBegin) || $gBegin < $partBegin)) {
            $partBegin = $gBegin;
        }
        if (is_null($partEnd)) {
            $partEnd = $gEnd;
        } elseif (is_null($gEnd)) {
            $partEnd = null;
        } elseif ($gEnd > $partEnd) {
            $partEnd = $gEnd;
        }
    }

    /**
     * ✅ جلب مواصفات الفئة محسّن
     */
    protected function fetchCategorySpecsOptimized(int $categoryId, int $catalogId): array
    {
        $valueIds = DB::table('category_spec_groups as csg')
            ->join('category_spec_group_items as csgi', 'csgi.group_id', '=', 'csg.id')
            ->join('specification_items as si', 'si.id', '=', 'csgi.specification_item_id')
            ->where('csg.category_id', $categoryId)
            ->where('csg.catalog_id', $catalogId)
            ->pluck('si.value_id')
            ->filter()
            ->map(fn($v) => (string) $v)
            ->unique()
            ->values()
            ->all();

        return $valueIds;
    }

    /**
     * استنتاج yearMonth مثل "202307" من فلاتر السيشن (إن وُجدت).
     */
    protected function extractYearMonth(array $specs): ?string
    {
        $year  = $specs['year']['value_id']  ?? null;
        $month = $specs['month']['value_id'] ?? null;
        return ($year && $month) ? $year . str_pad((string) $month, 2, '0', STR_PAD_LEFT) : null;
    }

    /**
     * ✅ جلب تفاصيل batch من القطع بدلاً من واحدة تلو الأخرى
     */
    protected function appendDetailsBatch(array $parts, int $sectionId, string $catalogCode, int $catalogId): array
    {
        if (empty($parts)) return [];

        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $extTable          = $this->dyn('part_extensions', $catalogCode);

        $partIds = array_column($parts, 'part_id');
        $partsById = array_column($parts, null, 'part_id');

        // ✅ استعلام واحد لجميع التفاصيل
        $detailsRows = DB::table("{$partsTable} as p")
            ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
            ->where('sp.section_id', $sectionId)
            ->whereIn('p.id', $partIds)
            ->select(
                'p.id as part_id',
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.qty      as part_qty',
                'p.callout  as callout',
                'p.part_number'
            )
            ->get()
            ->keyBy('part_id');

        // ✅ جمع جميع group_ids للاستعلام batch واحد
        $allGroupIds = [];
        foreach ($parts as $part) {
            $allGroupIds = array_merge($allGroupIds, $part['matched_group_ids'] ?? []);
        }
        $allGroupIds = array_unique($allGroupIds);

        // ✅ استعلام واحد لجميع الامتدادات
        $extensionsMap = [];
        if (!empty($allGroupIds)) {
            try {
                $extensionRows = DB::table($extTable)
                    ->whereIn('part_id', $partIds)
                    ->where('section_id', $sectionId)
                    ->whereIn('group_id', $allGroupIds)
                    ->select('part_id', 'group_id', 'extension_key', 'extension_value')
                    ->get();

                foreach ($extensionRows as $row) {
                    $key = $row->part_id . '_' . $row->group_id;
                    if (!isset($extensionsMap[$row->part_id])) {
                        $extensionsMap[$row->part_id] = [];
                    }
                    $extensionsMap[$row->part_id][$row->extension_key] = $row->extension_value;
                }
            } catch (\Exception $e) {
                // الجدول غير موجود
            }
        }

        // بناء النتائج
        $results = [];
        foreach ($parts as $part) {
            $partId = $part['part_id'];
            $details = $detailsRows[$partId] ?? null;
            $matchValueIds = $part['match_value_ids'] ?? [];

            $results[] = [
                'part_id'           => (int) $partId,
                'part_number'       => $details->part_number ?? null,
                'part_label_ar'     => $details->part_label_ar ?? null,
                'part_label_en'     => $details->part_label_en ?? null,
                'part_qty'          => $details->part_qty ?? null,
                'part_callout'      => $details->callout ?? null,
                'part_begin'        => $part['part_begin'] ?? null,
                'part_end'          => $part['part_end']   ?? null,
                'match_values'      => array_values(array_unique($matchValueIds)),
                'details'           => array_values(array_unique($matchValueIds)),
                'extensions'        => $extensionsMap[$partId] ?? [],
                'match_count'       => count($matchValueIds),
                'difference_count'  => 0,
            ];
        }

        return $results;
    }

    /**
     * ✅ إرجاع HTML للقطع (Server-side rendering)
     * يستخدم من JS بدلاً من renderProducts()
     */
    public function showHtml(Request $request)
    {
        // استخدام نفس منطق show() للحصول على البيانات
        $jsonResponse = $this->show($request);
        $data = $jsonResponse->getData(true);

        if (!($data['ok'] ?? false)) {
            return view('partials.api.part-details', [
                'catalogItems' => [],
                'pagination' => null,
                'error' => $data['error'] ?? 'Unknown error',
            ]);
        }

        // Pre-process catalogItems for view (DATA_FLOW_POLICY)
        $catalogItems = $this->preprocessCatalogItemsForView($data['catalogItems'] ?? []);

        // Pre-process pagination (DATA_FLOW_POLICY)
        $pagination = $data['pagination'] ?? null;
        $paginationData = $this->preprocessPaginationForView($pagination);

        return view('partials.api.part-details', [
            'catalogItems' => $catalogItems,
            'pagination' => $pagination,
            'paginationData' => $paginationData,
        ]);
    }

    /**
     * Pre-process pagination data for view rendering (DATA_FLOW_POLICY)
     */
    private function preprocessPaginationForView(?array $pagination): array
    {
        if (!$pagination || ($pagination['last_page'] ?? 1) <= 1) {
            return ['show' => false];
        }

        $currentPage = $pagination['current_page'] ?? 1;
        $lastPage = $pagination['last_page'] ?? 1;

        // Calculate page range (show max 5 pages)
        $startPage = 1;
        $endPage = $lastPage;

        if ($lastPage > 5) {
            if ($currentPage <= 3) {
                $startPage = 1;
                $endPage = 5;
            } elseif ($currentPage >= $lastPage - 2) {
                $startPage = $lastPage - 4;
                $endPage = $lastPage;
            } else {
                $startPage = $currentPage - 2;
                $endPage = $currentPage + 2;
            }
        }

        return [
            'show' => true,
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'total' => $pagination['total'] ?? 0,
            'from' => $pagination['from'] ?? 0,
            'to' => $pagination['to'] ?? 0,
            'hasPrev' => $currentPage > 1,
            'hasNext' => $currentPage < $lastPage,
            'prevPage' => $currentPage - 1,
            'nextPage' => $currentPage + 1,
            'startPage' => $startPage,
            'endPage' => $endPage,
            'pageRange' => range($startPage, $endPage),
        ];
    }

    /**
     * Pre-process catalog items data for view rendering (DATA_FLOW_POLICY)
     * Pre-computes ALL display values to eliminate @php blocks in view
     */
    private function preprocessCatalogItemsForView(array $catalogItems): array
    {
        $isArabic = str_starts_with(app()->getLocale(), 'ar');

        foreach ($catalogItems as &$item) {
            // Pre-process extensions
            $item['extensions_parsed'] = $this->parseExtensions($item['extensions'] ?? []);

            // Pre-compute localized name
            $en = $item['part_label_en'] ?? '';
            $ar = $item['part_label_ar'] ?? '';
            $item['localized_name'] = $isArabic ? ($ar ?: $en ?: '—') : ($en ?: $ar ?: '—');

            // Pre-compute qty and callout (with empty check)
            $item['display_qty'] = isset($item['part_qty']) && trim((string)$item['part_qty']) !== '' ? $item['part_qty'] : '';
            $item['display_callout'] = isset($item['part_callout']) && trim((string)$item['part_callout']) !== '' ? $item['part_callout'] : '';

            // Pre-compute match values as array
            $matchValues = $item['match_values'] ?? [];
            if (is_string($matchValues)) {
                $matchValues = array_filter(array_map('trim', explode(',', $matchValues)));
            }
            $item['match_values_array'] = $matchValues;
            $item['is_generic'] = empty($matchValues);

            // Pre-compute period dates
            $item['period_from'] = $this->formatYearMonth($item['part_begin'] ?? null);
            $item['period_to'] = $this->formatYearMonth($item['part_end'] ?? null);

            // Pre-compute offers
            $selfOffers = $item['self_offers'] ?? 0;
            $altOffers = $item['alt_offers'] ?? 0;
            $item['total_offers'] = $selfOffers + $altOffers;
            $item['has_self_and_alt'] = $selfOffers > 0 && $altOffers > 0;
            $item['has_self_only'] = $selfOffers > 0 && $altOffers == 0;
            $item['has_alt_only'] = $selfOffers == 0 && $altOffers > 0;
        }
        return $catalogItems;
    }

    /**
     * Format year-month from various string formats
     */
    private function formatYearMonth($s): string
    {
        if (empty($s)) return '';
        $raw = trim((string) $s);
        if (!$raw) return '';
        $d = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($d) >= 6) {
            $y = substr($d, 0, 4);
            $m = substr($d, 4, 2);
            if (preg_match('/^(19|20)\d{2}$/', $y) && preg_match('/^(0[1-9]|1[0-2])$/', $m)) {
                return "{$y}-{$m}";
            }
        }
        if (strlen($d) === 4) return $d;
        return $raw;
    }

    /**
     * Parse extensions from various formats to a consistent array
     */
    private function parseExtensions($ext): array
    {
        if (empty($ext)) return [];

        // Decode JSON string if needed
        if (is_string($ext)) {
            $ext = json_decode($ext, true);
            if (!is_array($ext)) return [];
        }

        if (!is_array($ext)) return [];

        $result = [];
        // Check if associative array
        if (array_keys($ext) !== range(0, count($ext) - 1)) {
            // Associative array
            foreach ($ext as $key => $value) {
                if (!empty($value)) {
                    $result[] = ['key' => $key, 'value' => $value];
                }
            }
        } else {
            // Sequential array
            foreach ($ext as $item) {
                $k = $item['extension_key'] ?? $item['key'] ?? '';
                $v = $item['extension_value'] ?? $item['value'] ?? '';
                if ($k && $v) {
                    $result[] = ['key' => $k, 'value' => $v];
                }
            }
        }
        return $result;
    }
}

