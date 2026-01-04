<?php

namespace App\Http\Controllers\Admin;

use App\Models\OperatorRole;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class RoleController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = OperatorRole::latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('section', function(OperatorRole $operatorRole) {
                                $details =  str_replace('_',' ',$operatorRole->section);
                                $details =  ucwords($details);
                                return  '<div>'.$details.'</div>';
                            })
                            ->addColumn('action', function(OperatorRole $operatorRole) {
                                return '<div class="action-list"><a href="' . route('admin-role-edit',$operatorRole->id) . '"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('admin-role-delete',$operatorRole->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['section','action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('admin.role.index');
    }

    //*** GET Request
    public function create()
    {
        return view('admin.role.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
               'photo'      => '',
                ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $operatorRole = new OperatorRole();
        $input = $request->all();
        if(!empty($request->section))
        {
            $input['section'] = implode(" , ",$request->section);
        }
        else{
            $input['section'] = '';
        }

        $operatorRole->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.').'<a href="'.route('admin-role-index').'">'.__('View Operator Role Lists.').'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends


    }

    //*** GET Request
    public function edit($id)
    {
        $operatorRole = OperatorRole::findOrFail($id);
        return view('admin.role.edit',compact('operatorRole'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
               'photo'      => '',
                ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $operatorRole = OperatorRole::findOrFail($id);
        $input = $request->all();
        if(!empty($request->section))
        {
            $input['section'] = implode(" , ",$request->section);
        }
        else{
            $input['section'] = '';
        }
        $operatorRole->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.').'<a href="'.route('admin-role-index').'">'.__('View Operator Role Lists.').'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends

    }
    //*** GET Request Delete
    public function destroy($id)
    {
        $operatorRole = OperatorRole::findOrFail($id);
        $operatorRole->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
