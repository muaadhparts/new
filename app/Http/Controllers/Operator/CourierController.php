<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Shipping\Models\Courier;
use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Accounting\Models\WalletLog;
use App\Domain\Shipping\Services\CourierDatatablesService;
use App\Classes\MuaadhMailer;

use Illuminate\{
    Http\Request,
    Support\Str
};

use Carbon\Carbon;
use Validator;
use Datatables;


class CourierController extends OperatorBaseController
{
    public function __construct(
        protected CourierDatatablesService $datatablesService
    ) {
        parent::__construct();
    }
    //*** JSON Request
    public function datatables()
    {
        // Optimized: uses withCount instead of loading all deliveries
        $datas = $this->datatablesService->getCouriersWithDeliveryCount();

        return Datatables::of($datas)
            ->addColumn('total_delivery', function (Courier $data) {
                return $data->deliveries_count;
            })
            ->addColumn('action', function (Courier $data) {
                $class = $data->status == 0 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';
                $ban = '<select class="process select droplinks ' . $class . '">' .
                    '<option data-val="0" value="' . route('operator-courier-ban', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Block") . '</option>' .
                    '<option data-val="1" value="' . route('operator-courier-ban', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("UnBlock") . '</option></select>';


                return '<div class="action-list">
                            '
                    . $ban .
                    '
                    <a href="' . route('operator-courier-show', $data->id) . '" >
                        <i class="fas fa-eye"></i> ' . __("Details") . '
                    </a>

                    <a href="javascript:;" data-href="' . route('operator-courier-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete">
                            <i class="fas fa-trash-alt"></i>
                            </a>

                        </div>';
            })
            ->rawColumns(['action', 'total_delivery'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.courier.index');
    }



    public function withdraws()
    {
        return view('operator.courier.withdraws');
    }

    //*** GET Request
    public function show($id)
    {
        $data = Courier::findOrFail($id);
        return view('operator.courier.show', compact('data'));
    }

    //*** GET Request
    public function ban($id1, $id2)
    {
        $user = Courier::findOrFail($id1);
        $user->status = $id2;
        $user->update();

        $msg = $id2 == 1 ? __('Courier Blocked Successfully.') : __('Courier Unblocked Successfully.');
        return response()->json($msg);
    }



    //*** GET Request Delete
    public function destroy($id)
    {
        $user = Courier::findOrFail($id);
        $user->delete();

        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
    }

    //*** JSON Request
    public function withdrawdatatables()
    {
        // Optimized: eager loads courier relationship to prevent N+1
        $datas = $this->datatablesService->getCourierWithdraws();

        return Datatables::of($datas)
            ->addColumn('email', function (Withdraw $data) {
                $email = $data->courier->email;
                return $email;
            })
            ->addColumn('phone', function (Withdraw $data) {
                $phone = $data->courier->phone;
                return $phone;
            })
            ->editColumn('status', function (Withdraw $data) {
                $status = ucfirst($data->status);
                return $status;
            })
            ->editColumn('amount', function (Withdraw $data) {
                $sign = $this->curr;
                $amount = $data->amount * $sign->value;
                return \PriceHelper::showAdminCurrencyPrice($amount);;
            })
            ->addColumn('action', function (Withdraw $data) {
                $action = '<div class="action-list"><a data-href="' . route('operator-withdraw-courier-show', $data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i> ' . __("Details") . '</a>';
                if ($data->status == "pending") {
                    $action .= '<a data-href="' . route('operator-withdraw-courier-accept', $data->id) . '" data-bs-toggle="modal" data-bs-target="#status-modal1"> <i class="fas fa-check"></i> ' . __("Accept") . '</a><a data-href="' . route('operator-withdraw-courier-reject', $data->id) . '" data-bs-toggle="modal" data-bs-target="#status-modal"> <i class="fas fa-trash-alt"></i> ' . __("Reject") . '</a>';
                }
                $action .= '</div>';
                return $action;
            })
            ->rawColumns(['name', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }


    //*** GET Request
    public function withdrawdetails($id)
    {
        $sign = $this->curr;
        $withdraw = Withdraw::findOrFail($id);
        return view('operator.courier.withdraw-details', compact('withdraw', 'sign'));
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
        $account = Courier::findOrFail($withdraw->courier->id);
        $account->balance = $account->balance + $withdraw->amount + $withdraw->fee;
        $account->update();
        $data['status'] = "rejected";
        $withdraw->update($data);
        //--- Redirect Section
        $msg = __('Withdraw Rejected Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
