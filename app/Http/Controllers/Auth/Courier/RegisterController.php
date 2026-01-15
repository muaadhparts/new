<?php

namespace App\Http\Controllers\Auth\Courier;

use App\{
	Models\Courier,
	Classes\MuaadhMailer,
	Models\Muaadhsetting,
	Http\Controllers\Controller
};
use Illuminate\Http\Request;
use Auth;
use Validator;

class RegisterController extends Controller
{

	public function register(Request $request)
	{


		$gs = Muaadhsetting::findOrFail(1);


		// if ($gs->is_capcha == 1) {
		// 	$request->validate(
		// 		[
		// 			'g-recaptcha-response' => 'required|captcha',
		// 		],
		// 		[
		// 			'g-recaptcha-response.required' => 'Captcha is required.',
		// 			'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
		// 		]
		// 	);
		// }

		$request->validate([
			'name' => 'required',
			'email' => 'required|email|unique:couriers',
			"phone" => "required|unique:couriers",
			'password' => 'required|confirmed',
		]);

		$courier = new Courier();
		$input = $request->all();
		$input['password'] = bcrypt($request['password']);
		$token = md5(time() . $request->name . $request->email);
		$input['email_token'] = $token;
		$courier->fill($input)->save();

		if ($gs->is_verification_email == 1) {
			$to = $request->email;
			$subject = 'Verify your email address.';
			$msg = "Dear Courier,<br>We noticed that you need to verify your email address.<br>Simply click the link below to verify. <a href=" . url('courier/register/verify/' . $token) . ">" . url('courier/register/verify/' . $token) . "</a>";
			//Sending Email To Courier

			$data = [
				'to' => $to,
				'subject' => $subject,
				'body' => $msg,
			];

			$mailer = new MuaadhMailer();
			$mailer->sendCustomMail($data);

			return back()->with('success', 'We need to verify your email address. We have sent an email to ' . $to . ' to verify your email address. Please click link in that email to continue.');


		} else {

			$courier->email_verify = 'Yes';
			$courier->update();

			$data = [
				'to' => $courier->email,
				'type' => "welcome_customer",
				'cname' => $courier->name,
				'oamount' => "",
				'aname' => "",
				'aemail' => "",
				'onumber' => "",
			];
			$mailer = new MuaadhMailer();
			$mailer->sendAutoMail($data);
			Auth::guard('courier')->login($courier);
			return redirect()->route('courier-dashboard');
		}
	}

	public function token($token)
	{
		$gs = Muaadhsetting::findOrFail(1);

		if ($gs->is_verification_email == 1) {
			$courier = Courier::where('email_token', '=', $token)->first();
			if (isset($courier)) {
				$courier->email_verify = 'Yes';
				$courier->update();

				// Welcome Email For User

				$data = [
					'to' => $courier->email,
					'type' => "welcome_customer",
					'cname' => $courier->name,
					'oamount' => "",
					'aname' => "",
					'aemail' => "",
					'onumber' => "",
				];
				$mailer = new MuaadhMailer();
				$mailer->sendAutoMail($data);


				Auth::guard('courier')->login($courier);
				return redirect()->route('courier-dashboard')->with('success', __('Email Verified Successfully'));
			}
		} else {
			return redirect()->back();
		}
	}
}
