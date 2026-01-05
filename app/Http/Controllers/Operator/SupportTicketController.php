<?php

namespace App\Http\Controllers\Operator;

use App\Classes\MuaadhMailer;
use App\Models\SupportThread;
use App\Models\SupportMessage;
use App\Models\Purchase;
use App\Models\Courier;
use App\Models\User;
use Auth;
use Datatables;
use Illuminate\Http\Request;

class SupportTicketController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables($type)
    {
        $datas = SupportThread::where('type', '=', $type)->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('created_at', function (SupportThread $data) {
                $date = $data->created_at->diffForHumans();
                return $date;
            })
            ->addColumn('name', function (SupportThread $data) {
                $name = $data->user->name;
                return $name;
            })
            ->addColumn('action', function (SupportThread $data) {
                return '<div class="action-list"><a href="' . route('operator-support-ticket-show', $data->id) . '"> <i class="fas fa-eye"></i> ' . __('Details') . '</a><a href="javascript:;" data-href="' . route('operator-support-ticket-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.support-ticket.index');
    }

    public function dispute()
    {
        return view('operator.support-ticket.dispute');
    }

    //*** GET Request
    public function message($id)
    {
        $conv = SupportThread::findOrfail($id);
        return view('operator.support-ticket.create', compact('conv'));
    }

    //*** GET Request
    public function messageshow($id)
    {
        $conv = SupportThread::findOrfail($id);
        return view('load.support-message', compact('conv'));
    }

    //*** GET Request
    public function messagedelete($id)
    {
        $conv = SupportThread::findOrfail($id);
        if ($conv->messages->count() > 0) {
            foreach ($conv->messages as $key) {
                $key->delete();
            }
        }
        $conv->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** POST Request
    public function postmessage(Request $request)
    {
        $msg = new SupportMessage();
        $input = $request->all();
        $msg->fill($input)->save();
        //--- Redirect Section
        $msg = __('Message Sent!');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** POST Request
    public function usercontact(Request $request)
    {

        $data = 1;
        $operator = Auth::guard('operator')->user();

        if ($request->type == "courier") {
            $user = Courier::where('email', '=', $request->to)->first();
            if (empty($user)) {
                $data = 0;
                return response()->json($data);
            }
            $to = $request->to;
            $subject = $request->subject;
            $from = $operator->email;
            $msg = "Email: " . $from . "<br>Message: " . $request->message;

            $datas = [
                'to' => $to,
                'subject' => $subject,
                'body' => $msg,
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($datas);

            return response()->json($data);
        }

        $user = User::where('email', '=', $request->to)->first();

        if (empty($user)) {
            $data = 0;
            return response()->json($data);
        }
        $to = $request->to;
        $subject = $request->subject;
        $from = $operator->email;
        $msg = "Email: " . $from . "<br>Message: " . $request->message;

        $datas = [
            'to' => $to,
            'subject' => $subject,
            'body' => $msg,
        ];
        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($datas);

        if ($request->type == 'Ticket') {
            $thread = SupportThread::where('type', '=', 'Ticket')->where('user_id', '=', $user->id)->where('subject', '=', $subject)->first();
        } else {
            $thread = SupportThread::where('type', '=', 'Dispute')->where('user_id', '=', $user->id)->where('subject', '=', $subject)->first();
        }
        if (isset($thread)) {
            $msg = new SupportMessage();
            $msg->thread_id = $thread->id;
            $msg->message = $request->message;
            $msg->save();
            return response()->json($data);
        } else {
            $thread = new SupportThread();
            $thread->subject = $subject;
            $thread->user_id = $user->id;
            $thread->message = $request->message;
            $thread->purchase_number = $request->purchase;
            $thread->type = $request->type;
            $thread->save();
            $msg = new SupportMessage();
            $msg->thread_id = $thread->id;
            $msg->message = $request->message;
            $msg->save();
            return response()->json($data);
        }
    }
}
