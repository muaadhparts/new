<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewCategory;
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
     * إرجاع جميع القطع الموافقة لـ callout داخل section/category معينين:
     *  - مطابقة القروب العام (group_index=0 بدون قيم) ← دائمًا مطابق
     *  - أو أي قروب قيمه ⊆ expectedSet (مع مراعاة الفترة الزمنية لو وُجدت)
     * ثم إلحاق تفاصيل القطعة + الامتدادات فقط (بدون أي حقول متجر).
     */
    public function show(Request $request)
    {
        $t0 = microtime(true);

        $sectionId   = (int) $request->query('section_id');
        $categoryId  = (int) $request->query('category_id');
        $catalogCode = (string) $request->query('catalog_code');
        $calloutKey  = (string) $request->query('callout');

        if (!$sectionId || !$categoryId || !$catalogCode || !$calloutKey) {
            return response()->json([
                'ok'    => false,
                'error' => 'Missing required parameters: section_id, category_id, catalog_code, callout',
            ], 422);
        }

        $category = NewCategory::with('catalog')->find($categoryId);
        if (!$category || !$category->catalog) {
            return response()->json([
                'ok'    => false,
                'error' => 'Invalid category or catalog',
            ], 404);
        }

        // مواصفات المستخدم من السيشن (إن وُجدت)
        $specs     = Session::get('selected_filters', []);
        $yearMonth = $this->extractYearMonth($specs); // مثل "202307"

        // جلب القطع المرتبطة بالكول آوت داخل السيكشن
        $parts = $this->fetchPartsWithSpecs($sectionId, $calloutKey, $catalogCode, $category->catalog_id);
        if (empty($parts)) {
            return response()->json([
                'ok'         => true,
                'elapsed_ms' => (int) round((microtime(true) - $t0) * 1000),
                'products'   => [],
                'rawResults' => [],
            ]);
        }

        // expectedSet من مواصفات المستخدم أو مواصفات الفئة عند عدم اختيار المستخدم
        if (!empty($specs)) {
            $expectedSet   = collect($specs)->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
            $categorySpecs = null;
        } else {
            $categorySpecs = $this->fetchCategorySpecs($categoryId, $category->catalog_id);
            $expectedSet   = collect($categorySpecs)->pluck('spec_items')->flatten(1)
                ->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
        }

        // مطابقة القروبات (يشمل القروب العام)
        $matchedBasic = [];
        foreach ($parts as $part) {
            $matchedGroupIds = [];
            $unionValues     = []; // اتحاد value_ids للقروبات المطابقة (غير العامة)
            $partBegin       = null;
            $partEnd         = null;
            $hasAnyMatch     = false;

            foreach ($part['groups'] as $group) {
                $gIndex = (int) ($group['group_index'] ?? 0);
                $values = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();

                // (A) القروب العام: group_index=0 بدون قيم → مطابق دائمًا (بدون شرط تاريخ)
                if ($gIndex === 0 && count($values) === 0) {
                    $hasAnyMatch = true;
                    $matchedGroupIds[] = (int) ($group['group_id'] ?? 0);

                    $gBegin = $group['begin_date'] ?? null;
                    $gEnd   = $group['end_date']   ?? null;
                    if (is_null($partBegin) || ($gBegin && $gBegin < $partBegin)) $partBegin = $gBegin;
                    if (is_null($partEnd)) {
                        $partEnd = $gEnd;
                    } else {
                        if (is_null($gEnd)) $partEnd = null;
                        elseif (!is_null($partEnd) && $gEnd > $partEnd) $partEnd = $gEnd;
                    }
                    continue;
                }

                // (B) قروبات ذات قيم: ⊆ expectedSet + ضمن الفترة (إن وُجد yearMonth)
                if (!empty($values) && !empty($expectedSet)) {
                    $difference = array_diff($values, $expectedSet);
                    if (count($difference) !== 0) continue;

                    if ($yearMonth) {
                        $begin = $group['begin_date'] ?? null; // "YYYYMM"
                        $end   = $group['end_date']   ?? null;
                        if (($begin && $yearMonth < $begin) || ($end && $yearMonth > $end)) continue;
                    }

                    $hasAnyMatch = true;
                    $matchedGroupIds[] = (int) ($group['group_id'] ?? 0);
                    $unionValues = array_values(array_unique(array_merge($unionValues, $values)));

                    $gBegin = $group['begin_date'] ?? null;
                    $gEnd   = $group['end_date']   ?? null;
                    if (is_null($partBegin) || ($gBegin && $gBegin < $partBegin)) $partBegin = $gBegin;
                    if (is_null($partEnd)) {
                        $partEnd = $gEnd;
                    } else {
                        if (is_null($gEnd)) $partEnd = null;
                        elseif (!is_null($partEnd) && $gEnd > $partEnd) $partEnd = $gEnd;
                    }
                }
            }

            if ($hasAnyMatch && !empty($matchedGroupIds)) {
                $matchedBasic[] = [
                    'part_id'           => (int) $part['part_id'],
                    'part_number'       => (string) $part['part_number'],
                    'matched_group_ids' => array_values(array_unique($matchedGroupIds)),
                    'match_value_ids'   => array_values(array_unique($unionValues)), // فارغة = "عام"
                    'part_begin'        => $partBegin,
                    'part_end'          => $partEnd,
                ];
            }
        }

        // ترتيب: الأكثر match_values أولًا ثم رقم القطعة تصاعديًا
        usort($matchedBasic, function ($a, $b) {
            $byCount = count($b['match_value_ids']) <=> count($a['match_value_ids']);
            if ($byCount !== 0) return $byCount;
            return strcmp($a['part_number'], $b['part_number']);
        });

        // إلحاق التفاصيل + الامتدادات (بدون حقول متجر)
        $products = collect($matchedBasic)->map(function ($p) use ($sectionId, $catalogCode, $category) {
            return $this->appendDetails($p, $sectionId, $catalogCode, $category->catalog_id);
        })->values()->all();

        $elapsed = (int) round((microtime(true) - $t0) * 1000);
        return response()->json([
            'ok'         => true,
            'elapsed_ms' => $elapsed,
            'products'   => $products,
            // للحفاظ على التوافق مع واجهات قديمة
            'rawResults' => $products,
        ]);
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
     */
    protected function fetchPartsWithSpecs(int $sectionId, string $calloutKey, string $catalogCode, int $catalogId): array
    {
        $groupTable        = $this->dyn('part_spec_groups', $catalogCode);
        $itemTable         = $this->dyn('part_spec_group_items', $catalogCode);
        $periodTable       = $this->dyn('part_periods', $catalogCode);
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        $parts = DB::table("{$partsTable} as p")
            ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
            ->where('sp.section_id', $sectionId)
            ->where('p.callout', $calloutKey)
            ->select('p.id as part_id', 'p.part_number')
            ->get();

        if ($parts->isEmpty()) return [];

        $partIds = $parts->pluck('part_id')->all();

        $groups = DB::table("{$groupTable} as g")
            ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
            ->whereIn('g.part_id', $partIds)
            ->where('g.section_id', $sectionId)
            ->where('g.catalog_id', $catalogId)
            ->select('g.id as group_id', 'g.part_id', 'g.group_index', 'pp.begin_date', 'pp.end_date')
            ->get();

        $groupIds = $groups->pluck('group_id')->all();

        $items = empty($groupIds) ? collect() : DB::table("{$itemTable} as gi")
            ->join('specification_items as si', 'si.id', '=', 'gi.specification_item_id')
            ->join('specifications as s', 's.id', '=', 'si.specification_id')
            ->whereIn('gi.group_id', $groupIds)
            ->select('gi.group_id', 's.name as spec_code', 'si.value_id')
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
                        'spec_items'  => isset($itemsGrouped[$g->group_id]) ? $itemsGrouped[$g->group_id]->values()->all() : [],
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
     */
    protected function appendDetails(array $part, int $sectionId, string $catalogCode, int $catalogId): array
    {
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $extTable          = $this->dyn('part_extensions', $catalogCode);

        $matchedGroupIds = $part['matched_group_ids'] ?? [];
        $matchValueIds   = $part['match_value_ids'] ?? [];

        // تفاصيل أساسية
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
        $extensions = [];
        if (!empty($matchedGroupIds)) {
            $exists = DB::table('information_schema.tables')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $extTable)
                ->exists();

            if ($exists) {
                $extensionRows = DB::table($extTable)
                    ->where('part_id', $part['part_id'])
                    ->where('section_id', $sectionId)
                    ->whereIn('group_id', $matchedGroupIds)
                    ->select('extension_key', 'extension_value')
                    ->get();

                foreach ($extensionRows as $row) {
                    $extensions[$row->extension_key] = $row->extension_value;
                }
            }
        }

        return [
            'part_id'           => (int) $part['part_id'],
            'part_number'       => optional($details)->part_number,
            'part_label_ar'     => optional($details)->part_label_ar,
            'part_label_en'     => optional($details)->part_label_en,
            'part_qty'          => optional($details)->part_qty,
            'part_callout'      => optional($details)->callout,
            'part_begin'        => $part['part_begin'] ?? null,
            'part_end'          => $part['part_end']   ?? null,
            'match_values'      => array_values(array_unique($matchValueIds)),
            'details'           => array_values(array_unique($matchValueIds)), // توافق قديم
            'extensions'        => $extensions,
            'match_count'       => count($matchValueIds),
            'difference_count'  => 0,
        ];
    }
}

// يعيد المابق بشكل كامل 
// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use App\Models\NewCategory;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Session;
// use App\Models\Product;  // لربط المنتجات بالكتالوج

// class CalloutController extends Controller
// {
//     // helper: اسم الجدول الديناميكي
//     protected function dyn(string $base, string $catalogCode): string
//     {
//         return strtolower("{$base}_{$catalogCode}");
//     }

//     /**
//      * العرض الرئيسى لبيانات الكول آوت
//      * يرجع JSON يحتوى على المنتجات المطابقة + كافة النتائج الأولية.
//      */
//     public function show(Request $request)
//     {
//         $t0 = microtime(true);

//         $sectionId   = (int) $request->query('section_id');
//         $categoryId  = (int) $request->query('category_id');
//         $catalogCode = (string) $request->query('catalog_code');
//         $calloutKey  = (string) $request->query('callout');

//         // تحقق من الباراميترات المطلوبة
//         if (!$sectionId || !$categoryId || !$catalogCode || !$calloutKey) {
//             return response()->json([
//                 'ok'    => false,
//                 'error' => 'Missing required parameters: section_id, category_id, catalog_code, callout',
//             ], 422);
//         }

//         $category = NewCategory::with('catalog')->find($categoryId);
//         if (!$category || !$category->catalog) {
//             return response()->json([
//                 'ok'    => false,
//                 'error' => 'Invalid category or catalog',
//             ], 404);
//         }

//         // مواصفات المستخدم من السيشن (إن وجدت)
//         $specs = Session::get('selected_filters', []);
//         $yearMonth = $this->extractYearMonth($specs); // مثال: "202307"

//         // جلب القطع المتعلقة بالكول-أوت
//         $parts = $this->fetchPartsWithSpecs($sectionId, $calloutKey, $catalogCode, $category->catalog_id);

//         // تجهيز expectedSet من مواصفات المستخدم أو مواصفات الفئة
//         if (!empty($specs)) {
//             $expectedSet = collect($specs)
//                 ->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
//             $categorySpecs = null;
//         } else {
//             $categorySpecs = $this->fetchCategorySpecs($categoryId, $category->catalog_id);
//             $expectedSet = collect($categorySpecs)->pluck('spec_items')->flatten(1)
//                 ->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
//         }

//         // إذا لم يوجد expectedSet فلا يمكن تطبيق الوتر الحساس. فى هذه الحالة نعيد القروبات العامة فقط.
//         $matchedBasic = [];
//         foreach ($parts as $part) {
//             $matchedGroupIds = [];
//             $unionValues     = [];
//             $beginDate       = null;
//             $endDate         = null;
//             $hasMatch        = false;

//             foreach ($part['groups'] as $group) {
//                 $gIndex = (int) ($group['group_index'] ?? 0);
//                 $values = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string)$v)->all();

//                 // إذا كان group_index = 0 والقيم فارغة → قروب عام دائمًا
//                 if ($gIndex === 0 && count($values) === 0) {
//                     $hasMatch = true;
//                     $matchedGroupIds[] = (int) $group['group_id'];
//                     // تجميع الفترة
//                     $b = $group['begin_date'] ?? null;
//                     $e = $group['end_date']   ?? null;
//                     if (is_null($beginDate) || ($b && $b < $beginDate)) {
//                         $beginDate = $b;
//                     }
//                     if (is_null($endDate)) {
//                         $endDate = $e;
//                     } else {
//                         if (is_null($e)) {
//                             $endDate = null;
//                         } elseif (!is_null($endDate) && $e > $endDate) {
//                             $endDate = $e;
//                         }
//                     }
//                     continue;
//                 }

//                 // المطابقة الحساسة: يجب أن تكون القيم ⊆ expectedSet
//                 if (!empty($values) && !empty($expectedSet)) {
//                     $diff = array_diff($values, $expectedSet);
//                     if (count($diff) !== 0) continue;

//                     // فلتر التاريخ إن وجد
//                     if ($yearMonth) {
//                         $b = $group['begin_date'] ?? null;
//                         $e = $group['end_date']   ?? null;
//                         if (($b && $yearMonth < $b) || ($e && $yearMonth > $e)) continue;
//                     }

//                     // قروب مطابق
//                     $hasMatch = true;
//                     $matchedGroupIds[] = (int) $group['group_id'];
//                     $unionValues = array_values(array_unique(array_merge($unionValues, $values)));

//                     // تجميع الفترة
//                     $b = $group['begin_date'] ?? null;
//                     $e = $group['end_date']   ?? null;
//                     if (is_null($beginDate) || ($b && $b < $beginDate)) {
//                         $beginDate = $b;
//                     }
//                     if (is_null($endDate)) {
//                         $endDate = $e;
//                     } else {
//                         if (is_null($e)) {
//                             $endDate = null;
//                         } elseif (!is_null($endDate) && $e > $endDate) {
//                             $endDate = $e;
//                         }
//                     }
//                 }
//             }

//             if ($hasMatch && !empty($matchedGroupIds)) {
//                 $matchedBasic[] = [
//                     'part_id'           => (int) $part['part_id'],
//                     'part_number'       => (string) $part['part_number'],
//                     'matched_group_ids' => array_values(array_unique($matchedGroupIds)),
//                     'match_value_ids'   => array_values(array_unique($unionValues)),
//                     'part_begin'        => $beginDate,
//                     'part_end'          => $endDate,
//                 ];
//             }
//         }

//         // ترتيب النتائج: الأكثر قيم مطابقة أولاً
//         usort($matchedBasic, function ($a, $b) {
//             $byCount = count($b['match_value_ids']) <=> count($a['match_value_ids']);
//             if ($byCount !== 0) return $byCount;
//             return strcmp($a['part_number'], $b['part_number']);
//         });

//         // بناء التفاصيل مع إرفاق بيانات المتجر
//         $allMatchedParts = collect($matchedBasic)->map(function ($p) use ($sectionId, $catalogCode, $category) {
//             return $this->appendDetails($p, $sectionId, $catalogCode, $category->catalog_id);
//         })->values();

//         $products   = $allMatchedParts->all();
//         $rawResults = $products; // فقط للرجوع الكامل

//         $elapsed = round((microtime(true) - $t0) * 1000);

//         return response()->json([
//             'ok'         => true,
//             'elapsed_ms' => $elapsed,
//             'products'   => $products,
//             'rawResults' => $rawResults,
//         ]);
//     }

//     protected function extractYearMonth(array $specs): ?string
//     {
//         $year  = $specs['year']['value_id']  ?? null;
//         $month = $specs['month']['value_id'] ?? null;
//         return ($year && $month) ? $year . str_pad($month, 2, '0', STR_PAD_LEFT) : null;
//     }

//     protected function fetchPartsWithSpecs(int $sectionId, string $calloutKey, string $catalogCode, int $catalogId): array
//     {
//         $groupTable        = $this->dyn('part_spec_groups', $catalogCode);
//         $itemTable         = $this->dyn('part_spec_group_items', $catalogCode);
//         $periodTable       = $this->dyn('part_periods', $catalogCode);
//         $partsTable        = $this->dyn('parts', $catalogCode);
//         $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

//         // جلب جميع القطع فى هذا القسم والكول آوت
//         $parts = DB::table("{$partsTable} as p")
//             ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
//             ->where('sp.section_id', $sectionId)
//             ->where('p.callout', $calloutKey)
//             ->select('p.id as part_id', 'p.part_number')
//             ->get();

//         if ($parts->isEmpty()) return [];

//         $partIds = $parts->pluck('part_id')->all();

//         // جلب جميع القروبات المرتبطة بهذه القطع
//         $groups = DB::table("{$groupTable} as g")
//             ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
//             ->whereIn('g.part_id', $partIds)
//             ->where('g.section_id', $sectionId)
//             ->where('g.catalog_id', $catalogId)
//             ->select('g.id as group_id', 'g.part_id', 'g.group_index', 'pp.begin_date', 'pp.end_date')
//             ->get();

//         $groupIds = $groups->pluck('group_id')->all();

//         // جلب عناصر المواصفات لكل قروب
//         $items = empty($groupIds) ? collect() : DB::table("{$itemTable} as gi")
//             ->join('specification_items as si', 'si.id', '=', 'gi.specification_item_id')
//             ->join('specifications as s', 's.id', '=', 'si.specification_id')
//             ->whereIn('gi.group_id', $groupIds)
//             ->select('gi.group_id', 's.name as spec_code', 'si.value_id')
//             ->get();

//         $itemsGrouped = $items->groupBy('group_id');
//         $groupsByPart = $groups->groupBy('part_id');

//         return $parts->map(function ($part) use ($groupsByPart, $itemsGrouped) {
//             $gs = $groupsByPart[$part->part_id] ?? collect();
//             return [
//                 'part_id'     => $part->part_id,
//                 'part_number' => $part->part_number,
//                 'groups' => $gs->map(function ($g) use ($itemsGrouped) {
//                     return [
//                         'group_id'    => $g->group_id,
//                         'group_index' => $g->group_index,
//                         'begin_date'  => $g->begin_date,
//                         'end_date'    => $g->end_date,
//                         'spec_items'  => isset($itemsGrouped[$g->group_id]) ? $itemsGrouped[$g->group_id]->values()->all() : [],
//                     ];
//                 })->values()->all(),
//             ];
//         })->toArray();
//     }

//     protected function fetchCategorySpecs(int $categoryId, int $catalogId): array
//     {
//         $groups = DB::table('category_spec_groups as csg')
//             ->leftJoin('category_periods as cp', 'cp.id', '=', 'csg.category_period_id')
//             ->where('csg.category_id', $categoryId)
//             ->where('csg.catalog_id', $catalogId)
//             ->select('csg.id as group_id', 'csg.group_index', 'cp.begin_date', 'cp.end_date')
//             ->get()
//             ->map(function ($group) {
//                 $items = DB::table('category_spec_group_items as csgi')
//                     ->join('specification_items as si', 'si.id', '=', 'csgi.specification_item_id')
//                     ->join('specifications as s', 's.id', '=', 'si.specification_id')
//                     ->where('csgi.group_id', $group->group_id)
//                     ->select('s.name as spec_code', 'si.value_id')
//                     ->get()
//                     ->toArray();
//                 return [
//                     'group_index' => $group->group_index,
//                     'begin_date'  => $group->begin_date,
//                     'end_date'    => $group->end_date,
//                     'spec_items'  => $items,
//                 ];
//             })
//             ->filter(fn($g) => count($g['spec_items']) > 0)
//             ->values()
//             ->toArray();

//         return $groups;
//     }

//     /**
//      * appendDetails: استرجاع تفاصيل القطعة وإلحاق بيانات المتجر إن وُجدت.
//      */
//     protected function appendDetails(array $part, int $sectionId, string $catalogCode, int $catalogId): array
//     {
//         $partsTable        = $this->dyn('parts', $catalogCode);
//         $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
//         $extTable          = $this->dyn('part_extensions', $catalogCode);

//         // الحصول على groupIds المطابقة
//         $matchedGroupIds = $part['matched_group_ids'] ?? [];
//         $matchValueIds   = $part['match_value_ids'] ?? [];

//         // معلومات القطعة الأساسية من جدول parts
//         $details = DB::table("{$partsTable} as p")
//             ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
//             ->where('sp.section_id', $sectionId)
//             ->where('p.id', $part['part_id'])
//             ->select('p.label_en as part_label_en', 'p.label_ar as part_label_ar', 'p.qty as part_qty', 'p.callout', 'p.part_number')
//             ->first();

//         // جمع الامتدادات عبر كل القروبات المطابقة
//         $extensions = [];
//         if (!empty($matchedGroupIds)) {
//             $exists = DB::table('information_schema.tables')
//                 ->where('table_schema', DB::getDatabaseName())
//                 ->where('table_name', $extTable)
//                 ->exists();
//             if ($exists) {
//                 $extensionRows = DB::table($extTable)
//                     ->where('part_id', $part['part_id'])
//                     ->where('section_id', $sectionId)
//                     ->whereIn('group_id', $matchedGroupIds)
//                     ->select('extension_key', 'extension_value')
//                     ->get();
//                 foreach ($extensionRows as $row) {
//                     $extensions[$row->extension_key] = $row->extension_value;
//                 }
//             }
//         }

//         // محاولة ربط القطعة بمنتج المتجر باستخدام sku
//         $storeData = [
//             'store_id'    => null,
//             'store_slug'  => null,
//             'store_price' => null,
//             'store_qty'   => null,
//             'add_to_cart' => null,
//             'quick_view'  => null,
//         ];
//         try {
//             $sku = $part['part_number'] ?? null;
//             if ($sku) {
//                 $product = Product::where('sku', $sku)->first();
//                 if ($product) {
//                     $storeData['store_id']    = $product->id;
//                     $storeData['store_slug']  = $product->slug;
//                     $storeData['store_price'] = method_exists($product, 'showPrice') ? $product->showPrice() : ($product->price ?? null);
//                     $storeData['store_qty']   = $product->stock ?? null;
//                     // روابط الإضافة للسلة و العرض السريع
//                     try {
//                         $storeData['add_to_cart'] = route('product.cart.add', ['product' => $product->id]);
//                     } catch (\Throwable $e) {
//                         $storeData['add_to_cart'] = null;
//                     }
//                     try {
//                         $storeData['quick_view'] = route('product.quick', ['id' => $product->id]);
//                     } catch (\Throwable $e) {
//                         $storeData['quick_view'] = null;
//                     }
//                 }
//             }
//         } catch (\Throwable $e) {
//             // تجاهل الأخطاء
//         }

//         return array_merge([
//             'part_id'      => (int) $part['part_id'],
//             'part_number'  => optional($details)->part_number,
//             'part_label_ar'=> optional($details)->part_label_ar,
//             'part_label_en'=> optional($details)->part_label_en,
//             'part_qty'     => optional($details)->part_qty,
//             'part_callout' => optional($details)->callout,
//             'part_begin'   => $part['part_begin'] ?? null,
//             'part_end'     => $part['part_end'] ?? null,
//             'match_values' => $matchValueIds,
//             'details'      => $matchValueIds,
//             'extensions'   => $extensions,
//             'match_count'  => count($matchValueIds),
//             'difference_count' => 0,
//         ], $storeData);
//     }
// }


// اول كود غير مطور 
// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use App\Models\NewCategory;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Session;

// class CalloutController extends Controller
// {
//     // helper: اسم الجدول الديناميكي
//     protected function dyn(string $base, string $catalogCode): string
//     {
//         return strtolower("{$base}_{$catalogCode}");
//     }

//     public function show(Request $request)
//     {
//         $t0 = microtime(true);

//         $sectionId   = (int) $request->query('section_id');
//         $categoryId  = (int) $request->query('category_id');
//         $catalogCode = (string) $request->query('catalog_code');
//         $calloutKey  = (string) $request->query('callout');

//         if (!$sectionId || !$categoryId || !$catalogCode || !$calloutKey) {
//             return response()->json([
//                 'ok' => false,
//                 'error' => 'Missing required parameters: section_id, category_id, catalog_code, callout',
//             ], 422);
//         }

//         $category = NewCategory::with('catalog')->find($categoryId);
//         if (!$category || !$category->catalog) {
//             return response()->json([
//                 'ok' => false,
//                 'error' => 'Invalid category or catalog',
//             ], 404);
//         }

//         // مواصفات المستخدم من السيشن (إن وجدت)
//         $specs = Session::get('selected_filters', []);
//         $yearMonth = $this->extractYearMonth($specs);

//         // جلب القطع المتعلقة بالكول-أوت
//         $parts = $this->fetchPartsWithSpecs($sectionId, $calloutKey, $catalogCode, $category->catalog_id);

//         // تجهيز expectedSet من مواصفات المستخدم أو مواصفات الفئة
//         if (!empty($specs)) {
//             $expectedSet = collect($specs)
//                 ->pluck('value_id')->map(fn($v) => (string) $v)->unique()->values()->all();
//             $categorySpecs = null;
//         } else {
//             $categorySpecs = $this->fetchCategorySpecs($categoryId, $category->catalog_id);
//             $expectedSet = collect($categorySpecs)->pluck('spec_items')->flatten(1)
//                 ->pluck('value_id')->map(fn($v) => (string) $v)->unique()->values()->all();
//         }

//         // تقييم القروبات (الوتر الحساس) + احترام التاريخ لو محدد
//         $rawMatches = collect($parts)->map(function ($part) use ($expectedSet, $yearMonth) {
//             foreach ($part['groups'] as $group) {
//                 $gIndex = (int) $group['group_index'];
//                 $values = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();

//                 // قروب عام
//                 if ($gIndex === 0 && empty($values)) {
//                     return [
//                         ...$part,
//                         'matched_group_index' => $gIndex,
//                         'match_count' => 0,
//                         'difference_count' => 0,
//                     ];
//                 }

//                 $intersection = array_intersect($values, $expectedSet);
//                 $difference   = array_diff($values, $expectedSet);

//                 if (count($intersection) >= 1 && count($difference) === 0) {
//                     if ($gIndex > 0 && $yearMonth) {
//                         $begin = $group['begin_date'] ?? null;
//                         $end   = $group['end_date']   ?? null;
//                         if (($begin && $yearMonth < $begin) || ($end && $yearMonth > $end)) {
//                             continue;
//                         }
//                     }
//                     return [
//                         ...$part,
//                         'matched_group_index' => $gIndex,
//                         'match_count' => count($intersection),
//                         'difference_count' => 0,
//                     ];
//                 }
//             }
//             return null;
//         })->filter()->values();

//         // اختيار القطعة المطابقة الرسمية
//         if (!empty($specs)) {
//             $match = $yearMonth
//                 ? $this->matchWithSpecsAndDate($parts, $specs, $yearMonth)
//                 : $this->matchWithSpecs($parts, $specs);
//         } else {
//             $match = $this->matchWithCategorySpecs($parts, $categorySpecs);
//         }

//         // بناء التفاصيل (بالامتدادات عند توفر group_id مطابق)
//         $allMatchedParts = $rawMatches->map(fn($p) => $this->appendDetails($p, $sectionId, $catalogCode, $category->catalog_id));
//         $rawResults = $allMatchedParts->all();

//         $products = $match
//             ? [ $this->appendDetails($match, $sectionId, $catalogCode, $category->catalog_id) ]
//             : [];

//         $elapsed = round((microtime(true) - $t0) * 1000); // ms

//         $response = [
//             'ok' => true,
//             'elapsed_ms' => $elapsed,
//             'products' => $products,
//             'rawResults' => $rawResults,
//         ];

//         // dd($response); // فحص سريع عند الحاجة

//         return response()->json($response);
//     }

//     protected function extractYearMonth(array $specs): ?string
//     {
//         $year  = $specs['year']['value_id']  ?? null;
//         $month = $specs['month']['value_id'] ?? null;
//         return ($year && $month) ? $year . str_pad($month, 2, '0', STR_PAD_LEFT) : null;
//     }

//     protected function fetchPartsWithSpecs(int $sectionId, string $calloutKey, string $catalogCode, int $catalogId): array
//     {
//         $groupTable        = $this->dyn('part_spec_groups', $catalogCode);
//         $itemTable         = $this->dyn('part_spec_group_items', $catalogCode);
//         $periodTable       = $this->dyn('part_periods', $catalogCode);
//         $partsTable        = $this->dyn('parts', $catalogCode);
//         $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

//         $parts = DB::table("{$partsTable} as p")
//             ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
//             ->where('sp.section_id', $sectionId)
//             ->where('p.callout', $calloutKey)
//             ->select('p.id as part_id', 'p.part_number')
//             ->get();

//         if ($parts->isEmpty()) return [];

//         $partIds = $parts->pluck('part_id')->all();

//         $groups = DB::table("{$groupTable} as g")
//             ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
//             ->whereIn('g.part_id', $partIds)
//             ->where('g.section_id', $sectionId)
//             ->where('g.catalog_id', $catalogId)
//             ->select('g.id as group_id', 'g.part_id', 'g.group_index', 'pp.begin_date', 'pp.end_date')
//             ->get();

//         $items = $groups->isEmpty() ? collect() : DB::table("{$itemTable} as gi")
//             ->join('specification_items as si', 'si.id', '=', 'gi.specification_item_id')
//             ->join('specifications as s', 's.id', '=', 'si.specification_id')
//             ->whereIn('gi.group_id', $groups->pluck('id')->all() ?: $groups->pluck('group_id')->all())
//             ->select('gi.group_id', 's.name as spec_code', 'si.value_id')
//             ->get();

//         $itemsGrouped = $items->groupBy('group_id');
//         $groupsByPart = $groups->groupBy('part_id');

//         return $parts->map(function ($part) use ($groupsByPart, $itemsGrouped) {
//             $gs = $groupsByPart[$part->part_id] ?? collect();
//             return [
//                 'part_id'     => $part->part_id,
//                 'part_number' => $part->part_number,
//                 'groups' => $gs->map(function ($g) use ($itemsGrouped) {
//                     return [
//                         'group_id'    => $g->group_id,
//                         'group_index' => $g->group_index,
//                         'begin_date'  => $g->begin_date,
//                         'end_date'    => $g->end_date,
//                         'spec_items'  => isset($itemsGrouped[$g->group_id]) ? $itemsGrouped[$g->group_id]->values()->all() : [],
//                     ];
//                 })->values()->all(),
//             ];
//         })->toArray();
//     }

//     protected function fetchCategorySpecs(int $categoryId, int $catalogId): array
//     {
//         $groups = DB::table('category_spec_groups as csg')
//             ->leftJoin('category_periods as cp', 'cp.id', '=', 'csg.category_period_id')
//             ->where('csg.category_id', $categoryId)
//             ->where('csg.catalog_id', $catalogId)
//             ->select('csg.id as group_id', 'csg.group_index', 'cp.begin_date', 'cp.end_date')
//             ->get()
//             ->map(function ($group) {
//                 $items = DB::table('category_spec_group_items as csgi')
//                     ->join('specification_items as si', 'si.id', '=', 'csgi.specification_item_id')
//                     ->join('specifications as s', 's.id', '=', 'si.specification_id')
//                     ->where('csgi.group_id', $group->group_id)
//                     ->select('s.name as spec_code', 'si.value_id')
//                     ->get()
//                     ->toArray();
//                 return [
//                     'group_index' => $group->group_index,
//                     'begin_date'  => $group->begin_date,
//                     'end_date'    => $group->end_date,
//                     'spec_items'  => $items,
//                 ];
//             })
//             ->filter(fn($g) => count($g['spec_items']) > 0)
//             ->values()
//             ->toArray();

//         return $groups;
//     }

//     protected function matchWithSpecs(array $parts, array $expected): ?array
//     {
//         $expectedSet = collect($expected)->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
//         foreach ($parts as $part) {
//             foreach ($part['groups'] as $group) {
//                 $values       = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();
//                 $intersection = array_intersect($values, $expectedSet);
//                 $difference   = array_diff($values, $expectedSet);
//                 if (count($intersection) >= 1 && count($difference) === 0) {
//                     return [
//                         ...$part,
//                         'matched_group_index' => $group['group_index'],
//                         'match_count' => count($intersection),
//                         'difference_count' => count($difference),
//                     ];
//                 }
//             }
//         }
//         return null;
//     }

//     protected function matchWithSpecsAndDate(array $parts, array $expected, string $yearMonth): ?array
//     {
//         $expectedSet = collect($expected)->pluck('value_id')->filter()->map(fn($v) => (string) $v)->unique()->values()->all();
//         foreach ($parts as $part) {
//             foreach ($part['groups'] as $group) {
//                 $gIndex = (int) $group['group_index'];
//                 $values = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();

//                 if ($gIndex === 0 && empty($values)) {
//                     return [
//                         ...$part,
//                         'matched_group_index' => $gIndex,
//                         'match_count' => 0,
//                         'difference_count' => 0,
//                     ];
//                 }

//                 $intersection = array_intersect($values, $expectedSet);
//                 $difference   = array_diff($values, $expectedSet);
//                 if (count($intersection) >= 1 && count($difference) === 0) {
//                     $begin = $group['begin_date'] ?? null;
//                     $end   = $group['end_date']   ?? null;
//                     if (($begin && $yearMonth < $begin) || ($end && $yearMonth > $end)) {
//                         continue;
//                     }
//                     return [
//                         ...$part,
//                         'matched_group_index' => $group['group_index'],
//                         'match_count' => count($intersection),
//                         'difference_count' => 0,
//                     ];
//                 }
//             }
//         }
//         return null;
//     }

//     protected function matchWithCategorySpecs(array $parts, ?array $categorySpecs): ?array
//     {
//         if (!$categorySpecs) return null;

//         foreach ($parts as $part) {
//             foreach ($part['groups'] as $group) {
//                 $gIndex = (int) $group['group_index'];
//                 $values = collect($group['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();

//                 if ($gIndex === 0 && empty($values)) {
//                     return [
//                         ...$part,
//                         'matched_group_index' => $gIndex,
//                         'match_count' => 0,
//                         'difference_count' => 0,
//                     ];
//                 }

//                 foreach ($categorySpecs as $catGroup) {
//                     $catValues    = collect($catGroup['spec_items'])->pluck('value_id')->map(fn($v) => (string) $v)->all();
//                     $intersection = array_intersect($values, $catValues);
//                     $difference   = array_diff($values, $catValues);
//                     if (count($intersection) >= 1 && count($difference) === 0) {
//                         return [
//                             ...$part,
//                             'matched_group_index' => $gIndex,
//                             'match_count' => count($intersection),
//                             'difference_count' => 0,
//                         ];
//                     }
//                 }
//             }
//         }
//         return null;
//     }

//     protected function appendDetails(array $part, int $sectionId, string $catalogCode, int $catalogId): array
//     {
//         $partsTable        = $this->dyn('parts', $catalogCode);
//         $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
//         $extTable          = $this->dyn('part_extensions', $catalogCode);

//         $matchedGroupIndex = $part['matched_group_index'] ?? null;
//         $groups            = $part['groups'] ?? [];
//         $matchedGroup      = collect($groups)->firstWhere('group_index', $matchedGroupIndex);
//         $groupId           = $matchedGroup['group_id'] ?? null;

//         $details = DB::table("{$partsTable} as p")
//             ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
//             ->where('sp.section_id', $sectionId)
//             ->where('p.id', $part['part_id'])
//             ->select('p.label_en as part_label_en', 'p.label_ar as part_label_ar', 'p.qty as part_qty', 'p.callout', 'p.part_number')
//             ->first();

//         $extensions = [];
//         if ($groupId) {
//             $exists = DB::table('information_schema.tables')
//                 ->where('table_schema', DB::getDatabaseName())
//                 ->where('table_name', $extTable)
//                 ->exists();

//             if ($exists) {
//                 $extensionRows = DB::table($extTable)
//                     ->where('part_id', $part['part_id'])
//                     ->where('section_id', $sectionId)
//                     ->where('group_id', $groupId)
//                     ->select('extension_key', 'extension_value')
//                     ->get();

//                 $extensions = $extensionRows->mapWithKeys(fn($row) => [$row->extension_key => $row->extension_value])->all();
//             }
//         }

//         return [
//             'part_id'           => $part['part_id'],
//             'part_number'       => optional($details)->part_number,
//             'part_label_ar'     => optional($details)->part_label_ar,
//             'part_label_en'     => optional($details)->part_label_en,
//             'part_qty'          => optional($details)->part_qty,
//             'part_callout'      => optional($details)->callout,
//             'part_begin'        => $matchedGroup['begin_date'] ?? null,
//             'part_end'          => $matchedGroup['end_date'] ?? null,
//             'match_values'      => collect($matchedGroup['spec_items'] ?? [])->pluck('value_id')->all(),
//             'details'           => $matchedGroup['spec_items'] ?? [],
//             'extensions'        => $extensions,
//             'match_count'       => $part['match_count'] ?? 0,
//             'difference_count'  => $part['difference_count'] ?? 0,
//         ];
//     }
// }
