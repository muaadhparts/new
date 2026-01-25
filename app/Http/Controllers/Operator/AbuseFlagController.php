<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Catalog\Models\AbuseFlag;
use Datatables;

class AbuseFlagController extends OperatorBaseController
{

	//*** JSON Request
	// Note: brand_id moved from catalog_items to merchant_items (2026-01-20)
	public function datatables()
	{
		$datas = AbuseFlag::with(['catalogItem.fitments.brand', 'merchantItem.user', 'merchantItem.qualityBrand', 'merchantItem.merchantBranch', 'user'])
			->latest('id')
			->get();

		return Datatables::of($datas)
			->addColumn('catalogItem', function (AbuseFlag $data) {
				$name = $data->catalogItem ? getLocalizedCatalogItemName($data->catalogItem, 50) : __('N/A');

				// Build link to catalog item
				if ($data->catalogItem && $data->catalogItem->part_number) {
					$itemLink = route('front.part-result', $data->catalogItem->part_number);
				} else {
					$itemLink = '#';
				}

				$item = '<a href="' . $itemLink . '" target="_blank">' . $name . '</a>';
				return $item;
			})
			->addColumn('brand', function (AbuseFlag $data) {
				// Brand from catalog item fitments (OEM brand)
				$fitments = $data->catalogItem?->fitments ?? collect();
				$brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
				$firstBrand = $brands->first();
				return $firstBrand ? getLocalizedBrandName($firstBrand) : __('N/A');
			})
			->addColumn('quality_brand', function (AbuseFlag $data) {
				return $data->merchantItem && $data->merchantItem->qualityBrand
					? getLocalizedQualityName($data->merchantItem->qualityBrand)
					: __('N/A');
			})
			->addColumn('merchant', function (AbuseFlag $data) {
				// Display merchant info
				if ($data->merchantItem && $data->merchantItem->user) {
					$shopName = $data->merchantItem->user->shop_name ?: $data->merchantItem->user->name;
					return '<a href="' . route('operator-merchant-show', $data->merchantItem->user_id) . '" target="_blank">' . $shopName . '</a>';
				}
				return __('N/A');
			})
			->addColumn('reporter', function (AbuseFlag $data) {
				return $data->user->name;
			})
			->addColumn('name', function (AbuseFlag $data) {
				$text = mb_strlen(strip_tags($data->name), 'UTF-8') > 250 ? mb_substr(strip_tags($data->name), 0, 250, 'UTF-8') . '...' : strip_tags($data->name);
				return $text;
			})
			->editColumn('created_at', function (AbuseFlag $data) {
				return $data->created_at->diffForHumans();
			})
			->addColumn('action', function (AbuseFlag $data) {
				return '<div class="action-list"><a data-href="' . route('operator-abuse-flag-show', $data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>' . __('Details') . '</a><a href="javascript:;" data-href="' . route('operator-abuse-flag-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
			})
			->rawColumns(['catalogItem', 'merchant', 'action'])
			->toJson();
	}

		public function index(){
			return view('operator.abuse-flag.index');
		}

	//*** GET Request
	public function show($id)
	{
		// Brand from catalog item fitments
		$data = AbuseFlag::with(['catalogItem.fitments.brand', 'merchantItem.user', 'merchantItem.qualityBrand', 'merchantItem.merchantBranch'])->findOrFail($id);
		return view('operator.abuse-flag.show', compact('data'));
	}

	    //*** GET Request Delete
		public function destroy($id)
		{
		    $data = AbuseFlag::findOrFail($id);
		    $data->delete();
		    //--- Redirect Section
		    $msg = __('Data Deleted Successfully.');
		    return response()->json($msg);
		    //--- Redirect Section Ends
		}
}
