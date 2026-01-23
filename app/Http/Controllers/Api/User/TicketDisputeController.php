<?php

namespace App\Http\Controllers\Api\User;

use App\Classes\MuaadhMailer;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketDisputeMessageResource;
use App\Http\Resources\TicketDisputeResource;
use App\Models\SupportThread;
use App\Models\SupportMessage;
use App\Models\CatalogEvent;
use App\Models\Purchase;
use App\Models\FrontendSetting;
use Illuminate\Http\Request;
use Validator;

class TicketDisputeController extends Controller
{
    public function tickets()
    {
        try {
            return response()->json(['status' => true, 'data' => TicketDisputeResource::collection(SupportThread::where('user_id', auth()->user()->id)->where('type', 'Ticket')->get()), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function disputes()
    {
        try {
            return response()->json(['status' => true, 'data' => TicketDisputeResource::collection(SupportThread::where('user_id', auth()->user()->id)->where('type', 'Dispute')->get()), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'subject' => 'required',
                'message' => 'required',
                'type' => 'required',
                'purchase_number' => [
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->type == 'Dispute') {
                            if (empty($request->purchase_number)) {
                                $fail('The purchase number field is required');
                            }
                        }
                    },
                ],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }


            if ($request->type ==  'Dispute') {
                $purchase = Purchase::where('purchase_number', $request->purchase_number)->exists();
                if (!$purchase) {
                    return response()->json(['status' => false, 'data' => [], 'error' => ["purchase_number" => ["Purchase Number Not Found"]]]);
                }
            }

            $type = $request->type;
            $checkType = in_array($type, ['Ticket', 'Dispute']);
            if (!$checkType) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "This type doesn't exists."]]);
            }

            $user = auth()->user();
            $ps = platformSettings();
            $subject = $request->subject;
            $to = FrontendSetting::find(1)->contact_email;
            $from = $user->email;
            $msg = "Email: " . $from . "\nMessage: " . $request->message;
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

            if ($request->type == 'Ticket') {
                $thread = SupportThread::where('type', '=', 'Ticket')->where('user_id', '=', $user->id)->where('subject', '=', $subject)->first();
            } else {
                $thread = SupportThread::where('type', '=', 'Dispute')->where('user_id', '=', $user->id)->where('subject', '=', $subject)->first();
            }

            if (isset($thread)) {

                $msg = new SupportMessage();
                $msg->thread_id = $thread->id;
                $msg->message = $request->message;
                $msg->user_id = $user->id;
                $msg->save();
                return response()->json(['status' => true, 'data' => new TicketDisputeMessageResource($msg), 'error' => []]);
            } else {
                $thread = new SupportThread();
                $thread->subject = $subject;
                $thread->user_id = $user->id;
                $thread->message = $request->message;
                $thread->purchase_number = $request->purchase_number;
                $thread->type = $request->type;
                $thread->save();

                $notification = new CatalogEvent;
                $notification->chat_thread_id = $thread->id;
                $notification->save();

                $msg = new SupportMessage();
                $msg->thread_id = $thread->id;
                $msg->message = $request->message;
                $msg->user_id = $user->id;
                $msg->save();
                return response()->json(['status' => true, 'data' => new TicketDisputeResource($thread), 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function delete($id)
    {
        try {
            $thread = SupportThread::find($id);

            if (!$thread) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Not found."]]);
            }

            if ($thread->messages->count() > 0) {
                foreach ($thread->messages as $key) {
                    $key->delete();
                }
            }
            $thread->delete();
            return response()->json(['status' => true, 'data' => ['message' => 'Message Deleted Successfully!'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function messageStore(Request $request)
    {
        try {
            $rules =
                [
                    'user_id' => 'required',
                    'message' => 'required',
                    'thread_id' => 'required',
                ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $msg = new SupportMessage();
            $input = $request->all();
            $input['user_id'] = auth()->user()->id;
            $msg->fill($input)->save();
            $notification = new CatalogEvent;
            $notification->chat_thread_id = $msg->thread->id;
            $notification->save();
            //--- Redirect Section

            return response()->json(['status' => true, 'data' => new TicketDisputeMessageResource($msg), 'error' => []]);
            //--- Redirect Section Ends
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
