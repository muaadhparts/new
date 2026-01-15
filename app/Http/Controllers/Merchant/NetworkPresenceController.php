<?php

namespace App\Http\Controllers\Merchant;

use App\Models\NetworkPresence;
use Illuminate\Http\Request;

class NetworkPresenceController extends MerchantBaseController
{

    public function index()
    {
        $datas = $this->user->networkPresences()->latest('id')->get();
        return view('merchant.network-presence.index', compact('datas'));
    }

    public function create()
    {
        return view('merchant.network-presence.create');
    }

    //*** POST Request
    public function store(Request $request)
    {

        //--- Logic Section
        $data = new NetworkPresence;
        $input = $request->all();
        $input['user_id'] = $this->user->id;
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return back()->with('success', $msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = NetworkPresence::findOrFail($id);
        return view('merchant.network-presence.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Logic Section
        $data = NetworkPresence::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return redirect()->route('merchant-network-presence-index')->with('success', $msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $data = NetworkPresence::findOrFail($id1);
        if ($data->user_id == $this->user->id) {
            $data->status = $id2;
            $data->update();
            //--- Redirect Section
            $msg = 'Status Updated Successfully.';
            return back()->with('success', $msg);
            //--- Redirect Section Ends
        }
    }

    //*** GET Request
    public function destroy($id)
    {
        $data = NetworkPresence::findOrFail($id);
        if ($data->user_id == $this->user->id) {
            $data->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return redirect()->route('merchant-network-presence-index')->with('success', $msg);
            //--- Redirect Section Ends
        }
    }
}
