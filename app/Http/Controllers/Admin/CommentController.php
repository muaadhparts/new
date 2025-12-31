<?php

namespace App\Http\Controllers\Admin;

use App\Models\Comment;
use Datatables;

class CommentController extends AdminBaseController
{

	//*** JSON Request
	public function datatables()
	{
		$datas = Comment::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand', 'user'])
			->latest('id')
			->get();

		return Datatables::of($datas)
			->addColumn('product', function (Comment $data) {
				$name = $data->catalogItem ? getLocalizedProductName($data->catalogItem, 50) : __('N/A');

				// الرابط للمنتج
				if ($data->merchantItem && $data->merchantItem->id && $data->catalogItem) {
					$prodLink = route('front.catalog-item', [
						'slug' => $data->catalogItem->slug,
						'vendor_id' => $data->merchantItem->user_id,
						'merchant_item_id' => $data->merchantItem->id
					]);
				} elseif ($data->catalogItem && $data->catalogItem->sku) {
					$prodLink = route('search.result', $data->catalogItem->sku);
				} else {
					$prodLink = '#';
				}

				$product = '<a href="' . $prodLink . '" target="_blank">' . $name . '</a>';
				return $product;
			})
			->addColumn('brand', function (Comment $data) {
				return $data->catalogItem && $data->catalogItem->brand ? getLocalizedBrandName($data->catalogItem->brand) : __('N/A');
			})
			->addColumn('quality_brand', function (Comment $data) {
				return $data->merchantItem && $data->merchantItem->qualityBrand
					? getLocalizedQualityName($data->merchantItem->qualityBrand)
					: __('N/A');
			})
			->addColumn('vendor', function (Comment $data) {
				if ($data->merchantItem && $data->merchantItem->user) {
					$shopName = $data->merchantItem->user->shop_name ?: $data->merchantItem->user->name;
					return '<a href="' . route('admin-vendor-show', $data->merchantItem->user_id) . '" target="_blank">' . $shopName . '</a>';
				}
				return __('N/A');
			})
			->addColumn('commenter', function (Comment $data) {
				return $data->user->name;
			})
			->addColumn('text', function (Comment $data) {
				$text = mb_strlen(strip_tags($data->text), 'utf-8') > 250 ? mb_substr(strip_tags($data->text), 0, 250, 'utf-8') . '...' : strip_tags($data->text);
				return $text;
			})
			->addColumn('action', function (Comment $data) {
				return '<div class="action-list"><a data-href="' . route('admin-comment-show', $data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>' . __('Details') . '</a><a href="javascript:;" data-href="' . route('admin-comment-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
			})
			->rawColumns(['product', 'vendor', 'action'])
			->toJson();
	}

	public function index()
	{
		return view('admin.comment.index');
	}

	//*** GET Request
	public function show($id)
	{
		$data = Comment::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand'])->findOrFail($id);
		return view('admin.comment.show', compact('data'));
	}

	//*** GET Request Delete
	public function destroy($id)
	{
		$comment = Comment::findOrFail($id);
		if ($comment->replies->count() > 0) {
			foreach ($comment->replies as $reply) {
				$reply->delete();
			}
		}
		$comment->delete();
		//--- Redirect Section
		$msg = __('Data Deleted Successfully.');
		return response()->json($msg);
		//--- Redirect Section Ends
	}
}
