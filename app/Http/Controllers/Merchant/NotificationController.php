<?php

namespace App\Http\Controllers\Merchant;

use App\Models\UserCatalogEvent;

class NotificationController extends MerchantBaseController
{

    public function order_notf_count($id)
    {
        $data = UserCatalogEvent::where('user_id','=',$id)->where('is_read','=',0)->get()->count();
        return response()->json($data);
    }

    public function order_notf_clear($id)
    {
        $data = UserCatalogEvent::where('user_id','=',$id);
        $data->delete();
        return back()->with("success","Notification Clear Successfully");
    }

    public function order_notf_show($id)
    {
        $datas = UserCatalogEvent::where('user_id','=',$id)->get();
        if($datas->count() > 0){
          foreach($datas as $data){
            $data->is_read = 1;
            $data->update();
          }
        }       
        return view('merchant.notification.order',compact('datas'));           
    } 
}
