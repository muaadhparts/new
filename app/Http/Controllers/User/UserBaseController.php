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

        // Set Global Platform Settings
        $this->gs = platformSettings();

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

            if ($this->language) {
                App::setlocale($this->language->name);
            } else {
                App::setlocale('en');
            }
    
            // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
            $this->curr = monetaryUnit()->getCurrent();

            // Share common variables with views
            view()->share('gs', $this->gs);
            view()->share('curr', $this->curr);
            view()->share('user', $this->user);

            return $next($request);
        });
    }

}
