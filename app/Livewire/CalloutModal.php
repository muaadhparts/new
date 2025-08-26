<?php

namespace App\Livewire;

use App\Models\NewCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class CalloutModal extends Component
{



    public function render()
    {
        return view('livewire.callout-viewer-modal');
    }
}