<?php

namespace App\Domain\Commerce\ViewComposers;

use Illuminate\View\View;
use App\Domain\Shipping\Models\City;
use App\Domain\Commerce\Models\MerchantPayment;
use Illuminate\Support\Facades\Cache;

/**
 * Checkout Composer
 *
 * Provides checkout form data to views.
 */
class CheckoutComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $cities = Cache::remember('active_cities', 3600, function () {
            return City::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar']);
        });

        $paymentMethods = Cache::remember('payment_methods', 3600, function () {
            return [
                ['code' => 'cod', 'name' => 'Cash on Delivery', 'name_ar' => 'الدفع عند الاستلام'],
                ['code' => 'online', 'name' => 'Online Payment', 'name_ar' => 'الدفع الإلكتروني'],
                ['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'name_ar' => 'تحويل بنكي'],
            ];
        });

        $view->with([
            'cities' => $cities,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
