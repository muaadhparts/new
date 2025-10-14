<?php

namespace App\Http\Controllers\Vendor;

use App\Models\SocialLink;
use Illuminate\Http\Request;

class SocialLinkController extends VendorBaseController
{

    public function index()
    {
        $datas = $this->user->sociallinks()->latest('id')->get();
        return view('vendor.sociallink.index', compact('datas'));
    }

    public function create()
    {
        return view('vendor.sociallink.create');
    }

    //*** POST Request
    public function store(Request $request)
    {

        //--- Logic Section
        $data = new SocialLink;
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
        $data = SocialLink::findOrFail($id);
        return view('vendor.sociallink.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Logic Section
        $data = SocialLink::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return redirect()->route('vendor-sociallink-index')->with('success', $msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $data = SocialLink::findOrFail($id1);
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
        $data = SocialLink::findOrFail($id);
        if ($data->user_id == $this->user->id) {
            $data->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return redirect()->route('vendor-sociallink-index')->with('success', $msg);
            //--- Redirect Section Ends
        }
    }
}
