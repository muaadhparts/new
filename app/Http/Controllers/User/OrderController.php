<?php

namespace App\Http\Controllers\User;

use App\{
    Models\Order,
    Models\Product
};

class OrderController extends UserBaseController
{

    public function orders()
    {
        $user = $this->user;
        $orders = Order::where('user_id','=',$user->id)->latest('id')->get();
        return view('user.order.index',compact('user','orders'));
    }

    public function ordertrack()
    {
        $user = $this->user;
        return view('user.order-track',compact('user'));
    }

    public function trackload($id)
    {
        $user = $this->user;
        $order = $user->orders()->where('order_number','=',$id)->first();
        $datas = array('Pending','Processing','On Delivery','Completed');
        return view('load.track-load',compact('order','datas'));

    }


    public function order($id)
    {
        $user = $this->user;
        $order = $user->orders()->whereId($id)->firstOrFail();
        $cart = json_decode($order->cart, true);;
        return view('user.order.details',compact('user','order','cart'));
    }

    public function orderdownload($slug,$id)
    {
        $user = $this->user;
        $order = Order::where('order_number','=',$slug)->first();
        $prod = Product::findOrFail($id);
        if(!isset($order) || $prod->type == 'Physical' || $order->user_id != $user->id)
        {
            return redirect()->back();
        }
        return response()->download(public_path('assets/files/'.$prod->file));
    }

    public function orderprint($id)
    {
        $user = $this->user;
        // Security: Only allow printing own orders
        $order = $user->orders()->whereId($id)->firstOrFail();
        $cart = json_decode($order->cart, true);
        return view('user.order.print',compact('user','order','cart'));
    }

    public function trans()
    {
        $user = $this->user;
        $id = request()->input('id');
        $trans = request()->input('tin');

        // Security: Only allow updating own orders
        $order = $user->orders()->whereId($id)->firstOrFail();

        // Validate transaction ID
        if (empty($trans) || strlen($trans) > 255) {
            return response()->json(['error' => 'Invalid transaction ID'], 400);
        }

        $order->txnid = $trans;
        $order->update();
        $data = $order->txnid;
        return response()->json($data);
    }  

}
