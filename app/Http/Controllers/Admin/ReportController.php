<?php

namespace App\Http\Controllers\Admin;

use App\Models\Report;
use Datatables;

class ReportController extends AdminBaseController
{

	//*** JSON Request
	public function datatables()
	{
		$datas = Report::with(['product.brand', 'merchantProduct.user', 'merchantProduct.qualityBrand', 'user'])
			->latest('id')
			->get();

		return Datatables::of($datas)
			->addColumn('product', function (Report $data) {
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

				$product = '<a href="' . $prodLink . '" target="_blank">' . $name . '</a>';
				return $product;
			})
			->addColumn('brand', function (Report $data) {
				return $data->product && $data->product->brand ? getLocalizedBrandName($data->product->brand) : __('N/A');
			})
			->addColumn('quality_brand', function (Report $data) {
				return $data->merchantProduct && $data->merchantProduct->qualityBrand
					? getLocalizedQualityName($data->merchantProduct->qualityBrand)
					: __('N/A');
			})
			->addColumn('manufacturer', function (Report $data) {
				return $data->merchantProduct && $data->merchantProduct->qualityBrand && $data->merchantProduct->qualityBrand->manufacturer
					? $data->merchantProduct->qualityBrand->manufacturer
					: __('N/A');
			})
			->addColumn('vendor', function (Report $data) {
				if ($data->merchantProduct && $data->merchantProduct->user) {
					$shopName = $data->merchantProduct->user->shop_name ?: $data->merchantProduct->user->name;
					return '<a href="' . route('admin-vendor-show', $data->merchantProduct->user_id) . '" target="_blank">' . $shopName . '</a>';
				}
				return __('N/A');
			})
			->addColumn('reporter', function (Report $data) {
				return $data->user->name;
			})
			->addColumn('title', function (Report $data) {
				$text = mb_strlen(strip_tags($data->title), 'UTF-8') > 250 ? mb_substr(strip_tags($data->title), 0, 250, 'UTF-8') . '...' : strip_tags($data->title);
				return $text;
			})
			->editColumn('created_at', function (Report $data) {
				return $data->created_at->diffForHumans();
			})
			->addColumn('action', function (Report $data) {
				return '<div class="action-list"><a data-href="' . route('admin-report-show', $data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i>' . __('Details') . '</a><a href="javascript:;" data-href="' . route('admin-report-delete', $data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
			})
			->rawColumns(['product', 'vendor', 'action'])
			->toJson();
	}
		
		public function index(){
			return view('admin.report.index');
		}

	//*** GET Request
	public function show($id)
	{
		$data = Report::with(['product.brand', 'merchantProduct.user', 'merchantProduct.qualityBrand'])->findOrFail($id);
		return view('admin.report.show', compact('data'));
	}

	    //*** GET Request Delete
		public function destroy($id)
		{
		    $data = Report::findOrFail($id);
		    $data->delete();
		    //--- Redirect Section     
		    $msg = __('Data Deleted Successfully.');
		    return response()->json($msg);      
		    //--- Redirect Section Ends    
		}
}