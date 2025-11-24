<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class AdminBaseController extends Controller
{
    protected $gs;
    protected $curr;
    protected $language_id;
    protected $language;
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->gs = DB::table('generalsettings')->find(1);
        $this->language = DB::table('languages')->where('is_default', '=', 1)->first();
        view()->share('langg', $this->language);
        if ($this->language) {
            App::setlocale($this->language->name);
        } else {
            // Fallback to English if no default language is set
            App::setlocale('en');
        }
        $this->curr = DB::table('currencies')->where('is_default', '=', 1)->first();
    }
}
