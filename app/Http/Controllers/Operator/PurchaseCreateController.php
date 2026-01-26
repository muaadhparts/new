<?php

namespace App\Http\Controllers\Operator;

use App\Classes\MuaadhMailer;
use App\Helpers\PurchaseHelper;
use App\Http\Controllers\Controller;
// MerchantCart removed - operator cart methods need rewrite
use App\Domain\Shipping\Models\Country;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Platform\Models\FrontendSetting;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PurchaseCreateController extends OperatorBaseController
{
    public function create(Request $request)
    {
        // Handle pre-selected catalog items from request
        if ($request->catalogItems) {
            $selectd_products = $request->catalogItems;
            foreach ($selectd_products as $itemId) {
                $catalogItems[] = CatalogItem::findOrFail($itemId);
            }
        } else {
            $selectd_products = [];
            $catalogItems = [];
        }

        $sign = $this->curr;
        Session::forget('purchase_products');

        // Pre-computed data for views
        $users = User::where('status', '!=', 2)->get(['id', 'name', 'phone']);
        // Country selection depends on whether session has saved address
        $selectedCountry = Session::has('order_address') ? Session::get('order_address')['customer_country'] : null;
        $countriesHtml = \App\Helpers\LocationHelper::getCountriesOptionsHtml($selectedCountry);

        return view('operator.purchase.create.index', compact('catalogItems', 'selectd_products', 'sign', 'users', 'countriesHtml'));
    }

    public function datatables()
    {
        // Query merchant items directly - each merchant item = independent row
        $datas = \App\Domain\Merchant\Models\MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])
            ->where('status', 1)
            ->whereHas('catalogItem', function($q) {
                $q->where('status', 1);
            });

        return Datatables::of($datas)
            ->addColumn('name', function (\App\Domain\Merchant\Models\MerchantItem $mi) {
                $catalogItem = $mi->catalogItem;
                if (!$catalogItem) return __('N/A');

                // Price from merchant_items with commission (per-merchant)
                $finalPrice = $mi->merchantSizePrice() * $this->curr->value;

                $photoUrl = filter_var($catalogItem->photo, FILTER_VALIDATE_URL)
                    ? $catalogItem->photo
                    : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png'));
                $img = '<img src="' . $photoUrl . '" alt="Image" class="img-thumbnail" width="100"> <br>';
                $name = getLocalizedCatalogItemName($catalogItem, 50);

                // Merchant info
                $merchantInfo = $mi->user ? '<span class="badge badge-info">' . ($mi->user->shop_name ?: $mi->user->name) . '</span>' : '';

                // Item condition (new/used)
                $condition = $mi->item_condition == 1 ? ' <span class="badge badge-warning">' . __('Used') . '</span>' : '';

                // Stock
                $stock = $mi->stock === null ? __('Unlimited') : (($mi->stock > 0) ? $mi->stock : '<span class="text-danger">' . __('Out Of Stock') . '</span>');

                return $img . $name . $condition . '<br>' . $merchantInfo . '<br><small>' . __("Price") . ': ' . number_format($finalPrice, 2) . ' ' . $this->curr->sign . '</small><br><small>' . __("Stock") . ': ' . $stock . '</small>';
            })

            ->addColumn('action', function (\App\Domain\Merchant\Models\MerchantItem $mi) {
                // Use merchant_item_id instead of catalog_item_id
                return '<div class="action-list"><a href="javascript:;" class="purchase_product_add" data-bs-toggle="modal" class="add-btn-small pl-2" data-bs-target="#add-catalogItem" data-href="' . $mi->id . '" data-catalog-item-id="' . $mi->catalog_item_id . '"> <i class="fas fa-plus"></i></a></div>';
            })

            ->rawColumns(['name', 'action'])
            ->toJson();
    }


    public function addCatalogItem($catalogItem_id)
    {

        $purchase_catalogItems = Session::get('purchase_catalogItems');
        if (!$purchase_catalogItems) {
            $purchase_catalogItems = [];
        }
        if (!in_array($catalogItem_id, $purchase_catalogItems)) {
            $purchase_catalogItems[] = $catalogItem_id;
        }

        Session::put('purchase_catalogItems', $purchase_catalogItems);

        $sign = $this->curr;
        return view('operator.purchase.partials.catalogItem_add_table', compact('sign'));
    }


    public function purchaseStore(Request $request)
    {
        // TODO: Implement purchase store logic
        return redirect()->back()->with('error', __('Feature not implemented yet.'));
    }


    public function removePurchaseCatalogItem($catalogItem_id)
    {
        $items = Session::get('purchase_catalogItems');
        foreach ($items as $key => $item) {
            if ($item == $catalogItem_id) {
                unset($items[$key]);
            }
        }
        $sign = $this->curr;
        if ($items) {
            Session::put('purchase_catalogItems', $items);
        } else {
            Session::forget('purchase_catalogItems');
        }

        return view('operator.purchase.partials.catalogItem_add_table', compact('sign'));
    }


    // Show catalog item details for adding to purchase
    public function catalogItem_show($id)
    {
        $data['catalogItem'] = CatalogItem::find($id);
        $data['curr'] = $this->curr;
        return view('operator.purchase.create.add-catalogItem', $data);
    }



    public function userAddress(Request $request)
    {
        Session::forget('purchase_address');

        if ($request->user_id == 'guest') {
            $isUser = 0;
            $countriesHtml = \App\Helpers\LocationHelper::getCountriesOptionsHtml();
            return view('operator.purchase.create.address_form', compact('countriesHtml', 'isUser'));
        } else {
            $isUser = 1;
            $user = User::findOrFail($request->user_id);
            $countriesHtml = \App\Helpers\LocationHelper::getCountriesOptionsHtml($user->country);
            return view('operator.purchase.create.address_form', compact('user', 'countriesHtml', 'isUser'));
        }
    }


    public function userAddressSubmit(Request $request)
    {
        Session::put('purchase_address', $request->all());
        return back();
    }


    public function viewCreatePurchase(Request $request)
    {

        Session::put('purchase_address', $request->all());

        $cart = Session::get('admin_cart');
        $address = Session::get('purchase_address');

        return view('operator.purchase.create.view', compact('cart', 'address'));
    }


}
