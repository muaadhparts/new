<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuyerNoteResource;

use App\Http\Resources\CatalogItemDetailsResource;
use App\Http\Resources\CatalogReviewResource;
use App\Http\Resources\NoteResponseResource;
use App\Domain\Catalog\Models\BuyerNote;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Events\ProductViewedEvent;
use Illuminate\Support\Facades\Auth;

class CatalogItemController extends Controller
{
    public function catalogItemDetails($id)
    {
        try {
            $catalogItem = CatalogItem::with([
                'merchantPhotos',
                'catalogReviews',
                'buyerNotes',
                'merchantItems' => fn($q) => $q->where('status', 1)->with(['user' => fn($u) => $u->withCount('merchantItems')]),
            ])
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating')
            ->find($id);

            if (!$catalogItem) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Item not found."]]);
            }

            // ═══════════════════════════════════════════════════════════════════
            // EVENT-DRIVEN: Dispatch ProductViewedEvent
            // ═══════════════════════════════════════════════════════════════════
            event(new ProductViewedEvent(
                catalogItemId: $catalogItem->id,
                customerId: Auth::id(),
                sessionId: request()->header('X-Session-Id'),
                source: 'api'
            ));

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

    public function buyerNotes($id)
    {
        try {
            $catalogItem = CatalogItem::find($id);
            if (!$catalogItem) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Item not found."]]);
            }
            $buyerNotes = $catalogItem->buyerNotes()->orderBy('id', 'DESC')->get();
            return response()->json(['status' => true, 'data' => BuyerNoteResource::collection($buyerNotes), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function replies($id)
    {
        try {
            $buyerNote = BuyerNote::find($id);
            if (!$buyerNote) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Buyer Note not found."]]);
            }
            $replies = $buyerNote->noteResponses()->orderBy('id', 'DESC')->get();
            return response()->json(['status' => true, 'data' => NoteResponseResource::collection($replies), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
