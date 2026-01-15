<?php

namespace App\Http\Controllers\Operator;

use App\{
    Models\User,
    Models\Withdraw,
    Models\WalletLog,
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\UserMembershipPlan
};

use Illuminate\{
    Http\Request,
    Support\Str
};

use Carbon\Carbon;
use Validator;
use Datatables;


class UserController extends OperatorBaseController
{
        //*** JSON Request
        public function datatables()
        {
             $datas = User::latest('id')->get();
             //--- Integrating This Collection Into Datatables
             return Datatables::of($datas)
                                ->addColumn('action', function(User $data) {
                                    $class = $data->ban == 0 ? 'drop-success' : 'drop-danger';
                                    $s = $data->ban == 1 ? 'selected' : '';
                                    $ns = $data->ban == 0 ? 'selected' : '';
                                    $ban = '<select class="process select droplinks '.$class.'">'.
                '<option data-val="0" value="'. route('operator-user-ban',['id1' => $data->id, 'id2' => 1]).'" '.$s.'>'.__("Block").'</option>'.
                '<option data-val="1" value="'. route('operator-user-ban',['id1' => $data->id, 'id2' => 0]).'" '.$ns.'>'.__("UnBlock").'</option></select>';

                                    // Build merchant toggle action link
                                    $merchant = $data->is_merchant != 2 ? '<a href="javascript:;" data-bs-toggle="modal" data-bs-target="#modal1" class="make-merchant" data-href="' . route('operator-user-merchant',$data->id) . '" >
                                    <i class="fas fa-users"></i> '.__("Make Merchant").'
                                    </a>' : '<a href="javascript:;">
                                    <i class="fas fa-users"></i> '.__("Merchant").'
                                    </a>';
                                    return '<div class="action-list">
                                            <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#modal1" class="topup" data-href="' . route('operator-user-top-up',$data->id) . '" >
                                            <i class="fas fa-dollar-sign"></i> '.__("Manage Top Up").'
                                            </a>'
                                            .$merchant.
                                            '<a href="' . route('operator-user-show',$data->id) . '" >
                                            <i class="fas fa-eye"></i> '.__("Details").'
                                            </a>
                                            <a data-href="' . route('operator-user-edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1">
                                            <i class="fas fa-edit"></i>'.__("Edit").
                                            '</a>
                                            <a href="javascript:;" class="send" data-email="'. $data->email .'" data-bs-toggle="modal" data-bs-target="#merchantform">
                                            <i class="fas fa-envelope"></i> '.__("Send").'
                                            </a>'
                                            .$ban.
                                            '<a href="javascript:;" data-href="' . route('operator-user-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete">
                                            <i class="fas fa-trash-alt"></i>
                                            </a>
                                            </div>';
                                }) 
                                ->rawColumns(['action'])
                                ->toJson(); //--- Returning Json Data To Client Side
        }

        public function index(){
            return view('operator.user.index');
        }

        public function create(){
            return view('operator.user.create');
        }

        public function withdraws(){
            return view('operator.user.withdraws');
        }

        //*** GET Request
        public function show($id)
        {
            $data = User::findOrFail($id);
            return view('operator.user.show',compact('data'));
        }

        //*** GET Request
        public function ban($id1,$id2)
        {
            $user = User::findOrFail($id1);
            $user->ban = $id2;
            $user->update();

        }

        //*** POST Request
        public function store(Request $request)
        {
            //--- Validation Section
            $rules = [
                        'email'    => 'required|email|unique:users',
                        'photo'    => 'required|mimes:jpeg,jpg,png,svg',
                        'password' => 'required'
                    ];

            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
              return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends
            $data = new User();
            $input = $request->all();
            $input['password'] = bcrypt($request['password']);
            if ($file = $request->file('photo'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/images/users',$name);
                $input['photo'] = $name;
            }
            $input['email_verified'] = 'Yes';
            $data->fill($input)->save();

			// Welcome Email For User

			$data = [
				'to' => $data->email,
				'type' => "new_registration",
				'cname' => $data->name,
				'oamount' => "",
				'aname' => "",
				'aemail' => "",
				'onumber' => "",
			];
			$mailer = new MuaadhMailer();
			$mailer->sendAutoMail($data);  

            $msg = __('New Customer Added Successfully.');
            return response()->json($msg);   
        }

        //*** GET Request    
        public function edit($id)
        {
            $data = User::findOrFail($id);
            return view('operator.user.edit',compact('data'));
        }

        //*** POST Request
        public function update(Request $request, $id)
        {
            //--- Validation Section
            $rules = [
                'email'    => 'required|email|unique:users,email,'.$id,
                'photo'    => 'mimes:jpeg,jpg,png,svg'
                 ];

            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
              return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            $user = User::findOrFail($id);
            $data = $request->all();
            if ($file = $request->file('photo'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/images/users',$name);
                if($user->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/users/'.$user->photo)) {
                        unlink(public_path().'/assets/images/users/'.$user->photo);
                    }
                }
                $data['photo'] = $name;
            }

			if(!empty($request->password)){
				$data['password'] = bcrypt($request['password']);
			}else {
				$data['password'] = $user->password;
			}

            $user->update($data);
            $msg = __('Customer Information Updated Successfully.');
            return response()->json($msg);   
        }

        //*** GET Request Delete
        public function destroy($id)
        {
        $user = User::findOrFail($id);

        if($user->abuseFlags->count() > 0)
        {
            foreach ($user->abuseFlags as $gal) {
                $gal->delete();
            }
        }

        if($user->catalogReviews->count() > 0)
        {
            foreach ($user->catalogReviews as $gal) {
                $gal->delete();
            }
        }

        if($user->notifications->count() > 0)
        {
            foreach ($user->notifications as $gal) {
                $gal->delete();
            }
        }

        if($user->favorites->count() > 0)
        {
            foreach ($user->favorites as $gal) {
                $gal->delete();
            }
        }

        if($user->withdraws->count() > 0)
        {
            foreach ($user->withdraws as $gal) {
                $gal->delete();
            }
        }

        if($user->oauthAccounts->count() > 0)
        {
            foreach ($user->oauthAccounts as $gal) {
                $gal->delete();
            }
        }

        if($user->chatThreads->count() > 0)
        {
            foreach ($user->chatThreads as $gal) {
            if($gal->messages->count() > 0)
            {
                foreach ($gal->messages as $key) {
                    $key->delete();
                }
            }
                $gal->delete();
            }
        }
        if($user->buyerNotes->count() > 0)
        {
            foreach ($user->buyerNotes as $gal) {
            if($gal->replies->count() > 0)
            {
                foreach ($gal->replies as $key) {
                    $key->delete();
                }
            }
                $gal->delete();
            }
        }

        if($user->replies->count() > 0)
        {
            foreach ($user->replies as $gal) {
            if($gal->subreplies->count() > 0)
                {
                    foreach ($gal->subreplies as $key) {
                        $key->delete();
                    }
                }
                $gal->delete();
            }
        }

        if($user->favorites->count() > 0)
        {
            foreach ($user->favorites as $gal) {
                $gal->delete();
            }
        }

        if($user->subscribes->count() > 0)
        {
            foreach ($user->subscribes as $gal) {
                $gal->delete();
            }
        }

        if($user->services->count() > 0)
        {
            foreach ($user->services as $gal) {
                if (file_exists(public_path().'/assets/images/services/'.$gal->photo)) {
                    unlink(public_path().'/assets/images/services/'.$gal->photo);
                }
                $gal->delete();
            }
        }

        if($user->withdraws->count() > 0)
        {
            foreach ($user->withdraws as $gal) {
                $gal->delete();
            }
        }

        if($user->catalogItems->count() > 0)
        {

// CATALOG ITEMS
            foreach ($user->catalogItems as $catalogItem) {
                if($catalogItem->merchantPhotos->count() > 0)
                {
                    foreach ($catalogItem->merchantPhotos as $photo) {
                            if (file_exists(public_path().'/assets/images/merchant-photos/'.$photo->photo)) {
                                unlink(public_path().'/assets/images/merchant-photos/'.$photo->photo);
                            }
                        $photo->delete();
                    }
                }
                if($catalogItem->catalogReviews->count() > 0)
                {
                    foreach ($catalogItem->catalogReviews as $gal) {
                        $gal->delete();
                    }
                }
                if($catalogItem->favorites->count() > 0)
                {
                    foreach ($catalogItem->favorites as $gal) {
                        $gal->delete();
                    }
                }
                if($catalogItem->clicks->count() > 0)
                {
                    foreach ($catalogItem->clicks as $gal) {
                        $gal->delete();
                    }
                }
                if($catalogItem->buyerNotes->count() > 0)
                {
                    foreach ($catalogItem->buyerNotes as $gal) {
                    if($gal->replies->count() > 0)
                    {
                        foreach ($gal->replies as $key) {
                            $key->delete();
                        }
                    }
                        $gal->delete();
                    }
                }
                if (file_exists(public_path().'/assets/images/catalogItems/'.$catalogItem->photo)) {
                    unlink(public_path().'/assets/images/catalogItems/'.$catalogItem->photo);
                }

                $catalogItem->delete();
            }

// CATALOG ITEMS ENDS

        }
// OTHER SECTION 

        if($user->senders->count() > 0)
        {
            foreach ($user->senders as $gal) {
            if($gal->messages->count() > 0)
            {
                foreach ($gal->messages as $key) {
                    $key->delete();
                }
            }
                $gal->delete();
            }
        }

        if($user->recievers->count() > 0)
        {
            foreach ($user->recievers as $gal) {
            if($gal->messages->count() > 0)
            {
                foreach ($gal->messages as $key) {
                    $key->delete();
                }
            }
                $gal->delete();
            }
        }

        if($user->chatThreads->count() > 0)
        {
            foreach ($user->chatThreads as $gal) {
            if($gal->messages->count() > 0)
            {
                foreach ($gal->messages as $key) {
                    $key->delete();
                }
            }
                $gal->delete();
            }
        }

        if($user->merchantPurchases->count() > 0)
        {
            foreach ($user->merchantPurchases as $gal) {
                $gal->delete();
            }
        }

        if($user->userCatalogEvents->count() > 0)
        {
            foreach ($user->userCatalogEvents as $gal) {
                $gal->delete();
            }
        }

        if($user->shippings->count() > 0)
        {
            foreach ($user->shippings as $gal) {
                $gal->delete();
            }
        }

        if($user->packages->count() > 0)
        {
            foreach ($user->packages as $gal) {
                $gal->delete();
            }
        }
        if($user->verifies->count() > 0)
        {
            foreach ($user->verifies as $gal) {
                $gal->delete();
            }
        }
        if($user->networkPresences->count() > 0)
        {
            foreach ($user->networkPresences as $gal) {
                $gal->delete();
            }
        }
// OTHER SECTION ENDS

            //If Photo Doesn't Exist
            if($user->photo == null){
                $user->delete();
                //--- Redirect Section     
                $msg = __('Data Deleted Successfully.');
                return response()->json($msg);      
                //--- Redirect Section Ends 
            }
            //If Photo Exist
            if (file_exists(public_path().'/assets/images/users/'.$user->photo)) {
                    unlink(public_path().'/assets/images/users/'.$user->photo);
                 }
            $user->delete();
            //--- Redirect Section     
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);      
            //--- Redirect Section Ends    
        }

        //*** JSON Request
        public function withdrawdatatables()
        {

             $datas = Withdraw::where('type','=','user')->latest('id')->get();

             //--- Integrating This Collection Into Datatables
             return Datatables::of($datas)
                                ->addColumn('email', function(Withdraw $data) {
                                    $email = $data->user->email;
                                    return $email;
                                }) 
                                ->addColumn('phone', function(Withdraw $data) {
                                    $phone = $data->user->phone;
                                    return $phone;
                                }) 
                                ->editColumn('status', function(Withdraw $data) {
                                    $status = ucfirst($data->status);
                                    return $status;
                                }) 
                                ->editColumn('amount', function(Withdraw $data) {
                                    $sign = $this->curr;
                                    $amount = $data->amount * $sign->value;
                                    return \PriceHelper::showAdminCurrencyPrice($amount);;
                                }) 
                                ->addColumn('action', function(Withdraw $data) {
                                    $action = '<div class="action-list"><a data-href="' . route('operator-withdraw-show',$data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i> '.__("Details").'</a>';
                                    if($data->status == "pending") {
                                    $action .= '<a data-href="' . route('operator-withdraw-accept',$data->id) . '" data-bs-toggle="modal" data-bs-target="#status-modal1"> <i class="fas fa-check"></i> '.__("Accept").'</a><a data-href="' . route('operator-withdraw-reject',$data->id) . '" data-bs-toggle="modal" data-bs-target="#status-modal"> <i class="fas fa-trash-alt"></i> '.__("Reject").'</a>';
                                    }
                                    $action .= '</div>';
                                    return $action;
                                }) 
                                ->rawColumns(['name','action'])
                                ->toJson(); //--- Returning Json Data To Client Side
        }


        //*** GET Request       
        public function withdrawdetails($id)
        {
            $sign = $this->curr;
            $withdraw = Withdraw::findOrFail($id);
            return view('operator.user.withdraw-details',compact('withdraw','sign'));
        }

        //*** GET Request   
        public function accept($id)
        {
            $withdraw = Withdraw::findOrFail($id);
            $data['status'] = "completed";
            $withdraw->update($data);
            //--- Redirect Section     
            $msg = __('Withdraw Accepted Successfully.');
            return response()->json($msg);      
            //--- Redirect Section Ends   
        }

        //*** GET Request   
        public function reject($id)
        {
            $withdraw = Withdraw::findOrFail($id);
            $account = User::findOrFail($withdraw->user->id);
            $account->affilate_income = $account->affilate_income + $withdraw->amount + $withdraw->fee;
            $account->update();
            $data['status'] = "rejected";
            $withdraw->update($data);
            //--- Redirect Section     
            $msg = __('Withdraw Rejected Successfully.');
            return response()->json($msg);      
            //--- Redirect Section Ends   
        }



        //*** GET Request
        public function topUp($id)
        {
            $sign = $this->curr;
            $data = User::findOrFail($id);
            return view('operator.user.top-up',compact('data','sign'));
        }

        public function topUpUpdate(Request $request, $id)
        {
            $sign = $this->curr;
            $user = User::findOrFail($id);
            if($request->type == 'plus') {
                $user->balance += (double)$request->amount;
            }else{
                $user->balance -= (double)$request->amount;
            }
            $user->update();
            $walletLog = new WalletLog;
            $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
            $walletLog->amount = $request->amount;
            $walletLog->user_id = $id;
            $walletLog->currency_sign = $sign->sign;
            $walletLog->currency_code = $sign->name;
            $walletLog->currency_value = $sign->value;
            $walletLog->method = null;
            $walletLog->txnid = null;
            $walletLog->details = $request->details;
            $walletLog->type = $request->type;
            $walletLog->save();
            $msg = __('Data Updated Successfully.');
            return response()->json($msg);   
        }


        //*** GET Request - Set user as merchant
        public function merchant($id)
        {
            $data = User::findOrFail($id);
            if($data->is_merchant != 2){
                return view('operator.user.setmerchant',compact('data'));
            }

        }

        // Set user as merchant with subscription
        public function setMerchant(Request $request, $id)
        {

            //--- Validation Section

            $rules = [
                'shop_name'   => 'unique:users',
                ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }

            //--- Validation Section Ends

            // Logic Section

            $user = User::findOrFail($id);
            $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
            $today = Carbon::now()->format('Y-m-d');
            $input = $request->all();
            $user->is_merchant = 2;
            $user->date = date('Y-m-d', strtotime($today.' + '.$membershipPlan->days.' days'));
            $user->mail_sent = 1;
            $user->update($input);

            $userPlan = new UserMembershipPlan;
            $userPlan->user_id = $user->id;
            $userPlan->membership_plan_id = $membershipPlan->id;
            $userPlan->title = $membershipPlan->title;
            $userPlan->currency_sign = $this->curr->sign;
            $userPlan->currency_code = $this->curr->name;
            $userPlan->currency_value = $this->curr->value;
            $userPlan->price = $membershipPlan->price * $this->curr->value;
            $userPlan->price = $userPlan->price / $this->curr->value;
            $userPlan->days = $membershipPlan->days;
            $userPlan->allowed_items = $membershipPlan->allowed_items;
            $userPlan->details = $membershipPlan->details;
            $userPlan->method = 'Free';
            $userPlan->status = 1;
            $userPlan->save();

            $msg = __('Successfully Created Merchant');
            return response()->json($msg);

            // Logic Section Ends


        }


}