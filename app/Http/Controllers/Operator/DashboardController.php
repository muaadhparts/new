<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Validator;

class DashboardController extends OperatorBaseController
{

    public function index()
    {

        $data['pending'] = Purchase::where('status', '=', 'pending')->get();
        $data['processing'] = Purchase::where('status', '=', 'processing')->get();
        $data['completed'] = Purchase::where('status', '=', 'completed')->get();
        $data['days'] = "";
        $data['sales'] = "";
        for ($i = 0; $i < 30; $i++) {
            $data['days'] .= "'" . date("d M", strtotime('-' . $i . ' days')) . "',";

            $data['sales'] .= "'" . Purchase::where('status', '=', 'completed')->whereDate('created_at', '=', date("Y-m-d", strtotime('-' . $i . ' days')))->count() . "',";
        }
        $data['users'] = User::count();
        $data['catalogItems'] = CatalogItem::count();

        // CatalogItem-first: Get latest catalog items with active merchant offers
        $data['latestCatalogItems'] = CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with(['merchantItems' => fn($q) => $q->where('status', 1)->orderBy('price')])
            ->latest('id')
            ->take(5)
            ->get();

        $data['recentPurchases'] = Purchase::latest('id')->take(5)->get();

        // CatalogItem-first: Get popular catalog items (by views)
        $data['popularCatalogItems'] = CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with(['merchantItems' => fn($q) => $q->where('status', 1)->orderBy('price')])
            ->orderByDesc('views')
            ->take(5)
            ->get();

        $data['recentUsers'] = User::latest('id')->take(5)->get();

        return view('operator.dashboard', $data);
    }

    public function profile()
    {
        $data = Auth::guard('operator')->user();
        return view('operator.profile', compact('data'));
    }

    public function profileupdate(Request $request)
    {
        //--- Validation Section

        $rules =
            [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:operators,email,' . Auth::guard('operator')->user()->id,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends
        $input = $request->all();
        $data = Auth::guard('operator')->user();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/operators/', $name);
            if ($data->photo != null) {
                if (file_exists(public_path() . '/assets/images/operators/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/operators/' . $data->photo);
                }
            }
            $input['photo'] = $name;
        }
        $data->update($input);
        $msg = __('Successfully updated your profile');
        return response()->json($msg);
    }

    public function passwordreset()
    {
        $data = Auth::guard('operator')->user();
        return view('operator.password', compact('data'));
    }

    public function changepass(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        if ($request->cpass) {
            if (Hash::check($request->cpass, $operator->password)) {
                if ($request->newpass == $request->renewpass) {
                    $input['password'] = Hash::make($request->newpass);
                } else {
                    return response()->json(array('errors' => [0 => __('Confirm password does not match.')]));
                }
            } else {
                return response()->json(array('errors' => [0 => __('Current password Does not match.')]));
            }
        }
        $operator->update($input);
        $msg = __('Successfully changed your password');
        return response()->json($msg);
    }
}
