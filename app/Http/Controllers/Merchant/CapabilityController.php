<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Capability;

use Illuminate\Http\Request;

use Validator;
use Datatables;

class CapabilityController extends MerchantBaseController
{

    //*** JSON Request
    public function datatables()
    {
         $user = $this->user;
         $datas =  $user->capabilities()->latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->editColumn('photo', function(Capability $data) {
                                $photo = $data->photo ? url('assets/images/services/'.$data->photo):url('assets/images/noimage.png');
                                return '<img src="' . $photo . '" alt="Image">';
                            })
                            ->editColumn('name', function(Capability $data) {
                                $name = mb_strlen(strip_tags($data->name),'UTF-8') > 250 ? mb_substr(strip_tags($data->name),0,250,'UTF-8').'...' : strip_tags($data->name);
                                return  $name;
                            })
                            ->editColumn('details', function(Capability $data) {
                                $details = mb_strlen(strip_tags($data->details),'UTF-8') > 250 ? mb_substr(strip_tags($data->details),0,250,'UTF-8').'...' : strip_tags($data->details);
                                return  $details;
                            })
                            ->addColumn('action', function(Capability $data) {
                                return '<div class="action-list"><a data-href="' . route('merchant-capability-edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('merchant-capability-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['photo', 'action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index(){
        return view('merchant.capability.index');
    }

    public function create(){
        return view('merchant.capability.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
               'photo'      => 'required|mimes:jpeg,jpg,png,svg',
                ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new Capability();
        $input = $request->all();
        if ($file = $request->file('photo'))
         {
            $extensions = ['jpeg','jpg','png','svg'];
            if(!in_array($file->getClientOriginalExtension(),$extensions)){
                return response()->json(array('errors' => ['Image format not supported']));
            }

            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/services',$name);
            $input['photo'] = $name;
        }
        $input['user_id'] = $this->user->id;
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = Capability::findOrFail($id);
        return view('merchant.capability.edit',compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
               'photo'      => 'mimes:jpeg,jpg,png,svg',
                ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = Capability::findOrFail($id);
        $input = $request->all();
            if ($file = $request->file('photo'))
            {
                $extensions = ['jpeg','jpg','png','svg'];
                if(!in_array($file->getClientOriginalExtension(),$extensions)){
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/images/services',$name);
                if($data->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/services/'.$data->photo)) {
                        unlink(public_path().'/assets/images/services/'.$data->photo);
                    }
                }
            $input['photo'] = $name;
            }
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = Capability::findOrFail($id);
        //If Photo Doesn't Exist
        if($data->photo == null){
            $data->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path().'/assets/images/services/'.$data->photo)) {
            unlink(public_path().'/assets/images/services/'.$data->photo);
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

}
