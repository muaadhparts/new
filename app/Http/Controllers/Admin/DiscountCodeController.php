<?php

namespace App\Http\Controllers\Admin;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Datatables;
use Validator;

class DiscountCodeController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = DiscountCode::latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('type', function (DiscountCode $data) {
                $type = $data->type == 0 ? "Discount By Percentage" : "Discount By Amount";
                return $type;
            })
            ->editColumn('price', function (DiscountCode $data) {
                $price = $data->type == 0 ? $data->price . '%' : \PriceHelper::showAdminCurrencyPrice($data->price * $this->curr->value);
                return $price;
            })
            ->addColumn('merchant', function (DiscountCode $data) {
                // Get merchant info for display
                if ($data->user_id) {
                    $merchant = User::find($data->user_id);
                    return $merchant ? ($merchant->shop_name ?? $merchant->name) : '-';
                }
                return '-';
            })
            ->addColumn('status', function (DiscountCode $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('admin-discount-code-status', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Activated") . '</option><option data-val="0" value="' . route('admin-discount-code-status', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option></select></div>';
            })
            ->addColumn('action', function (DiscountCode $data) {
                return '<div class="action-list"><a href="' . route('admin-discount-code-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('admin-discount-code-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('admin.discount-code.index');
    }


    //*** GET Request
    public function create()
    {
        // TODO: Removed - old category system
        $categories = collect(); // Category::where('status', 1)->get();
        $sub_categories = collect(); // Subcategory::where('status', 1)->get();
        $child_categories = collect(); // Childcategory::where('status', 1)->get();
        // Get active merchants for merchant dropdown
        $merchants = User::where('is_merchant', 2)->get();
        return view('admin.discount-code.create', compact('categories', 'sub_categories', 'child_categories', 'merchants'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['code' => 'unique:discount_codes'];
        $customs = ['code.unique' => __('This code has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new DiscountCode();
        $input = $request->all();
        if ($request->apply_to == 'category') {
            $input['sub_category'] = null;
            $input['child_category'] = null;
        } elseif ($request->apply_to == 'sub_category') {
            $input['category'] = null;
            $input['child_category'] = null;
        } else {
            $input['category'] = null;
            $input['sub_category'] = null;
        }
        $input['start_date'] = Carbon::parse($input['start_date'])->format('Y-m-d');
        $input['end_date'] = Carbon::parse($input['end_date'])->format('Y-m-d');
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.') . '<a href="' . route("admin-discount-code-index") . '">' . __("View Discount Code Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        // TODO: Removed - old category system
        $categories = collect(); // Category::where('status', 1)->get();
        $sub_categories = collect(); // Subcategory::where('status', 1)->get();
        $child_categories = collect(); // Childcategory::where('status', 1)->get();
        // Get active merchants for merchant dropdown
        $merchants = User::where('is_merchant', 2)->get();
        $data = DiscountCode::findOrFail($id);
        return view('admin.discount-code.edit', compact('data', 'categories', 'sub_categories', 'child_categories', 'merchants'));
    }


    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section

        $rules = ['code' => 'unique:discount_codes,code,' . $id];
        $customs = ['code.unique' => __('This code has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = DiscountCode::findOrFail($id);
        $input = $request->all();
        if ($request->apply_to == 'category') {
            $input['sub_category'] = null;
            $input['child_category'] = null;
        } elseif ($request->apply_to == 'sub_category') {
            $input['category'] = null;
            $input['child_category'] = null;
        } else {
            $input['category'] = null;
            $input['sub_category'] = null;
        }
        $input['start_date'] = Carbon::parse($input['start_date'])->format('Y-m-d');
        $input['end_date'] = Carbon::parse($input['end_date'])->format('Y-m-d');

        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.') . '<a href="' . route("admin-discount-code-index") . '">' . __("View Discount Code Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
    //*** GET Request Status
    public function status($id1, $id2)
    {
        $data = DiscountCode::findOrFail($id1);
        $data->status = $id2;
        $data->update();
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }


    //*** GET Request Delete
    public function destroy($id)
    {
        $data = DiscountCode::findOrFail($id);
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
