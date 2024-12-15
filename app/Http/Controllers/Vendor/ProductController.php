<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Currency;
use App\Models\Gallery;
use App\Models\Generalsetting;
use App\Models\Product;
use App\Models\Subcategory;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Image;
use Validator;

class ProductController extends VendorBaseController
{

    public function index()
    {
        $user = $this->user;
        $datas = $user->products()->where('product_type', 'normal')->latest('id')->paginate(10);
        return view('vendor.product.index', compact('datas'));
    }

    public function types()
    {
        return view('vendor.product.types');
    }

    public function catalogs()
    {
        $user = $this->user;
        $datas = Product::where('product_type', 'normal')->where('status', '=', 1)->where('is_catalog', '=', 1)->latest('id')->get();
        return view('vendor.product.catalogs', compact('datas', 'user'));
    }

    //*** GET Request
    public function create($slug)
    {
        $user = $this->user;
        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                Session::flash('unsuccess', __('You must complete your verfication first.'));
                return redirect()->route('vendor-verify');
            }
        }

        $cats = Category::all();
        $sign = $this->curr;
        if ($slug == 'physical') {
            if ($this->gs->physical == 1) {
                return view('vendor.product.create.physical', compact('cats', 'sign'));
            } else {
                return back();
            }
        } else if ($slug == 'digital') {
            if ($this->gs->digital == 1) {
                return view('vendor.product.create.digital', compact('cats', 'sign'));
            } else {
                return back();
            }
        } else if (($slug == 'license')) {
            if ($this->gs->license == 1) {
                return view('vendor.product.create.license', compact('cats', 'sign'));
            } else {
                return back();
            }
        } else if (($slug == 'listing')) {
            if ($this->gs->listing == 1) {
                return view('vendor.product.create.listing', compact('cats', 'sign'));
            } else {
                return back();
            }
        }
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $data = Product::findOrFail($id1);
        $data->status = $id2;
        $data->update();

        return back()->with("success",__('Status Updated Successfully.'));
        //--- Redirect Section Ends
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

        $data = Product::findOrFail($id);

        //--- Validation Section Ends
        $image = $request->image;
        list($type, $image) = explode(';', $image);
        list(, $image) = explode(',', $image);
        $image = base64_decode($image);
        $image_name = time() . Str::random(8) . '.png';
        $path = 'assets/images/products/' . $image_name;
        file_put_contents($path, $image);
        if ($data->photo != null) {
            if (file_exists(public_path() . '/assets/images/products/' . $data->photo)) {
                unlink(public_path() . '/assets/images/products/' . $data->photo);
            }
        }
        $input['photo'] = $image_name;
        $data->update($input);
        if ($data->thumbnail != null) {
            if (file_exists(public_path() . '/assets/images/thumbnails/' . $data->thumbnail)) {
                unlink(public_path() . '/assets/images/thumbnails/' . $data->thumbnail);
            }
        }

        $img = Image::make(public_path() . '/assets/images/products/' . $data->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
        $data->thumbnail = $thumbnail;
        $data->update();
        return response()->json(['status' => true, 'file_name' => $image_name]);
    }

    //*** POST Request
    public function import()
    {

        $cats = Category::all();
        $sign = $this->curr;
        return view('vendor.product.productcsv', compact('cats', 'sign'));
    }

    public function importSubmit(Request $request)
    {
        $user = $this->user;
        $package = $user->subscribes()->orderBy('id', 'desc')->first();
        $prods = $user->products()->orderBy('id', 'desc')->get()->count();

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if ($prods < $package->allowed_products || $package->allowed_products == 0) {
            $log = "";
            //--- Validation Section
            $request->validate([
                'csvfile' => 'required',
            ]);

            $filename = '';
            if ($file = $request->file('csvfile')) {
                $extensions = ['csv'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $filename = time() . '-' . $file->getClientOriginalName();
                $file->move('assets/temp_files', $filename);
            }

            $datas = "";

            $file = fopen(public_path('assets/temp_files/' . $filename), "r");
            $i = 1;
            while (($line = fgetcsv($file)) !== false) {

                if ($i != 1) {

                    if (!Product::where('sku', $line[0])->exists()) {

                        //--- Validation Section Ends

                        //--- Logic Section
                        $data = new Product;
                        $sign = Currency::where('is_default', '=', 1)->first();

                        $input['type'] = 'Physical';
                        $input['sku'] = $line[0];

                        $input['category_id'] = null;
                        $input['subcategory_id'] = null;
                        $input['childcategory_id'] = null;

                        $mcat = Category::where(DB::raw('lower(name)'), strtolower($line[1]));
                        //$mcat = Category::where("name", $line[1]);

                        if ($mcat->exists()) {
                            $input['category_id'] = $mcat->first()->id;

                            if ($line[2] != "") {
                                $scat = Subcategory::where(DB::raw('lower(name)'), strtolower($line[2]));

                                if ($scat->exists()) {
                                    $input['subcategory_id'] = $scat->first()->id;
                                }
                            }
                            if ($line[3] != "") {
                                $chcat = Childcategory::where(DB::raw('lower(name)'), strtolower($line[3]));

                                if ($chcat->exists()) {
                                    $input['childcategory_id'] = $chcat->first()->id;
                                }
                            }

                            $input['photo'] = $line[5];
                            $input['name'] = $line[4];
                            $input['details'] = $line[6];
                            //                $input['category_id'] = $request->category_id;
                            //                $input['subcategory_id'] = $request->subcategory_id;
                            //                $input['childcategory_id'] = $request->childcategory_id;
                            $input['color'] = $line[13];
                            $input['price'] = $line[7];
                            $input['previous_price'] = $line[8] != "" ? $line[8] : null;
                            $input['stock'] = $line[9];
                            $input['size'] = $line[10];
                            $input['size_qty'] = $line[11];
                            $input['size_price'] = $line[12];
                            $input['youtube'] = $line[15];
                            $input['policy'] = $line[16];
                            $input['meta_tag'] = $line[17];
                            $input['meta_description'] = $line[18];
                            $input['tags'] = $line[14];
                            $input['product_type'] = $line[19];
                            $input['affiliate_link'] = $line[20];

                            $input['slug'] = Str::slug($input['name'], '-') . '-' . strtolower($input['sku']);

                            $image_url = $line[5];

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_URL, $image_url);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_HEADER, true);
                            curl_setopt($ch, CURLOPT_NOBODY, true);

                            $content = curl_exec($ch);
                            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                            $thumb_url = '';
                            if (strpos($contentType, 'image/') !== false) {
                                $fimg = Image::make($line[5])->resize(800, 800);
                                $fphoto = time() . Str::random(8) . '.jpg';
                                $fimg->save(public_path() . '/assets/images/products/' . $fphoto);
                                $input['photo'] = $fphoto;
                                $thumb_url = $line[5];
                            } else {
                                $fimg = Image::make(public_path() . '/assets/images/noimage.png')->resize(800, 800);
                                $fphoto = time() . Str::random(8) . '.jpg';
                                $fimg->save(public_path() . '/assets/images/products/' . $fphoto);
                                $input['photo'] = $fphoto;
                                $thumb_url = public_path() . '/assets/images/noimage.png';
                            }

                            $timg = Image::make($thumb_url)->resize(285, 285);
                            $thumbnail = time() . Str::random(8) . '.jpg';
                            $timg->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
                            $input['thumbnail'] = $thumbnail;

                            // Conert Price According to Currency
                            $input['price'] = ($input['price'] / $sign->value);
                            $input['previous_price'] = ($input['previous_price'] / $sign->value);
                            $input['user_id'] = $user->id;
                            // Save Data
                            $data->fill($input)->save();
                        } else {
                            $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('No Category Found!') . "<br>";
                        }
                    } else {
                        $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('Duplicate Product Code!') . "<br>";
                    }
                }

                $i++;
            }
            fclose($file);

            //--- Redirect Section
            $msg = __("New Product Added Successfully.") . $log;
            return back()->with('success', $msg);
        } else {
            //--- Redirect Section
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));

            //--- Redirect Section Ends
        }
    }

    //*** POST Request
    public function store(Request $request)
    {

        $user = $this->user;
        $package = $user->subscribes()->latest('id')->first();

        $prods = $user->products()->latest('id')->get()->count();
        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if ($prods < $package->allowed_products || $package->allowed_products == 0) {

            //--- Validation Section
            $rules = [
                'photo' => 'required',
                'file' => 'mimes:zip',
            ];
            $request->validate($rules);

            //--- Logic Section
            $data = new Product;
            $sign = $this->curr;
            $input = $request->all();
            // Check File
            if ($file = $request->file('file')) {
                $extensions = ['zip'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/files', $name);
                $input['file'] = $name;
            }

            $image = $request->photo;
            list($type, $image) = explode(';', $image);
            list(, $image) = explode(',', $image);
            $image = base64_decode($image);
            $image_name = time() . Str::random(8) . '.png';
            $path = 'assets/images/products/' . $image_name;
            file_put_contents($path, $image);
            $input['photo'] = $image_name;

            if ($request->type == "Physical" || $request->type == "Listing") {
                $rules = ['sku' => 'min:8|unique:products'];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
                }

                if ($request->product_condition_check == "") {
                    $input['product_condition'] = 0;
                }

                if ($request->preordered_check == "") {
                    $input['preordered'] = 0;
                }

                if ($request->minimum_qty_check == "") {
                    $input['minimum_qty'] = null;
                }

                if ($request->shipping_time_check == "") {
                    $input['ship'] = null;
                }

                if (empty($request->stock_check)) {
                    $input['stock_check'] = 0;
                    $input['size'] = null;
                    $input['size_qty'] = null;
                    $input['size_price'] = null;
                    $input['color'] = null;
                } else {
                    if (in_array(null, $request->size) || in_array(null, $request->size_qty) || in_array(null, $request->size_price)) {
                        $input['stock_check'] = 0;
                        $input['size'] = null;
                        $input['size_qty'] = null;
                        $input['size_price'] = null;
                        $input['color'] = null;
                    } else {
                        $input['stock_check'] = 1;
                        // $input['color'] = implode(',', $request->color);
                        $input['size'] = implode(',', $request->size);
                        $input['size_qty'] = implode(',', $request->size_qty);
                        $size_prices = $request->size_price;
                        $s_price = array();
                        foreach ($size_prices as $key => $sPrice) {
                            $s_price[$key] = $sPrice / $sign->value;
                        }

                        $input['size_price'] = implode(',', $s_price);
                    }
                }

                if (empty($request->color_check)) {
                    $input['color_all'] = null;
                } else {
                    $input['color_all'] = implode(',', $request->color_all);
                }

                if (empty($request->whole_check)) {
                    $input['whole_sell_qty'] = null;
                    $input['whole_sell_discount'] = null;
                } else {
                    if (in_array(null, $request->whole_sell_qty) || in_array(null, $request->whole_sell_discount)) {
                        $input['whole_sell_qty'] = null;
                        $input['whole_sell_discount'] = null;
                    } else {
                        $input['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
                        $input['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
                    }
                }

                if ($request->mesasure_check == "") {
                    $input['measure'] = null;
                }
            }

            if (empty($request->seo_check)) {
                $input['meta_tag'] = null;
                $input['meta_description'] = null;
            } else {
                if (!empty($request->meta_tag)) {
                    $input['meta_tag'] = $request->meta_tag;
                    $input['is_meta'] = 1;
                }
            }

            // Check License

            if ($request->type == "License") {

                if (in_array(null, $request->license) || in_array(null, $request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $input['license'] = implode(',,', $request->license);
                    $input['license_qty'] = implode(',', $request->license_qty);
                }
            }

            // Check Features
            if (in_array(null, $request->features) || in_array(null, $request->colors)) {
                $input['features'] = null;
                $input['colors'] = null;
            } else {
                $input['features'] = implode(',', str_replace(',', ' ', $request->features));
                $input['colors'] = implode(',', str_replace(',', ' ', $request->colors));
            }

            //tags
            if (!empty($request->tags)) {
                $input['tags'] = $request->tags;
            }

            // Conert Price According to Currency
            $input['price'] = ($input['price'] / $sign->value);
            $input['previous_price'] = ($input['previous_price'] / $sign->value);
            $input['user_id'] = $this->user->id;

            $attrArr = [];
            if (!empty($request->category_id)) {
                $catAttrs = Attribute::where('attributable_id', $request->category_id)->where('attributable_type', 'App\Models\Category')->get();
                if (!empty($catAttrs)) {
                    foreach ($catAttrs as $key => $catAttr) {
                        $in_name = $catAttr->input_name;

                        if ($request->has("$in_name")) {
                            $attrArr["$in_name"]["values"] = $request["$in_name"];
                            foreach ($request["$in_name" . "_price"] as $aprice) {
                                $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                            }
                            $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                            if ($catAttr->details_status) {
                                $attrArr["$in_name"]["details_status"] = 1;
                            } else {
                                $attrArr["$in_name"]["details_status"] = 0;
                            }
                        }
                    }
                }
            }

            //dd($request->all());

            if (!empty($request->subcategory_id)) {
                $subAttrs = Attribute::where('attributable_id', $request->subcategory_id)->where('attributable_type', 'App\Models\Subcategory')->get();
                if (!empty($subAttrs)) {
                    foreach ($subAttrs as $key => $subAttr) {
                        $in_name = $subAttr->input_name;
                        if ($request->has("$in_name")) {
                            $attrArr["$in_name"]["values"] = $request["$in_name"];
                            foreach ($request["$in_name" . "_price"] as $aprice) {
                                $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                            }
                            $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                            if ($subAttr->details_status) {
                                $attrArr["$in_name"]["details_status"] = 1;
                            } else {
                                $attrArr["$in_name"]["details_status"] = 0;
                            }
                        }
                    }
                }
            }

            if (!empty($request->childcategory_id)) {
                $childAttrs = Attribute::where('attributable_id', $request->childcategory_id)->where('attributable_type', 'App\Models\Childcategory')->get();
                if (!empty($childAttrs)) {
                    foreach ($childAttrs as $key => $childAttr) {
                        $in_name = $childAttr->input_name;
                        if ($request->has("$in_name")) {
                            $attrArr["$in_name"]["values"] = $request["$in_name"];
                            foreach ($request["$in_name" . "_price"] as $aprice) {
                                $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                            }
                            $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                            if ($childAttr->details_status) {
                                $attrArr["$in_name"]["details_status"] = 1;
                            } else {
                                $attrArr["$in_name"]["details_status"] = 0;
                            }
                        }
                    }
                }
            }

            if (empty($attrArr)) {
                $input['attributes'] = null;
            } else {
                $jsonAttr = json_encode($attrArr);
                $input['attributes'] = $jsonAttr;
            }

            // Save Data
            $data->fill($input)->save();

            // Set SLug

            $prod = Product::find($data->id);
            if ($prod->type != 'Physical') {
                $prod->slug = Str::slug($data->name, '-') . '-' . strtolower(Str::random(3) . $data->id . Str::random(3));
            } else {
                $prod->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
            }
            // Set Thumbnail
            $img = Image::make(public_path() . '/assets/images/products/' . $prod->photo)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $prod->thumbnail = $thumbnail;
            $prod->update();

            // Add To Gallery If any
            $lastid = $data->id;
            if ($files = $request->file('gallery')) {
                foreach ($files as $key => $file) {
                    $extensions = ['jpeg', 'jpg', 'png', 'svg'];
                    if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                        return response()->json(array('errors' => ['Image format not supported']));
                    }

                    $gallery = new Gallery;
                    $name = \PriceHelper::ImageCreateName($file);
                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $thumbnail = time() . Str::random(8) . '.jpg';
                    $img->save(public_path() . '/assets/images/galleries/' . $name);
                    $gallery['photo'] = $name;
                    $gallery['product_id'] = $lastid;
                    $gallery->save();

                }
            }
            //logic Section Ends

            //--- Redirect Section
            $msg = __('New Product Added Successfully.');
            return back()->with('success', $msg);
            //--- Redirect Section Ends
        } else {
            //--- Redirect Section
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));

            //--- Redirect Section Ends
        }
    }

    //*** GET Request
    public function edit($id)
    {
        $cats = Category::all();
        $data = Product::findOrFail($id);
        $sign = $this->curr;

        if ($data->type == 'Digital') {
            return view('vendor.product.edit.digital', compact('cats', 'data', 'sign'));
        } elseif ($data->type == 'License') {
            return view('vendor.product.edit.license', compact('cats', 'data', 'sign'));
        } elseif ($data->type == 'Listing') {
            return view('vendor.product.edit.listing', compact('cats', 'data', 'sign'));
        } else {
            return view('vendor.product.edit.physical', compact('cats', 'data', 'sign'));
        }
    }

    //*** GET Request CATALOG
    public function catalogedit($id)
    {
        $cats = Category::all();
        $data = Product::findOrFail($id);
        $sign = $this->curr;

        if ($data->type == 'Digital') {
            return view('vendor.product.edit.catalog.digital', compact('cats', 'data', 'sign'));
        } elseif ($data->type == 'License') {
            return view('vendor.product.edit.catalog.license', compact('cats', 'data', 'sign'));
        } else {
            return view('vendor.product.edit.catalog.physical', compact('cats', 'data', 'sign'));
        }
    }

    //*** POST Request
    public function update(Request $request, $id)
    {

        //--- Validation Section
        $rules = [
            'file' => 'mimes:zip',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //-- Logic Section
        $data = Product::findOrFail($id);
        $sign = $this->curr;
        $input = $request->all();

        //Check Types
        if ($request->type_check == 1) {
            $input['link'] = null;
        } else {
            if ($data->file != null) {
                if (file_exists(public_path() . '/assets/files/' . $data->file)) {
                    unlink(public_path() . '/assets/files/' . $data->file);
                }
            }
            $input['file'] = null;
        }

        // Check Physical
        if ($data->type == "Physical") {

            //--- Validation Section
            $rules = ['sku' => 'min:8|unique:products,sku,' . $id];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            // Check Condition
            if ($request->product_condition_check == "") {
                $input['product_condition'] = 0;
            }

            // Check Preorderd
            if ($request->preordered_check == "") {
                $input['preordered'] = 0;
            }

            // Check Minimum Qty
            if ($request->minimum_qty_check == "") {
                $input['minimum_qty'] = null;
            }

            // Check Shipping Time
            if ($request->shipping_time_check == "") {
                $input['ship'] = null;
            }

            // Check Size
            if (empty($request->stock_check)) {
                $input['stock_check'] = 0;
                $input['size'] = null;
                $input['size_qty'] = null;
                $input['size_price'] = null;
                $input['color'] = null;
            } else {
                if (in_array(null, $request->size) || in_array(null, $request->size_qty) || in_array(null, $request->size_price)) {
                    $input['stock_check'] = 0;
                    $input['size'] = null;
                    $input['size_qty'] = null;
                    $input['size_price'] = null;
                    $input['color'] = null;
                } else {
                    $input['stock_check'] = 1;
                    // $input['color'] = implode(',', $request->color);
                    $input['size'] = implode(',', $request->size);
                    $input['size_qty'] = implode(',', $request->size_qty);
                    $size_prices = $request->size_price;
                    $s_price = array();
                    foreach ($size_prices as $key => $sPrice) {
                        $s_price[$key] = $sPrice / $sign->value;
                    }

                    $input['size_price'] = implode(',', $s_price);
                }
            }

            // Check Color
            if (empty($request->color_check)) {
                $input['color_all'] = null;
            } else {
                $input['color_all'] = implode(',', $request->color_all);
            }

            // Check Whole Sale
            if (empty($request->whole_check)) {
                $input['whole_sell_qty'] = null;
                $input['whole_sell_discount'] = null;
            } else {
                if (in_array(null, $request->whole_sell_qty) || in_array(null, $request->whole_sell_discount)) {
                    $input['whole_sell_qty'] = null;
                    $input['whole_sell_discount'] = null;
                } else {
                    $input['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
                    $input['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
                }
            }

            if (empty($request->color_check)) {
                $input['color_all'] = null;
            } else {
                $input['color_all'] = implode(',', $request->color_all);
            }

            // Check Measure
            if ($request->measure_check == "") {
                $input['measure'] = null;
            }
        }
        //dd($request->all());
        // Check Seo
        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = $request->meta_tag;
                $input['is_meta'] = 1;
            }
        }

        // Check License
        if ($data->type == "License") {

            if (!in_array(null, $request->license) && !in_array(null, $request->license_qty)) {
                $input['license'] = implode(',,', $request->license);
                $input['license_qty'] = implode(',', $request->license_qty);
            } else {
                if (in_array(null, $request->license) || in_array(null, $request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $license = explode(',,', $data->license);
                    $license_qty = explode(',', $data->license_qty);
                    $input['license'] = implode(',,', $license);
                    $input['license_qty'] = implode(',', $license_qty);
                }
            }
        }
        // Check Features
        if (!in_array(null, $request->features) && !in_array(null, $request->colors)) {
            $input['features'] = implode(',', str_replace(',', ' ', $request->features));
            $input['colors'] = implode(',', str_replace(',', ' ', $request->colors));
        } else {
            if (in_array(null, $request->features) || in_array(null, $request->colors)) {
                $input['features'] = null;
                $input['colors'] = null;
            } else {
                $features = explode(',', $data->features);
                $colors = explode(',', $data->colors);
                $input['features'] = implode(',', $features);
                $input['colors'] = implode(',', $colors);
            }
        }

        //Product Tags
        if (!empty($request->tags)) {
            $input['tags'] = $request->tags;
        }
        if (empty($request->tags)) {
            $input['tags'] = null;
        }

        $input['price'] = $input['price'] / $sign->value;
        $input['previous_price'] = $input['previous_price'] / $sign->value;

        // store filtering attributes for physical product
        $attrArr = [];
        if (!empty($request->category_id)) {
            $catAttrs = Attribute::where('attributable_id', $request->category_id)->where('attributable_type', 'App\Models\Category')->get();
            if (!empty($catAttrs)) {
                foreach ($catAttrs as $key => $catAttr) {
                    $in_name = $catAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        if ($catAttr->details_status) {
                            $attrArr["$in_name"]["details_status"] = 1;
                        } else {
                            $attrArr["$in_name"]["details_status"] = 0;
                        }
                    }
                }
            }
        }

        if (!empty($request->subcategory_id)) {
            $subAttrs = Attribute::where('attributable_id', $request->subcategory_id)->where('attributable_type', 'App\Models\Subcategory')->get();
            if (!empty($subAttrs)) {
                foreach ($subAttrs as $key => $subAttr) {
                    $in_name = $subAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        if ($subAttr->details_status) {
                            $attrArr["$in_name"]["details_status"] = 1;
                        } else {
                            $attrArr["$in_name"]["details_status"] = 0;
                        }
                    }
                }
            }
        }
        if (!empty($request->childcategory_id)) {
            $childAttrs = Attribute::where('attributable_id', $request->childcategory_id)->where('attributable_type', 'App\Models\Childcategory')->get();
            if (!empty($childAttrs)) {
                foreach ($childAttrs as $key => $childAttr) {
                    $in_name = $childAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        if ($childAttr->details_status) {
                            $attrArr["$in_name"]["details_status"] = 1;
                        } else {
                            $attrArr["$in_name"]["details_status"] = 0;
                        }
                    }
                }
            }
        }

        if (empty($attrArr)) {
            $input['attributes'] = null;
        } else {
            $jsonAttr = json_encode($attrArr);
            $input['attributes'] = $jsonAttr;
        }

        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);

        $data->update($input);
        //-- Logic Section Ends

        // Add To Gallery If any
        $lastid = $data->id;
        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                $extensions = ['jpeg', 'jpg', 'png', 'svg'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }

                $gallery = new Gallery;
                $name = \PriceHelper::ImageCreateName($file);
                $img = Image::make($file->getRealPath())->resize(800, 800);
                $thumbnail = time() . Str::random(8) . '.jpg';
                $img->save(public_path() . '/assets/images/galleries/' . $name);
                $gallery['photo'] = $name;
                $gallery['product_id'] = $lastid;
                $gallery->save();

            }
        }
        //logic Section Ends

        //--- Redirect Section
        $msg = __('Product Updated Successfully.');
        return back()->with('success', $msg);
        //--- Redirect Section Ends
    }

    //*** POST Request CATALOG
    public function catalogupdate(Request $request, $id)
    {

        $user = $this->user;
        $package = $user->subscribes()->latest('id')->first();
        $prods = $user->products()->latest('id')->get()->count();
        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }
        if ($prods < $package->allowed_products || $package->allowed_products == 0) {

            //--- Validation Section
            $rules = [
                'file' => 'mimes:zip',
            ];

            $request->validate($rules);
            //--- Logic Section
            $data = new Product;
            $sign = $this->curr;
            $input = $request->all();
            // Check File
            if ($file = $request->file('file')) {
                $extensions = ['zip'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/files', $name);
                $input['file'] = $name;
            }

            $image = $request->photo;
            if ($request->is_photo == '1') {
                list($type, $image) = explode(';', $image);
                list(, $image) = explode(',', $image);
                $image = base64_decode($image);
                $image_name = time() . Str::random(8) . '.png';
                $path = 'assets/images/products/' . $image_name;
                file_put_contents($path, $image);
            } else {
                $image_name = $request->photo;
            }

            $input['photo'] = $image_name;

            // Check Physical
            if ($request->type == "Physical") {

                //--- Validation Section
                $rules = ['sku' => 'min:8|unique:products'];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
                }
                //--- Validation Section Ends

                // Check Condition
                if ($request->product_condition_check == "") {
                    $input['product_condition'] = 0;
                }

                // Check Preorderd
                if ($request->preordered_check == "") {
                    $input['preordered'] = 0;
                }

                // Check Minimum Qty
                if ($request->minimum_qty_check == "") {
                    $input['minimum_qty'] = null;
                }

                // Check Shipping Time
                if ($request->shipping_time_check == "") {
                    $input['ship'] = null;
                }

                // Check Size
                if (empty($request->size_check)) {
                    $input['size'] = null;
                    $input['size_qty'] = null;
                    $input['size_price'] = null;
                } else {
                    if (in_array(null, $request->size) || in_array(null, $request->size_qty)) {
                        $input['size'] = null;
                        $input['size_qty'] = null;
                        $input['size_price'] = null;
                    } else {
                        $input['size'] = implode(',', $request->size);
                        $input['size_qty'] = implode(',', $request->size_qty);
                        $size_prices = $request->size_price;
                        $s_price = array();
                        foreach ($size_prices as $key => $sPrice) {
                            $s_price[$key] = $sPrice / $sign->value;
                        }

                        $input['size_price'] = implode(',', $s_price);
                    }
                }

                // Check Whole Sale
                if (empty($request->whole_check)) {
                    $input['whole_sell_qty'] = null;
                    $input['whole_sell_discount'] = null;
                } else {
                    if (in_array(null, $request->whole_sell_qty) || in_array(null, $request->whole_sell_discount)) {
                        $input['whole_sell_qty'] = null;
                        $input['whole_sell_discount'] = null;
                    } else {
                        $input['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
                        $input['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
                    }
                }

                if (empty($request->color_check)) {
                    $input['color_all'] = null;
                } else {
                    $input['color_all'] = implode(',', $request->color_all);
                }

                // Check Measurement
                if ($request->mesasure_check == "") {
                    $input['measure'] = null;
                }
            }

            // Check Seo
            if (empty($request->seo_check)) {
                $input['meta_tag'] = null;
                $input['meta_description'] = null;
            } else {
                if (!empty($request->meta_tag)) {
                    $input['meta_tag'] = $request->meta_tag;
                    $input['is_meta'] = 1;
                }
            }

            // Check License

            if ($request->type == "License") {

                if (in_array(null, $request->license) || in_array(null, $request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $input['license'] = implode(',,', $request->license);
                    $input['license_qty'] = implode(',', $request->license_qty);
                }
            }

            // Check Features
            if (in_array(null, $request->features) || in_array(null, $request->colors)) {
                $input['features'] = null;
                $input['colors'] = null;
            } else {
                $input['features'] = implode(',', str_replace(',', ' ', $request->features));
                $input['colors'] = implode(',', str_replace(',', ' ', $request->colors));
            }

            //tags
            if (!empty($request->tags)) {
                $input['tags'] = $request->tags;
            }

            // Conert Price According to Currency
            $input['price'] = ($input['price'] / $sign->value);
            $input['previous_price'] = ($input['previous_price'] / $sign->value);
            $input['user_id'] = $this->user->id;

            // store filtering attributes for physical product
            $attrArr = [];
            if (!empty($request->category_id)) {
                $catAttrs = Attribute::where('attributable_id', $request->category_id)->where('attributable_type', 'App\Models\Category')->get();
                if (!empty($catAttrs)) {
                    foreach ($catAttrs as $key => $catAttr) {
                        $in_name = $catAttr->input_name;
                        if ($request->has("$in_name")) {
                            $attrArr["$in_name"]["values"] = $request["$in_name"];
                            foreach ($request["$in_name" . "_price"] as $aprice) {
                                $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                            }
                            $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                            if ($catAttr->details_status) {
                                $attrArr["$in_name"]["details_status"] = 1;
                            } else {
                                $attrArr["$in_name"]["details_status"] = 0;
                            }
                        }
                    }
                }
            }

            if (!empty($request->subcategory_id)) {
                $subAttrs = Attribute::where('attributable_id', $request->subcategory_id)->where('attributable_type', 'App\Models\Subcategory')->get();
                if (!empty($subAttrs)) {
                    foreach ($subAttrs as $key => $subAttr) {
                        $in_name = $subAttr->input_name;
                        if ($request->has("$in_name")) {
                            $attrArr["$in_name"]["values"] = $request["$in_name"];
                            foreach ($request["$in_name" . "_price"] as $aprice) {
                                $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                            }
                            $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                            if ($subAttr->details_status) {
                                $attrArr["$in_name"]["details_status"] = 1;
                            } else {
                                $attrArr["$in_name"]["details_status"] = 0;
                            }
                        }
                    }
                }
            }
            if (!empty($request->childcategory_id)) {
                $childAttrs = Attribute::where('attributable_id', $request->childcategory_id)->where('attributable_type', 'App\Models\Childcategory')->get();
                if (!empty($childAttrs)) {
                    foreach ($childAttrs as $key => $childAttr) {
                        $in_name = $childAttr->input_name;
                        if ($request->has("$in_name")) {
                            $attrArr["$in_name"]["values"] = $request["$in_name"];
                            foreach ($request["$in_name" . "_price"] as $aprice) {
                                $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                            }
                            $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                            if ($childAttr->details_status) {
                                $attrArr["$in_name"]["details_status"] = 1;
                            } else {
                                $attrArr["$in_name"]["details_status"] = 0;
                            }
                        }
                    }
                }
            }

            if (empty($attrArr)) {
                $input['attributes'] = null;
            } else {
                $jsonAttr = json_encode($attrArr);
                $input['attributes'] = $jsonAttr;
            }

            // Save Data
            $data->fill($input)->save();

            // Set SLug

            $prod = Product::find($data->id);
            if ($prod->type != 'Physical') {
                $prod->slug = Str::slug($data->name, '-') . '-' . strtolower(Str::random(3) . $data->id . Str::random(3));
            } else {
                $prod->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
            }
            $photo = $prod->photo;
            if ($request->is_photo == '0') {
                // Set Photo
                $newimg = Image::make(public_path() . '/assets/images/products/' . $prod->photo)->resize(800, 800);
                $photo = time() . Str::random(8) . '.jpg';
                $newimg->save(public_path() . '/assets/images/products/' . $photo);
            }

            // Set Thumbnail
            $img = Image::make(public_path() . '/assets/images/products/' . $prod->photo)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $prod->thumbnail = $thumbnail;
            $prod->photo = $photo;
            $prod->update();

            // Add To Gallery If any
            $lastid = $data->id;
            if ($files = $request->file('gallery')) {
                foreach ($files as $key => $file) {
                    $extensions = ['jpeg', 'jpg', 'png', 'svg'];
                    if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                        return response()->json(array('errors' => ['Image format not supported']));
                    }

                    $gallery = new Gallery;
                    $name = \PriceHelper::ImageCreateName($file);
                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $thumbnail = time() . Str::random(8) . '.jpg';
                    $img->save(public_path() . '/assets/images/galleries/' . $name);
                    $gallery['photo'] = $name;
                    $gallery['product_id'] = $lastid;
                    $gallery->save();

                }
            }
            //logic Section Ends

            //--- Redirect Section
            $msg = __('New Product Added Successfully.');

            return back()->with('success', $msg);
            //--- Redirect Section Ends
        } else {
            //--- Redirect Section
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));

            //--- Redirect Section Ends
        }
    }

    //*** GET Request
    public function destroy($id)
    {

        $data = Product::findOrFail($id);
        if ($data->galleries->count() > 0) {
            foreach ($data->galleries as $gal) {
                if (file_exists(public_path() . '/assets/images/galleries/' . $gal->photo)) {
                    unlink(public_path() . '/assets/images/galleries/' . $gal->photo);
                }
                $gal->delete();
            }
        }

        if ($data->ratings->count() > 0) {
            foreach ($data->ratings as $gal) {
                $gal->delete();
            }
        }
        if ($data->wishlists->count() > 0) {
            foreach ($data->wishlists as $gal) {
                $gal->delete();
            }
        }
        if ($data->clicks->count() > 0) {
            foreach ($data->clicks as $gal) {
                $gal->delete();
            }
        }
        if ($data->comments->count() > 0) {
            foreach ($data->comments as $gal) {
                if ($gal->replies->count() > 0) {
                    foreach ($gal->replies as $key) {
                        $key->delete();
                    }
                }
                $gal->delete();
            }
        }

        if (!filter_var($data->photo, FILTER_VALIDATE_URL)) {
            if (file_exists(public_path() . '/assets/images/products/' . $data->photo)) {
                unlink(public_path() . '/assets/images/products/' . $data->photo);
            }
        }

        if (file_exists(public_path() . '/assets/images/thumbnails/' . $data->thumbnail) && $data->thumbnail != "") {
            unlink(public_path() . '/assets/images/thumbnails/' . $data->thumbnail);
        }
        if ($data->file != null) {
            if (file_exists(public_path() . '/assets/files/' . $data->file)) {
                unlink(public_path() . '/assets/files/' . $data->file);
            }
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('Product Deleted Successfully.');
        return back()->with('success', $msg);
        //--- Redirect Section Ends

        // PRODUCT DELETE ENDS
    }

    public function getAttributes(Request $request)
    {
        $model = '';
        if ($request->type == 'category') {
            $model = 'App\Models\Category';
        } elseif ($request->type == 'subcategory') {
            $model = 'App\Models\Subcategory';
        } elseif ($request->type == 'childcategory') {
            $model = 'App\Models\Childcategory';
        }

        $attributes = Attribute::where('attributable_id', $request->id)->where('attributable_type', $model)->get();
        $attrOptions = [];
        foreach ($attributes as $key => $attribute) {
            $options = AttributeOption::where('attribute_id', $attribute->id)->get();
            $attrOptions[] = ['attribute' => $attribute, 'options' => $options];
        }
        return response()->json($attrOptions);
    }
}
