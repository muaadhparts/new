<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MerchantProduct extends Model
{
    protected $table = 'merchant_products';


    protected $fillable = [
        'product_id',
        'user_id',
        'price',
        'previous_price',
        'stock',
        'is_discount',
        'discount_date',
        'whole_sell_qty',
        'whole_sell_discount',
        'preordered',
        'minimum_qty',
        'stock_check',
        'popular',
        'status',
        'is_popular',
        'licence_type',
        'license_qty',
        'license',
        'ship',
        'product_condition',
        'size_price',
        'size_qty',
        'size',
        'color_all',
        'size_all',
    ];

    /**
     * Get the underlying product definition for this merchant product.
     */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * احسب السعر النهائي لعرض البائع مع إضافة فرق المقاس والخصائص والعمولات.
     */
    public function vendorSizePrice(): float
    {
        // dd(['base' => $this->price, 'size_price' => $this->size_price]); // فحص سريع (معلّق حسب قاعدتك)

        $price = (float) ($this->price ?? 0);

        // فرق المقاس (أخذ أول قيمة إن وُجدت)
        $sizeAddon = 0.0;
        if (!empty($this->size_price)) {
            $raw = $this->size_price;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $first = array_values($decoded)[0] ?? 0;
                    $sizeAddon = (float) $first;
                } else {
                    // صيغة نصية مفصولة بفواصل
                    $parts = explode(',', $raw);
                    $sizeAddon = isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
                }
            } elseif (is_array($raw)) {
                $first = array_values($raw)[0] ?? 0;
                $sizeAddon = (float) $first;
            }
        }
        $price += $sizeAddon;

        // TODO: إضافة أسعار الخصائص المفعّلة (details_status=1) لو مخزنة على مستوى عرض التاجر
        $optsTotal = 0.0;
        // $optsTotal = ...;
        $price += $optsTotal;

        // عمولة المنصّة: ثابتة + نسبة
        $gs = cache()->remember('generalsettings_for_commission', now()->addHours(12), function () {
            return DB::table('generalsettings')->first(['fixed_commission', 'percentage_commission']);
        });

        $fixed   = isset($gs->fixed_commission) ? (float) $gs->fixed_commission : 0.0;
        $percent = isset($gs->percentage_commission) ? (float) $gs->percentage_commission : 0.0;

        $price += $fixed;
        if ($percent !== 0.0) {
            $price += ($price * $percent / 100);
        }

        // dd(['mp_id' => $this->id, 'final' => $price, 'fixed' => $fixed, 'percent' => $percent]); // فحص سريع (معلّق)
        return round($price, 2);
    }

    /**
     * أعِد السعر بتنسيق العملة الحالية.
     */
    public function showPrice(): string
    {
        $final = $this->vendorSizePrice();
        return Product::convertPrice($final);
    }
}