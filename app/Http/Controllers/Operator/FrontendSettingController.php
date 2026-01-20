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


    public function homeupdate(Request $request)
    {
        $data = FrontendSetting::findOrFail(1);
        $input = $request->all();

        if ($request->category == ""){
            $input['category'] = 0;
        }
        if ($request->our_services == ""){
            $input['our_services'] = 0;
        }
        if ($request->blog == ""){
            $input['blog'] = 0;
        }
        if ($request->third_left_banner == ""){
            $input['third_left_banner'] = 0;
        }
        if ($request->brand == ""){
            $input['brand'] = 0;
        }
        if ($request->top_brand == ""){
            $input['top_brand'] = 0;
        }


        $data->update($input);

        cache()->forget('frontend_settings');
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }

    public function menuupdate(Request $request)
    {
        $data = FrontendSetting::findOrFail(1);
        $input = $request->all();

        if ($request->home == ""){
            $input['home'] = 0;
        }
        if ($request->blog == ""){
            $input['blog'] = 0;
        }
        if ($request->faq == ""){
            $input['faq'] = 0;
        }
        if ($request->contact == ""){
            $input['contact'] = 0;
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

    public function customize()
    {
        $data = FrontendSetting::find(1);
        return view('operator.frontend-setting.customize',compact('data'));
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

    public function menu_links()
    {
        $data = FrontendSetting::find(1);
        return view('operator.frontend-setting.menu_links',compact('data'));
    }

}
