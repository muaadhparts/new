<?php

namespace App\Http\Controllers\Operator;

use App\Models\CatalogItem;
use App\Models\SkuAlternative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AlternativeController - إدارة البدائل
 *
 * يستخدم جدول sku_alternatives مع group_id فقط
 * جدول sku_alternative_item هو Legacy ولا يُستخدم
 *
 * - البحث عن صنف
 * - عرض بدائله (كل الأصناف بنفس group_id)
 * - إضافة بديل جديد
 * - إزالة بديل (نقله لمجموعة جديدة)
 */
class AlternativeController extends OperatorBaseController
{
    /**
     * صفحة البدائل الرئيسية - البحث عن صنف
     */
    public function index(Request $request)
    {
        $query = trim($request->input('q', ''));
        $catalogItem = null;
        $alternatives = collect();
        $skuRecord = null;

        if (strlen($query) >= 2) {
            // البحث عن catalog_item
            $catalogItem = CatalogItem::where('part_number', $query)->first();

            if ($catalogItem) {
                // جلب سجل البديل من sku_alternatives
                $skuRecord = SkuAlternative::where('part_number', $query)->first();

                if ($skuRecord && $skuRecord->group_id) {
                    // جلب البدائل من نفس المجموعة (group_id)
                    $alternatives = SkuAlternative::where('group_id', $skuRecord->group_id)
                        ->where('part_number', '!=', $query)
                        ->with('catalogItem')
                        ->get();
                }
            }
        }

        return view('operator.alternative.index', compact(
            'query',
            'catalogItem',
            'alternatives',
            'skuRecord'
        ));
    }

    /**
     * البحث عن أصناف (للـ autocomplete)
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = CatalogItem::where('part_number', 'like', "{$query}%")
            ->select('id', 'part_number', 'label_en', 'label_ar')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'part_number' => $item->part_number,
                    'label' => $item->label_en ?: $item->label_ar,
                ];
            });

        return response()->json($items);
    }

    /**
     * إضافة بديل
     */
    public function addAlternative(Request $request)
    {
        $request->validate([
            'main_part_number' => 'required|string',
            'alternative_part_number' => 'required|string',
        ]);

        $mainPartNumber = trim($request->input('main_part_number'));
        $alternativePartNumber = trim($request->input('alternative_part_number'));

        // التحقق من وجود الأصناف في catalog_items
        $mainExists = CatalogItem::where('part_number', $mainPartNumber)->exists();
        $altExists = CatalogItem::where('part_number', $alternativePartNumber)->exists();

        if (!$mainExists) {
            return response()->json([
                'success' => false,
                'message' => __('Main part number not found in catalog'),
            ], 404);
        }

        if (!$altExists) {
            return response()->json([
                'success' => false,
                'message' => __('Alternative part number not found in catalog'),
            ], 404);
        }

        if ($mainPartNumber === $alternativePartNumber) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot add item as alternative to itself'),
            ], 400);
        }

        // إضافة البديل
        $added = SkuAlternative::addAlternative($mainPartNumber, $alternativePartNumber);

        if ($added) {
            return response()->json([
                'success' => true,
                'message' => __('Alternative added successfully'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Alternative already exists in this group'),
        ], 400);
    }

    /**
     * إزالة بديل من مجموعة
     * ينقل الصنف لمجموعة جديدة خاصة به
     */
    public function removeAlternative(Request $request)
    {
        $request->validate([
            'part_number' => 'required|string',
        ]);

        $partNumber = trim($request->input('part_number'));

        $removed = SkuAlternative::removeAlternative($partNumber);

        if ($removed) {
            return response()->json([
                'success' => true,
                'message' => __('Alternative removed from group successfully'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Part number not found in alternatives'),
        ], 404);
    }

    /**
     * إحصائيات البدائل
     * يستخدم جدول sku_alternatives مع group_id
     */
    public function stats()
    {
        // إجمالي سجلات sku_alternatives
        $totalRecords = SkuAlternative::count();

        // عدد المجموعات الفريدة
        $totalGroups = SkuAlternative::distinct('group_id')->count('group_id');

        // عدد الأصناف التي لها بدائل (في مجموعة بها أكثر من صنف)
        $groupsWithMultipleItems = DB::table('sku_alternatives')
            ->select('group_id')
            ->groupBy('group_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('group_id');

        $itemsWithAlternatives = SkuAlternative::whereIn('group_id', $groupsWithMultipleItems)->count();

        // عدد الأصناف بدون بدائل (وحيدة في مجموعتها)
        $itemsWithoutAlternatives = $totalRecords - $itemsWithAlternatives;

        // أكبر 10 مجموعات (أكثر بدائل)
        $topGroups = DB::table('sku_alternatives')
            ->select('group_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('group_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        // جلب part_number لكل مجموعة (أول صنف)
        $topItems = $topGroups->map(function ($group) {
            $firstItem = SkuAlternative::where('group_id', $group->group_id)
                ->first();
            return (object) [
                'group_id' => $group->group_id,
                'part_number' => $firstItem?->part_number ?? 'N/A',
                'cnt' => $group->cnt - 1, // عدد البدائل (بدون الصنف نفسه)
            ];
        });

        return view('operator.alternative.stats', compact(
            'totalRecords',
            'totalGroups',
            'itemsWithAlternatives',
            'itemsWithoutAlternatives',
            'topItems'
        ));
    }
}
