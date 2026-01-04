<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuyerNoteResource;

use App\Http\Resources\CatalogItemDetailsResource;
use App\Http\Resources\CatalogReviewResource;
use App\Http\Resources\NoteResponseResource;
use App\Models\BuyerNote;
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
            $comments = $catalogItem->buyerNotes()->orderBy('id', 'DESC')->get();
            return response()->json(['status' => true, 'data' => BuyerNoteResource::collection($comments), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function replies($id)
    {
        try {
            $buyerNote = BuyerNote::find($id);
            if (!$buyerNote) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Comment not found."]]);
            }
            $replies = $buyerNote->noteResponses()->orderBy('id', 'DESC')->get();
            return response()->json(['status' => true, 'data' => NoteResponseResource::collection($replies), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
