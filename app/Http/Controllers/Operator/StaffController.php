<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Platform\Models\Operator;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Datatables;

class StaffController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = Operator::where('id','!=',1)->where('id','!=',Auth::guard('operator')->user()->id)->latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('role', function(Operator $operator) {
                                $role = $operator->role_id == 0 ? __('No Role') : $operator->role->name;
                                return $role;
                            })
                            ->addColumn('action', function(Operator $operator) {
                                $delete ='<a href="javascript:;" data-href="' . route('operator-staff-delete',$operator->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                                return '<div class="action-list"><a data-href="' . route('operator-staff-show',$operator->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>'.__("Details").'</a><a data-href="' . route('operator-staff-edit',$operator->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__('Edit').'</a>'.$delete.'</div>';
                            }) 
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index(){
        return view('operator.staff.index');
    }

    public function create(){
        $roles = \DB::table('roles')->get();
        return view('operator.staff.create', compact('roles'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
               'email'      => 'required|email|unique:operators',
               'photo'      => 'required|mimes:jpeg,jpg,png,svg',
                ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $operator = new Operator();
        $input = $request->all();
        if ($file = $request->file('photo')) 
         {      
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/operators',$name);           
            $input['photo'] = $name;
        } 
        $input['role'] = 'Staff';
        $input['password'] = bcrypt($request['password']);
        $operator->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section        
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);      
        //--- Redirect Section Ends    
    }


    public function edit($id)
    {
        $operator = Operator::findOrFail($id);
        $roles = \DB::table('roles')->get();
        return view('operator.staff.edit', compact('operator', 'roles'));
    }

    public function update(Request $request,$id)
    {
        //--- Validation Section
        if($id != Auth::guard('operator')->user()->id)
        {
            $rules =
            [
                'photo' => 'mimes:jpeg,jpg,png,svg',
                'email' => 'required|unique:operators,email,'.$id
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends
            $input = $request->all();
            $operator = Operator::findOrFail($id);
                if ($file = $request->file('photo'))
                {
                    $name = \PriceHelper::ImageCreateName($file);
                    $file->move('assets/images/operators/',$name);
                    if($operator->photo != null)
                    {
                        if (file_exists(public_path().'/assets/images/operators/'.$operator->photo)) {
                            unlink(public_path().'/assets/images/operators/'.$operator->photo);
                        }
                    }
                $input['photo'] = $name;
                }
            if($request->password == ''){
                $input['password'] = $operator->password;
            }
            else{
                $input['password'] = Hash::make($request->password);
            }
            $operator->update($input);
            $msg = __('Data Updated Successfully.');
            return response()->json($msg);
        }
        else{
            $msg = __('You can not change your role.');
            return response()->json($msg);            
        }
 
    }

    //*** GET Request
    public function show($id)
    {
        $operator = Operator::findOrFail($id);
        return view('operator.staff.show',compact('operator'));
    }

    //*** GET Request Delete
    public function destroy($id)
    {
    	if($id == 1)
    	{
        return "You don't have access to remove this operator";
    	}
        $operator = Operator::findOrFail($id);
        //If Photo Doesn't Exist
        if($operator->photo == null){
            $operator->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path().'/assets/images/operators/'.$operator->photo)) {
            unlink(public_path().'/assets/images/operators/'.$operator->photo);
        }
        $operator->delete();
        //--- Redirect Section     
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);      
        //--- Redirect Section Ends    
    }
}
