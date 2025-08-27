@if ($gs->theme == 'theme1')
    @include('frontend.theme.home1')
@elseif ($gs->theme == 'theme2')
    @include('frontend.theme.home2')
@elseif ($gs->theme == 'theme3')
    @include('frontend.theme.home3')
@else
    @include('frontend.theme.home4')
@endif
