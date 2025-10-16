<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Catalog;
use App\Models\Brand;
use App\Models\Specification;
use App\Models\SpecificationItem;
use App\Models\VinDecodedCache;
use App\Models\VinSpecMapped;
use App\Services\CatalogSessionManager;

class Attributes extends Component
{
    protected $listeners = [
        'vinSelected' => 'loadFilters',
        'save' => 'save',
    ];

    public $catalog;
    public $vin;
    public $filters = [];
    public $data = [];
    public $availableYears = [];
    public $availableMonths = [];

    protected CatalogSessionManager $sessionManager;

    public function boot(CatalogSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function mount($catalog = null, $vin = null)
    {
        // 🧹 حذف الجلسات القديمة عند الدخول من الصفحة الرئيسية
        if (request()->routeIs('front.index')) {
            $this->sessionManager->clearAll();
        }

        $this->catalog = is_string($catalog)
            ? Catalog::where('code', $catalog)->first()
            : $catalog;

        $this->vin = $vin;

        $this->generateAvailableDateRanges();

        // 🧠 تحميل الفلاتر من الخدمة الموحدة
        $this->data = $this->sessionManager->getSelectedFilters();
        foreach ($this->data as $key => $item) {
            if (!is_array($item)) {
                $this->data[$key] = [
                    'value_id' => $item,
                    'source' => 'manual',
                ];
            }
        }

        $this->loadFilters();
    }

    protected function generateAvailableDateRanges()
    {
        $this->availableMonths = range(1, 12);

        if (!$this->catalog) return;

        $start = $this->catalog->beginDate;
        $end = $this->catalog->endDate;

        $startYear = ($start && strlen($start) >= 6) ? (int)substr($start, 0, 4) : 1980;
        $endYear = ($end && strlen($end) >= 6 && $end !== '000000') ? (int)substr($end, 0, 4) : date('Y');

        $this->availableYears = range($endYear, $startYear);
    }

    public function loadFilters()
    {
        // ✅ تحميل الفلاتر من رقم الهيكل (VIN)
        if ($this->vin) {
            $this->loadFiltersFromVin();
        }

        // ✅ تحميل الفلاتر من الكتالوج والمواصفات
        if ($this->catalog) {
            $this->loadFiltersFromCatalog();
        }
    }

    protected function loadFiltersFromVin()
    {
        $vinData = VinDecodedCache::where('vin', $this->vin)->first();
        if (!$vinData) return;

        // ✅ Eager load specifications and specification items
        $mappings = VinSpecMapped::with([
            'specification',
            'specificationItem'
        ])->where('vin_id', $vinData->id)->get();

        // ✅ جمع specification IDs لجلب جميع items دفعة واحدة
        $specIds = $mappings->pluck('specification.id')->unique()->filter();

        // ✅ تحميل جميع specification items مرة واحدة بدل حلقة
        $allItems = SpecificationItem::whereIn('specification_id', $specIds->toArray())
            ->when($this->catalog, fn($q) => $q->where('catalog_id', $this->catalog->id))
            ->get()
            ->groupBy('specification_id');

        foreach ($mappings as $map) {
            $spec = $map->specification;
            $item = $map->specificationItem;

            $this->data[$spec->name] = [
                'value_id' => $item->value_id ?? $item->id,
                'source' => 'vin',
            ];

            // ✅ استخدام البيانات المحملة مسبقاً بدل استعلام جديد
            $items = $allItems->get($spec->id, collect());

            $this->filters[$spec->name] = [
                'label' => $spec->label,
                'items' => $items,
                'selected' => $this->data[$spec->name]['value_id'] ?? null,
            ];
        }

        // 🔢 تحميل سنة وشهر التصنيع
        if (!empty($vinData->buildDate) && strlen($vinData->buildDate) >= 6) {
            $this->data['year'] = [
                'value_id' => substr($vinData->buildDate, 0, 4),
                'source' => 'vin',
            ];
            $this->data['month'] = [
                'value_id' => substr($vinData->buildDate, 4, 2),
                'source' => 'vin',
            ];
        }

        $this->sessionManager->setSelectedFilters($this->data);
    }

    protected function loadFiltersFromCatalog()
    {
        // ✅ Eager load specifications with their items filtered by catalog
        $specs = \App\Models\Specification::with(['items' => fn($q) =>
            $q->where('catalog_id', $this->catalog->id)
        ])->get();

        foreach ($specs as $spec) {
            if ($spec->items->count()) {
                $this->filters[$spec->name] = [
                    'label' => $spec->label,
                    'items' => $spec->items,
                    'selected' => $this->data[$spec->name]['value_id'] ?? null,
                ];
            }
        }

        if (!$this->vin) {
            $this->filters['year'] = [
                'label' => 'Production Year',
                'items' => collect($this->availableYears)->map(fn($year) => [
                    'value_id' => $year,
                    'label' => $year,
                ]),
                'selected' => $this->data['year']['value_id'] ?? null,
            ];

            $this->filters['month'] = [
                'label' => 'Production Month',
                'items' => collect($this->availableMonths)->map(fn($month) => [
                    'value_id' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'label' => str_pad($month, 2, '0', STR_PAD_LEFT),
                ]),
                'selected' => $this->data['month']['value_id'] ?? null,
            ];
        }
    }

    public function save()
    {
        if (!$this->vin) {
            // تنظيف البيانات - إزالة القيم الفارغة
            $cleanedData = array_filter($this->data, function($item) {
                if (is_array($item)) {
                    return !empty($item['value_id']);
                }
                return !empty($item);
            });

            $this->sessionManager->setSelectedFilters($cleanedData);
        }

        $savedFilters = $this->sessionManager->getSelectedFilters();

        // فقط إنشاء labeled data إذا كان هناك فلاتر محفوظة
        if (!empty($savedFilters)) {
            $labeledData = $this->generateLabeledData($savedFilters);
            $this->sessionManager->setLabeledFilters($labeledData);
        } else {
            $this->sessionManager->setLabeledFilters([]);
        }

        // إرسال الحدث للـ JavaScript لإعادة تحميل الصفحة
        $this->dispatch('filtersSelected');
    }

    protected function generateLabeledData($mergedData)
    {
        $labeled = [];

        foreach ($mergedData as $key => $filterData) {
            $value_id = is_array($filterData) ? $filterData['value_id'] : $filterData;
            $source = is_array($filterData) ? ($filterData['source'] ?? 'manual') : 'manual';

            $label = $key;
            $displayValue = $value_id;

            if (in_array($key, ['year', 'month'])) {
                $label = $key === 'year' ? 'Production Year' : 'Production Month';
            }

            if (isset($this->filters[$key])) {
                $label = $this->filters[$key]['label'] ?? $key;
                $item = collect($this->filters[$key]['items'])->first(fn($i) => $i['value_id'] == $value_id);
                if ($item) {
                    $displayValue = $item['label'];
                }
            }

            $labeled[$key] = [
                'label' => $label,
                'value' => $displayValue,
                'value_id' => $value_id,
                'source' => $source,
            ];
        }

        return $labeled;
    }

    public function resetFilters()
    {
        if ($this->vin) return;

        // مسح البيانات من الجلسة أولاً
        $this->sessionManager->clearFilters();

        // إعادة تعيين البيانات المحلية بقيم فارغة
        $this->data = [];
        $this->filters = [];

        // إعادة تحميل الفلاتر بدون قيم محددة
        $this->loadFilters();

        // تهيئة $data بقيم فارغة لكل filter موجود
        foreach ($this->filters as $key => $filter) {
            $this->data[$key] = ['value_id' => '', 'source' => 'manual'];
        }

        // إرسال حدث لإعادة تحميل الصفحة بعد المسح
        $this->dispatch('filtersCleared');
    }

    public function render()
    {
        return view('livewire.attributes', [
            'catalogName' => $this->catalog->name ?? null,
            'shortName' => $this->catalog->shortName ?? null,
            'catalogCode' => $this->catalog->code ?? null,
            'source' => $this->vin ? 'VIN' : null,
        ]);
    }
}

