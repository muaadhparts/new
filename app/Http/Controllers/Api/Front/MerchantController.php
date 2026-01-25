<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\MuaadhMailer;
use App\Helpers\CatalogItemContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use App\Http\Resources\MerchantResource;
use App\Domain\Commerce\Models\ChatThread;
use App\Domain\Commerce\Models\ChatEntry;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;

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
            $isArabic = app()->getLocale() === 'ar';

            if ($sort === 'name_asc') {
                if ($isArabic) {
                    $query->orderByRaw("CASE WHEN catalog_items.label_ar IS NOT NULL AND catalog_items.label_ar != '' THEN 0 ELSE 1 END ASC")
                          ->orderByRaw("COALESCE(NULLIF(catalog_items.label_ar, ''), NULLIF(catalog_items.label_en, ''), catalog_items.name) ASC");
                } else {
                    $query->orderByRaw("CASE WHEN catalog_items.label_en IS NOT NULL AND catalog_items.label_en != '' THEN 0 ELSE 1 END ASC")
                          ->orderByRaw("COALESCE(NULLIF(catalog_items.label_en, ''), NULLIF(catalog_items.label_ar, ''), catalog_items.name) ASC");
                }
            } else {
                match ($sort) {
                    'price_desc' => $query->orderByRaw('(select min(mp.price) from merchant_items mp where mp.catalog_item_id = catalog_items.id and mp.user_id = ? and mp.status = 1) desc', [$merchant->id]),
                    'part_number' => $query->orderBy('catalog_items.part_number', 'asc'),
                    default => $query->orderByRaw('(select min(mp.price) from merchant_items mp where mp.catalog_item_id = catalog_items.id and mp.user_id = ? and mp.status = 1) asc', [$merchant->id]),
                };
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

            $ps = platformSettings();
            $subject = $request->subject;
            $to = $merchant->email;
            $name = $user->name;
            $from = $user->email;
            $msg = "Name: " . $name . "\nEmail: " . $from . "\nMessage: " . $request->message;
            if ($ps->get('mail_driver')) {
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            } else {
                $headers = "From: " . $ps->get('from_name') . "<" . $ps->get('from_email') . ">";
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
