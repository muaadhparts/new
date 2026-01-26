<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;

use App\Http\Resources\UserResource;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;use Auth;
use Hash;
use Illuminate\Http\Request;
use Validator;

class ProfileController extends Controller
{
    public function dashboard()
    {
        try {
            $user = Auth::guard('api')->user();
            $data['user'] = $user;
            $data['affilate_income'] = CatalogItem::merchantConvertPrice($user->affilate_income);
            $data['completed_purchases'] = (string) Auth::user()->purchases()->where('status', 'completed')->count();
            $data['pending_purchases'] = (string) Auth::user()->purchases()->where('status', 'pending')->count();
            $data['recent_purchases'] = (string) Auth::user()->purchases()->latest()->take(5)->get();
            return response()->json(['status' => true, 'data' => $data, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        try {
            //--- Validation Section

            $rules =
                [
                'name' => 'required',
                'email' => 'required|email|unique:users,email,' . auth()->user()->id,
                'phone' => 'required',
                'fax' => 'required',
                'city' => 'required',
                'country' => 'required',
                'zip' => 'required',
                'address' => 'required',
                'photo' => 'mimes:jpeg,jpg,png,svg',

            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            //--- Validation Section Ends
            $input = $request->all();
            $data = auth()->user();
            if ($file = $request->file('photo')) {
                $name = time() . $file->getClientOriginalName();
                $file->move('assets/images/users/', $name);
                if ($data->photo != null) {
                    if (file_exists(public_path() . '/assets/images/users/' . $data->photo)) {
                        unlink(public_path() . '/assets/images/users/' . $data->photo);
                    }
                }
                $input['photo'] = $name;
            }

            if ($request->shop_name) {
                unset($input['shop_name']);
            }

            if ($request->balance) {
                unset($input['balance']);
            }

            if ($request->is_merchant) {
                unset($input['is_merchant']);
            }

            if ($request->email) {
                unset($input['email']);
            }

            if ($request->ban) {
                unset($input['ban']);
            }

            if ($request->mail_sent) {
                unset($input['mail_sent']);
            }

            if ($request->date) {
                unset($input['date']);
            }

            $data->update($input);

            return response()->json(['status' => true, 'data' => new UserResource($data), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function updatePassword(Request $request)
    {

        $rules =
            [
            'current_password' => 'required',
            'new_password' => 'required',
            'renew_password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
        }

        try {
            $user = auth()->user();
            if (Hash::check($request->current_password, $user->password)) {
                if ($request->new_password == $request->renew_password) {
                    $input['password'] = Hash::make($request->new_password);
                } else {
                    return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'Confirm password does not match.']]);
                }
            } else {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'Current password Does not match.']]);
            }
            $user->update($input);
            return response()->json(['status' => true, 'data' => ['message' => 'Successfully changed your password.'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function favorite(Request $request)
    {
        try {
            $input = $request->all();
            $user = Auth::guard('api')->user();
            $ck = FavoriteSeller::where('user_id', $user->id)->where('merchant_item_id', $input['merchant_item_id'])->exists();
            if (!$ck) {
                $fav = new FavoriteSeller();
                $fav->user_id = $user->id;
                $fav->merchant_item_id = $input['merchant_item_id'];
                // Get catalog_item_id from MerchantItem
                $merchantItem = \App\Domain\Merchant\Models\MerchantItem::find($input['merchant_item_id']);
                if ($merchantItem) {
                    $fav->catalog_item_id = $merchantItem->catalog_item_id;
                }
                $fav->save();
                return response()->json(['status' => true, 'data' => ['message' => 'Successfully Added To Favorite Seller.'], 'error' => []]);
            } else {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'Added To Favorite Already.']]);
            }

        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function favorites()
    {

        try {
            $user = Auth::guard('api')->user();
            $favorites = FavoriteSeller::where('user_id', '=', $user->id)
                ->with(['merchantItem.user'])
                ->get();
            $merchants = array();
            foreach ($favorites as $key => $favorite) {
                $seller = $favorite->merchantItem?->user;
                if ($seller) {
                    $merchants[$key]['id'] = $favorite->id;
                    $merchants[$key]['shop_id'] = $seller->id;
                    $merchants[$key]['shop_name'] = $seller->shop_name;
                    $merchants[$key]['owner_name'] = $seller->owner_name;
                    $merchants[$key]['shop_address'] = $seller->shop_address;
                    $merchants[$key]['shop_link'] = route('front.merchant', str_replace(' ', '-', ($seller->shop_name)));
                }
            }
            return response()->json(['status' => true, 'data' => $merchants, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function favdelete($id)
    {
        try {
            $wish = FavoriteSeller::find($id);
            $wish->delete();
            return response()->json(['status' => true, 'data' => ['message' => 'Successfully Removed The Seller.'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
