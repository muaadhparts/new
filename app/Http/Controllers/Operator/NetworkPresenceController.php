<?php

namespace App\Http\Controllers\Operator;

use App\Models\NetworkPresence;
use Illuminate\Http\Request;
use Datatables;

class NetworkPresenceController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = NetworkPresence::where('user_id','=',0)->latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('status', function(NetworkPresence $data) {
                                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                                $s = $data->status == 1 ? 'selected' : '';
                                $ns = $data->status == 0 ? 'selected' : '';
                                return '<div class="action-list"><select class="process select droplinks '.$class.'"><option data-val="1" value="'. route('operator-network-presence-status',['id1' => $data->id, 'id2' => 1]).'" '.$s.'>'.__("Activated").'</option><option data-val="0" value="'. route('operator-network-presence-status',['id1' => $data->id, 'id2' => 0]).'" '.$ns.'>'.__("Deactivated").'</option></select></div>';
                            })
                            ->addColumn('action', function(NetworkPresence $data) {
                                return '<div class="action-list"><a href="' . route('operator-network-presence-edit',$data->id) . '"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('operator-network-presence-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['status', 'action'])
                            ->toJson();//--- Returning Json Data To Client Side
    }

    public function index(){
        return view('operator.network-presence.index');
    }

    public function create(){
        return view('operator.network-presence.create');
    }

    //*** POST Request
    public function store(Request $request)
    {

        //--- Logic Section
        $data = new NetworkPresence;
        $input = $request->all();
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.').'<a href="'.route("operator-network-presence-index").'">'.__("View Lists").'</a>';;
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = NetworkPresence::findOrFail($id);
        return view('operator.network-presence.edit',compact('data'));
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
        $msg = __('Data Updated Successfully.').'<a href="'.route("operator-network-presence-index").'">'.__("View Lists").'</a>';;
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function status($id1,$id2)
    {
        $data = NetworkPresence::findOrFail($id1);
        $data->status = $id2;
        $data->update();
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }


    //*** GET Request
    public function destroy($id)
    {
        $data = NetworkPresence::findOrFail($id);
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
