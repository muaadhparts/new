<?php

namespace App\Http\Controllers\Operator;

use App\Models\TrustBadge;

use Illuminate\Http\Request;
use Datatables;

class TrustBadgeController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables($status)
    {
        if($status == 'Pending'){
            $datas = TrustBadge::where('status','=','Pending')->get();
        }
        else{
           $datas = TrustBadge::get();
        }

         return Datatables::of($datas)
                            ->addColumn('name', function(TrustBadge $data) {
                                $name = isset($data->user->owner_name) ? $data->user->owner_name : __('Removed');
                                return  $name;
                            })
                            ->addColumn('email', function(TrustBadge $data) {
                                $name = isset($data->user->email) ? $data->user->email : __('Removed');
                                return  $name;
                            })
                            ->editColumn('text', function(TrustBadge $data) {
                                $details = mb_strlen($data->text,'UTF-8') > 250 ? mb_substr($data->text,0,250,'UTF-8').'...' : $data->text;
                                return  $details;
                            })
                            ->addColumn('status', function(TrustBadge $data) {
                                $class = $data->status == 'Pending' ? '' : ($data->status == 'Trusted' ? 'drop-success' : 'drop-danger');
                                $s = $data->status == 'Trusted' ? 'selected' : '';
                                $ns = $data->status == 'Rejected' ? 'selected' : '';
                                return '<div class="action-list"><select class="process select merchant-droplinks '.$class.'">'.
                                 '<option value="'. route('operator-trust-badge-status',['id1' => $data->id, 'id2' => 'Pending']).'" '.$s.'>'.__("Pending").'</option>'.
                                '<option value="'. route('operator-trust-badge-status',['id1' => $data->id, 'id2' => 'Trusted']).'" '.$s.'>'.__("Trusted").'</option>'.
                                '<option value="'. route('operator-trust-badge-status',['id1' => $data->id, 'id2' => 'Rejected']).'" '.$ns.'>'.__("Rejected").'</option></select></div>';
                            })
                            ->addColumn('action', function(TrustBadge $data) {
                                return '<div class="action-list">
                                            <a href="javascript:;" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery">
                                                <input type="hidden" value="'.$data->id.'">
                                                <i class="fas fa-paperclip"></i> '.__('View Attachments').
                                            '</a>
                                            <a href="javascript:;" data-href="' . route('operator-trust-badge-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete">
                                            <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>';
                            })
                            ->rawColumns(['status','action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index($slug)
    {
        if($slug == 'all'){
            return view('operator.trust-badge.index');
        }else if($slug == 'pending'){
            return view('operator.trust-badge.pending');
        }

    }

    public function show()
    {
        $data[0] = 0;
        $id = $_GET['id'];
        $trustBadge = TrustBadge::findOrFail($id);
        $attachments = explode(',', $trustBadge->attachments);
        if(count($attachments))
        {
            $data[0] = 1;
            $data[1] = $attachments;
            $data[2] = $trustBadge->text;
            $data[3] = ''.route('operator-trust-badge-status',['id1' => $trustBadge->id, 'id2' => 'Trusted']).'';
            $data[4] = ''.route('operator-trust-badge-status',['id1' => $trustBadge->id, 'id2' => 'Rejected']).'';
        }
        return response()->json($data);
    }


    public function edit($id)
    {
        $data = \App\Models\Purchase::find($id);
        return view('operator.purchase.delivery',compact('data'));
    }


    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Logic Section
        $data = \App\Models\Purchase::findOrFail($id);

        $input = $request->all();


        // Then Save Without Changing it.
            $input['status'] = "completed";
            $data->update($input);
            //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }


    //*** GET Request
    public function status($id1,$id2)
    {
        $trustBadge = TrustBadge::findOrFail($id1);
        $trustBadge->status = $id2;
        $trustBadge->update();

        // When trust badge is approved, activate merchant account
        if ($id2 == 'Trusted' && $trustBadge->user) {
            $trustBadge->user->is_merchant = 2;
            $trustBadge->user->save();
        }

        // When declined, return merchant status to pending verification
        if ($id2 == 'Rejected' && $trustBadge->user) {
            $trustBadge->user->is_merchant = 1;
            $trustBadge->user->save();
        }

        //--- Redirect Section
        $msg[0] = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function destroy($id)
    {
        $data = TrustBadge::findOrFail($id);
        $photos =  explode(',',$data->attachments);
        foreach($photos as $photo){
            @unlink(public_path().'/assets/images/attachments/'.$photo);
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

}
