<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use DB;
use App;
use Auth;
use Session;

class UserBaseController extends Controller
{
    protected $gs;
    protected $curr;
    protected $language_id;
    protected $user;
    protected $language;

    public function __construct()
    {
        $this->middleware('auth');

        // Set Global MuaadhSettings
        $this->gs = DB::table('muaadhsettings')->find(1);

        $this->middleware(function ($request, $next) {

        // Set Global Users
        $this->user = Auth::user();

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
    
            // Set Global MonetaryUnit

            if (Session::has('currency')) {
                $this->curr = DB::table('monetary_units')->find(Session::get('currency'));
            }
            else {
                $this->curr = DB::table('monetary_units')->where('is_default','=',1)->first();
            }

            // Share common variables with views
            view()->share('gs', $this->gs);
            view()->share('curr', $this->curr);
            view()->share('user', $this->user);

            return $next($request);
        });
    }

}
