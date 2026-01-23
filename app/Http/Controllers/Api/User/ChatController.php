<?php

namespace App\Http\Controllers\Api\User;

use App\Classes\MuaadhMailer;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatEntryResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\ChatThread;
use App\Models\ChatEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;

class ChatController extends Controller
{
    public function messages()
    {
        try {
            return response()->json(['status' => true, 'data' => ChatThreadResource::collection(ChatThread::where('sent_user', auth()->user()->id)->orWhere('recieved_user', auth()->user()->id)->with('chatEntries')->get()), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    //Send email to user
    public function usercontact(Request $request)
    {
        try {

            $rules =
                [
                'user_id' => 'required',
                'email' => 'required',
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
            $merchant = User::where('email', '=', $request->email)->first();
            if (!$merchant) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Email not found."]]);
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
                $message->recieved_user = $merchant->id;
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

    public function postmessage(Request $request)
    {

        try {
            $user = auth()->user();

            $rules =
                [
                'chat_thread_id' => 'required',
                'sent_user' => [
                    function ($attribute, $value, $fail) use ($request, $user) {
                        if ($request->sent_user == $user->id) {
                            if (empty($request->sent_user)) {
                                $fail('sent_user id is required.');
                            }
                        }
                    },
                ],
                'recieved_user' => [
                    function ($attribute, $value, $fail) use ($request, $user) {
                        if ($request->recieved_user == $user->id) {
                            if (empty($request->recieved_user)) {
                                $fail('recieved_user id is required.');
                            }
                        }
                    },
                ],
                'message' => 'required',

            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $msg = new ChatEntry();
            $input = $request->all();

            $mgs = $msg->fill($input)->save();
            //--- Redirect Section
            return response()->json(['status' => true, 'data' => new ChatEntryResource($msg), 'error' => []]);
            //--- Redirect Section Ends

        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }

    }

    public function messagedelete($id)
    {
        try {

            $conv = ChatThread::find($id);
            if (!$conv) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Conversation Not found."]]);
            }

            if ($conv->messages->count() > 0) {
                foreach ($conv->messages as $key) {
                    $key->delete();
                }
            }

            $conv->delete();
            return response()->json(['status' => true, 'data' => ['message' => 'Message Deleted Successfully!'], 'error' => []]);

        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }

    }
}
