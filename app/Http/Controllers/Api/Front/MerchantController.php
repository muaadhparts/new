<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\MuaadhMailer;
use App\Helpers\CatalogItemContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use App\Http\Resources\MerchantResource;
use App\Models\ChatThread;
use App\Models\Muaadhsetting;
use App\Models\ChatEntry;
use App\Models\CatalogItem;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Validator;

class MerchantController extends Controller
{
    public function index(Request $request, $shop_name)
    {
        try {
            $minprice = $request->min;
            $maxprice = $request->max;
            $sort = $request->sort;
            $string = str_replace('-', ' ', $shop_name);
            $merchant = User::where('shop_name', '=', $string)->first();
            if (!$merchant) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Shop name not found."]]);
            }
            $data['merchant'] = new MerchantResource($merchant);

            // CatalogItem-first: Query catalog items that have merchant offers from this merchant
            $query = CatalogItem::whereHas('merchantItems', function($q) use ($merchant) {
                    $q->where('user_id', $merchant->id)->where('status', 1);
                })
                ->with(['merchantItems' => function($q) use ($merchant, $minprice, $maxprice) {
                    $q->where('user_id', $merchant->id)->where('status', 1);
                    if ($minprice) $q->where('price', '>=', $minprice);
                    if ($maxprice) $q->where('price', '<=', $maxprice);
                }]);

            // Apply price filtering via whereHas
            if ($minprice || $maxprice) {
                $query->whereHas('merchantItems', function($q) use ($merchant, $minprice, $maxprice) {
                    $q->where('user_id', $merchant->id)->where('status', 1);
                    if ($minprice) $q->where('price', '>=', $minprice);
                    if ($maxprice) $q->where('price', '<=', $maxprice);
                });
            }

            // Apply sorting
            if ($sort == 'date_desc') {
                $query->orderBy('id', 'DESC');
            } elseif ($sort == 'date_asc') {
                $query->orderBy('id', 'ASC');
            } else {
                $query->orderBy('id', 'DESC');
            }

            $prods = $query->get();

            // Inject merchant context for each catalog item
            $prods->each(function($catalogItem) use ($merchant) {
                $mp = $catalogItem->merchantItems->first();
                if ($mp) {
                    CatalogItemContextHelper::apply($catalogItem, $mp);
                }
            });

            $vprods = (new Collection(CatalogItem::filterProducts($prods)));
            $data['catalogItems'] = CatalogItemListResource::collection($vprods);

            return response()->json(['status' => true, 'data' => $data, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    //Send email to user
    public function merchantcontact(Request $request)
    {

        try {

            $rules =
                [
                'user_id' => 'required',
                'merchant_id' => 'required',
                'subject' => 'required',
                'message' => 'required',

            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "User not found."]]);
            }
            $merchant = User::find($request->merchant_id);
            if (!$merchant) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Merchant not found."]]);
            }

            $gs = Muaadhsetting::find(1);
            $subject = $request->subject;
            $to = $merchant->email;
            $name = $user->name;
            $from = $user->email;
            $msg = "Name: " . $name . "\nEmail: " . $from . "\nMessage: " . $request->message;
            if ($gs->is_smtp) {
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            } else {
                $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
                mail($to, $subject, $msg, $headers);
            }

            $conv = ChatThread::where('sent_user', '=', $user->id)->where('subject', '=', $subject)->first();
            if (isset($conv)) {
                $msg = new ChatEntry();
                $msg->chat_thread_id = $conv->id;
                $msg->message = $request->message;
                $msg->sent_user = $user->id;
                $msg->save();
            } else {
                $message = new ChatThread();
                $message->subject = $subject;
                $message->sent_user = $request->user_id;
                $message->recieved_user = $request->merchant_id;
                $message->message = $request->message;
                $message->save();
                $msg = new ChatEntry();
                $msg->chat_thread_id = $message->id;
                $msg->message = $request->message;
                $msg->sent_user = $request->user_id;
                $msg->save();

            }

            return response()->json(['status' => true, 'data' => ["message" => "Message Sent Successfully!"], 'error' => []]);

        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }

    }
}
