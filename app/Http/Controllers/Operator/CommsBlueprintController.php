<?php

namespace App\Http\Controllers\Operator;

use App\{
    Classes\MuaadhMailer,
    Models\CommsBlueprint,
    Models\User
};
use Illuminate\Http\Request;
use Datatables;

class CommsBlueprintController extends OperatorBaseController
{

    //*** JSON Request
    public function datatables()
    {
         $datas = CommsBlueprint::latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('action', function(CommsBlueprint $commsBlueprint) {
                                return '<div class="action-list"><a data-href="' . route('operator-mail-edit',$commsBlueprint->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__('Edit').'</a></div>';
                            })
                            ->toJson();//--- Returning Json Data To Client Side
    }

    public function index(){
        return view('operator.comms-blueprint.index');
    }

    public function config(){
        return view('operator.comms-blueprint.config');
    }

    public function edit($id)
    {
        $data = CommsBlueprint::findOrFail($id);
        return view('operator.comms-blueprint.edit',compact('data'));
    }

    public function groupemail()
    {
        return view('operator.comms-blueprint.group');
    }

    public function groupemailpost(Request $request)
    {
        $ps = platformSettings();
        if($request->type == 0)
        {
        $users = User::all();
        //Sending Email To Users
        foreach($users as $user)
        {
            if ($ps->get('mail_driver'))
            {
                $data = [
                    'to' => $user->email,
                    'subject' => $request->subject,
                    'body' => $request->body,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            }
            else
            {
               $to = $user->email;
               $subject = $request->subject;
               $msg = $request->body;
                $headers = "From: ".$ps->get('from_name')."<".$ps->get('from_email').">";
               mail($to,$subject,$msg,$headers);
            }
        }
        //--- Redirect Section
        $msg = __('Email Sent Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
        }

        else if($request->type == 1)
        {
        // Get all merchants
        $users = User::where('is_merchant','=','2')->get();
        // Sending Email To Merchants
        foreach($users as $user)
        {
            if ($ps->get('mail_driver'))
            {
                $data = [
                    'to' => $user->email,
                    'subject' => $request->subject,
                    'body' => $request->body,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            }
            else
            {
               $to = $user->email;
               $subject = $request->subject;
               $msg = $request->body;
                $headers = "From: ".$ps->get('from_name')."<".$ps->get('from_email').">";
               mail($to,$subject,$msg,$headers);
            }
        }
        //--- Redirect Section
        $msg = __('Email Sent Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
        }


    }

    public function update(Request $request, $id)
    {
        $data = CommsBlueprint::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

}
