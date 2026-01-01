<?php

namespace App\Http\Controllers\Merchant;

use App\Models\DiscountCode;
use App\Models\Currency;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Datatables;
use Validator;
use Auth;

class DiscountCodeController extends MerchantBaseController
{
    //*** JSON Request
    public function datatables()
    {
        // الحصول على المستخدم الحالي
        $user = Auth::user();
        $curr = Currency::where('is_default', 1)->first();

        // عرض كوبونات التاجر الحالي فقط
        $datas = DiscountCode::where('user_id', $user->id)->latest('id')->get();

        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('type', function (DiscountCode $data) {
                $type = $data->type == 0 ? __("Discount By Percentage") : __("Discount By Amount");
                return $type;
            })
            ->editColumn('price', function (DiscountCode $data) use ($curr) {
                $price = $data->type == 0 ? $data->price . '%' : \PriceHelper::showAdminCurrencyPrice($data->price * $curr->value);
                return $price;
            })
            ->addColumn('status', function (DiscountCode $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('merchant-discount-code-status', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Activated") . '</option><option data-val="0" value="' . route('merchant-discount-code-status', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option></select></div>';
            })
            ->addColumn('action', function (DiscountCode $data) {
                return '<div class="action-list"><a href="' . route('merchant-discount-code-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('merchant-discount-code-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function index()
    {
        $datas = DiscountCode::where('user_id', Auth::user()->id)->latest('id')->get();
        return view('merchant.discount-code.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        return view('merchant.discount-code.create');
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

        // تعيين التاجر الحالي تلقائياً
        $input['user_id'] = Auth::user()->id;

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
        $msg = __('New Data Added Successfully.') . '<a href="' . route("vendor-discount-code-index") . '">' . __("View Discount Code Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = DiscountCode::where('id', $id)->where('user_id', Auth::user()->id)->firstOrFail();

        return view('merchant.discount-code.edit', compact('data'));
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
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = DiscountCode::where('id', $id)->where('user_id', Auth::user()->id)->firstOrFail();

        $input = $request->all();

        // التأكد من عدم تغيير التاجر
        $input['user_id'] = Auth::user()->id;

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
        $msg = __('Data Updated Successfully.') . '<a href="' . route("vendor-discount-code-index") . '">' . __("View Discount Code Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Status
    public function status($id1, $id2)
    {
        // التأكد من أن الكوبون يخص التاجر الحالي
        $data = DiscountCode::where('id', $id1)->where('user_id', Auth::user()->id)->firstOrFail();
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
        $data = DiscountCode::where('id', $id)->where('user_id', Auth::user()->id)->firstOrFail();
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

        // جلب معرفات العناصر الخاصة بالتاجر
        $merchantItemIds = \App\Models\MerchantItem::where('user_id', $userId)
            ->where('status', 1)
            ->pluck('catalog_item_id');

        // جلب العناصر كـ Collection
        $catalogItems = \App\Models\CatalogItem::whereIn('id', $merchantItemIds)->get();

        // TODO: Removed - old category system
        // if ($type == 'category') {
        //     $categoryIds = $catalogItems->pluck('category_id')->unique()->filter();
        //     $categories = Category::whereIn('id', $categoryIds)->where('status', 1)->get(['id', 'name']);
        //     return response()->json($categories);
        // } elseif ($type == 'sub_category') {
        //     $subCategoryIds = $catalogItems->pluck('subcategory_id')->unique()->filter();
        //     $subCategories = Subcategory::whereIn('id', $subCategoryIds)->where('status', 1)->get(['id', 'name']);
        //     return response()->json($subCategories);
        // } elseif ($type == 'child_category') {
        //     $childCategoryIds = $catalogItems->pluck('childcategory_id')->unique()->filter();
        //     $childCategories = Childcategory::whereIn('id', $childCategoryIds)->where('status', 1)->get(['id', 'name']);
        //     return response()->json($childCategories);
        // }

        return response()->json(collect());
    }
}
