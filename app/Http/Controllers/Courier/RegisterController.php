<?php

namespace App\Http\Controllers\Courier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domain\Identity\Models\User;
use App\Classes\MuaadhMailer;
use App\Http\Controllers\Front\FrontBaseController;
use App\Domain\Identity\Models\UserCatalogEvent;
use Auth;

use Validator;

class RegisterController extends FrontBaseController
{

	public function showRegisterForm()
	{

		return view('courier.register');
	}



	public function token($token)
	{
		if (setting('is_verification_email') == 1) {
			$user = User::where('verification_link', '=', $token)->first();
			if (isset($user)) {
				$user->email_verified = 'Yes';
				$user->update();
				$notification = new UserCatalogEvent;
				$notification->user_id = $user->id;
				$notification->save();
				Auth::guard('web')->login($user);
				return redirect()->route('user-dashboard')->with('success', 'Email Verified Successfully');
			}
		} else {
			return redirect()->back();
		}
	}
}
