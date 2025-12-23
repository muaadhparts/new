{{--
================================================================================
SECTION PARTIAL: Newsletter
================================================================================
Simple newsletter subscription form
================================================================================
--}}

<div class="muaadh-newsletter-card">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <div class="muaadh-newsletter-content">
                <h3 class="muaadh-newsletter-title">@lang('Subscribe to Our Newsletter')</h3>
                <p class="muaadh-newsletter-text">
                    @lang('Get the latest updates on new products and upcoming sales')
                </p>
            </div>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('front.subscribe') }}" method="POST" class="muaadh-newsletter-form">
                @csrf
                <div class="muaadh-newsletter-input-group">
                    <input type="email"
                           name="email"
                           class="muaadh-newsletter-input"
                           placeholder="@lang('Enter your email')"
                           required>
                    <button type="submit" class="muaadh-newsletter-btn">
                        @lang('Subscribe')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
