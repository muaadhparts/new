@if ($gs->theme == 'muaadh_oem')
    @include('frontend.theme.home1')
@elseif ($gs->theme == 'muaadh_storefront')
    @include('frontend.theme.home2')
@elseif ($gs->theme == 'muaadh_minimal')
    @include('frontend.theme.home3')
@else
    @include('frontend.theme.home4')
@endif
