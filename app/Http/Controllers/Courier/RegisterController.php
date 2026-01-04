<?php

namespace App\Http\Controllers\Courier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Muaadhsetting;
use App\Models\User;
use App\Classes\MuaadhMailer;
use App\Http\Controllers\Front\FrontBaseController;
use App\Models\CatalogEvent;
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
		$gs = Muaadhsetting::findOrFail(1);

		if ($gs->is_verification_email == 1) {
			$user = User::where('verification_link', '=', $token)->first();
			if (isset($user)) {
				$user->email_verified = 'Yes';
				$user->update();
				$notification = new CatalogEvent;
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
