<?php

namespace App\Http\Controllers\Operator;

use App\{
    Models\MonetaryUnit,
    Models\Shipping
};
use Illuminate\Http\Request;
use Validator;
use Datatables;

class ShippingController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        // Only platform shippings (user_id = 0)
        $datas = Shipping::where('user_id', 0)->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('integration_type_label', function(Shipping $data) {
                $type = $data->integration_type ?? 'manual';
                return match($type) {
                    'api' => '<span class="badge bg-info">'.__('API').'</span>',
                    'none' => '<span class="badge bg-success">'.__('Free').'</span>',
                    default => '<span class="badge bg-secondary">'.__('Manual').'</span>',
                };
            })
            ->editColumn('provider', function(Shipping $data) {
                return $data->provider ?: '-';
            })
            ->editColumn('price', function(Shipping $data) {
                $price = $data->price * $this->curr->value;
                return \PriceHelper::showAdminCurrencyPrice($price);
            })
            ->addColumn('free_above_display', function(Shipping $data) {
                if ($data->free_above > 0) {
                    $freeAbove = $data->free_above * $this->curr->value;
                    return \PriceHelper::showAdminCurrencyPrice($freeAbove);
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('status_label', function(Shipping $data) {
                $status = $data->status ?? 1;
                return $status == 1
                    ? '<span class="badge bg-success">'.__('Active').'</span>'
                    : '<span class="badge bg-danger">'.__('Inactive').'</span>';
            })
            ->addColumn('action', function(Shipping $data) {
                return '<div class="action-list"><a data-href="' . route('operator-shipping-edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('operator-shipping-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['action', 'integration_type_label', 'free_above_display', 'status_label'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('operator.shipping.index');
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        return view('operator.shipping.create',compact('sign'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'name' => 'required|unique:shippings,name',
            'subtitle' => 'required',
            'price' => 'required|numeric|min:0',
            'integration_type' => 'nullable|in:none,manual,api',
            'provider' => 'nullable|string|max:255',
            'free_above' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:0,1',
        ];
        $customs = ['name.unique' => __('This name has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $sign = $this->curr;
        $data = new Shipping();
        $input = $request->all();

        // Platform shipping - user_id = 0
        $input['user_id'] = 0;
        $input['integration_type'] = $input['integration_type'] ?? 'manual';
        $input['provider'] = $input['provider'] ?? '';
        $input['price'] = ($input['price'] / $sign->value);
        $input['free_above'] = !empty($input['free_above']) ? ($input['free_above'] / $sign->value) : 0;
        $input['status'] = $input['status'] ?? 1;

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
        // Only allow editing platform shippings (user_id = 0)
        $data = Shipping::where('id', $id)->where('user_id', 0)->firstOrFail();
        return view('operator.shipping.edit',compact('data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'name' => 'required|unique:shippings,name,'.$id,
            'subtitle' => 'required',
            'price' => 'required|numeric|min:0',
            'integration_type' => 'nullable|in:none,manual,api',
            'provider' => 'nullable|string|max:255',
            'free_above' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:0,1',
        ];
        $customs = ['name.unique' => __('This name has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $sign = $this->curr;
        // Only allow updating platform shippings (user_id = 0)
        $data = Shipping::where('id', $id)->where('user_id', 0)->firstOrFail();
        $input = $request->all();

        // Ensure user_id stays 0 (platform shipping)
        $input['user_id'] = 0;
        $input['integration_type'] = $input['integration_type'] ?? 'manual';
        $input['provider'] = $input['provider'] ?? '';
        $input['price'] = ($input['price'] / $sign->value);
        $input['free_above'] = !empty($input['free_above']) ? ($input['free_above'] / $sign->value) : 0;
        $input['status'] = $input['status'] ?? 1;

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
        // Only allow deleting platform shippings (user_id = 0)
        $data = Shipping::where('id', $id)->where('user_id', 0)->firstOrFail();
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
