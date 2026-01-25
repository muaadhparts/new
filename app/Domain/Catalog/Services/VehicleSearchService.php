<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\NewCategory;
use App\Traits\NormalizesInput;
use Illuminate\Support\Facades\DB;

/**
 * Service for vehicle parts search operations.
 * Handles catalog part search, suggestions, and callout retrieval.
 */
class VehicleSearchService
{
    use NormalizesInput;

    protected int $maxResults = 1000;

    /**
     * Find catalog by code.
     *
     * @param string $catalogCode
     * @param bool $withBrand
     * @return Catalog|null
     */
    public function findCatalogByCode(string $catalogCode, bool $withBrand = false): ?Catalog
    {
        $query = Catalog::where('code', $catalogCode);

        if ($withBrand) {
            $query->with('brand');
        }

        return $query->first();
    }

    /**
     * Get label suggestions for autocomplete.
     *
     * @param string $catalogCode
     * @param string $query
     * @param array $allowedCodes
     * @return array
     */
    public function getLabelSuggestions(string $catalogCode, string $query, array $allowedCodes): array
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

        // Use LIKE prefix for first word to utilize index
        $firstWord = array_shift($words);
        $base->where(function ($q) use ($firstWord) {
            $q->where('p.label_en', 'like', "{$firstWord}%")
              ->orWhere('p.label_ar', 'like', "{$firstWord}%");
        });

        // Remaining words use full LIKE
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
     * Fetch callouts by part number.
     *
     * @param string $catalogCode
     * @param string $query
     * @param array $allowedCodes
     * @return array
     */
    public function fetchCalloutsByNumber(string $catalogCode, string $query, array $allowedCodes): array
    {
        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $cleanQuery = preg_replace('/[^0-9A-Za-z]+/', '', $query ?? '');

        if (empty($cleanQuery) || empty($allowedCodes)) {
            return [];
        }

        return DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes)
            ->where(function ($q) use ($cleanQuery) {
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
     * Fetch callouts by label (name).
     *
     * @param string $catalogCode
     * @param string $query
     * @param array $allowedCodes
     * @return array
     */
    public function fetchCalloutsByLabel(string $catalogCode, string $query, array $allowedCodes): array
    {
        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        if (empty($query) || empty($allowedCodes)) {
            return [];
        }

        return DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes)
            ->where(function ($q) use ($query) {
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
     * Enrich callout options with category keys.
     *
     * @param array $options
     * @param Catalog $catalog
     * @return array
     */
    public function enrichCalloutOptionsWithKeys(array $options, Catalog $catalog): array
    {
        if (empty($options)) {
            return [];
        }

        $codes = collect($options)->pluck('category_code')->filter()->unique()->values()->all();

        if (empty($codes)) {
            return $options;
        }

        $cats = NewCategory::query()
            ->where('catalog_id', $catalog->id)
            ->whereIn('full_code', $codes)
            ->select('id', 'full_code', 'parents_key', 'spec_key', 'Applicability')
            ->get()
            ->keyBy('full_code');

        $catIds = $cats->pluck('id')->filter()->unique()->values()->all();

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
     * Build callout options from raw rows.
     *
     * @param array $rows
     * @param array $allowedCodes
     * @return array
     */
    public function buildCalloutOptions(array $rows, array $allowedCodes): array
    {
        return collect($rows)
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
    }

    /**
     * Build dynamic table name.
     *
     * @param string $base
     * @param string $catalogCode
     * @return string
     */
    protected function dyn(string $base, string $catalogCode): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $catalogCode)) {
            throw new \Exception('Invalid catalog code');
        }
        return strtolower("{$base}_{$catalogCode}");
    }
}
