<?php

namespace App\Http\Controllers\Front;

use App\Models\Category;
use App\Models\Report;
use App\Models\Subcategory;
use App\Services\CatalogItemFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CatalogController extends FrontBaseController
{
    public function __construct(
        private CatalogItemFilterService $filterService
    ) {
        parent::__construct();
    }

    // CATEGORIES SECTION

    public function categories(Request $request)
    {
        // Handle view mode
        if ($request->view_check) {
            Session::put('view', $request->view_check);
        }

        $perPage = $this->gs->page_count ?? 12;
        $currValue = $this->curr->value ?? 1;

        // Use service to get all data (no category selected = ALL catalog items)
        $data = $this->filterService->getCatalogItemResults(
            $request,
            null,  // no category
            null,  // no subcategory
            null,  // no childcategory
            $perPage,
            $currValue
        );

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.catalog-items', $data);
    }

    // -------------------------------- CATEGORY SECTION ----------------------------------------

    public function category(Request $request, $slug = null, $slug1 = null, $slug2 = null, $slug3 = null)
    {
        // Handle view mode
        if ($request->view_check) {
            Session::put('view', $request->view_check);
        }

        // Get pagination settings
        $pageby = $request->pageby && $request->pageby !== 'undefined' && is_numeric($request->pageby) ? (int)$request->pageby : null;
        $perPage = $pageby ?? $this->gs->page_count ?? 12;
        $currValue = $this->curr->value ?? 1;

        // Use service to get all data with filters applied
        $data = $this->filterService->getCatalogItemResults(
            $request,
            $slug,       // category slug
            $slug1,      // subcategory slug
            $slug2,      // childcategory slug
            $perPage,
            $currValue
        );

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.catalog-items', $data);
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
        // Security: Only allow specific fields, set user_id from authenticated user
        $data->product_id = $request->input('product_id');
        $data->merchant_product_id = $request->input('merchant_product_id');
        $data->title = $request->input('title');
        $data->note = $request->input('note');
        $data->user_id = auth()->id(); // Set from authenticated user, not from request
        $data->save();

        return back()->with('success', 'Report has been sent successfully.');

    }
}
