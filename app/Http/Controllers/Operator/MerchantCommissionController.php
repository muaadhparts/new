<?php

namespace App\Http\Controllers\Operator;

use App\Models\MerchantCommission;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class MerchantCommissionController extends OperatorBaseController
{
    /**
     * Display DataTables of merchants with their commissions.
     */
    public function datatables()
    {
        // Get all verified merchants (is_merchant = 2)
        $merchants = User::where('is_merchant', 2)
            ->with('merchantCommission')
            ->select(['id', 'name', 'email', 'shop_name', 'created_at'])
            ->latest('id')
            ->get();

        return Datatables::of($merchants)
            ->addColumn('shop', function ($user) {
                return $user->shop_name ?: '-';
            })
            ->addColumn('fixed_commission', function ($user) {
                $commission = $user->merchantCommission;
                return $commission ? number_format($commission->fixed_commission, 2) : '0.00';
            })
            ->addColumn('percentage_commission', function ($user) {
                $commission = $user->merchantCommission;
                return $commission ? number_format($commission->percentage_commission, 2) . '%' : '0.00%';
            })
            ->addColumn('status', function ($user) {
                $commission = $user->merchantCommission;
                if (!$commission || $commission->is_active) {
                    return '<span class="badge bg-success">' . __('Active') . '</span>';
                }
                return '<span class="badge bg-danger">' . __('Inactive') . '</span>';
            })
            ->addColumn('action', function ($user) {
                return '<div class="action-list">
                    <a data-href="' . route('operator-merchant-commission-edit', $user->id) . '"
                       class="edit" data-bs-toggle="modal" data-bs-target="#modal1">
                       <i class="fas fa-edit"></i> ' . __('Edit') . '
                    </a>
                </div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    /**
     * Display listing of merchants with commissions.
     */
    public function index()
    {
        return view('operator.merchant-commission.index');
    }

    /**
     * Show form to edit merchant commission.
     */
    public function edit($id)
    {
        $merchant = User::where('is_merchant', 2)->findOrFail($id);
        $commission = MerchantCommission::getOrCreateForMerchant($id);

        return view('operator.merchant-commission.edit', compact('merchant', 'commission'));
    }

    /**
     * Update merchant commission settings.
     */
    public function update(Request $request, $id)
    {
        // Validation
        $rules = [
            'fixed_commission' => 'required|numeric|min:0',
            'percentage_commission' => 'required|numeric|min:0|max:100',
        ];
        $customs = [
            'fixed_commission.required' => __('Fixed commission is required.'),
            'fixed_commission.numeric' => __('Fixed commission must be a number.'),
            'fixed_commission.min' => __('Fixed commission cannot be negative.'),
            'percentage_commission.required' => __('Percentage commission is required.'),
            'percentage_commission.numeric' => __('Percentage commission must be a number.'),
            'percentage_commission.min' => __('Percentage commission cannot be negative.'),
            'percentage_commission.max' => __('Percentage commission cannot exceed 100%.'),
        ];

        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        // Verify merchant exists
        $merchant = User::where('is_merchant', 2)->findOrFail($id);

        // Update or create commission
        $commission = MerchantCommission::updateOrCreate(
            ['user_id' => $id],
            [
                'fixed_commission' => $request->fixed_commission,
                'percentage_commission' => $request->percentage_commission,
                'is_active' => $request->has('is_active') ? 1 : 0,
                'notes' => $request->notes,
            ]
        );

        $msg = __('Commission settings updated successfully.');
        return response()->json($msg);
    }

    /**
     * Bulk update commission for multiple merchants.
     */
    public function bulkUpdate(Request $request)
    {
        $rules = [
            'merchant_ids' => 'required|array',
            'merchant_ids.*' => 'exists:users,id',
            'fixed_commission' => 'required|numeric|min:0',
            'percentage_commission' => 'required|numeric|min:0|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        foreach ($request->merchant_ids as $merchantId) {
            MerchantCommission::updateOrCreate(
                ['user_id' => $merchantId],
                [
                    'fixed_commission' => $request->fixed_commission,
                    'percentage_commission' => $request->percentage_commission,
                    'is_active' => $request->has('is_active') ? 1 : 0,
                ]
            );
        }

        $msg = __('Commission settings updated for selected merchants.');
        return response()->json($msg);
    }
}
