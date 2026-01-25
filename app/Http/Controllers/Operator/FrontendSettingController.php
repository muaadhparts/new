<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Platform\Models\FrontendSetting;
use Illuminate\Http\Request;

class FrontendSettingController extends OperatorBaseController
{
    /**
     * تحديث إعدادات الواجهة الأمامية
     */
    public function update(Request $request)
    {
        $data = FrontendSetting::firstOrCreate(['id' => 1], [
            'contact_email' => '',
            'street' => '',
            'phone' => '',
            'fax' => '',
            'email' => '',
        ]);

        $data->update($request->only($data->getFillable()));

        cache()->forget('frontend_settings');

        return response()->json(__('Data Updated Successfully.'));
    }

    /**
     * صفحة إعدادات التواصل
     */
    public function contact()
    {
        $data = FrontendSetting::firstOrCreate(['id' => 1], [
            'contact_email' => '',
            'street' => '',
            'phone' => '',
            'fax' => '',
            'email' => '',
        ]);
        return view('operator.frontend-setting.contact', compact('data'));
    }
}
