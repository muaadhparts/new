<?php

namespace App\Http\Controllers\Operator;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Datatables;
use Validator;

/**
 * DiscountCodeController - إدارة أكواد الخصم
 */
class DiscountCodeController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = DiscountCode::with(['merchant'])->latest('id')->get();

        return Datatables::of($datas)
            ->editColumn('type', function (DiscountCode $data) {
                $type = $data->type == 0 ? __("Discount By Percentage") : __("Discount By Amount");
                return $type;
            })
            ->editColumn('price', function (DiscountCode $data) {
                $price = $data->type == 0
                    ? $data->price . '%'
                    : \PriceHelper::showAdminCurrencyPrice($data->price * $this->curr->value);
                return $price;
            })
            ->addColumn('merchant', function (DiscountCode $data) {
                if ($data->merchant) {
                    return $data->merchant->shop_name ?? $data->merchant->name;
                }
                return __('All Merchants');
            })
            ->addColumn('status', function (DiscountCode $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('operator-discount-code-status', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Activated") . '</option><option data-val="0" value="' . route('operator-discount-code-status', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option></select></div>';
            })
            ->addColumn('action', function (DiscountCode $data) {
                return '<div class="action-list"><a href="' . route('operator-discount-code-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('operator-discount-code-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function index()
    {
        return view('operator.discount-code.index');
    }

    //*** GET Request
    public function create()
    {
        // جلب التجار النشطين
        $merchants = User::where('is_merchant', 2)->get();

        return view('operator.discount-code.create', compact('merchants'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'code' => 'required|unique:discount_codes',
            'type' => 'required|in:0,1',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
        $customs = [
            'code.unique' => __('This code has already been taken.'),
            'code.required' => __('Code is required.'),
            'type.required' => __('Type is required.'),
            'price.required' => __('Price/Percentage is required.'),
        ];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new DiscountCode();
        $input = $request->only([
            'code', 'type', 'price', 'times', 'user_id'
        ]);

        // Set default values
        $input['status'] = 1; // Active by default
        $input['used'] = 0;   // Not used yet

        // Format dates
        $input['start_date'] = Carbon::parse($request->start_date)->format('Y-m-d');
        $input['end_date'] = Carbon::parse($request->end_date)->format('Y-m-d');

        // Handle empty values
        $input['user_id'] = $input['user_id'] ?: null;

        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.') . ' <a href="' . route("operator-discount-code-index") . '">' . __("View Discount Code Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = DiscountCode::findOrFail($id);

        // جلب التجار النشطين
        $merchants = User::where('is_merchant', 2)->get();

        return view('operator.discount-code.edit', compact('data', 'merchants'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'code' => 'required|unique:discount_codes,code,' . $id,
            'type' => 'required|in:0,1',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
        $customs = [
            'code.unique' => __('This code has already been taken.'),
        ];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = DiscountCode::findOrFail($id);
        $input = $request->only([
            'code', 'type', 'price', 'times', 'user_id'
        ]);

        // Format dates
        $input['start_date'] = Carbon::parse($request->start_date)->format('Y-m-d');
        $input['end_date'] = Carbon::parse($request->end_date)->format('Y-m-d');

        // Handle empty values
        $input['user_id'] = $input['user_id'] ?: null;

        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.') . ' <a href="' . route("operator-discount-code-index") . '">' . __("View Discount Code Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Status
    public function status($id1, $id2)
    {
        $data = DiscountCode::findOrFail($id1);
        $data->status = $id2;
        $data->update();

        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = DiscountCode::findOrFail($id);
        $data->delete();

        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
    }
}
