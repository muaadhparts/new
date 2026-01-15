<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Typeface;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class TypefaceController extends OperatorBaseController
{
    public function datatables(){
        $datas = Typeface::orderBy('id','desc')->get();
        return Datatables::of($datas)
                            ->addColumn('action',function(Typeface $data){
                                $default = $data->is_default == 1 ? '<a><i class="fa fa-check"></i> Default</a>' : '<a class="status" data-href="'.route('operator.typefaces.status',$data->id).'">Set Default</a>';
                                return '<div class="action-list"><a data-href="' . route('operator.typefaces.edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__("Edit").'</a><a href="javascript:;" data-href="' . route('operator.typefaces.delete',['id' => $data->id]) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>'.$default.'</div>';
                            })
                            ->rawColumns(['action'])
                            ->toJson();
    }

    public function index(){
        return view('operator.typefaces.index');
    }

    public function create(){
        return view('operator.typefaces.create');
    }

    public function store(Request $request){
        //--- Validation Section
        $rules = [
            'font_family' => 'required',
                ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        $typeface = new Typeface();
        $input = $request->all();
        $input['font_value'] = preg_replace('/\s+/', '+',$request->font_family);
        $input['is_default'] = 0;
        $typeface->fill($input)->save();

        //--- Redirect Section
        $msg = __('Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function update(Request $request,$id){
        //--- Validation Section
        $rules = [
            'font_family' => 'required',
                ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        $typeface = Typeface::find($id);
        $input = $request->all();
        $input['font_value'] = preg_replace('/\s+/', '+',$request->font_family);
        $input['is_default'] = 0;
        $typeface->update($input);

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function edit($id){
        $data = Typeface::findOrFail($id);
        return view('operator.typefaces.edit',compact('data'));
    }

    public function status($id){
        $typeface_update =  Typeface::find($id);
        $typeface_update->is_default = 1;
        $typeface_update->update();

        $previous_typefaces = Typeface::where('id','!=',$id)->get();

        foreach($previous_typefaces as $previous_typeface){
            $previous_typeface->is_default = 0;
            $previous_typeface->update();
        }
        cache()->forget('default_typeface');
        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
   }

   //*** GET Request Delete
   public function destroy($id)
   {

       if($id == 1)
       {
       return response()->json(__("You don't have access to remove this typeface."));
       }
       $typeface = Typeface::findOrFail($id);
       if($typeface->is_default == 1)
       {
       return response()->json(__("You can not remove default typeface."));
       }
       $typeface->delete();
       //--- Redirect Section
       $msg = __('Data Deleted Successfully.');
       return response()->json($msg);
       //--- Redirect Section Ends
   }
}
