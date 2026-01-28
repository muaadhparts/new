<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class CityController extends Controller
{
    public function managecity($country_id)
    {
        $country = Country::findOrFail($country_id);
        return view('operator.country.city.index', compact('country'));
    }

    //*** JSON Request
    public function datatables($country_id)
    {
        $datas = City::with('country')->orderBy('id', 'desc')->where('country_id', $country_id)->get();

        return DataTables::of($datas)
            ->addColumn('action', function (City $data) use ($country_id) {
                return '<div class="action-list">
                    <a data-href="' . route('operator-city-edit', $data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1">
                        <i class="fas fa-edit"></i>Edit
                    </a>
                    <a href="javascript:;" data-href="' . route('operator-city-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>';
            })

            ->editColumn('country_id', function (City $data) {
                return $data->country->country_name ?? '-';
            })

            ->addColumn('status', function (City $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';

                return '<div class="action-list">
                    <select class="process select droplinks ' . $class . '">
                        <option data-val="1" value="' . route('operator-city-status', [$data->id, 1]) . '" ' . $s . '>Activated</option>
                        <option data-val="0" value="' . route('operator-city-status', [$data->id, 0]) . '" ' . $ns . '>Deactivated</option>
                    </select>
                </div>';
            })

            ->rawColumns(['action', 'status', 'country_id'])
            ->toJson();
    }

    public function create($country_id)
    {
        $country = Country::findOrFail($country_id);
        return view('operator.country.city.create', compact('country'));
    }

    public function store(Request $request, $country_id)
    {
        $rules = [
            'name'  => 'required|unique:cities,name',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $country = Country::findOrFail($country_id);

        // ملاحظة: المدن لها اسم إنجليزي فقط - لا يوجد city_name_ar
        $city = new City();
        $city->name = $request->name;
        $city->country_id = $country_id;
        $city->status = 1;
        $city->save();

        $mgs = __('Data Added Successfully.');
        return response()->json($mgs);
    }

    //*** GET Request Status
    public function status($id1, $id2)
    {
        $city = City::findOrFail($id1);
        $city->update(['status' => $id2]);
    }

    public function edit($id)
    {
        $city = City::findOrFail($id);
        return view('operator.country.city.edit', compact('city'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name'  => 'required|unique:cities,name,' . $id,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        // ملاحظة: المدن لها اسم إنجليزي فقط - لا يوجد city_name_ar
        $city = City::findOrFail($id);
        $city->name = $request->name;
        $city->update();

        $mgs = __('Data Updated Successfully.');
        return response()->json($mgs);
    }

    public function delete($id)
    {
        $city = City::findOrFail($id);
        $city->delete();

        $mgs = __('Data Deleted Successfully.');
        return response()->json($mgs);
    }
}
