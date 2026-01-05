<?php

namespace App\Http\Controllers\Operator;

use App\Models\MembershipPlan;

use Illuminate\Http\Request;
use Datatables;

class MembershipPlanController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = MembershipPlan::latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->editColumn('price', function(MembershipPlan $data) {
                                $price = $data->price * $this->curr->value;
                                return \PriceHelper::showAdminCurrencyPrice($price);
                            })
                            ->editColumn('allowed_items', function(MembershipPlan $data) {
                                $allowed_items = $data->allowed_items == 0 ? "Unlimited": $data->allowed_items;
                                return $allowed_items;
                            })
                            ->addColumn('action', function(MembershipPlan $data) {
                                return '<div class="action-list"><a data-href="' . route('operator-membership-plan-edit',$data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('operator-membership-plan-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('operator.membership-plan.index',[
            'sign' => $this->curr
        ]);
    }

    //*** GET Request
    public function create()
    {
        return view('operator.membership-plan.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Logic Section
        $data = new MembershipPlan();
        $input = $request->all();

        if($input['limit'] == 0)
         {
            $input['allowed_items'] = 0;
         }
        $input['price'] = ($input['price'] / $this->curr->value);
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
        $sign = $this->curr;
        $data = MembershipPlan::findOrFail($id);
        return view('operator.membership-plan.edit',compact('data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Logic Section
        $data = MembershipPlan::findOrFail($id);
        $input = $request->all();
        if($input['limit'] == 0)
         {
            $input['allowed_items'] = 0;
         }
         $input['price'] = ($input['price'] / $this->curr->value);
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = MembershipPlan::findOrFail($id);
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
