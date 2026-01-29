<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\MuaadhMailer;
use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Domain\Commerce\Models\ChatThread;
use App\Domain\Commerce\Models\ChatEntry;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Services\MerchantCatalogService;

use Illuminate\Http\Request;
use Validator;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantCatalogService $merchantCatalogService
    ) {}

    public function index(Request $request, $shop_name)
    {
        try {
            // Find merchant by slug
            $merchant = $this->merchantCatalogService->findMerchantBySlug($shop_name);

            if (!$merchant) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Shop name not found."]]);
            }

            $data['merchant'] = new MerchantResource($merchant);

            // Get filtered catalog items using service
            $filters = [
                'min' => $request->min,
                'max' => $request->max,
                'sort' => $request->sort,
            ];

            // Get DTOs from service (Clean Architecture)
            $catalogItemCards = $this->merchantCatalogService->getFilteredCatalogItemsForApi($merchant->id, $filters);
            $data['catalogItems'] = $catalogItemCards->toArray();

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
