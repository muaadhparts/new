<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;

use App\Http\Resources\CatalogItemDetailsResource;
use App\Http\Resources\CatalogReviewResource;
use App\Http\Resources\ReplyResource;
use App\Models\Comment;
use App\Models\CatalogItem;

class CatalogItemController extends Controller
{
    public function catalogItemDetails($id)
    {
        try {
            $catalogItem = CatalogItem::find($id);
            if (!$catalogItem) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Item not found."]]);
            }
            return response()->json(['status' => true, 'data' => new CatalogItemDetailsResource($catalogItem), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function catalogReviews($id)
    {
        try {
            $catalogItem = CatalogItem::find($id);

            if (!$catalogItem) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Item not found."]]);
            }
            $catalogReviews = $catalogItem->catalogReviews;

            return response()->json(['status' => true, 'data' => CatalogReviewResource::collection($catalogReviews), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function comments($id)
    {
        try {
            $catalogItem = CatalogItem::find($id);
            if (!$catalogItem) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Item not found."]]);
            }
            $comments = $catalogItem->comments()->orderBy('id', 'DESC')->get();
            return response()->json(['status' => true, 'data' => CommentResource::collection($comments), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function replies($id)
    {
        try {
            $comment = Comment::find($id);
            if (!$comment) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Comment not found."]]);
            }
            $replies = $comment->replies()->orderBy('id', 'DESC')->get();
            return response()->json(['status' => true, 'data' => ReplyResource::collection($replies), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
