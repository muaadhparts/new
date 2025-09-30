@if(isset($productt,$vendorId,$merchant) && $vendorId && $merchant)
  <link rel="canonical" href="{{ route('front.product', ['slug'=>$productt->slug, 'vendor_id'=>$vendorId, 'merchant_product_id'=>$merchant->id]) }}">
@endif
