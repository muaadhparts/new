<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\Commerce\Models\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\PurchaseDetailsResource;

use Illuminate\Http\Request;
use Validator;

class PurchaseController extends Controller
{
  public function purchases(Request $request)
  {
    try {

      if ($request->view) {
        $paginate = $request->view;
      } else {
        $paginate = 12;
      }

      $purchases = Purchase::where('user_id', '=', auth()->user()->id)->orderBy('id', 'desc')->paginate($paginate);
      return response()->json(['status' => true, 'data' => PurchaseResource::collection($purchases), 'error' => []]);
    } catch (\Exception $e) {
      return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
    }
  }


  public function purchase($id)
  {
    try {
      $purchase = Purchase::findOrfail($id);
      return response()->json(['status' => true, 'data' => new PurchaseDetailsResource($purchase), 'error' => []]);
    } catch (\Exception $e) {
      return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
    }
  }

  public function updateTransaction(Request $request)
  {
    try {
      $rules = [
        'purchase_id' => 'required',
        'transaction_id' => 'required'
      ];

      $validator = Validator::make($request->all(), $rules);
      if ($validator->fails()) {
        return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
      }

      $purchase = Purchase::find($request->purchase_id);
      $purchase->txnid = $request->transaction_id;
      $purchase->save();

      return response()->json(['status' => true, 'data' => new PurchaseDetailsResource($purchase), 'error' => []]);
    } catch (\Exception $e) {
      return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
    }
  }
}
