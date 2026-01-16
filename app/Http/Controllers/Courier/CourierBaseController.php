<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use DB;
use App;
use Auth;
use Session;

class CourierBaseController extends Controller
{
    protected $gs;
    protected $curr;
    protected $language;
    protected $courier;

    public function __construct()
    {
        $this->middleware('auth:courier');

        // Set Global MuaadhSettings
        $this->gs = DB::table('muaadhsettings')->find(1);

        $this->middleware(function ($request, $next) {

        // Set Global Users
        $this->courier = Auth::guard('courier')->user();

            // Set Global Language

            if (Session::has('language'))
            {
                $this->language = DB::table('languages')->find(Session::get('language'));
            }
            else
            {
                $this->language = DB::table('languages')->where('is_default','=',1)->first();
            }
            view()->share('langg', $this->language);
            App::setlocale($this->language->name);

            // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
            $this->curr = monetaryUnit()->getCurrent();

            // Share common variables with views
            view()->share('gs', $this->gs);
            view()->share('curr', $this->curr);
            view()->share('courier', $this->courier);

            return $next($request);
        });
    }

}
