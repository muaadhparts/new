<?php

namespace App\Livewire;

use App\Services\CompatibilityService;
use Livewire\Component;

/**
 * مكون Compatibility Tabs - يعرض التوافق بصيغة تبويبات
 */
class CompatibilityTabs extends Component
{
    public $sku;
    public $results;

    protected CompatibilityService $compatibilityService;

    public function boot(CompatibilityService $compatibilityService)
    {
        $this->compatibilityService = $compatibilityService;
    }

    public function mount($sku)
    {
        $this->sku = $sku;
        $this->results = $this->compatibilityService->getCompatibleCatalogs($sku);
    }

    public function render()
    {
        return view('livewire.compatibility-tabs', [
            'results' => $this->results,
            'sku'     => $this->sku,
        ]);
    }
}
