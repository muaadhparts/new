<?php

namespace App\Http\Controllers\Operator;

use App\Models\FrontendSetting;
use Illuminate\Http\Request;

class FrontendSettingController extends OperatorBaseController
{
    /**
     * تحديث إعدادات الواجهة الأمامية
     */
    public function update(Request $request)
    {
        $data = FrontendSetting::findOrFail(1);
        $data->update($request->only($data->getFillable()));

        cache()->forget('frontend_settings');

        return response()->json(__('Data Updated Successfully.'));
    }

    /**
     * صفحة إعدادات التواصل
     */
    public function contact()
    {
        $data = FrontendSetting::find(1);
        return view('operator.frontend-setting.contact', compact('data'));
    }
}
