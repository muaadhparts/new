<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminRole;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class RoleController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = AdminRole::latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('section', function(AdminRole $data) {
                                $details =  str_replace('_',' ',$data->section);
                                $details =  ucwords($details);
                                return  '<div>'.$details.'</div>';
                            })
                            ->addColumn('action', function(AdminRole $data) {
                                return '<div class="action-list"><a href="' . route('admin-role-edit',$data->id) . '"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('admin-role-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
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
        $data = new AdminRole();
        $input = $request->all();
        if(!empty($request->section))
        {
            $input['section'] = implode(" , ",$request->section);
        }
        else{
            $input['section'] = '';
        }

        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.').'<a href="'.route('admin-role-index').'">'.__('View Admin Role Lists.').'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends


    }

    //*** GET Request
    public function edit($id)
    {
        $data = AdminRole::findOrFail($id);
        return view('admin.role.edit',compact('data'));
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
        $data = AdminRole::findOrFail($id);
        $input = $request->all();
        if(!empty($request->section))
        {
            $input['section'] = implode(" , ",$request->section);
        }
        else{
            $input['section'] = '';
        }
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.').'<a href="'.route('admin-role-index').'">'.__('View Admin Role Lists.').'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends

    }
    //*** GET Request Delete
    public function destroy($id)
    {
        $data = AdminRole::findOrFail($id);
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
