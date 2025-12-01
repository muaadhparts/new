<?php

namespace App\Http\Controllers\Admin;

use App\Models\Comment;
use Datatables;

class CommentController extends AdminBaseController
{

	//*** JSON Request
	public function datatables()
	{
		$datas = Comment::with(['product.brand', 'merchantProduct.user', 'merchantProduct.qualityBrand', 'user'])
			->latest('id')
			->get();

		return Datatables::of($datas)
			->addColumn('product', function (Comment $data) {
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
			->addColumn('brand', function (Comment $data) {
				return $data->product && $data->product->brand ? getLocalizedBrandName($data->product->brand) : __('N/A');
			})
			->addColumn('quality_brand', function (Comment $data) {
				return $data->merchantProduct && $data->merchantProduct->qualityBrand
					? getLocalizedQualityName($data->merchantProduct->qualityBrand)
					: __('N/A');
			})
			->addColumn('vendor', function (Comment $data) {
				if ($data->merchantProduct && $data->merchantProduct->user) {
					$shopName = $data->merchantProduct->user->shop_name ?: $data->merchantProduct->user->name;
					return '<a href="' . route('admin-vendor-show', $data->merchantProduct->user_id) . '" target="_blank">' . $shopName . '</a>';
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
				return '<div class="action-list"><a data-href="' . route('admin-comment-show', $data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i>' . __('Details') . '</a><a href="javascript:;" data-href="' . route('admin-comment-delete', $data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
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
		$data = Comment::with(['product.brand', 'merchantProduct.user', 'merchantProduct.qualityBrand'])->findOrFail($id);
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
