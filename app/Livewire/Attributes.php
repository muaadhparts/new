<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Catalog;
use App\Models\Brand;
use App\Models\Specification;
use App\Services\CatalogSessionManager;
use App\Services\CategoryFilterService;
use Illuminate\Support\Facades\Session;

class Attributes extends Component
{
    // ========================================
    // Public Properties (Livewire State)
    // ========================================

    public ?string $catalogCode = null;
    public ?int $catalogId = null;
    public bool $isVinMode = false;

    // بيانات الفلاتر: key => ['label' => '...', 'items' => [...], 'readonly' => bool]
    public array $filters = [];

    // القيم المختارة: key => value_id (string)
    public array $selectedValues = [];

    // ========================================
    // Lifecycle
    // ========================================

    public function boot()
    {
        // Services are injected fresh on each request
    }

    public function mount($catalog = null)
    {
        // تحميل الكتالوج
        if (is_string($catalog)) {
            $catalogModel = Catalog::where('code', $catalog)->first();
            if ($catalogModel) {
                $this->catalogCode = $catalogModel->code;
                $this->catalogId = $catalogModel->id;
            }
        } elseif ($catalog instanceof Catalog) {
            $this->catalogCode = $catalog->code;
            $this->catalogId = $catalog->id;
        }

        // التحقق من وضع VIN
        $vin = Session::get('vin');
        $this->isVinMode = !empty($vin);

        // تحميل القيم المحفوظة من الجلسة
        $this->loadSavedFilters();

        // تحميل الفلاتر المتاحة
        $this->loadAvailableFilters();
    }

    // ========================================
    // Helper: Get Catalog Model
    // ========================================

    protected function getCatalog(): ?Catalog
    {
        if (!$this->catalogId) return null;
        return Catalog::find($this->catalogId);
    }

    protected function getSessionManager(): CatalogSessionManager
    {
        return app(CatalogSessionManager::class);
    }

    protected function getFilterService(): CategoryFilterService
    {
        return app(CategoryFilterService::class);
    }

    // ========================================
    // Data Loading
    // ========================================

    protected function loadSavedFilters(): void
    {
        $saved = $this->getSessionManager()->getSelectedFilters();

        foreach ($saved as $key => $value) {
            if (is_array($value) && isset($value['value_id'])) {
                $this->selectedValues[$key] = (string) $value['value_id'];
            } elseif (!is_array($value)) {
                $this->selectedValues[$key] = (string) $value;
            }
        }
    }

    protected function loadAvailableFilters(): void
    {
        $catalog = $this->getCatalog();
        if (!$catalog) return;

        $savedFilters = $this->getSessionManager()->getSelectedFilters();

        // تحميل المواصفات المتاحة للكتالوج
        $specs = Specification::with(['items' => function ($q) use ($catalog) {
            $q->where('catalog_id', $catalog->id);
        }])->get();

        foreach ($specs as $spec) {
            if ($spec->items->isEmpty()) continue;

            // تحديد إذا كانت القيمة من VIN
            $isFromVin = isset($savedFilters[$spec->name])
                && is_array($savedFilters[$spec->name])
                && ($savedFilters[$spec->name]['source'] ?? '') === 'vin';

            $this->filters[$spec->name] = [
                'label' => $spec->label ?? $spec->name,
                'items' => $spec->items->map(fn($item) => [
                    'value_id' => $item->value_id,
                    'label' => $item->label ?? $item->value_id,
                ])->toArray(),
                'readonly' => $isFromVin,
            ];
        }

        // إضافة فلاتر السنة والشهر
        $this->addDateFilters($catalog, $savedFilters);
    }

    protected function addDateFilters(Catalog $catalog, array $savedFilters): void
    {
        // حساب نطاق السنوات
        $start = $catalog->beginDate;
        $end = $catalog->endDate;

        $startYear = ($start && strlen($start) >= 4) ? (int)substr($start, 0, 4) : 1980;
        $endYear = ($end && strlen($end) >= 4 && $end !== '000000') ? (int)substr($end, 0, 4) : (int)date('Y');

        $years = range($endYear, $startYear);
        $months = range(1, 12);

        // هل السنة والشهر من VIN؟
        $yearFromVin = isset($savedFilters['year'])
            && is_array($savedFilters['year'])
            && ($savedFilters['year']['source'] ?? '') === 'vin';

        $monthFromVin = isset($savedFilters['month'])
            && is_array($savedFilters['month'])
            && ($savedFilters['month']['source'] ?? '') === 'vin';

        $this->filters['year'] = [
            'label' => __('Production Year'),
            'items' => collect($years)->map(fn($y) => [
                'value_id' => (string)$y,
                'label' => (string)$y,
            ])->toArray(),
            'readonly' => $yearFromVin,
        ];

        $this->filters['month'] = [
            'label' => __('Production Month'),
            'items' => collect($months)->map(fn($m) => [
                'value_id' => str_pad($m, 2, '0', STR_PAD_LEFT),
                'label' => str_pad($m, 2, '0', STR_PAD_LEFT),
            ])->toArray(),
            'readonly' => $monthFromVin,
        ];
    }

    // ========================================
    // Actions
    // ========================================

    public function save(): void
    {
        \Log::info('Attributes::save() called', [
            'isVinMode' => $this->isVinMode,
            'selectedValues' => $this->selectedValues,
        ]);

        // في وضع VIN لا نغير شيء، فقط أغلق
        if ($this->isVinMode) {
            $this->dispatch('specs-saved');
            return;
        }

        // بناء البيانات للحفظ بنفس البنية المتوقعة
        $dataToSave = [];

        foreach ($this->selectedValues as $key => $value) {
            if (!empty($value) && $value !== '') {
                $dataToSave[$key] = [
                    'value_id' => $value,
                    'source' => 'manual',
                ];
            }
        }

        \Log::info('Attributes::save() dataToSave', ['dataToSave' => $dataToSave]);

        // حفظ في الجلسة
        $this->getSessionManager()->setSelectedFilters($dataToSave);

        // تحديث أكواد Level3 المسموحة
        $this->updateAllowedCodes();

        \Log::info('Attributes::save() completed, dispatching specs-saved');

        // إرسال حدث للـ JavaScript
        $this->dispatch('specs-saved');
    }

    public function clearFilters(): void
    {
        if ($this->isVinMode) return;

        // مسح الجلسة
        $this->getSessionManager()->clearFilters();

        // إعادة تعيين القيم المحلية
        $this->selectedValues = [];

        // تحديث أكواد Level3
        $this->updateAllowedCodes(true);

        // إرسال حدث
        $this->dispatch('specs-cleared');
    }

    protected function updateAllowedCodes(bool $clearAll = false): void
    {
        $catalog = $this->getCatalog();
        if (!$catalog) return;

        $brand = Brand::find($catalog->brand_id);
        if (!$brand) return;

        $filterService = $this->getFilterService();
        $sessionManager = $this->getSessionManager();

        if ($clearAll) {
            $allowedCodes = $filterService->getFilteredLevel3FullCodes(
                $catalog,
                $brand,
                null,
                []
            );
        } else {
            $specItemIds = $sessionManager->getSpecItemIds($catalog);
            $filterDate = $sessionManager->getFilterDate();

            $allowedCodes = $filterService->getFilteredLevel3FullCodes(
                $catalog,
                $brand,
                $filterDate,
                $specItemIds
            );
        }

        $sessionManager->setAllowedLevel3Codes($allowedCodes);
    }

    // ========================================
    // Computed
    // ========================================

    public function getSelectedCountProperty(): int
    {
        return collect($this->selectedValues)->filter(fn($v) => !empty($v))->count();
    }

    // ========================================
    // Render
    // ========================================

    public function render()
    {
        $catalog = $this->getCatalog();

        return view('livewire.attributes', [
            'catalogName' => $catalog->name ?? $catalog->shortName ?? $this->catalogCode ?? '',
            'selectedCount' => $this->selectedCount,
        ]);
    }
}
