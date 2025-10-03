<?php

namespace App\Livewire;

use App\Services\CompatibilityService;
use Livewire\Component;

/**
 * مكون موحد لعرض توافق القطعة مع الكتالوجات
 * الآن يستخدم CompatibilityService
 */
class Compatibility extends Component
{
    public $sku;
    public $results;
    public $displayMode = 'list'; // أو 'tabs'

    protected CompatibilityService $compatibilityService;

    public function boot(CompatibilityService $compatibilityService)
    {
        $this->compatibilityService = $compatibilityService;
    }

    public function mount($sku, $displayMode = 'list')
    {
        $this->sku = $sku;
        $this->displayMode = $displayMode;
        $this->results = $this->compatibilityService->getCompatibleCatalogs($sku);
    }

    /**
     * للتوافق مع الكود القديم
     */
    public function getCompatibility()
    {
        return $this->results;
    }

    public function render()
    {
        // اختيار View بناءً على displayMode
        $viewName = $this->displayMode === 'tabs'
            ? 'livewire.compatibility-tabs'
            : 'livewire.compatibility';

        return view($viewName, [
            'results' => $this->results,
            'sku' => $this->sku,
        ]);
    }
}
