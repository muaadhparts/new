<?php

namespace App\Http\Controllers\Operator;

use App\Models\MonetaryUnit;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class MonetaryUnitController extends OperatorBaseController
{

    //*** JSON Request
    public function datatables()
    {
        $datas = MonetaryUnit::latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('action', function (MonetaryUnit $data) {
                $delete = $data->id == 1 ? '' : '<a href="javascript:;" data-href="' . route('operator-monetary-unit-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                $default = $data->is_default == 1 ? '<a><i class="fa fa-check"></i> ' . __('Default') . '</a>' : '<a class="status" data-href="' . route('operator-monetary-unit-status', ['id1' => $data->id, 'id2' => 1]) . '">' . __('Set Default') . '</a>';
                return '<div class="action-list"><a data-href="' . route('operator-monetary-unit-edit', $data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>' . __('Edit') . '</a>' . $delete . $default . '</div>';
            })
            ->rawColumns(['action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.monetary-unit.index');
    }

    public function create()
    {
        return view('operator.monetary-unit.create');
    }
    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['name' => 'unique:monetary_units', 'sign' => 'unique:monetary_units'];
        $customs = ['name.unique' => __('This name has already been taken.'), 'sign.unique' => __('This sign has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new MonetaryUnit();
        $input = $request->all();
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
        $data = MonetaryUnit::findOrFail($id);
        return view('operator.monetary-unit.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = ['name' => 'unique:monetary_units,name,' . $id, 'sign' => 'unique:monetary_units,sign,' . $id];
        $customs = ['name.unique' => __('This name has already been taken.'), 'sign.unique' => __('This sign has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = MonetaryUnit::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        //--- Logic Section Ends
        cache()->forget('default_monetary_unit');

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function status($id1, $id2)
    {
        $data = MonetaryUnit::findOrFail($id1);
        $data->is_default = $id2;
        $data->update();
        $data = MonetaryUnit::where('id', '!=', $id1)->update(['is_default' => 0]);
        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        if ($id == 1) {
            return __("You cant't remove the main monetary unit.");
        }
        $data = MonetaryUnit::findOrFail($id);
        if ($data->is_default == 1) {
            MonetaryUnit::where('id', '=', 1)->update(['is_default' => 1]);
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
