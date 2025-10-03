<?php

namespace App\Livewire;

use App\Services\AlternativeService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * مكون موحد للبدائل - الآن يستخدم AlternativeService
 * يمكن استخدامه لعرض البدائل فقط أو البدائل مع المنتج الأساسي
 */
class Alternativeproduct extends Component
{
    public string $sku;
    public Collection $alternatives;
    public bool $includeSelf = false;

    protected AlternativeService $alternativeService;

    public function boot(AlternativeService $alternativeService)
    {
        $this->alternativeService = $alternativeService;
    }

    public function mount(string $sku, bool $includeSelf = false): void
    {
        $this->sku = $sku;
        $this->includeSelf = $includeSelf;
        $this->alternatives = $this->alternativeService->getAlternatives($sku, $includeSelf);
    }

    public function render()
    {
        return view('livewire.alternativeproduct', [
            'alternatives' => $this->alternatives,
        ]);
    }

    /**
     * للتوافق مع الكود القديم
     */
    public function getalternatives()
    {
        return $this->alternatives;
    }
}
