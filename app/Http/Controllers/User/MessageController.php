<?php

namespace App\Http\Controllers\User;

use App\Classes\MuaadhMailer;
use App\Models\SupportThread;
use App\Models\SupportMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends UserBaseController
{

    public function messages()
    {
        $user = $this->user;
        $convs = Conversation::where('sent_user', '=', $user->id)->orWhere('recieved_user', '=', $user->id)->paginate(12);
        return view('user.message.index', compact('user', 'convs'));
    }

    public function message($id)
    {
        $user = $this->user;
        $conv = Conversation::findOrfail($id);
        return view('user.message.create', compact('user', 'conv'));
    }

    public function messagedelete($id)
    {
        $conv = Conversation::findOrfail($id);
        if ($conv->messages->count() > 0) {
            foreach ($conv->messages as $key) {
                $key->delete();
            }
        }
        $conv->delete();
        return redirect()->back()->with('success', __('Message Deleted Successfully'));
    }

    public function msgload($id)
    {
        $conv = Conversation::findOrfail($id);
        return view('load.usermsg', compact('conv'));
    }

    //Send email to user
    public function usercontact(Request $request)
    {
        //dd($request->all());
        $data = 1;
        $user = User::findOrFail($request->user_id);
        $vendor = User::where('email', '=', $request->email)->first();
        $seller = User::findOrFail($request->vendor_id);

        if (!$vendor) {
            return back()->with('unsuccess', 'Vendor Not Found');
        }

        if ($vendor->email == $seller->email) {
            return back()->with('unsuccess', 'You can not message yourself!!');
        }

        $subject = $request->subject;
        $name = $request->name;
        $from = $request->email;
        $msg = "Name: " . $name . "\nEmail: " . $from . "\nMessage: " . $request->message;

        $data = [
            'to' => $seller->email,
            'subject' => $request->subject,
            'body' => $msg,
        ];

        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($data);

        $conv = Conversation::where('sent_user', '=', $user->id)->where('subject', '=', $subject)->first();

        if (isset($conv)) {
            $msg = new Message();
            $msg->conversation_id = $conv->id;
            $msg->message = $request->message;
            $msg->sent_user = $user->id;
            $msg->save();
            return back()->with('success', 'Message sent successfully');
        } else {
            $message = new Conversation();
            $message->subject = $subject;
            $message->sent_user = $request->user_id;
            $message->recieved_user = $vendor->id;
            $message->message = $request->message;
            $message->save();

            $msg = new Message();
            $msg->conversation_id = $message->id;
            $msg->message = $request->message;
            $msg->sent_user = $request->user_id;
            $msg->save();
            return back()->with('success', 'Message sent successfully');
        }
    }

    public function postmessage(Request $request)
    {
        $msg = new Message();
        $input = $request->all();
        $msg->fill($input)->save();
        return back()->with('success', __('Message Sent Successfully'));
    }

    public function adminmessages()
    {
        $user = $this->user;
        $convs = SupportThread::where('type', '=', 'Ticket')->where('user_id', '=', $user->id)->paginate(12);
        return view('user.ticket.index', compact('convs'));
    }

    public function adminDiscordmessages()
    {
        $user = $this->user;
        $convs = SupportThread::where('type', '=', 'Dispute')->where('user_id', '=', $user->id)->paginate(12);
        return view('user.dispute.index', compact('convs'));
    }

    public function messageload($id)
    {
        $conv = SupportThread::findOrfail($id);
        return view('load.usermessage', compact('conv'));
    }

    public function adminmessage($id)
    {
        $conv = SupportThread::findOrfail($id);
        return view('user.ticket.create', compact('conv'));
    }

    public function adminmessagedelete($id)
    {
        $conv = SupportThread::findOrfail($id);
        if ($conv->messages->count() > 0) {
            foreach ($conv->messages as $key) {
                $key->delete();
            }
        }
        $conv->delete();
        return redirect()->back()->with('success', __('Message Deleted Successfully'));
    }

    public function adminpostmessage(Request $request)
    {
        $msg = new SupportMessage();
        $input = $request->all();
        $msg->fill($input)->save();
        $notification = new Notification;
        $notification->conversation_id = $msg->thread->id;
        $notification->save();
        return back()->with('success', __('Message Sent Successfully'));
    }

    public function adminusercontact(Request $request)
    {

        if ($request->type == 'Dispute') {
            $order = Order::where('order_number', $request->order)->exists();
            if (!$order) {
                return back()->with('unsuccess', 'Order Number Not Found');
            }
        }

        $user = $this->user;
        $gs = $this->gs;
        $subject = $request->subject;
        $to = \DB::table('pagesettings')->first()->contact_email;
        $from = $user->email;
        $msg = "Email: " . $from . "\nMessage: " . $request->message;

        $data = [
            'to' => $to,
            'subject' => $subject,
            'body' => $msg,
        ];

        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($data);

        if ($request->type == 'Ticket') {
            $thread = SupportThread::whereType('Ticket')->whereUserId($user->id)->whereSubject($subject)->first();
        } else {
            $thread = SupportThread::whereType('Dispute')->whereUserId($user->id)->whereSubject($subject)->first();
        }

        if (isset($thread)) {
            $msg = new SupportMessage();
            $msg->thread_id = $thread->id;
            $msg->message = $request->message;
            $msg->user_id = $user->id;
            $msg->save();
            return back()->with('success', 'Message sent successfully');
        } else {
            $thread = new SupportThread();
            $thread->subject = $subject;
            $thread->user_id = $user->id;
            $thread->message = $request->message;
            $thread->order_number = $request->order;
            $thread->type = $request->type;
            $thread->save();
            $notification = new Notification;
            $notification->conversation_id = $thread->id;
            $notification->save();
            $msg = new SupportMessage();
            $msg->thread_id = $thread->id;
            $msg->message = $request->message;
            $msg->user_id = $user->id;
            $msg->save();
            return back()->with('success', 'Message sent successfully');
        }
    }
}
