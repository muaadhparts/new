<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MonetaryUnit;

/**
 * MerchantPayment Model - Payment gateways for merchants
 *
 * Domain: Merchant
 * Table: merchant_payments
 *
 * @property int $id
 * @property int $user_id
 * @property int $operator
 * @property string $name
 * @property string|null $title
 * @property string|null $keyword
 * @property int $status
 */
class MerchantPayment extends Model
{
    protected $table = 'merchant_payments';

    protected $fillable = [
        'user_id',
        'operator',
        'name',
        'title',
        'subtitle',
        'subname',
        'type',
        'information',
        'keyword',
        'monetary_unit_id',
        'checkout',
        'topup',
        'deposit',
        'subscription',
        'status',
    ];

    public $timestamps = false;

    // =========================================================
    // RELATIONS
    // =========================================================

    public function monetaryUnit(): BelongsTo
    {
        return $this->belongsTo(MonetaryUnit::class, 'currency_id')->withDefault();
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query
            ->where('status', 1)
            ->where(function ($q) use ($merchantId) {
                $q->where('user_id', $merchantId)
                ->orWhere(function ($q2) use ($merchantId) {
                    $q2->where('user_id', 0)
                       ->where('operator', $merchantId);
                });
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
    }

    public function scopePlatformOnly(Builder $query): Builder
    {
        return $query->where('user_id', 0);
    }

    // =========================================================
    // OWNERSHIP METHODS
    // =========================================================

    public function isPlatformOwned(): bool
    {
        return $this->user_id === 0 || $this->user_id === null;
    }

    public function isMerchantOwned(int $merchantId): bool
    {
        return $this->user_id > 0 && $this->user_id === $merchantId;
    }

    public function isEnabledForMerchant(int $merchantId): bool
    {
        if ($this->user_id === $merchantId) {
            return true;
        }

        if ($this->user_id === 0 && $this->operator === $merchantId) {
            return true;
        }

        return false;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    public static function scopeHasGateway($curr)
    {
        return self::where('currency_id', 'like', "%\"{$curr}\"%")->orwhere('currency_id', '*')->get();
    }

    public function convertAutoData(): array
    {
        return $this->information ? json_decode($this->information, true) : [];
    }

    public function getAutoDataText()
    {
        $text = $this->convertAutoData();
        return end($text);
    }

    public function showKeyword(): string
    {
        return $this->keyword ?? 'other';
    }

    public function showCheckoutLink($merchantId = null): string
    {
        $data = $this->keyword ?? 'other';

        if ($merchantId) {
            $routeMap = [
                'myfatoorah'    => 'merchant.payment.myfatoorah.process',
                'paypal'        => 'merchant.payment.paypal.process',
                'stripe'        => 'merchant.payment.stripe.process',
                'instamojo'     => 'merchant.payment.instamojo.process',
                'paystack'      => 'merchant.payment.paystack.process',
                'paytm'         => 'merchant.payment.paytm.process',
                'mollie'        => 'merchant.payment.mollie.process',
                'razorpay'      => 'merchant.payment.razorpay.process',
                'authorize.net' => 'merchant.payment.authorize.process',
                'mercadopago'   => 'merchant.payment.mercadopago.process',
                'flutterwave'   => 'merchant.payment.flutterwave.process',
                'sslcommerz'    => 'merchant.payment.sslcommerz.process',
                'voguepay'      => 'merchant.payment.voguepay.process',
                'cod'           => 'merchant.payment.cod.process',
            ];

            $routeName = $routeMap[$data] ?? 'merchant.payment.manual.process';
            return route($routeName, ['merchantId' => $merchantId]);
        }

        // Legacy routes
        $routeMap = [
            'myfatoorah' => 'front.myfatoorah.submit',
            'paypal' => 'front.paypal.submit',
            'stripe' => 'front.stripe.submit',
            'instamojo' => 'front.instamojo.submit',
            'paystack' => 'front.paystack.submit',
            'paytm' => 'front.paytm.submit',
            'mollie' => 'front.molly.submit',
            'razorpay' => 'front.razorpay.submit',
            'authorize.net' => 'front.authorize.submit',
            'mercadopago' => 'front.mercadopago.submit',
            'flutterwave' => 'front.flutter.submit',
            '2checkout' => 'front.twocheckout.submit',
            'sslcommerz' => 'front.ssl.submit',
            'voguepay' => 'front.voguepay.submit',
            'cod' => 'front.cod.submit',
        ];

        return route($routeMap[$data] ?? 'front.manual.submit');
    }

    public function showForm(): string
    {
        $data = $this->keyword ?? 'other';
        $noFormGateways = ['myfatoorah', 'cod', 'voguepay', 'sslcommerz', 'flutterwave', 'razorpay', 'mollie', 'paytm', 'paystack', 'paypal', 'instamojo', 'stripe'];
        return in_array($data, $noFormGateways) ? 'no' : 'yes';
    }
}
