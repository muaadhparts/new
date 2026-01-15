<?php

namespace App\Http\Controllers\Operator;

use App\Models\MerchantPayment;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class MerchantPaymentController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        // Only platform payment gateways (user_id = 0)
        $datas = MerchantPayment::where('user_id', 0)->latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->editColumn('title', function(MerchantPayment $data) {
                                if($data->type == 'automatic'){
                                    return  $data->name;
                                }else{
                                    return  $data->title;
                                }
                            })
                            ->editColumn('details', function(MerchantPayment $data) {
                                if($data->type == 'automatic'){
                                    return $data->getAutoDataText();
                                }else {
                                    if($data->keyword == 'cod'){
                                        return $data->subtitle;
                                    }else{
                                        $details = mb_strlen(strip_tags($data->details),'utf-8') > 250 ? mb_substr(strip_tags($data->details),0,250,'utf-8').'...' : strip_tags($data->details);
                                        return  $details;
                                    }

                                }
                            })
                            ->addColumn('checkout', function(MerchantPayment $data) {
                                $class = $data->checkout == 1 ? 'drop-success' : 'drop-danger';
                                $activeSelected = $data->checkout == 1 ? 'selected' : '';
                                $deactiveSelected = $data->checkout == 0 ? 'selected' : '';
                                $activeText = __('Showed');
                                $deactiveText = __('Not Showed');
                                $activeLink = route('operator-merchant-payment-status',['checkout',$data->id, 1]);
                                $deactiveLink = route('operator-merchant-payment-status',['checkout',$data->id, 0]);
                                return "<div class='action-list'>
                                            <select class='process select droplinks {$class}'>
                                            <option data-val='1' value='{$activeLink}' {$activeSelected}>{$activeText}</option>
                                            <option data-val='0' value='{$deactiveLink}' {$deactiveSelected}>{$deactiveText}</option>
                                            </select>
                                         </div>";
                            })
                            ->addColumn('topup', function(MerchantPayment $data) {
                                $class = $data->topup == 1 ? 'drop-success' : 'drop-danger';
                                $activeSelected = $data->topup == 1 ? 'selected' : '';
                                $deactiveSelected = $data->topup == 0 ? 'selected' : '';
                                $activeText = __('Showed');
                                $deactiveText = __('Not Showed');
                                $activeLink = route('operator-merchant-payment-status',['topup',$data->id, 1]);
                                $deactiveLink = route('operator-merchant-payment-status',['topup',$data->id, 0]);
                                if($data->keyword == 'cod'){
                                    return __("Not Available");
                                }
                                return "<div class='action-list'>
                                            <select class='process select droplinks {$class}'>
                                            <option data-val='1' value='{$activeLink}' {$activeSelected}>{$activeText}</option>
                                            <option data-val='0' value='{$deactiveLink}' {$deactiveSelected}>{$deactiveText}</option>
                                            </select>
                                         </div>";
                            })
                            ->addColumn('subscription', function(MerchantPayment $data) {
                                $class = $data->subscription == 1 ? 'drop-success' : 'drop-danger';
                                $activeSelected = $data->subscription == 1 ? 'selected' : '';
                                $deactiveSelected = $data->subscription == 0 ? 'selected' : '';
                                $activeText = __('Showed');
                                $deactiveText = __('Not Showed');
                                $activeLink = route('operator-merchant-payment-status',['subscription',$data->id, 1]);
                                $deactiveLink = route('operator-merchant-payment-status',['subscription',$data->id, 0]);
                                if($data->keyword == 'cod'){
                                    return __("Not Available");
                                }
                                return "<div class='action-list'>
                                            <select class='process select droplinks {$class}'>
                                            <option data-val='1' value='{$activeLink}' {$activeSelected}>{$activeText}</option>
                                            <option data-val='0' value='{$deactiveLink}' {$deactiveSelected}>{$deactiveText}</option>
                                            </select>
                                         </div>";
                            })
                            ->addColumn('action', function(MerchantPayment $data) {
                                $editLink = route('operator-merchant-payment-edit',$data->id);
                                $deleteLink = route('operator-merchant-payment-delete',$data->id);

                                $delete = $data->type == 'automatic' || $data->keyword != null ? "" : "<a href='javascript:;' data-href='{$deleteLink}' data-bs-toggle='modal' data-bs-target='#confirm-delete' class='delete'>
                                <i class='fas fa-trash-alt'></i>
                                </a>";
                                $editText = __('Edit');
                                return "<div class='action-list'>
                                            <a data-href='{$editLink}' class='edit' data-bs-toggle='modal' data-bs-target='#modal1'>
                                            <i class='fas fa-edit'></i>{$editText}
                                            </a>
                                            {$delete}
                                        </div>";
                                })
                            ->rawColumns(['checkout','topup','subscription','action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }


    public function index(){
        return view('operator.merchant-payment.index');
    }

    public function create(){
        return view('operator.merchant-payment.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['title' => 'unique:merchant_payments'];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new MerchantPayment();
        $input = $request->all();
        $input['type'] = "manual";
        // Platform payment gateway - user_id = 0
        $input['user_id'] = 0;
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        // Only allow editing platform payment gateways (user_id = 0)
        $data = MerchantPayment::where('id', $id)->where('user_id', 0)->firstOrFail();
        return view('operator.merchant-payment.edit',compact('data'));
    }

    private function setEnv($key, $value,$prev)
    {
        file_put_contents(app()->environmentFilePath(), str_replace(
            $key . '=' . $prev,
            $key . '=' . $value,
            file_get_contents(app()->environmentFilePath())
        ));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        // Only allow updating platform payment gateways (user_id = 0)
        $data = MerchantPayment::where('id', $id)->where('user_id', 0)->firstOrFail();
        $prev = '';
        if($data->type == "automatic"){

            //--- Validation Section
            $rules = [
                'name' => 'unique:merchant_payments,name,'.$id,
                'currency_id' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            //--- Logic Section

            $input = $request->all();

            $info_data = $input['pkey'];

            if($data->keyword == 'mollie'){
                $paydata = $data->convertAutoData();
                $prev = $paydata['key'];
            }

            if (array_key_exists("sandbox_check",$info_data)){
                $info_data['sandbox_check'] = 1;
            }else{
                if (strpos($data->information, 'sandbox_check') !== false) {
                    $info_data['sandbox_check'] = 0;
                    $text =  $info_data['text'];
                    unset($info_data['text']);
                    $info_data['text'] = $text;
                }
            }
            $input['information'] = json_encode($info_data);
            $input['currency_id'] = json_encode($request->currency_id);
            $data->update($input);


            if($data->keyword == 'mollie'){
                $paydata = $data->convertAutoData();
                $this->setEnv('MOLLIE_KEY',$paydata['key'],$prev);

            }
            //--- Logic Section Ends
        }
        else{
            //--- Validation Section
            $rules = ['title' => 'unique:merchant_payments,title,'.$id];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            //--- Logic Section

            $input = $request->all();
            $input['currency_id'] = json_encode($request->currency_id);
            $data->update($input);


            //--- Logic Section Ends

        }
        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

      //*** GET Request Status
      public function status($field,$id1,$id2)
        {
            // Only allow updating platform payment gateways (user_id = 0)
            $data = MerchantPayment::where('id', $id1)->where('user_id', 0)->firstOrFail();
            $data[$field] = $id2;
            $data->update();
            //--- Redirect Section
            $msg = __('Status Updated Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }

    //*** GET Request Delete
    public function destroy($id)
    {
        // Only allow deleting platform payment gateways (user_id = 0)
        $data = MerchantPayment::where('id', $id)->where('user_id', 0)->firstOrFail();
        if($data->type == 'manual' || $data->keyword != null){
            $data->delete();
        }
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
