<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class CalloutModal extends Component
{
    public $data;
    public $code;
    public $callout;
    public $products = [];
    public $isOpen = false;
    public $isLoading = false;


    protected $listeners = ['openCalloutModal' => 'loadCalloutData'];

    public function loadCalloutData($payload)
    {
        $this->isLoading = true;

        // تحميل البيانات
        $this->data = $payload['data'];
        $this->code = $payload['code'];
        $this->callout = $payload['callout'];

        $this->products = DB::table(Str::lower($this->data))
            ->select('callout', 'partnumber', 'label_en', 'label_ar')
            ->where('code', $this->code)
            ->where('callout', $this->callout)
            ->orderBy('partnumber')
            ->get()
            ->toArray();

        $this->isLoading = false;
        $this->isOpen = true;
    }


    public function closeModal()
    {
        $this->reset(['products', 'isOpen', 'data', 'code', 'callout']);
    }


    public function render()
    {
        return view('livewire.callout-viewer-modal');
    }
}
