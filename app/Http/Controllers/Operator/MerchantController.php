<?php

namespace App\Http\Controllers\Operator;

use App\Classes\MuaadhMailer;
use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Events\MerchantStatusChangedEvent;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use App\Domain\Merchant\Services\MerchantItemDisplayService;
use Auth;
use Datatables;
use Illuminate\Http\Request;
use Validator;

class MerchantController extends OperatorBaseController
{
    public function __construct(
        private MerchantItemQuery $itemQuery,
        private MerchantItemDisplayService $displayService,
    ) {
        parent::__construct();
    }

    /**
     * Merchants list
     */
    public function index()
    {
        return view('operator.merchant.index');
    }

    /**
     * Merchants datatable
     */
    public function datatables()
    {
        $merchants = User::whereIn('is_merchant', [1, 2])
            ->latest('id')
            ->get();

        return Datatables::of($merchants)
            ->addColumn('status', function (User $merchant) {
                $class = $merchant->is_merchant == 2 ? 'drop-success' : 'drop-danger';
                $activated = $merchant->is_merchant == 2 ? 'selected' : '';
                $deactivated = $merchant->is_merchant == 1 ? 'selected' : '';
                
                return '<div class="action-list">' .
                    '<select class="process select merchant-droplinks ' . $class . '">' .
                    '<option value="' . route('operator-merchant-st', ['id1' => $merchant->id, 'id2' => 2]) . '" ' . $activated . '>' . __("Activated") . '</option>' .
                    '<option value="' . route('operator-merchant-st', ['id1' => $merchant->id, 'id2' => 1]) . '" ' . $deactivated . '>' . __("Deactivated") . '</option>' .
                    '</select>' .
                    '</div>';
            })
            ->addColumn('action', function (User $merchant) {
                return '<div class="godropdown">' .
                    '<button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>' .
                    '<div class="action-list">' .
                    '<a href="' . route('operator-merchant-secret', $merchant->id) . '"> <i class="fas fa-user"></i> ' . __("Secret Login") . '</a>' .
                    '<a href="' . route('operator-merchant-show', $merchant->id) . '"> <i class="fas fa-eye"></i> ' . __("Details") . '</a>' .
                    '<a data-href="' . route('operator-merchant-edit', $merchant->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i> ' . __("Edit") . '</a>' .
                    '<a href="javascript:;" class="send" data-email="' . $merchant->email . '" data-bs-toggle="modal" data-bs-target="#merchantform"><i class="fas fa-envelope"></i> ' . __("Send Email") . '</a>' .
                    '<a href="javascript:;" data-href="' . route('operator-merchant-delete', $merchant->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete") . '</a>' .
                    '</div>' .
                    '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    /**
     * Withdraws page
     */
    public function withdraws()
    {
        return view('operator.merchant.withdraws');
    }

    /**
     * Change merchant status
     */
    public function status($id, $status)
    {
        $merchant = User::findOrFail($id);
        $oldStatus = $merchant->is_merchant;
        
        $merchant->update(['is_merchant' => $status]);

        event(new MerchantStatusChangedEvent($merchant, $oldStatus, $status));

        return redirect()->back()->with('success', __('Status updated successfully'));
    }

    /**
     * Show merchant edit form
     */
    public function edit($id)
    {
        $merchant = User::findOrFail($id);
        return view('operator.merchant.edit', compact('merchant'));
    }

    /**
     * Request trust badge page
     */
    public function requestTrustBadge($id)
    {
        $merchant = User::findOrFail($id);
        return view('operator.merchant.trust-badge', compact('merchant'));
    }

    /**
     * Submit trust badge
     */
    public function requestTrustBadgeSubmit(Request $request, $id)
    {
        $merchant = User::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update trust badge status logic here

        return redirect()->back()->with('success', __('Trust badge updated'));
    }

    /**
     * Update merchant
     */
    public function update(Request $request, $id)
    {
        $merchant = User::findOrFail($id);

        $rules = [
            'shop_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $merchant->update($request->only(['shop_name', 'email', 'phone', 'address']));

        return response()->json(['success' => true, 'message' => __('Merchant updated successfully')]);
    }

    /**
     * Show merchant details
     */
    public function show($id)
    {
        $merchant = User::findOrFail($id);

        $merchantItems = $this->itemQuery::make()
            ->forMerchant($id)
            ->withRelations()
            ->paginate(20);

        $itemsDisplay = collect($merchantItems->items())
            ->map(fn($item) => $this->displayService->format($item))
            ->toArray();

        $totalSales = $merchant->merchantPurchases()
            ->where('status', 'completed')
            ->sum('price');

        $totalOrders = $merchant->merchantPurchases()->count();

        return view('operator.merchant.show', compact(
            'merchant',
            'merchantItems',
            'itemsDisplay',
            'totalSales',
            'totalOrders'
        ));
    }

    /**
     * Merchant items datatable
     */
    public function merchantItemsDatatables($id)
    {
        $items = $this->itemQuery::make()
            ->forMerchant($id)
            ->withRelations()
            ->get();

        $itemsDisplay = $items->map(fn($item) => $this->displayService->format($item));

        return Datatables::of($itemsDisplay)
            ->addColumn('action', function ($item) {
                return '<a href="' . route('operator-merchant-item-edit', $item['id']) . '" class="btn btn-sm btn-primary">' . __('Edit') . '</a>';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    /**
     * Delete merchant
     */
    public function destroy($id)
    {
        $merchant = User::findOrFail($id);
        
        // Delete merchant items
        $this->itemQuery::make()
            ->forMerchant($id)
            ->getQuery()
            ->delete();

        $merchant->delete();

        return redirect()->back()->with('success', __('Merchant deleted successfully'));
    }

    /**
     * Secret login as merchant
     */
    public function secretLogin($id)
    {
        $merchant = User::findOrFail($id);

        if (!in_array($merchant->is_merchant, [1, 2])) {
            abort(403, 'Not a merchant');
        }

        Auth::login($merchant);

        return redirect()->route('merchant-dashboard');
    }

    /**
     * Send email to merchant
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $mailer = new MuaadhMailer();
        $mailer->sendCustomEmail(
            $request->email,
            $request->subject,
            $request->message
        );

        return response()->json(['success' => true, 'message' => __('Email sent successfully')]);
    }
}
