<?php

namespace App\Livewire;

use App\Services\AlternativeService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * مكون البدائل - يعرض البدائل مع المنتج الأساسي
 */
class Alternative extends Component
{
    public string $sku;
    public Collection $alternatives;

    protected AlternativeService $alternativeService;

    public function boot(AlternativeService $alternativeService)
    {
        $this->alternativeService = $alternativeService;
    }

    public function mount(string $sku): void
    {
        $this->sku = $sku;

        // جلب البدائل مع تضمين المنتج الأساسي
        $this->alternatives = $this->alternativeService->getAlternatives($sku, true);
    }

    public function render()
    {
        return view('livewire.alternative', [
            'alternatives' => $this->alternatives,
        ]);
    }
}
