<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\VendorOrder;
use Illuminate\Http\Request;

class OrderController extends VendorBaseController
{

    public function index()
    {
        $user = $this->user;
        $orders = Order::with(array('vendororders' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }))->orderby('id', 'desc')->get()->reject(function ($item) use ($user) {
            if ($item->vendororders()->where('user_id', '=', $user->id)->count() == 0) {
                return true;
            }
            return false;
        })->paginate(3);

        return view('vendor.order.index', compact('orders', 'user'));
    }

    public function show($slug)
    {
        $user = $this->user;
        $order = Order::where('order_number', '=', $slug)->first();
        $cart = json_decode($order->cart, true);
        return view('vendor.order.details', compact('user', 'order', 'cart'));
    }

    public function license(Request $request, $slug)
    {
        $order = Order::where('order_number', '=', $slug)->first();
        $cart = json_decode($order->cart, true);
        $cart['items'][$request->license_key]['license'] = $request->license;
        $new_cart = json_encode($cart);
        $order->cart = $new_cart;
        $order->update();
        $msg = __('Successfully Changed The License Key.');
        return redirect()->back()->with('license', $msg);
    }

    public function invoice($slug)
    {
        $user = $this->user;
        $order = Order::where('order_number', '=', $slug)->first();
        $cart = json_decode($order->cart, true);
        return view('vendor.order.invoice', compact('user', 'order', 'cart'));
    }

    public function printpage($slug)
    {
        $user = $this->user;
        $order = Order::where('order_number', '=', $slug)->first();
        $cart = json_decode($order->cart, true);
        return view('vendor.order.print', compact('user', 'order', 'cart'));
    }

    public function status($slug, $status)
    {
        $mainorder = VendorOrder::where('order_number', '=', $slug)->first();
        if ($mainorder->status == "completed") {
            return redirect()->back()->with('success', __('This Order is Already Completed'));
        } else {
            $user = $this->user;
            VendorOrder::where('order_number', '=', $slug)->where('user_id', '=', $user->id)->update(['status' => $status]);
            return redirect()->route('vendor-order-index')->with('success', __('Order Status Updated Successfully'));
        }
    }
}
