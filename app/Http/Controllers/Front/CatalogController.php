<?php

namespace App\Http\Controllers\Front;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Product;
use App\Models\Report;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CatalogController extends FrontBaseController
{

    // CATEGORIES SECTOPN

    public function categories()
    {
        $categories = Category::where('status', 1)->get();
        return view('frontend.products', compact('categories'));
    }

    // -------------------------------- CATEGORY SECTION ----------------------------------------

    public function category(Request $request, $slug = null, $slug1 = null, $slug2 = null, $slug3 = null)
    {
       
        $data['categories'] = Category::with('subs')->where('status', 1)->get();

        if ($request->view_check) {
            session::put('view', $request->view_check);
        }

        //   dd(session::get('view'));

        $cat = null;
        $subcat = null;
        $childcat = null;
        $flash = null;
        $minprice = $request->min;
        $maxprice = $request->max;
        $sort = $request->sort;
        $search = $request->search;
        $pageby = $request->pageby;

        $minprice = ($minprice / $this->curr->value);
        $maxprice = ($maxprice / $this->curr->value);
        $type = $request->has('type') ?? '';

        if (!empty($slug)) {
            $cat = Category::where('slug', $slug)->firstOrFail();
            $data['cat'] = $cat;
        }

        if (!empty($slug1)) {
            $subcat = Subcategory::where('slug', $slug1)->firstOrFail();
            $data['subcat'] = $subcat;
        }
        if (!empty($slug2) && $subcat) {
            $childcat = Childcategory::where('slug', $slug2)
                ->where('subcategory_id', $subcat->id)
                ->firstOrFail();
            $data['childcat'] = $childcat;
        }

        // Retrieve latest products that have at least one active merchant listing with a vendor account
        $data['latest_products'] = Product::whereLatest(1)
            ->whereHas('merchantProducts', function ($q) {
                $q->where('status', 1)
                    ->whereHas('user', function ($user) {
                        $user->where('is_vendor', 2);
                    });
            })
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->take(5)
            ->get();

        // Build product query based on categories and vendor-specific conditions
        $prods = Product::query();
        
        // Filter by category
        $prods = $prods->when($cat, function ($query, $cat) {
            return $query->where('category_id', $cat->id);
        });

        // Filter by subcategory
        $prods = $prods->when($subcat, function ($query, $subcat) {
            return $query->where('subcategory_id', $subcat->id);
        });

        // Filter by child category
        $prods = $prods->when($childcat, function ($query, $childcat) {
            return $query->where('childcategory_id', $childcat->id);
        });

        // Search by name
        $prods = $prods->when($search, function ($query, $search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', $search . '%');
            });
        });

        // Apply price, discount and stock filters via merchantProducts relationship
        $prods = $prods->whereHas('merchantProducts', function ($q) use ($minprice, $maxprice, $type) {
            $q->where('status', 1)
                ->where('stock', '>=', 1)
                ->when($minprice, function ($query) use ($minprice) {
                    return $query->where('price', '>=', $minprice);
                })
                ->when($maxprice, function ($query) use ($maxprice) {
                    return $query->where('price', '<=', $maxprice);
                })
                ->when($type, function ($query) {
                    // `type` indicates discount filter: require discount to be active and not expired
                    return $query->where('is_discount', 1)
                        ->where('discount_date', '>=', date('Y-m-d'));
                })
                ->whereHas('user', function ($user) {
                    $user->where('is_vendor', 2);
                });
        });

        // Sorting options
        $prods = $prods->when($sort, function ($query, $sort) {
            if ($sort == 'date_desc') {
                return $query->latest('products.id');
            } elseif ($sort == 'date_asc') {
                return $query->oldest('products.id');
            } elseif ($sort == 'price_desc') {
                // Order by minimum vendor price descending
                return $query->orderByRaw('(select min(price) from merchant_products where merchant_products.product_id = products.id and merchant_products.status = 1) desc');
            } elseif ($sort == 'price_asc') {
                // Order by minimum vendor price ascending
                return $query->orderByRaw('(select min(price) from merchant_products where merchant_products.product_id = products.id and merchant_products.status = 1) asc');
            }
        });
        
        // Default sorting if no sort option provided
        if (empty($sort)) {
            $prods = $prods->latest('products.id');
        }

        // Add ratings count and average
        $prods = $prods->withCount('ratings')->withAvg('ratings', 'rating');

        $prods = $prods->where(function ($query) use ($cat, $subcat, $childcat, $type, $request) {
            $flag = 0;
            if (!empty($cat)) {
                foreach ($cat->attributes as $key => $attribute) {
                    $inname = $attribute->input_name;
                    $chFilters = $request["$inname"];

                    if (!empty($chFilters)) {
                        $flag = 1;
                        foreach ($chFilters as $key => $chFilter) {
                            if ($key == 0) {
                                $query->where('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                            } else {
                                $query->orWhere('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                            }
                        }
                    }
                }
            }

            if (!empty($subcat)) {
                foreach ($subcat->attributes as $attribute) {
                    $inname = $attribute->input_name;
                    $chFilters = $request["$inname"];

                    if (!empty($chFilters)) {
                        $flag = 1;
                        foreach ($chFilters as $key => $chFilter) {
                            if ($key == 0 && $flag == 0) {
                                $query->where('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                            } else {
                                $query->orWhere('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                            }
                        }
                    }
                }
            }

            if (!empty($childcat)) {
                foreach ($childcat->attributes as $attribute) {
                    $inname = $attribute->input_name;
                    $chFilters = $request["$inname"];

                    if (!empty($chFilters)) {
                        $flag = 1;
                        foreach ($chFilters as $key => $chFilter) {
                            if ($key == 0 && $flag == 0) {
                                $query->where('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                            } else {
                                $query->orWhere('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                            }
                        }
                    }
                }
            }
        });
        
        // dd($prods->toSql(), $prods->getBindings(), $prods->count());


        // Paginate after applying filters and eager loading ratings.  Once paginated, transform each item
        $prods = $prods->paginate(isset($pageby) ? $pageby : $this->gs->page_count);
        
        $prods->getCollection()->transform(function ($item) {
            // Replace price with vendor size price (includes commission and attribute adjustments)
            $item->price = $item->vendorSizePrice();
            return $item;
        });
        
        $data['prods'] = $prods;
        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.products', $data);
    }

    public function getsubs(Request $request)
    {
        $category = Category::where('slug', $request->category)->firstOrFail();
        $subcategories = Subcategory::where('category_id', $category->id)->get();
        return $subcategories;
    }
    public function report(Request $request)
    {

        //--- Validation Section
        $rules = [
            'note' => 'max:400',
        ];
        $customs = [
            'note.max' => 'Note Must Be Less Than 400 Characters.',
        ];
        
        $request->validate($rules, $customs);


        $data = new Report;
        $input = $request->all();
        $data->fill($input)->save();
        return back()->with('success', 'Report has been sent successfully.');

    }
}
