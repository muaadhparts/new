<?php

namespace App\Http\Controllers\Operator;

use App\Models\CatalogEvent;
use DB;

class NotificationController extends OperatorBaseController
{
  public function all_notf_count()
  {
      $user_count = DB::table('notifications')->where('user_id','!=',null)->where('is_read','=',0)->count();
      $purchase_count = DB::table('notifications')->where('purchase_id','!=',null)->where('is_read','=',0)->count();
      $product_count = DB::table('notifications')->where('catalog_item_id','!=',null)->where('is_read','=',0)->count();
      $conv_count = DB::table('notifications')->where('chat_thread_id','!=',null)->where('is_read','=',0)->count();

      $data = array();        
      $data['user_count'] = $user_count;
      $data['conv_count'] = $conv_count;
      $data['order_count'] = $purchase_count;
      $data['product_count'] = $product_count;

      return response()->json($data);            
  } 

  public function user_notf_clear()
  {
      $data = CatalogEvent::where('user_id','!=',null);
      $data->delete();        
  } 

  public function user_notf_show()
  {
      $datas = CatalogEvent::where('user_id','!=',null)->latest('id')->get();
      if($datas->count() > 0){
        foreach($datas as $data){
          $data->is_read = 1;
          $data->update();
        }
      }       
      return view('operator.notification.register',compact('datas'));           
  } 

  public function purchase_notf_clear()
  {
      $data = CatalogEvent::where('purchase_id','!=',null);
      $data->delete();
  }

  public function purchase_notf_show()
  {
      $datas = CatalogEvent::where('purchase_id','!=',null)->latest('id')->get();
      if($datas->count() > 0){
        foreach($datas as $data){
          $data->is_read = 1;
          $data->update();
        }
      }
      return view('operator.notification.purchase',compact('datas'));           
  } 

  public function catalogItem_notf_clear()
  {
      $data = CatalogEvent::where('catalog_item_id','!=',null);
      $data->delete();        
  } 

  public function catalogItem_notf_show()
  {
      $datas = CatalogEvent::where('catalog_item_id','!=',null)->latest('id')->get();
      if($datas->count() > 0){
        foreach($datas as $data){
          $data->is_read = 1;
          $data->update();
        }
      }       
      return view('operator.notification.catalogItem',compact('datas'));           
  } 

  public function conv_notf_clear()
  {
      $data = CatalogEvent::where('chat_thread_id','!=',null);
      $data->delete();        
  } 

  public function conv_notf_show()
  {
      $datas = CatalogEvent::where('chat_thread_id','!=',null)->latest('id')->get();
      if($datas->count() > 0){
        foreach($datas as $data){
          $data->is_read = 1;
          $data->update();
        }
      }       
      return view('operator.notification.message',compact('datas'));           
  } 

}