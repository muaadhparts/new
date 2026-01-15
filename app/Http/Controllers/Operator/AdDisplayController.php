<?php

namespace App\Http\Controllers\Operator;

use Datatables;
use App\Models\AdDisplay;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Validator;

class AdDisplayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:operator');
    }

    //*** JSON Request
    public function datatables()
    {
         $datas = AdDisplay::orderBy('id','desc')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->editColumn('photo', function(AdDisplay $data) {
                                $photo = $data->photo ? url('assets/images/ad-display/'.$data->photo):url('assets/images/noimage.png');
                                return '<img src="' . $photo . '" alt="Image">';
                            })
                            ->addColumn('action', function(AdDisplay $data) {
                                return '<div class="action-list"><a data-href="' . route('operator-ad-display-edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>Edit</a><a href="javascript:;" data-href="' . route('operator-ad-display-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['photo', 'action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('operator.ad-display.index');
    }

    //*** GET Request
    public function create()
    {
        return view('operator.ad-display.create');
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
        $data = new AdDisplay();
        $input = $request->all();
        if ($file = $request->file('photo'))
         {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/ad-display',$name);
            $input['photo'] = $name;
        }
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = 'New Data Added Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = AdDisplay::findOrFail($id);
        return view('operator.ad-display.edit',compact('data'));
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
        $data = AdDisplay::findOrFail($id);
        $input = $request->all();
            if ($file = $request->file('photo'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/images/ad-display',$name);
                if($data->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/ad-display/'.$data->photo)) {
                        unlink(public_path().'/assets/images/ad-display/'.$data->photo);
                    }
                }
            $input['photo'] = $name;
            }

        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = 'Data Updated Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = AdDisplay::findOrFail($id);
        //If Photo Doesn't Exist
        if($data->photo == null){
            $data->delete();
            //--- Redirect Section
            $msg = 'Data Deleted Successfully.';
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path().'/assets/images/ad-display/'.$data->photo)) {
            unlink(public_path().'/assets/images/ad-display/'.$data->photo);
        }
        $data->delete();
        //--- Redirect Section
        $msg = 'Data Deleted Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
