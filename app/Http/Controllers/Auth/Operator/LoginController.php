<?php

namespace App\Http\Controllers\Auth\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Validator;

class LoginController extends Controller
{
    public function __construct()
    {
      $this->middleware('guest:operator', ['except' => ['logout']]);
    }
    
    public function showForm()
    {
      return view('operator.login');
    }


    public function login(Request $request)
    {
        //--- Validation Section
        $rules = [
                  'email'   => 'required|email',
                  'password' => 'required'
                ];

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

      // Attempt to log the user in
      if (Auth::guard('operator')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
        // if successful, then redirect to their intended location
        return response()->json(redirect()->intended(route('operator.dashboard'))->getTargetUrl());
      }

      // if unsuccessful, then redirect back to the login with the form data
          return response()->json(array('errors' => [ 0 => 'Credentials Doesn\'t Match !' ]));     
    }


    public function logout(Request $request)
    {
        Auth::guard('operator')->logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        return redirect('/');
    }
}