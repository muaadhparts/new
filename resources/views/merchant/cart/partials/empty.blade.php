{{--
    Empty Cart State
    Used when cart has no items
--}}
<div class="m-cart__empty">
    <div class="m-cart__empty-icon">
        <i class="fas fa-shopping-cart"></i>
    </div>
    <h3>@lang('Your cart is empty')</h3>
    <p>@lang('Looks like you haven\'t added any items yet')</p>
    <a href="{{ route('front.index') }}" class="m-btn m-btn--primary">
        <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
        @lang('Continue Shopping')
    </a>
</div>
