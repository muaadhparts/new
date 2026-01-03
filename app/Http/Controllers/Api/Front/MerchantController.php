<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\MuaadhMailer;
use App\Helpers\CatalogItemContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\MerchantResource;
use App\Models\Conversation;
use App\Models\Muaadhsetting;
use App\Models\Message;
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
            $services = DB::table('services')->where('user_id', '=', $merchant->id)->get();
            $data['services'] = ServiceResource::collection($services);

            // Get catalog items through merchant_items for this merchant
            $merchantItemsQuery = \App\Models\MerchantItem::where('user_id', $merchant->id)
                ->where('status', 1)
                ->with(['catalogItem' => function($query) {
                    $query->where('status', 1);
                }]);

            // Apply price filtering on merchant_items.price
            $merchantItemsQuery->when($minprice, function ($query, $minprice) {
                return $query->where('price', '>=', $minprice);
            })
            ->when($maxprice, function ($query, $maxprice) {
                return $query->where('price', '<=', $maxprice);
            });

            // Apply sorting
            $merchantItemsQuery->when($sort, function ($query, $sort) {
                if ($sort == 'date_desc') {
                    return $query->orderBy('id', 'DESC');
                } elseif ($sort == 'date_asc') {
                    return $query->orderBy('id', 'ASC');
                } elseif ($sort == 'price_desc') {
                    return $query->orderBy('price', 'DESC');
                } elseif ($sort == 'price_asc') {
                    return $query->orderBy('price', 'ASC');
                }
            })
            ->when(empty($sort), function ($query, $sort) {
                return $query->orderBy('id', 'DESC');
            });

            $merchantItems = $merchantItemsQuery->get();

            // Extract catalog items and inject merchant context using CatalogItemContextHelper
            $prods = $merchantItems->map(function($mp) use ($merchant) {
                if (!$mp->catalogItem) return null;

                $catalogItem = $mp->catalogItem;
                // Use CatalogItemContextHelper for consistency
                CatalogItemContextHelper::apply($catalogItem, $mp);
                return $catalogItem;
            })->filter()->values();

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

            $conv = Conversation::where('sent_user', '=', $user->id)->where('subject', '=', $subject)->first();
            if (isset($conv)) {
                $msg = new Message();
                $msg->conversation_id = $conv->id;
                $msg->message = $request->message;
                $msg->sent_user = $user->id;
                $msg->save();
            } else {
                $message = new Conversation();
                $message->subject = $subject;
                $message->sent_user = $request->user_id;
                $message->recieved_user = $request->merchant_id;
                $message->message = $request->message;
                $message->save();
                $msg = new Message();
                $msg->conversation_id = $message->id;
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
