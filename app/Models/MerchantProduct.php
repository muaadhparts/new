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
        'brand_quality_id',
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
        'color_all',
        'color_price',
        'details',
        'policy',
        'features',
        'colors',
        'size',
        'size_qty',
        'size_price'
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

    public function qualityBrand(): BelongsTo
    {
        return $this->belongsTo(QualityBrand::class, 'brand_quality_id');
    }

    /**
     * احسب السعر النهائي لعرض البائع مع إضافة فرق المقاس والخصائص والعمولات.
     */
    // public function vendorSizePrice(): float
    // {
    //     // dd(['base' => $this->price, 'size_price' => $this->size_price]); // فحص سريع (معلّق حسب قاعدتك)

    //     $price = (float) ($this->price ?? 0);

    //     // فرق المقاس (أخذ أول قيمة إن وُجدت)
    //     $sizeAddon = 0.0;
    //     if (!empty($this->size_price)) {
    //         $raw = $this->size_price;
    //         if (is_string($raw)) {
    //             $decoded = json_decode($raw, true);
    //             if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    //                 $first = array_values($decoded)[0] ?? 0;
    //                 $sizeAddon = (float) $first;
    //             } else {
    //                 // صيغة نصية مفصولة بفواصل
    //                 $parts = explode(',', $raw);
    //                 $sizeAddon = isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
    //             }
    //         } elseif (is_array($raw)) {
    //             $first = array_values($raw)[0] ?? 0;
    //             $sizeAddon = (float) $first;
    //         }
    //     }
    //     $price += $sizeAddon;

    //     // TODO: إضافة أسعار الخصائص المفعّلة (details_status=1) لو مخزنة على مستوى عرض التاجر
    //     $optsTotal = 0.0;
    //     // $optsTotal = ...;
    //     $price += $optsTotal;

    //     // عمولة المنصّة: ثابتة + نسبة
    //     $gs = cache()->remember('generalsettings_for_commission', now()->addHours(12), function () {
    //         return DB::table('generalsettings')->first(['fixed_commission', 'percentage_commission']);
    //     });

    //     $fixed   = isset($gs->fixed_commission) ? (float) $gs->fixed_commission : 0.0;
    //     $percent = isset($gs->percentage_commission) ? (float) $gs->percentage_commission : 0.0;

    //     $price += $fixed;
    //     if ($percent !== 0.0) {
    //         $price += ($price * $percent / 100);
    //     }

    //     // dd(['mp_id' => $this->id, 'final' => $price, 'fixed' => $fixed, 'percent' => $percent]); // فحص سريع (معلّق)
    //     return round($price, 2);
    // }
    
    public function vendorSizePrice()
    {
        // السعر الأساسي = سعر عرض البائع + أي زيادات (مقاسات/خيارات) إن وُجدت وكانت رقمية
        $base = (float) ($this->price ?? 0);

        if (!empty($this->size_price) && is_numeric($this->size_price)) {
            $base += (float) $this->size_price;
        }

        // إلغاء العمولة عندما يكون السعر الأساسي صفرًا أو أقل
        if ($base <= 0) {
            // dd(['mp_id' => $this->id, 'base' => $base]); // فحص سريع عند الحاجة
            return 0.0;
        }

        // إضافة عمولة المنصّة (ثابتة + نسبة) على السعر الأساسي - مع cache
        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());

        $final = $base;

        if ($gs) {
            $fixed    = (float) ($gs->fixed_commission ?? 0);
            $percent  = (float) ($gs->percentage_commission ?? 0);

            if ($fixed > 0) {
                $final += $fixed;
            }
            if ($percent > 0) {
                $final += $base * ($percent / 100);
            }
        }

        return round($final, 2);
    }

    /**
     * أعِد السعر بتنسيق العملة الحالية.
     */
    public function showPrice(): string
    {
        $final = $this->vendorSizePrice();
        return Product::convertPrice($final);
    }

    /**
     * احسب نسبة الخصم بين السعر السابق والسعر الحالي
     */
    public function offPercentage(): float
    {
        if (!$this->previous_price || $this->previous_price <= 0) {
            return 0;
        }

        $current = $this->vendorSizePrice();
        if ($current === null || $current <= 0) {
            return 0;
        }

        // Build previous final price similar to current price
        $prev = (float) $this->previous_price;

        // Add size price to previous price if exists
        if (!empty($this->size_price)) {
            $raw = $this->size_price;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $first = array_values($decoded)[0] ?? 0;
                    $prev += (float) $first;
                } else {
                    $parts = explode(',', $raw);
                    $prev += isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
                }
            } elseif (is_array($raw)) {
                $first = array_values($raw)[0] ?? 0;
                $prev += (float) $first;
            }
        }

        // Add commission to previous price
        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $prev = $prev + (float) $gs->fixed_commission + ($prev * (float) $gs->percentage_commission / 100);

        if ($prev <= 0) {
            return 0;
        }

        $percentage = ((float) $prev - (float) $current) * 100 / (float) $prev;
        return round($percentage, 2);
    }

    /**
     * Get color list as array
     */
    public function getColorAllAttribute($value)
    {
        return $value === null ? [] : (is_array($value) ? $value : explode(',', $value));
    }

    /**
     * Get color prices as array
     */
    public function getColorPriceAttribute($value)
    {
        return $value === null ? [] : (is_array($value) ? $value : explode(',', $value));
    }

    /**
     * Get size as array
     */
    public function getSizeAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    /**
     * Get size qty as array
     */
    public function getSizeQtyAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    /**
     * Get size price as array
     */
    public function getSizePriceAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

}