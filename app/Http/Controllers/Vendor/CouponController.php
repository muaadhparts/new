<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Coupon;
use App\Models\Subcategory;
use App\Models\Currency;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Datatables;
use Validator;
use Auth;

class CouponController extends VendorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        // الحصول على المستخدم الحالي
        $user = Auth::user();
        $curr = Currency::where('is_default', 1)->first();

        // عرض كوبونات التاجر الحالي فقط
        $datas = Coupon::where('user_id', $user->id)->latest('id')->get();

        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('type', function (Coupon $data) {
                $type = $data->type == 0 ? __("Discount By Percentage") : __("Discount By Amount");
                return $type;
            })
            ->editColumn('price', function (Coupon $data) use ($curr) {
                $price = $data->type == 0 ? $data->price . '%' : \PriceHelper::showAdminCurrencyPrice($data->price * $curr->value);
                return $price;
            })
            ->addColumn('status', function (Coupon $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('vendor-coupon-status', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Activated") . '</option><option data-val="0" value="' . route('vendor-coupon-status', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option></select></div>';
            })
            ->addColumn('action', function (Coupon $data) {
                return '<div class="action-list"><a href="' . route('vendor-coupon-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('vendor-coupon-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function index()
    {
        $datas = Coupon::where('user_id', Auth::user()->id)->latest('id')->get();
        return view('vendor.coupon.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        return view('vendor.coupon.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['code' => 'unique:coupons'];
        $customs = ['code.unique' => __('This code has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new Coupon();
        $input = $request->all();

        // تعيين التاجر الحالي تلقائياً
        $input['user_id'] = Auth::user()->id;

        if ($request->coupon_type == 'category') {
            $input['sub_category'] = null;
            $input['child_category'] = null;
        } elseif ($request->coupon_type == 'sub_category') {
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
        $msg = __('New Data Added Successfully.') . '<a href="' . route("vendor-coupon-index") . '">' . __("View Coupon Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = Coupon::where('id', $id)->where('user_id', Auth::user()->id)->firstOrFail();

        return view('vendor.coupon.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = ['code' => 'unique:coupons,code,' . $id];
        $customs = ['code.unique' => __('This code has already been taken.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = Coupon::where('id', $id)->where('user_id', Auth::user()->id)->firstOrFail();

        $input = $request->all();

        // التأكد من عدم تغيير التاجر
        $input['user_id'] = Auth::user()->id;

        if ($request->coupon_type == 'category') {
            $input['sub_category'] = null;
            $input['child_category'] = null;
        } elseif ($request->coupon_type == 'sub_category') {
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
        $msg = __('Data Updated Successfully.') . '<a href="' . route("vendor-coupon-index") . '">' . __("View Coupon Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Status
    public function status($id1, $id2)
    {
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = Coupon::where('id', $id1)->where('user_id', Auth::user()->id)->firstOrFail();
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
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = Coupon::where('id', $id)->where('user_id', Auth::user()->id)->firstOrFail();
        $data->delete();

        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** AJAX Request - Get Categories based on type
    public function getCategories(Request $request)
    {
        $type = $request->type;
        $userId = Auth::user()->id;

        // جلب معرفات المنتجات الخاصة بالتاجر
        $merchantProductIds = \App\Models\MerchantProduct::where('user_id', $userId)
            ->where('status', 1)
            ->pluck('product_id');

        // جلب المنتجات كـ Collection
        $products = \App\Models\Product::whereIn('id', $merchantProductIds)->get();

        if ($type == 'category') {
            $categoryIds = $products->pluck('category_id')->unique()->filter();
            $categories = Category::whereIn('id', $categoryIds)->where('status', 1)->get(['id', 'name']);
            return response()->json($categories);
        } elseif ($type == 'sub_category') {
            $subCategoryIds = $products->pluck('subcategory_id')->unique()->filter();
            $subCategories = Subcategory::whereIn('id', $subCategoryIds)->where('status', 1)->get(['id', 'name']);
            return response()->json($subCategories);
        } elseif ($type == 'child_category') {
            $childCategoryIds = $products->pluck('childcategory_id')->unique()->filter();
            $childCategories = Childcategory::whereIn('id', $childCategoryIds)->where('status', 1)->get(['id', 'name']);
            return response()->json($childCategories);
        }

        return response()->json([]);
    }
}
