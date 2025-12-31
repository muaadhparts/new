<?php

namespace App\Http\Controllers\Admin;

use App\Models\CatalogReview;

use Datatables;

class CatalogReviewController extends AdminBaseController
{
	    //*** JSON Request
	    public function datatables()
	    {
	         $datas = CatalogReview::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand', 'user'])
	         	->latest('id')
	         	->get();

	         return Datatables::of($datas)
	                            ->addColumn('product', function(CatalogReview $data) {
									$name = $data->catalogItem ? getLocalizedProductName($data->catalogItem, 50) : __('N/A');

									// الرابط للمنتج
									if ($data->merchantItem && $data->merchantItem->id && $data->catalogItem) {
										$prodLink = route('front.catalog-item', [
											'slug' => $data->catalogItem->slug,
											'merchant_id' => $data->merchantItem->user_id,
											'merchant_item_id' => $data->merchantItem->id
										]);
									} elseif ($data->catalogItem && $data->catalogItem->sku) {
										$prodLink = route('search.result', $data->catalogItem->sku);
									} else {
										$prodLink = '#';
									}

	                                $product = '<a href="'.$prodLink.'" target="_blank">'.$name.'</a>';
	                                return $product;
	                            })
								->addColumn('brand', function (CatalogReview $data) {
									return $data->catalogItem && $data->catalogItem->brand ? getLocalizedBrandName($data->catalogItem->brand) : __('N/A');
								})
								->addColumn('quality_brand', function (CatalogReview $data) {
									return $data->merchantItem && $data->merchantItem->qualityBrand
										? getLocalizedQualityName($data->merchantItem->qualityBrand)
										: __('N/A');
								})
								->addColumn('vendor', function (CatalogReview $data) {
									if ($data->merchantItem && $data->merchantItem->user) {
										$shopName = $data->merchantItem->user->shop_name ?: $data->merchantItem->user->name;
										return '<a href="' . route('admin-vendor-show', $data->merchantItem->user_id) . '" target="_blank">' . $shopName . '</a>';
									}
									return __('N/A');
								})
	                            ->addColumn('reviewer', function(CatalogReview $data) {
	                                return $data->user->name;
	                            })
	                            ->addColumn('rating', function(CatalogReview $data) {
	                                return '<span class="badge badge-warning"><i class="fas fa-star"></i> ' . $data->rating . '</span>';
	                            })
	                            ->addColumn('review', function(CatalogReview $data) {
	                                $text = mb_strlen(strip_tags($data->review),'UTF-8') > 250 ? mb_substr(strip_tags($data->review),0,250,'UTF-8').'...' : strip_tags($data->review);
	                                return $text;
	                            })
	                            ->addColumn('action', function(CatalogReview $data) {
	                                return '<div class="action-list"><a data-href="' . route('admin-rating-show',$data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>'.__('Details').'</a><a href="javascript:;" data-href="' . route('admin-rating-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
	                            })
	                            ->rawColumns(['product', 'vendor', 'rating', 'action'])
	                            ->toJson();
		}

		public function index(){
			return view('admin.catalog-review.index');
		}

	    //*** GET Request
	    public function show($id)
	    {
	        $data = CatalogReview::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand'])->findOrFail($id);
	        return view('admin.catalog-review.show',compact('data'));
	    }

	    //*** GET Request Delete
		public function destroy($id)
		{
		    $review = CatalogReview::findOrFail($id);
		    $review->delete();
		    //--- Redirect Section
		    $msg = __('Data Deleted Successfully.');
		    return response()->json($msg);
		    //--- Redirect Section Ends
		}
}
