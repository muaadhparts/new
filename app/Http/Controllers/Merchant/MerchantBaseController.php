<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use DB;
use App;
use Auth;
use Session;


class MerchantBaseController extends Controller
{
    protected $gs;
    protected $curr;
    protected $language_id;
    protected $user;
    protected $language;

    public function __construct()
    {
        // Set Global MuaadhSettings (يمكن الوصول إليه بدون auth)
        $this->gs = DB::table('muaadhsettings')->find(1);

        // Middleware للتحقق من المصادقة والإعدادات العامة
        $this->middleware(function ($request, $next) {

            // التحقق من تسجيل الدخول
            if (!Auth::check()) {
                return redirect()->route('user.login');
            }

            // Set Global Users
            $this->user = Auth::user();

            // Set Global Language
            if (Session::has('language')) {
                $this->language = DB::table('languages')->find(Session::get('language'));
            } else {
                $this->language = DB::table('languages')->where('is_default', '=', 1)->first();
            }

            if ($this->language) {
                view()->share('langg', $this->language);
                App::setlocale($this->language->name);
            }

            // Set Global Currency (respect session preference)
            if (Session::has('currency')) {
                $this->curr = DB::table('currencies')->find(Session::get('currency'));
            } else {
                $this->curr = DB::table('currencies')->where('is_default', '=', 1)->first();
            }

            // Share common variables with views
            view()->share('curr', $this->curr);
            view()->share('gs', $this->gs);
            view()->share('user', $this->user);

            return $next($request);
        });
    }
}