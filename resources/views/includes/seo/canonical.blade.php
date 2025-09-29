@if(isset($productt,$vendorId) && $vendorId)
  <link rel="canonical" href="{{ route('front.product.user', ['slug'=>$productt->slug, 'user'=>$vendorId]) }}">
@endif
