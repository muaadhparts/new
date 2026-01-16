<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MerchantPhotoResource;
use App\Http\Resources\CatalogReviewResource;
use App\Http\Resources\BuyerNoteResource;

class CatalogItemDetailsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    // // dd(['merchantId' => $request->get('user'), 'catalog_item_id' => $this->id]); // debug

    // 1) التعرّف على التاجر (merchantId) من كويري سترنج ?user= أو من حقل حقناه مسبقًا على المنتج (merchant_user_id)
    $merchantId = (int) ($request->get('user') ?? $this->getAttribute('merchant_user_id') ?? 0);

    // 2) جلب عرض التاجر الفعّال من الـ eager-loaded collection (لتفادي N+1)
    //    يجب عمل eager load للـ merchantItems قبل استخدام هذا الـ Resource
    $mp = $this->merchantItems
      ->filter(fn($mi) => $mi->status == 1)
      ->when($merchantId, fn($col) => $col->where('user_id', $merchantId))
      ->sortBy('price')
      ->first();

    // 3) أسعار الـ API: تعتمد على دوال الـ CatalogItem الداعمة للبائع
    $currentPrice  = method_exists($this, 'ApishowDetailsPrice')
        ? (string) $this->ApishowDetailsPrice($merchantId ?: null)
        : (string) $this->ApishowPrice();

    $previousPrice = method_exists($this, 'ApishowPreviousPrice')
        ? (string) $this->ApishowPreviousPrice($merchantId ?: null)
        : (string) 0;

    // 4) اسم المتجر/عدد العناصر: من الـ eager-loaded user مع merchant_items_count
    $shopName = null;
    $shopCount = null;
    if ($mp && $mp->user) {
      $shopName  = $mp->user->shop_name;
      $shopCount = $mp->user->merchant_items_count . ' items';
    }

    return [
      // هوية المنتج (هوية فقط من catalog_items)
      'id'            => $this->id,
      'catalog_item_id'    => $this->id,
      'user_id'       => $mp ? $mp->user_id : ($this->user_id ?? 0), // إبقاء الحقل للتوافق، لكن يُفضّل اعتماد merchant.user_id
      'name'         => $this->name,
      'slug'          => $this->slug,
      'part_number'           => $this->part_number,
      'attributes'    => $this->attributes ? json_decode($this->attributes, true) : null,

      // صور
      'thumbnail'     => \Illuminate\Support\Facades\Storage::url($this->thumbnail) ?? asset('assets/images/noimage.png'),
      'first_image'   => \Illuminate\Support\Facades\Storage::url($this->photo) ?? asset('assets/images/noimage.png'),
      'images'        => MerchantPhotoResource::collection($this->whenLoaded('merchantPhotos', $this->merchantPhotos)),

      // تقييم - Use pre-loaded aggregate from withAvg('catalogReviews', 'rating')
      'rating'        => $this->catalog_reviews_avg_rating > 0
                          ? (string) round($this->catalog_reviews_avg_rating, 2)
                          : (string) '0.00',

      // أسعار مع دعم البائع
      'current_price'  => $currentPrice,
      'previous_price' => $previousPrice,

      // مخزون/حالة/شحن/مقاسات من عرض البائع (إن وُجد)، وإلا fallback لهوية المنتج
      'stock'          => $mp ? (int) $mp->stock : 0,
      'condition'      => $mp && $mp->item_condition
                            ? ($mp->item_condition == 2 ? 'New' : 'Used')
                            : null,
      'video'          => $this->youtube,
      'stock_check'    => $mp ? $mp->stock_check : 0,
      'estimated_shipping_time' => $mp ? $mp->ship : null,

      'colors'         => $mp ? $mp->color_all : [],     // Colors from merchant_items only
      'color_prices'   => $mp ? $mp->color_price : [],   // Color prices from merchant_items
      'sizes'          => $this->size,                   // Sizes from catalog_items (fixed at item level)
      'size_quantity'  => $this->size_qty,               // Size quantities from catalog_items
      'size_price'     => $this->size_price,             // Size prices from catalog_items

      'details'        => $mp && !empty($mp->details) ? strip_tags($mp->details) : strip_tags($this->policy),
      'policy'         => $mp && !empty($mp->policy) ? strip_tags($mp->policy) : strip_tags($this->policy),
      'features'       => $mp && !empty($mp->features) ? $mp->features : $this->features,
      'whole_sell_quantity' => $mp ? $mp->whole_sell_qty : null,
      'whole_sell_discount' => $mp ? $mp->whole_sell_discount : null,

      // علاقات
      'testimonials'        => CatalogReviewResource::collection($this->whenLoaded('catalogReviews', $this->catalogReviews)),
      'buyer_notes'       => BuyerNoteResource::collection($this->whenLoaded('buyerNotes', $this->buyerNotes)),

      // منتجات ذات صلة (تبقى كما لديك إن رغبت لاحقًا بتقييدها على بائع معيّن ممكن نحدثها)
      // ملاحظة: في نسختك القديمة كانت: category->catalog_items()->where('status',1)->where('id','!=',$this->id)->take(8)
      // يمكن إبقاؤها كما هي أو تقييدها لاحقًا على merchant_items.status=1.

      'shop' => [
        'name'  => $shopName,
        'items' => $shopCount, // قد تكون null إن لم يُحمّل user
      ],

      // معلومات التاجر المستخدمة
      'merchant' => $mp ? [
        'user_id'          => $mp->user_id,
        'merchant_item_id' => $mp->id,
        'status'           => (int) $mp->status,
      ] : null,

      'created_at'     => $this->created_at,
      'updated_at'     => $this->updated_at,
    ];
  }
}
