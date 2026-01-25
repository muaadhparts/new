<?php

namespace App\Http\Controllers\User;

use App\Domain\Catalog\Models\CatalogEvent;
use App\Domain\Identity\Models\OauthAccount;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Socialite;

class SocialRegisterController extends Controller
{

    public function __construct()
    {
      $ps = platformSettings();
      Config::set('services.google.client_id', $ps->get('google_client_id'));
      Config::set('services.google.client_secret', $ps->get('google_client_secret'));
      Config::set('services.google.redirect', url('/auth/google/callback'));
      Config::set('services.facebook.client_id', $ps->get('facebook_app_id'));
      Config::set('services.facebook.client_secret', $ps->get('facebook_app_secret'));
      $url = url('/auth/facebook/callback');
      $url = preg_replace("/^http:/i", "https:", $url);
      Config::set('services.facebook.redirect', $url);
    }

    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try
        {
            $socialUser = Socialite::driver($provider)->user();
        }
        catch(\Exception $e)
        {
            return redirect('/');
        }
        //check if we have logged provider
        $oauthAccount = OauthAccount::where('provider_id',$socialUser->getId())->first();
        if(!$oauthAccount)
        {
            if(User::where('email',$socialUser->email)->exists())
            {
                $auser = User::where('email',$socialUser->email)->first();
                Auth::guard('web')->login($auser); 
                return redirect()->route('user-dashboard');
            }

            //create a new user and provider
            $user = new User;
            $user->email = $socialUser->email;
            $user->name = $socialUser->name;
            $user->photo = $socialUser->avatar_original;
            $user->email_verified = 'Yes';
            $user->is_provider = 1;
            $user->affilate_code = $socialUser->name.$socialUser->email;
            $user->affilate_code = md5($user->affilate_code);
            $user->save();

            $user->oauthAccounts()->create(
                ['provider_id' => $socialUser->getId(), 'provider' => $provider]
            );
            $notification = new CatalogEvent;
            $notification->user_id = $user->id;
            $notification->save();

        }
        else
        {

            $user = $oauthAccount->user;
        }

        Auth::guard('web')->login($user); 
        return redirect()->route('user-dashboard');

    }
}
