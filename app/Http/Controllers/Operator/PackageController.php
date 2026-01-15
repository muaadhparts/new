<?php

namespace App\Http\Controllers\Operator;

use App\{
    Models\Package,
    Models\Currency
};
use Illuminate\Http\Request;
use Validator;
use Datatables;

class PackageController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        // Only platform packages (user_id = 0)
        $datas = Package::where('user_id', 0)->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('price', function(Package $data) {
                $price = $data->price * $this->curr->value;
                return \PriceHelper::showAdminCurrencyPrice($price);
            })
            ->addColumn('action', function(Package $data) {
                return '<div class="action-list"><a data-href="' . route('operator-package-edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('operator-package-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('operator.package.index');
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        return view('operator.package.create',compact('sign'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'title' => 'required|unique:packages,title',
            'subtitle' => 'required',
            'price' => 'required|numeric|min:0',
        ];
        $customs = ['title.unique' => __('This title has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $sign = $this->curr;
        $data = new Package();
        $input = $request->all();

        // Platform package - user_id = 0
        $input['user_id'] = 0;
        $input['price'] = ($input['price'] / $sign->value);

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
        $sign = $this->curr;
        // Only allow editing platform packages (user_id = 0)
        $data = Package::where('id', $id)->where('user_id', 0)->firstOrFail();
        return view('operator.package.edit',compact('data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'title' => 'required|unique:packages,title,'.$id,
            'subtitle' => 'required',
            'price' => 'required|numeric|min:0',
        ];
        $customs = ['title.unique' => __('This title has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $sign = $this->curr;
        // Only allow updating platform packages (user_id = 0)
        $data = Package::where('id', $id)->where('user_id', 0)->firstOrFail();
        $input = $request->all();

        // Ensure user_id stays 0 (platform package)
        $input['user_id'] = 0;
        $input['price'] = ($input['price'] / $sign->value);

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
        // Only allow deleting platform packages (user_id = 0)
        $data = Package::where('id', $id)->where('user_id', 0)->firstOrFail();
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
