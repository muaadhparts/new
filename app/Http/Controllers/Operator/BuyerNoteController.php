<?php

namespace App\Http\Controllers\Operator;

use App\Models\BuyerNote;
use Datatables;

class BuyerNoteController extends OperatorBaseController
{

	//*** JSON Request
	// Note: brand_id moved from catalog_items to merchant_items (2026-01-20)
	public function datatables()
	{
		$datas = BuyerNote::with(['catalogItem.fitments.brand', 'merchantItem.user', 'merchantItem.qualityBrand', 'user'])
			->latest('id')
			->get();

		return Datatables::of($datas)
			->addColumn('catalogItem', function (BuyerNote $data) {
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
			->addColumn('brand', function (BuyerNote $data) {
				// Brand from catalog item fitments (OEM brand)
				$fitments = $data->catalogItem?->fitments ?? collect();
				$brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
				$firstBrand = $brands->first();
				return $firstBrand ? getLocalizedBrandName($firstBrand) : __('N/A');
			})
			->addColumn('quality_brand', function (BuyerNote $data) {
				return $data->merchantItem && $data->merchantItem->qualityBrand
					? getLocalizedQualityName($data->merchantItem->qualityBrand)
					: __('N/A');
			})
			->addColumn('merchant', function (BuyerNote $data) {
				// Display merchant info
				if ($data->merchantItem && $data->merchantItem->user) {
					$shopName = $data->merchantItem->user->shop_name ?: $data->merchantItem->user->name;
					return '<a href="' . route('operator-merchant-show', $data->merchantItem->user_id) . '" target="_blank">' . $shopName . '</a>';
				}
				return __('N/A');
			})
			->addColumn('buyer', function (BuyerNote $data) {
				return $data->user->name;
			})
			->addColumn('text', function (BuyerNote $data) {
				$text = mb_strlen(strip_tags($data->text), 'utf-8') > 250 ? mb_substr(strip_tags($data->text), 0, 250, 'utf-8') . '...' : strip_tags($data->text);
				return $text;
			})
			->addColumn('action', function (BuyerNote $data) {
				return '<div class="action-list"><a data-href="' . route('operator-buyer-note-show', $data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>' . __('Details') . '</a><a href="javascript:;" data-href="' . route('operator-buyer-note-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
			})
			->rawColumns(['catalogItem', 'merchant', 'action'])
			->toJson();
	}

	public function index()
	{
		return view('operator.buyer-note.index');
	}

	//*** GET Request
	public function show($id)
	{
		// Brand from catalog item fitments
		$data = BuyerNote::with(['catalogItem.fitments.brand', 'merchantItem.user', 'merchantItem.qualityBrand'])->findOrFail($id);
		return view('operator.buyer-note.show', compact('data'));
	}

	//*** GET Request Delete
	public function destroy($id)
	{
		$buyerNote = BuyerNote::findOrFail($id);
		if ($buyerNote->noteResponses->count() > 0) {
			foreach ($buyerNote->noteResponses as $response) {
				$response->delete();
			}
		}
		$buyerNote->delete();
		//--- Redirect Section
		$msg = __('Data Deleted Successfully.');
		return response()->json($msg);
		//--- Redirect Section Ends
	}
}
