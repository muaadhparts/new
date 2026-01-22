<?php

namespace App\Http\Controllers\Operator;

use App\Models\FrontendSetting;
use Illuminate\Http\Request;
use Validator;

class FrontendSettingController extends OperatorBaseController
{
    protected $rules =
    [
        'rightbanner1'          => 'mimes:jpeg,jpg,png,svg',
        'rightbanner2'          => 'mimes:jpeg,jpg,png,svg'
    ];

    protected $customs =
    [
        'rightbanner1.mimes'        => 'Photo type must be in jpeg, jpg, png, svg.',
        'rightbanner2.mimes'        => 'Photo type must be in jpeg, jpg, png, svg.'
    ];

    // Page Settings All post requests will be done in this method
    public function update(Request $request)
    {
        //--- Validation Section
        $validator = Validator::make($request->all(), $this->rules,$this->customs);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        $data = FrontendSetting::findOrFail(1);
        $input = $request->all();

            if ($file = $request->file('rightbanner1'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->rightbanner1);
                $input['rightbanner1'] = $name;
            }
            if ($file = $request->file('rightbanner2'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->rightbanner2);
                $input['rightbanner2'] = $name;
            }

        $data->update($input);
        cache()->forget('frontend_settings');
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }


    public function contact()
    {
        $data = FrontendSetting::find(1);
        return view('operator.frontend-setting.contact',compact('data'));
    }

    public function page_banner()
    {
        $data = FrontendSetting::find(1);
        return view('operator.frontend-setting.page_banner',compact('data'));
    }

    public function right_banner()
    {
        $data = FrontendSetting::find(1);
        return view('operator.frontend-setting.right_banner',compact('data'));
    }

}
