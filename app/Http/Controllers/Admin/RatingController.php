<?php

namespace App\Http\Controllers\Admin;

use App\Models\Rating;

use Datatables;

class RatingController extends AdminBaseController
{
	    //*** JSON Request
	    public function datatables()
	    {
	         $datas = Rating::with(['product.brand', 'merchantProduct.user', 'merchantProduct.qualityBrand', 'user'])
	         	->latest('id')
	         	->get();

	         return Datatables::of($datas)
	                            ->addColumn('product', function(Rating $data) {
									$name = $data->product ? getLocalizedProductName($data->product, 50) : __('N/A');

									// الرابط للمنتج
									if ($data->merchantProduct && $data->merchantProduct->id && $data->product) {
										$prodLink = route('front.product', [
											'slug' => $data->product->slug,
											'vendor_id' => $data->merchantProduct->user_id,
											'merchant_product_id' => $data->merchantProduct->id
										]);
									} elseif ($data->product && $data->product->sku) {
										$prodLink = route('search.result', $data->product->sku);
									} else {
										$prodLink = '#';
									}

	                                $product = '<a href="'.$prodLink.'" target="_blank">'.$name.'</a>';
	                                return $product;
	                            })
								->addColumn('brand', function (Rating $data) {
									return $data->product && $data->product->brand ? getLocalizedBrandName($data->product->brand) : __('N/A');
								})
								->addColumn('quality_brand', function (Rating $data) {
									return $data->merchantProduct && $data->merchantProduct->qualityBrand
										? getLocalizedQualityName($data->merchantProduct->qualityBrand)
										: __('N/A');
								})
								->addColumn('vendor', function (Rating $data) {
									if ($data->merchantProduct && $data->merchantProduct->user) {
										$shopName = $data->merchantProduct->user->shop_name ?: $data->merchantProduct->user->name;
										return '<a href="' . route('admin-vendor-show', $data->merchantProduct->user_id) . '" target="_blank">' . $shopName . '</a>';
									}
									return __('N/A');
								})
	                            ->addColumn('reviewer', function(Rating $data) {
	                                return $data->user->name;
	                            })
	                            ->addColumn('rating', function(Rating $data) {
	                                return '<span class="badge badge-warning"><i class="fas fa-star"></i> ' . $data->rating . '</span>';
	                            })
	                            ->addColumn('review', function(Rating $data) {
	                                $text = mb_strlen(strip_tags($data->review),'UTF-8') > 250 ? mb_substr(strip_tags($data->review),0,250,'UTF-8').'...' : strip_tags($data->review);
	                                return $text;
	                            })
	                            ->addColumn('action', function(Rating $data) {
	                                return '<div class="action-list"><a data-href="' . route('admin-rating-show',$data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>'.__('Details').'</a><a href="javascript:;" data-href="' . route('admin-rating-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
	                            })
	                            ->rawColumns(['product', 'vendor', 'rating', 'action'])
	                            ->toJson();
		}

		public function index(){
			return view('admin.rating.index');
		}

	    //*** GET Request
	    public function show($id)
	    {
	        $data = Rating::with(['product.brand', 'merchantProduct.user', 'merchantProduct.qualityBrand'])->findOrFail($id);
	        return view('admin.rating.show',compact('data'));
	    }

	    //*** GET Request Delete
		public function destroy($id)
		{
		    $rating = Rating::findOrFail($id);
		    $rating->delete();
		    //--- Redirect Section
		    $msg = __('Data Deleted Successfully.');
		    return response()->json($msg);
		    //--- Redirect Section Ends
		}
}
