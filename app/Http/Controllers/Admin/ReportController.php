<?php

namespace App\Http\Controllers\Admin;

use App\Models\Report;
use Datatables;

class ReportController extends AdminBaseController
{

	//*** JSON Request
	public function datatables()
	{
		$datas = Report::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand', 'user'])
			->latest('id')
			->get();

		return Datatables::of($datas)
			->addColumn('catalogItem', function (Report $data) {
				$name = $data->catalogItem ? getLocalizedCatalogItemName($data->catalogItem, 50) : __('N/A');

				// Build link to catalog item
				if ($data->merchantItem && $data->merchantItem->id && $data->catalogItem) {
					$itemLink = route('front.catalog-item', [
						'slug' => $data->catalogItem->slug,
						'merchant_id' => $data->merchantItem->user_id,
						'merchant_item_id' => $data->merchantItem->id
					]);
				} elseif ($data->catalogItem && $data->catalogItem->sku) {
					$itemLink = route('search.result', $data->catalogItem->sku);
				} else {
					$itemLink = '#';
				}

				$item = '<a href="' . $itemLink . '" target="_blank">' . $name . '</a>';
				return $item;
			})
			->addColumn('brand', function (Report $data) {
				return $data->catalogItem && $data->catalogItem->brand ? getLocalizedBrandName($data->catalogItem->brand) : __('N/A');
			})
			->addColumn('quality_brand', function (Report $data) {
				return $data->merchantItem && $data->merchantItem->qualityBrand
					? getLocalizedQualityName($data->merchantItem->qualityBrand)
					: __('N/A');
			})
			->addColumn('merchant', function (Report $data) {
				// Display merchant info
				if ($data->merchantItem && $data->merchantItem->user) {
					$shopName = $data->merchantItem->user->shop_name ?: $data->merchantItem->user->name;
					return '<a href="' . route('admin-merchant-show', $data->merchantItem->user_id) . '" target="_blank">' . $shopName . '</a>';
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
				return '<div class="action-list"><a data-href="' . route('admin-report-show', $data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>' . __('Details') . '</a><a href="javascript:;" data-href="' . route('admin-report-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
			})
			->rawColumns(['catalogItem', 'merchant', 'action'])
			->toJson();
	}
		
		public function index(){
			return view('admin.report.index');
		}

	//*** GET Request
	public function show($id)
	{
		$data = Report::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand'])->findOrFail($id);
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