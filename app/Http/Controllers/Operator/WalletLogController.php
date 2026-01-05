<?php

namespace App\Http\Controllers\Operator;

use Datatables;
use App\Models\WalletLog;

class WalletLogController extends OperatorBaseController
{
        //*** JSON Request
        public function transdatatables()
        {
             $datas = WalletLog::orderBy('id','desc')->get();
             //--- Integrating This Collection Into Datatables
             return Datatables::of($datas)
                                ->addColumn('name', function(WalletLog $data) {
                                    $name = '<a href="'.route('operator-user-show',$data->user_id).'" target="_blank">'.$data->user['name'].'</a>';
                                    return $name;
                                })
                                ->addColumn('date', function(WalletLog $data) {
                                    $date = date('Y-m-d',strtotime($data->created_at));
                                    return $date;
                                })
                                ->editColumn('amount', function(WalletLog $data) {
                                    $price = $data->amount * $data->currency_value;
                                    $price = \PriceHelper::showOrderCurrencyPrice($price,$data->currency_sign);
                                    if($data->type == 'plus'){
                                        $price ='+'.$price;
                                    } else {
                                        $price ='-'.$price;
                                    }
                                    return  $price;
                                })
                                ->addColumn('action', function(WalletLog $data) {
                                    return '<div class="action-list">
                                                <a href="javascript:;" data-href="' . route('operator-wallet-log-show',$data->id) . '" class="view" data-bs-toggle="modal" data-bs-target="#modal1">
                                                <i class="fas fa-eye"></i> '.__("Details").'
                                                </a>
                                            </div>';
                                })
                                ->rawColumns(['name','action'])
                                ->toJson(); //--- Returning Json Data To Client Side
        }

        public function index(){
            return view('operator.wallet-log.index');
        }

        //*** GET Request
        public function transhow($id)
        {
            $data = WalletLog::find($id);
            return view('operator.wallet-log.show',compact('data'));
        }

}
