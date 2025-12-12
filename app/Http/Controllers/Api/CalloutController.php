<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewCategory;
use App\Models\Illustration;
use App\Models\Section;
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
                $parentsKeyMap = NewCategory::where('level', 3)
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

        $category = NewCategory::select('id', 'catalog_id')->find($categoryId);
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
                'products'   => [],
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

        $elapsed = (int) round((microtime(true) - $t0) * 1000);

        return response()->json([
            'ok'         => true,
            'elapsed_ms' => $elapsed,
            'products'   => $paginatedParts,
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
     * ✅ جلب القطع مع القروبات في استعلام مجمّع واحد
     */
    protected function fetchPartsWithGroupsOptimized(int $sectionId, string $calloutKey, string $catalogCode, int $catalogId): array
    {
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $groupTable        = $this->dyn('part_spec_groups', $catalogCode);
        $itemTable         = $this->dyn('part_spec_group_items', $catalogCode);
        $periodTable       = $this->dyn('part_periods', $catalogCode);

        // ✅ استعلام واحد: القطع + التفاصيل
        $parts = DB::table("{$partsTable} as p")
            ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
            ->where('sp.section_id', $sectionId)
            ->where('p.callout', $calloutKey)
            ->select(
                'p.id as part_id',
                'p.part_number',
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.qty as part_qty',
                'p.callout as part_callout'
            )
            ->get();

        if ($parts->isEmpty()) return [];

        $partIds = $parts->pluck('part_id')->all();

        // ✅ استعلام واحد: القروبات مع الفترات
        $groups = DB::table("{$groupTable} as g")
            ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
            ->where('g.section_id', $sectionId)
            ->where('g.catalog_id', $catalogId)
            ->whereIn('g.part_id', $partIds)
            ->select('g.id as group_id', 'g.part_id', 'g.group_index', 'pp.begin_date', 'pp.end_date')
            ->get();

        $groupIds = $groups->pluck('group_id')->all();

        // ✅ استعلام واحد: عناصر المواصفات
        $items = empty($groupIds) ? collect() : DB::table("{$itemTable}")
            ->whereIn('group_id', $groupIds)
            ->select('group_id', 'specification_item_id')
            ->get();

        // جلب value_ids من specification_items
        $specItemIds = $items->pluck('specification_item_id')->unique()->all();
        $specValues = empty($specItemIds) ? collect() : DB::table('specification_items')
            ->whereIn('id', $specItemIds)
            ->pluck('value_id', 'id');

        // ربط value_id بالـ group
        $itemsGrouped = $items->groupBy('group_id')->map(function ($groupItems) use ($specValues) {
            return $groupItems->map(fn($i) => ['value_id' => $specValues[$i->specification_item_id] ?? null])
                ->filter(fn($i) => $i['value_id'] !== null)
                ->values();
        });

        $groupsByPart = $groups->groupBy('part_id');

        // بناء النتيجة
        return $parts->map(function ($part) use ($groupsByPart, $itemsGrouped) {
            $partGroups = $groupsByPart[$part->part_id] ?? collect();

            return [
                'part_id'       => (int) $part->part_id,
                'part_number'   => $part->part_number,
                'part_label_en' => $part->part_label_en,
                'part_label_ar' => $part->part_label_ar,
                'part_qty'      => $part->part_qty,
                'part_callout'  => $part->part_callout,
                'groups'        => $partGroups->map(function ($g) use ($itemsGrouped) {
                    return [
                        'group_id'    => (int) $g->group_id,
                        'group_index' => (int) $g->group_index,
                        'begin_date'  => $g->begin_date,
                        'end_date'    => $g->end_date,
                        'spec_items'  => isset($itemsGrouped[$g->group_id]) ? $itemsGrouped[$g->group_id]->all() : [],
                    ];
                })->values()->all(),
            ];
        })->toArray();
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
     * جلب القطع + مجموعات المواصفات + عناصرها للقسم والكول آوت المحددين.
     *
     * ✅ محسّن: استخدام الفهارس الجديدة على part_spec_groups + section_parts
     */
    protected function fetchPartsWithSpecs(int $sectionId, string $calloutKey, string $catalogCode, int $catalogId): array
    {
        $groupTable        = $this->dyn('part_spec_groups', $catalogCode);
        $itemTable         = $this->dyn('part_spec_group_items', $catalogCode);
        $periodTable       = $this->dyn('part_periods', $catalogCode);
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        // ✅ استعلام محسّن: استخدام فهرس (section_id, part_id) + (callout)
        $parts = DB::table("{$partsTable} as p")
            ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
            ->where('sp.section_id', $sectionId)
            ->where('p.callout', $calloutKey)
            ->select('p.id as part_id', 'p.part_number')
            ->get();

        if ($parts->isEmpty()) return [];

        $partIds = $parts->pluck('part_id')->all();

        // ✅ استعلام محسّن: استخدام فهرس (section_id, catalog_id, part_id)
        $groups = DB::table("{$groupTable} as g")
            ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
            ->where('g.section_id', $sectionId)
            ->where('g.catalog_id', $catalogId)
            ->whereIn('g.part_id', $partIds)
            ->select('g.id as group_id', 'g.part_id', 'g.group_index', 'pp.begin_date', 'pp.end_date')
            ->get();

        if ($groups->isEmpty()) {
            // القطع موجودة لكن بدون groups - أعدها بدون groups
            return $parts->map(fn($part) => [
                'part_id'     => (int) $part->part_id,
                'part_number' => (string) $part->part_number,
                'groups'      => [],
            ])->toArray();
        }

        $groupIds = $groups->pluck('group_id')->all();

        // ✅ استعلام محسّن: استخدام فهرس group_id
        $items = DB::table("{$itemTable} as gi")
            ->join('specification_items as si', 'si.id', '=', 'gi.specification_item_id')
            ->whereIn('gi.group_id', $groupIds)
            ->select('gi.group_id', 'si.value_id')
            ->get();

        $itemsGrouped = $items->groupBy('group_id');
        $groupsByPart = $groups->groupBy('part_id');

        return $parts->map(function ($part) use ($groupsByPart, $itemsGrouped) {
            $gs = $groupsByPart[$part->part_id] ?? collect();
            return [
                'part_id'     => (int) $part->part_id,
                'part_number' => (string) $part->part_number,
                'groups' => $gs->map(function ($g) use ($itemsGrouped) {
                    return [
                        'group_id'    => (int) $g->group_id,
                        'group_index' => (int) $g->group_index,
                        'begin_date'  => $g->begin_date,
                        'end_date'    => $g->end_date,
                        'spec_items'  => isset($itemsGrouped[$g->group_id])
                            ? $itemsGrouped[$g->group_id]->map(fn($i) => ['value_id' => $i->value_id])->values()->all()
                            : [],
                    ];
                })->values()->all(),
            ];
        })->toArray();
    }

    /**
     * جلب مجموعات مواصفات الفئة عند عدم اختيار مواصفات من المستخدم.
     */
    protected function fetchCategorySpecs(int $categoryId, int $catalogId): array
    {
        $groups = DB::table('category_spec_groups as csg')
            ->leftJoin('category_periods as cp', 'cp.id', '=', 'csg.category_period_id')
            ->where('csg.category_id', $categoryId)
            ->where('csg.catalog_id', $catalogId)
            ->select('csg.id as group_id', 'csg.group_index', 'cp.begin_date', 'cp.end_date')
            ->get()
            ->map(function ($group) {
                $items = DB::table('category_spec_group_items as csgi')
                    ->join('specification_items as si', 'si.id', '=', 'csgi.specification_item_id')
                    ->join('specifications as s', 's.id', '=', 'si.specification_id')
                    ->where('csgi.group_id', $group->group_id)
                    ->select('s.name as spec_code', 'si.value_id')
                    ->get()
                    ->toArray();

                return [
                    'group_index' => (int) $group->group_index,
                    'begin_date'  => $group->begin_date,
                    'end_date'    => $group->end_date,
                    'spec_items'  => $items,
                ];
            })
            ->filter(fn($g) => count($g['spec_items']) > 0)
            ->values()
            ->toArray();

        return $groups;
    }

    /**
     * إلحاق تفاصيل القطعة + الامتدادات (بدون أي مفاتيح متجر).
     *
     * ✅ محسّن: إزالة information_schema check + استعلام واحد
     */
    protected function appendDetails(array $part, int $sectionId, string $catalogCode, int $catalogId): array
    {
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $extTable          = $this->dyn('part_extensions', $catalogCode);

        $matchedGroupIds = $part['matched_group_ids'] ?? [];
        $matchValueIds   = $part['match_value_ids'] ?? [];

        // تفاصيل أساسية - استعلام محسّن
        $details = DB::table("{$partsTable} as p")
            ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
            ->where('sp.section_id', $sectionId)
            ->where('p.id', $part['part_id'])
            ->select(
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.qty      as part_qty',
                'p.callout  as callout',
                'p.part_number'
            )
            ->first();

        // الامتدادات عبر القروبات المطابقة
        // ✅ محسّن: استخدام try-catch بدلاً من information_schema query
        $extensions = [];
        if (!empty($matchedGroupIds)) {
            try {
                $extensionRows = DB::table($extTable)
                    ->where('part_id', $part['part_id'])
                    ->where('section_id', $sectionId)
                    ->whereIn('group_id', $matchedGroupIds)
                    ->select('extension_key', 'extension_value')
                    ->get();

                foreach ($extensionRows as $row) {
                    $extensions[$row->extension_key] = $row->extension_value;
                }
            } catch (\Exception $e) {
                // الجدول غير موجود - تجاهل
            }
        }

        return [
            'part_id'           => (int) $part['part_id'],
            'part_number'       => $details->part_number ?? null,
            'part_label_ar'     => $details->part_label_ar ?? null,
            'part_label_en'     => $details->part_label_en ?? null,
            'part_qty'          => $details->part_qty ?? null,
            'part_callout'      => $details->callout ?? null,
            'part_begin'        => $part['part_begin'] ?? null,
            'part_end'          => $part['part_end']   ?? null,
            'match_values'      => array_values(array_unique($matchValueIds)),
            'details'           => array_values(array_unique($matchValueIds)),
            'extensions'        => $extensions,
            'match_count'       => count($matchValueIds),
            'difference_count'  => 0,
        ];
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
}

