<?php

namespace App\Http\Controllers\Operator;

use App\Models\ConnectConfig;
use Illuminate\Http\Request;

class ConnectConfigController extends OperatorBaseController
{

    // Connect Config All post requests will be done in this method
    public function socialupdate(Request $request)
    {
        //--- Logic Section
        $input = $request->all();
        $data = ConnectConfig::findOrFail(1);
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    // Connect Config All post requests will be done in this method
    public function socialupdateall(Request $request)
    {
        //--- Validation Section

        //--- Validation Section Ends

        //--- Logic Section
        $input = $request->all();
        $data = ConnectConfig::findOrFail(1);
        if ($request->f_status == ""){
            $input['f_status'] = 0;
        }
        if ($request->t_status == ""){
            $input['t_status'] = 0;
        }
        if ($request->g_status == ""){
            $input['g_status'] = 0;
        }
        if ($request->l_status == ""){
            $input['l_status'] = 0;
        }
        if ($request->d_status == ""){
            $input['d_status'] = 0;
        }
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = 'Data Updated Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    public function index()
    {
    	$data = ConnectConfig::findOrFail(1);
        return view('operator.connect-config.index',compact('data'));
    }

    public function facebook()
    {
    	$data = ConnectConfig::findOrFail(1);
        return view('operator.connect-config.facebook',compact('data'));
    }

    public function google()
    {
    	$data = ConnectConfig::findOrFail(1);
        return view('operator.connect-config.google',compact('data'));
    }

    public function facebookup($status)
    {
        $data = ConnectConfig::findOrFail(1);
        $data->f_check = $status;
        $data->update();

        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    public function googleup($status)
    {
        $data = ConnectConfig::findOrFail(1);
        $data->g_check = $status;
        $data->update();

        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

}
