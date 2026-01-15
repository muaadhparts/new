<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class OperatorBaseController extends Controller
{
    protected $gs;
    protected $curr;
    protected $language_id;
    protected $language;
    public function __construct()
    {
        $this->middleware('auth:operator');
        $this->gs = DB::table('muaadhsettings')->find(1);
        $this->language = DB::table('languages')->where('is_default', '=', 1)->first();
        $this->curr = DB::table('monetary_units')->where('is_default', '=', 1)->first();

        // Share common variables with views
        view()->share('langg', $this->language);
        view()->share('gs', $this->gs);
        view()->share('curr', $this->curr);

        if ($this->language) {
            App::setlocale($this->language->name);
        } else {
            App::setlocale('en');
        }
    }
}
