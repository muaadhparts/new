<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\GalleryResource;
use App\Http\Resources\RatingResource;
use App\Http\Resources\CommentResource;
use App\Models\Admin;

class ProductDetailsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    // // dd(['vendorId' => $request->get('user'), 'product_id' => $this->id]); // debug

    // 1) التعرّف على البائع (vendorId) من كويري سترنج ?user= أو من حقل حقناه مسبقًا على المنتج (vendor_user_id)
    $vendorId = (int) ($request->get('user') ?? $this->getAttribute('vendor_user_id') ?? 0);

    // 2) جلب عرض البائع الفعّال عبر دالّة المنتج (مضافة لديك في الموديل)
    //    إن لم يُمرَّر vendorId سنأخذ أول عرض فعّال.
    $mp = method_exists($this, 'activeMerchant')
        ? $this->activeMerchant($vendorId ?: null)
        : null;

    // 3) أسعار الـ API: تعتمد على دوال الـ Product الداعمة للبائع
    $currentPrice  = method_exists($this, 'ApishowDetailsPrice')
        ? (string) $this->ApishowDetailsPrice($vendorId ?: null)
        : (string) $this->ApishowPrice();

    $previousPrice = method_exists($this, 'ApishowPreviousPrice')
        ? (string) $this->ApishowPreviousPrice($vendorId ?: null)
        : (string) 0;

    // 4) اسم المتجر/عدد العناصر: إن وُجد MP + علاقته بالمستخدم
    $shopName = null;
    $shopCount = null;
    if ($mp && $mp->relationLoaded('user') || ($mp && $mp->user)) {
      $shopName  = $mp->user->shop_name;
      // ملاحظة: يمكن لاحقًا تحسين الأداء بـ withCount على العلاقة بدل count() المباشر.
      $shopCount = $mp->user->merchantProducts()->count() . ' items';
    } else {
      $shopName = ($this->user_id != 0 && $this->relationLoaded('user'))
          ? $this->user->shop_name
          : Admin::first()->shop_name;
    }

    return [
      // هوية المنتج (هوية فقط من products)
      'id'            => $this->id,
      'product_id'    => $this->id,
      'user_id'       => $mp ? $mp->user_id : ($this->user_id ?? 0), // إبقاء الحقل للتوافق، لكن يُفضّل اعتماد vendor.user_id
      'title'         => $this->name,
      'slug'          => $this->slug,
      'sku'           => $this->sku,
      'type'          => $this->type,
      'attributes'    => $this->attributes ? json_decode($this->attributes, true) : null,

      // صور
      'thumbnail'     => \Illuminate\Support\Facades\Storage::url($this->thumbnail) ?? asset('assets/images/noimage.png'),
      'first_image'   => \Illuminate\Support\Facades\Storage::url($this->photo) ?? asset('assets/images/noimage.png'),
      'images'        => GalleryResource::collection($this->whenLoaded('galleries', $this->galleries)),

      // تقييم
      'rating'        => $this->ratings()->avg('rating') > 0
                          ? (string) round($this->ratings()->avg('rating'), 2)
                          : (string) '0.00',

      // أسعار مع دعم البائع
      'current_price'  => $currentPrice,
      'previous_price' => $previousPrice,

      // مخزون/حالة/شحن/مقاسات من عرض البائع (إن وُجد)، وإلا fallback لهوية المنتج
      'stock'          => $mp ? (int) $mp->stock : 0,
      'condition'      => $mp && $mp->product_condition
                            ? ($mp->product_condition == 2 ? 'New' : 'Used')
                            : null,
      'video'          => $this->youtube,
      'stock_check'    => $mp ? $mp->stock_check : $this->stock_check,
      'estimated_shipping_time' => $mp ? $mp->ship : $this->ship,

      'colors'         => $this->color,            // بقيت بهوية المنتج (اللون العام)
      'sizes'          => $mp ? $mp->size : $this->size,
      'size_quantity'  => $mp ? $mp->size_qty : $this->size_qty,
      'size_price'     => $mp ? $mp->size_price : $this->size_price,

      'details'        => strip_tags($this->details),
      'policy'         => strip_tags($this->policy),
      'whole_sell_quantity' => $mp ? $mp->whole_sell_qty : $this->whole_sell_qty,
      'whole_sell_discount' => $mp ? $mp->whole_sell_discount : $this->whole_sell_discount,

      // علاقات
      'reviews'        => RatingResource::collection($this->whenLoaded('ratings', $this->ratings)),
      'comments'       => CommentResource::collection($this->whenLoaded('comments', $this->comments)),

      // منتجات ذات صلة (تبقى كما لديك إن رغبت لاحقًا بتقييدها على بائع معيّن ممكن نحدثها)
      // ملاحظة: في نسختك القديمة كانت: category->products()->where('status',1)->where('id','!=',$this->id)->take(8)
      // يمكن إبقاؤها كما هي أو تقييدها لاحقًا على merchant_products.status=1.

      'shop' => [
        'name'  => $shopName,
        'items' => $shopCount, // قد تكون null إن لم يُحمّل user
      ],

      // معلومات البائع المستخدمة
      'vendor' => $mp ? [
        'user_id'             => $mp->user_id,
        'merchant_product_id' => $mp->id,
        'status'              => (int) $mp->status,
      ] : null,

      'created_at'     => $this->created_at,
      'updated_at'     => $this->updated_at,
    ];
  }
}
