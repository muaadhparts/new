<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuyerNoteResource;
use App\Http\Resources\CatalogReviewResource;
use App\Http\Resources\NoteResponseResource;

use App\Http\Resources\AbuseFlagResource;
use App\Domain\Catalog\Models\BuyerNote;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\NoteResponse;
use App\Domain\Catalog\Models\AbuseFlag;
use Illuminate\Http\Request;
use Validator;

class CatalogItemController extends Controller
{
    public function reviewsubmit(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required',
                'catalog_item_id' => 'required',
                'rating' => 'required',
                'comment' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $ck = 0;
            $purchases = Purchase::where('user_id', '=', $request->user_id)->where('status', '=', 'completed')->get();

            foreach ($purchases as $purchase) {
                $cart = $purchase->cart; // Model cast handles decoding
                foreach ($cart['items'] as $item) {
                    if ($request->catalog_item_id == $item['item']['id']) {
                        $ck = 1;
                        break;
                    }
                }
            }
            if ($ck == 1) {
                $user = auth()->user();
                $prev = CatalogTestimonial::where('catalog_item_id', '=', $request->catalog_item_id)->where('user_id', '=', $user->id)->get();
                if (count($prev) > 0) {
                    return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'You Have Reviewed Already.']]);
                }
                $review = new CatalogReview;
                $in = $request->all();
                $in['review'] = $request->comment;
                $review->fill($in);
                $review['review_date'] = date('Y-m-d H:i:s');
                $review->save();
                return response()->json(['status' => true, 'data' => new CatalogReviewResource($review), 'error' => []]);
            } else {
                return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'Buy This CatalogItem First']]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function buyerNoteStore(Request $request)
    {
        try {
            $rules = [
                'catalog_item_id' => 'required',
                'comment' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $buyerNote = new BuyerNote;
            $buyerNote->user_id = auth()->user()->id;
            $buyerNote->catalog_item_id = $request->catalog_item_id;
            $buyerNote->text = $request->comment;
            $buyerNote->save();

            return response()->json(['status' => true, 'data' => new BuyerNoteResource($buyerNote), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function buyerNoteUpdate(Request $request)
    {
        try {
            $rules = [
                'id' => 'required',
                'comment' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $buyerNote = BuyerNote::find($request->id);
            $buyerNote->text = $request->comment;
            $buyerNote->save();

            return response()->json(['status' => true, 'data' => new BuyerNoteResource($buyerNote), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function buyerNoteDelete($id)
    {
        try {
            $buyerNote = BuyerNote::find($id);
            if ($buyerNote->noteResponses->count() > 0) {
                foreach ($buyerNote->noteResponses as $noteResponse) {
                    $noteResponse->delete();
                }
            }
            $buyerNote->delete();

            return response()->json(['status' => true, 'data' => ['message' => 'Buyer Note Deleted Successfully!'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function replystore(Request $request)
    {
        try {
            $rules = [
                'buyer_note_id' => 'required',
                'reply' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $noteResponse = new NoteResponse;
            $noteResponse->user_id = auth()->user()->id;
            $noteResponse->buyer_note_id = $request->buyer_note_id;
            $noteResponse->text = $request->reply;
            $noteResponse->save();

            return response()->json(['status' => true, 'data' => new NoteResponseResource($noteResponse), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function replyupdate(Request $request)
    {
        try {
            $rules = [
                'id' => 'required',
                'reply' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $noteResponse = NoteResponse::find($request->id);
            $noteResponse->text = $request->reply;
            $noteResponse->save();

            return response()->json(['status' => true, 'data' => new NoteResponseResource($noteResponse), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function replydelete($id)
    {
        try {
            $noteResponse = NoteResponse::find($id);
            $noteResponse->delete();

            return response()->json(['status' => true, 'data' => ['message' => 'Reply Deleted Successfully!'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function reportstore(Request $request)
    {
        try {

            //--- Validation Section
            $rules = [
                'catalog_item_id' => 'required',
                'name' => 'required',
                'note' => 'required|max:400',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }
            //--- Validation Section Ends
            //--- Logic Section
            $abuseFlag = new AbuseFlag;
            $abuseFlag->user_id = auth()->user()->id;
            $abuseFlag->catalog_item_id = $request->catalog_item_id;
            $abuseFlag->name = $request->name;
            $abuseFlag->note = $request->note;
            $abuseFlag->save();
            //--- Logic Section Ends

            //--- Redirect Section
            return response()->json(['status' => true, 'data' => new AbuseFlagResource($abuseFlag), 'error' => []]);
            //--- Redirect Section Ends
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
