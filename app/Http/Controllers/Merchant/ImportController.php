<?php

namespace App\Http\Controllers\Merchant;

use App\{
    Models\MerchantPhoto,
    Models\CatalogItem,
    Models\Muaadhsetting
};

// عرض البائع (MerchantItem) — المصدر الوحيد للسعر/المخزون/المقاسات
use App\Models\MerchantItem;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use Validator;
use Datatables;

class ImportController extends MerchantBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $user = $this->user;

        // عروض هذا البائع فقط + تحميل تعريف العنصر
        // item_type is now on merchant_items, not catalog_items
        $datas = MerchantItem::where('user_id', $user->id)
            ->where('item_type', 'affiliate')
            ->with('catalogItem')
            ->latest('id')
            ->get();

        return Datatables::of($datas)
            ->editColumn('name', function (MerchantItem $data) {
                $catalogItem = $data->catalogItem;
                $name = mb_strlen(strip_tags($catalogItem->name), 'UTF-8') > 50
                    ? mb_substr(strip_tags($catalogItem->name), 0, 50, 'UTF-8') . '...'
                    : strip_tags($catalogItem->name);

                // تمرير {merchant_item_id} في الرابط
                $id = '<small>' . __('Catalog Item ID') . ': <a href="' .
                    route('front.catalog-item', ['slug' => $catalogItem->slug, 'merchant_item_id' => $data->id]) .
                    '" target="_blank">' . sprintf("%'.08d", $catalogItem->id) . '</a></small>';

                return $name . '<br>' . $id;
            })
            ->editColumn('price', function (MerchantItem $data) {
                $price = round($data->price * $this->curr->value, 2);
                return \PriceHelper::showAdminCurrencyPrice($price);
            })
            ->addColumn('status', function (MerchantItem $data) {
                // نفترض أن المسار merchant-prod-status يقوم بتحديث حالة عرض البائع لهذا المنتج
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s     = $data->status == 1 ? 'selected' : '';
                $ns    = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('merchant-catalog-item-status', ['id1' => $data->catalog_item_id, 'id2' => 1]) . '" ' . $s . '>' . __('Activated') . '</option><option data-val="0" value="' . route('merchant-catalog-item-status', ['id1' => $data->catalog_item_id, 'id2' => 0]) . '" ' . $ns . '>' . __('Deactivated') . '</option></select></div>';
            })
            ->addColumn('action', function (MerchantItem $data) {
                return '<div class="action-list">
                            <a href="' . route('merchant-import-edit', $data->catalog_item_id) . '">
                                <i class="fas fa-edit"></i>' . __('Edit') . '
                            </a>
                            <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery">
                                <input type="hidden" value="' . $data->catalog_item_id . '">
                                <i class="fas fa-eye"></i> ' . __('View Gallery') . '
                            </a>
                            <a href="javascript:;" data-href="' . route('merchant-catalog-item-delete', $data->catalog_item_id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>';
            })
            ->rawColumns(['name', 'status', 'action'])
            ->toJson();
    }

    public function index()
    {
        if ($this->gs->affilite == 1) {
            return view('merchant.catalog-item-import.index');
        } else {
            return back();
        }
    }

    //*** GET Request
    public function createImport()
    {
        $cats = collect(); // Category system removed - using TreeCategories
        $sign = $this->curr;
        if ($this->gs->affilite == 1) {
            return view('merchant.catalog-item-import.createone', compact('cats', 'sign'));
        } else {
            return back();
        }
    }

    //*** GET Request
    public function importCSV()
    {
        $cats = collect(); // Category system removed - using TreeCategories
        $sign = $this->curr;
        return view('merchant.catalog-item-import.importcsv', compact('cats', 'sign'));
    }

    //*** POST Request
    public function uploadUpdate(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'image' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $data = CatalogItem::findOrFail($id);

        //--- Validation Section Ends
        $image = $request->image;
        list($type, $image) = explode(';', $image);
        list(, $image) = explode(',', $image);
        $image = base64_decode($image);
        $image_name = time() . Str::random(8) . '.png';
        $path = 'assets/images/catalogItems/' . $image_name;
        file_put_contents($path, $image);
        if ($data->photo != null) {
            if (file_exists(public_path() . '/assets/images/catalogItems/' . $data->photo)) {
                unlink(public_path() . '/assets/images/catalogItems/' . $data->photo);
            }
        }
        $input['photo'] = $image_name;
        $data->update($input);
        return response()->json(['status' => true, 'file_name' => $image_name]);
    }

    //*** POST Request
    public function store(Request $request)
    {
        // BEGIN NEW MERCHANT ITEM HANDLING
        $user = $this->user;
        $package = $user->membershipPlans()->latest('id')->first();
        // عدد العروض الخاصة بالبائع (على merchant_items)
        $prods = $user->merchantItems()->latest('id')->count();
        // // dd(['merchant_items_count' => $prods]); // اختباري

        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                return response()->json(array('errors' => [0 => __('You must complete your trust badge first.')]));
            }
        }

        if ($prods < $package->allowed_items) {
            // تحقق الصورة والملف إن لزم
            if ($request->image_source == 'file') {
                $rules = [
                    'photo' => 'required',
                    'file'  => 'mimes:zip'
                ];
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
                }
            }

            $sign = $this->curr;

            // تعريف العنصر (هوية فقط — بدون سعر/مخزون/مقاسات)
            $catalogItemInput = $request->only([
                'part_number',
                'attributes', 'name', 'details', 'weight', 'policy', 'tags',
                'features', 'colors', 'is_meta', 'meta_tag', 'meta_description',
                'youtube', 'measure', 'is_catalog',
                'catalog_id', 'cross_items'
            ]);

            // الصورة الرئيسية (base64 أو رابط)
            if ($request->photo != "") {
                $image = $request->photo;
                list($type, $image) = explode(';', $image);
                list(, $image) = explode(',', $image);
                $image = base64_decode($image);
                $image_name = time() . Str::random(8) . '.png';
                $path = 'assets/images/catalogItems/' . $image_name;
                file_put_contents($path, $image);
                $catalogItemInput['photo'] = $image_name;
            } else {
                $catalogItemInput['photo'] = $request->photolink;
            }

            // إنشاء تعريف العنصر (item_type moved to merchant_items)
            $catalogItem = new CatalogItem;
            $catalogItem->fill($catalogItemInput);
            $catalogItem->save();

            // مدخلات عرض البائع (MerchantItem) — السعر/المخزون/المقاسات
            // item_type is now stored on merchant_items
            $merchantInput = $request->only([
                'stock', 'is_discount', 'discount_date', 'whole_sell_qty',
                'whole_sell_discount', 'preordered', 'minimum_qty', 'stock_check',
                'popular', 'status', 'is_popular', 'ship', 'item_condition', 'affiliate_link'
            ]);
            $merchantInput['item_type'] = 'affiliate';

            // تحويل العملة للسعر
            $merchantInput['price'] = $request->price / $sign->value;
            $merchantInput['previous_price'] = $request->previous_price / $sign->value;
            $merchantInput['catalog_item_id'] = $catalogItem->id;
            $merchantInput['user_id'] = $user->id;

            // إنشاء عرض البائع
            $merchantItem = MerchantItem::create($merchantInput);
            // // dd(['mp_created' => $merchantItem->id]); // اختباري

            // حفظ صور التاجر إن وجدت
            if ($files = $request->file('gallery')) {
                foreach ($files as $key => $file) {
                    if (in_array($key, (array) $request->galval)) {
                        $merchantPhoto = new MerchantPhoto;
                        $name = \PriceHelper::ImageCreateName($file);
                        $file->move('assets/images/merchant-photos', $name);
                        $merchantPhoto['photo'] = $name;
                        $merchantPhoto['catalog_item_id'] = $catalogItem->id;
                        $merchantPhoto->save();
                    }
                }
            }

            // توليد slug و thumbnail
            $catalogItem->slug = Str::slug($catalogItem->name, '-') . '-' . strtolower(Str::random(3) . $catalogItem->id . Str::random(3));
            $catalogItem->save();

            $fimageData = public_path() . '/assets/images/catalogItems/' . $catalogItem->photo;
            if (filter_var($catalogItem->photo, FILTER_VALIDATE_URL)) {
                $fimageData = $catalogItem->photo;
            }
            $img = Image::make($fimageData)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $catalogItem->thumbnail = $thumbnail;
            $catalogItem->save();

            $msg = __('New Affiliate CatalogItem Added Successfully.') . '<a href="' . route('merchant-import-index') . '">' . __('View CatalogItem Lists.') . '</a>';
            return response()->json($msg);
        } else {
            return response()->json(array('errors' => [0 => __('You Can\'t Add More CatalogItem.')]));
        }
        // END NEW MERCHANT ITEM HANDLING
    }

    //*** GET Request
    public function edit($id)
    {
        $cats = collect(); // Category system removed - using TreeCategories
        $data = CatalogItem::findOrFail($id);

        // Get merchant item data for this merchant
        $merchantItem = MerchantItem::where('catalog_item_id', $id)
            ->where('user_id', $this->user->id)
            ->first();

        $sign = $this->curr;
        return view('merchant.catalog-item-import.editone', compact('cats', 'data', 'merchantItem', 'sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        // تعريف العنصر + عرض البائع الحالي
        $catalogItem = CatalogItem::findOrFail($id);
        $merchant = MerchantItem::where('catalog_item_id', $id)
            ->where('user_id', $this->user->id)
            ->firstOrFail();

        $sign = $this->curr;

        // تحقق من الملف المضغوط
        $rules = [
            'file' => 'mimes:zip'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        // حقول تعريف العنصر (هوية فقط)
        $catalogItemInput = $request->only([
            'part_number',
            'attributes', 'name', 'slug', 'photo', 'thumbnail', 'details',
            'weight', 'policy', 'tags', 'features', 'colors', 'is_meta', 'meta_tag',
            'meta_description', 'youtube',
            'measure', 'is_catalog', 'catalog_id', 'cross_items'
        ]);

        // مصدر الصورة
        if ($request->image_source == 'file') {
            $catalogItemInput['photo'] = $request->photo;
        } else {
            $catalogItemInput['photo'] = $request->photolink;
        }

        // حقول عرض البائع (MP)
        $merchantInput = $request->only([
            'stock', 'is_discount', 'discount_date', 'whole_sell_qty',
            'whole_sell_discount', 'preordered', 'minimum_qty', 'stock_check',
            'popular', 'status', 'is_popular', 'ship', 'item_condition', 'affiliate_link'
        ]);

        // تحويل العملة للسعر
        $merchantInput['price'] = $request->price / $sign->value;
        $merchantInput['previous_price'] = $request->previous_price / $sign->value;

        // احتفظ بالصورة القديمة قبل التحديث لتحديد ما إذا تغيّرت
        $oldPhoto = $catalogItem->photo;

        // تحديث تعريف العنصر
        $catalogItem->update($catalogItemInput);

        // تحديث عرض البائع
        $merchant->update($merchantInput);
        // // dd(['merchant_item_updated' => true]); // اختباري

        // لو تغيّرت الصورة الرئيسية — أعِد توليد الـ Thumbnail
        if (!empty($catalogItemInput['photo']) && $oldPhoto !== $catalogItemInput['photo']) {
            if ($catalogItem->thumbnail && file_exists(public_path() . '/assets/images/thumbnails/' . $catalogItem->thumbnail)) {
                @unlink(public_path() . '/assets/images/thumbnails/' . $catalogItem->thumbnail);
            }

            $fimageData = public_path() . '/assets/images/catalogItems/' . $catalogItemInput['photo'];
            if (filter_var($catalogItemInput['photo'], FILTER_VALIDATE_URL)) {
                $fimageData = $catalogItemInput['photo'];
            }

            $img = Image::make($fimageData)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $catalogItem->thumbnail = $thumbnail;
            $catalogItem->save();
        }

        $msg = __('CatalogItem Updated Successfully.') . '<a href="' . route('merchant-import-index') . '">' . __('View CatalogItem Lists.') . '</a>';
        return response()->json($msg);
    }
}
