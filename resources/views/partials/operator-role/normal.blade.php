@if(Auth::guard('operator')->user()->role_id != 0)

@if(Auth::guard('operator')->user()->sectionCheck('purchases'))

<li>
    <a href="#purchase" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false"><i
            class="fas fa-hand-holding-usd"></i>{{ __('Purchases') }}</a>
    <ul class="collapse list-unstyled" id="purchase" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-purchases-all') }}"> {{ __('All Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('operator-purchases-all') }}?status=pending"> {{ __('Pending Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('operator-purchases-all') }}?status=processing"> {{ __('Processing Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('operator-purchases-all') }}?status=completed"> {{ __('Completed Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('operator-purchases-all') }}?status=declined"> {{ __('Declined Purchases') }}</a>
        </li>

    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('manage-country'))


<li>
    <a href="#menu1" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-flag"></i>{{ __('Manage Country') }}
    </a>
    <ul class="collapse list-unstyled" id="menu1" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-country-index') }}"><span>{{ __('Country') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-country-tax') }}"><span>{{ __('Manage Tax') }}</span></a>
        </li>
    </ul>
</li>


@if(Auth::guard('operator')->user()->sectionCheck('earning'))

<li>
    <a href="#income" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false"><i
            class="fas fa-hand-holding-usd"></i>{{ __('Total Earning') }}</a>
    <ul class="collapse list-unstyled" id="income" data-bs-parent="#accordion">
        <li>
            <a href="{{route('operator-tax-calculate-income')}}"> {{ __('Tax Calculate') }}</a>
        </li>

        <li>
            <a href="{{route('operator-withdraw-income')}}"> {{ __('Withdraw Earning') }}</a>
        </li>

        <li>
            <a href="{{route('operator-commission-income')}}"> {{ __('Commission Earning') }}</a>
        </li>

    </ul>
</li>
@endif

@endif

{{-- Old Category menu removed - using TreeCategories system --}}

@if(Auth::guard('operator')->user()->sectionCheck('catalogItems'))

<li>
    <a href="#menu2" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-cart"></i>{{ __('CatalogItems') }}
    </a>
    <ul class="collapse list-unstyled" id="menu2" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-catalog-item-types') }}"><span>{{ __('Add New CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-catalog-item-index') }}"><span>{{ __('All CatalogItems') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-catalog-item-deactive') }}"><span>{{ __('Deactivated CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-catalog-item-catalog-index') }}"><span>{{ __('CatalogItem Catalogs') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-catalog-item-settings') }}"><span>{{ __('CatalogItem Settings') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('affilate_products'))

<li>
    <a href="#affiliateprod" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-opencart"></i>{{ __('Affiliate CatalogItems') }}
    </a>
    <ul class="collapse list-unstyled" id="affiliateprod" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-import-create') }}"><span>{{ __('Add Affiliate CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-import-index') }}"><span>{{ __('All Affiliate CatalogItems') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('bulk_product_upload'))

<li>
    <a href="{{ route('operator-catalog-item-import') }}"><i class="fas fa-upload"></i>{{ __('Bulk CatalogItem Upload') }}</a>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('product_discussion'))

<li>
    <a href="#menu4" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-speech-comments"></i>{{ __('CatalogItem Discussion') }}
    </a>
    <ul class="collapse list-unstyled" id="menu4" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-catalog-review-index') }}"><span>{{ __('CatalogItem Reviews') }}</span></a>
        </li>
     
        <li>
            <a href="{{ route('operator-abuse-flag-index') }}"><span>{{ __('Reports') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('set_discount_codes'))

<li>
    <a href="{{ route('operator-discount-code-index') }}" class=" wave-effect"><i class="fas fa-percentage"></i>{{ __('Set
        Discount Codes') }}</a>
</li>

@endif


@if(Auth::guard('operator')->user()->sectionCheck('customers'))

<li>
    <a href="#menu3" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-user"></i>{{ __('Customers') }}
    </a>
    <ul class="collapse list-unstyled" id="menu3" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-user-index') }}"><span>{{ __('Customers List') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-withdraw-index') }}"><span>{{ __('Withdraws') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-user-image') }}"><span>{{ __('Customer Default Image') }}</span></a>
        </li>
    </ul>
</li>


@endif

@if(Auth::guard('operator')->user()->sectionCheck('customer_topups'))

<li>
    <a href="#customerTopUp" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-money"></i>{{ __('Customer Top Ups') }}
    </a>
    <ul class="collapse list-unstyled" id="customerTopUp" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-wallet-log-index') }}"><span>{{ __('Wallet Logs') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('merchants'))

<li>
    <a href="#merchant" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-ui-user-group"></i>{{ __('Merchants') }}
    </a>
    <ul class="collapse list-unstyled" id="merchant" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-merchant-index') }}"><span>{{ __('Merchants List') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-merchant-withdraw-index') }}"><span>{{ __('Withdraws') }}</span></a>
        </li>


    </ul>
</li>


@endif

@if(Auth::guard('operator')->user()->sectionCheck('merchant_verifications'))

<li>
    <a href="#merchantVerifications" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-verification-check"></i>{{ __('Merchant Verifications') }}
    </a>
    <ul class="collapse list-unstyled" id="merchantVerifications" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-vr-index','all') }}"><span>{{ __('All Verifications') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-vr-index','pending') }}"><span>{{ __('Pending Verifications') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('vendor_membership_plans'))

<li>
    <a href="{{ route('operator-merchant-commission-index') }}" class=" wave-effect"><i class="fas fa-percentage"></i>{{
        __('Merchant Commissions') }}</a>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('chat_entries'))

<li>
    <a href="#msg" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-fw fa-newspaper"></i>{{ __('Messages') }}
    </a>
    <ul class="collapse list-unstyled" id="msg" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-support-ticket-index') }}"><span>{{ __('Tickets') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-support-ticket-dispute') }}"><span>{{ __('Disputes') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('blog'))

<li>
    <a href="#blog" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-fw fa-newspaper"></i>{{ __('Blog') }}
    </a>
    <ul class="collapse list-unstyled" id="publication" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-article-type-index') }}"><span>{{ __('Categories') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-publication-index') }}"><span>{{ __('Posts') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-publication-settings') }}"><span>{{ __('Publication Settings') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('muaadh_settings'))

<li>
    <a href="#general" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-cogs"></i>{{ __('General Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="general" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-gs-logo') }}"><span>{{ __('Logo') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-fav') }}"><span>{{ __('Favicon') }}</span></a>
        </li>
 
        <li>
            <a href="{{ route('operator-shipping-index') }}"><span>{{ __('Shipping Methods') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-package-index') }}"><span>{{ __('Packagings') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-pick-index') }}"><span>{{ __('Pickup Locations') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-contents') }}"><span>{{ __('Website Contents') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-affilate') }}"><span>{{__('Affiliate Program')}}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-popup') }}"><span>{{ __('Popup Banner') }}</span></a>
        </li>
        {{-- Breadcrumb Banner removed - using modern minimal design --}}
        <li>
            <a href="{{ route('operator-gs-error-banner') }}"><span>{{ __('Error Banner') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-maintenance') }}"><span>{{ __('Website Maintenance') }}</span></a>
        </li>

    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('home_page_settings'))

<li>
    <a href="#homepage" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-edit"></i>{{ __('Home Page Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="homepage" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-sl-index') }}"><span>{{ __('Sliders') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-deal') }}"><span>{{ __('Deal of the day') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-best-sellers') }}"><span>{{ __('Best Sellers') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-top-rated') }}"><span>{{ __('Top Rated') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-big-save') }}"><span>{{ __('Big Save') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-trending') }}"><span>{{ __('Trending') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-featured') }}"><span>{{ __('Featured CatalogItems') }}</span></a>
        </li>

        <li>
            <a href="{{ route('operator-service-index') }}"><span>{{ __('Services') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-brand-index') }}"><span>{{ __('Brands') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-customize') }}"><span>{{ __('Home Page Customization') }}</span></a>
        </li>
    </ul>
</li>

@endif


@if(Auth::guard('operator')->user()->sectionCheck('menu_page_settings'))

<li>
    <a href="#menu" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-file-code"></i>{{ __('Menu Page Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="menu" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-help-article-index') }}"><span>{{ __('Help Article Page') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-fs-contact') }}"><span>{{ __('Contact Us Page') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-static-content-index') }}"><span>{{ __('Other Pages') }}</span></a>
        </li>

        <li>
            <a href="{{ route('operator-fs-menu-links') }}"><span>{{ __('Customize Menu Links') }}</span></a>
        </li>
    </ul>
</li>

@endif


@if(Auth::guard('operator')->user()->sectionCheck('emails_settings'))

<li>
    <a href="#emails" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-at"></i>{{ __('Email Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="emails" data-bs-parent="#accordion">
        <li><a href="{{ route('operator-mail-index') }}"><span>{{ __('Email Template') }}</span></a></li>
        <li><a href="{{ route('operator-mail-config') }}"><span>{{ __('Email Configurations') }}</span></a></li>
        <li><a href="{{ route('operator-group-show') }}"><span>{{ __('Group Email') }}</span></a></li>
    </ul>
</li>

@endif


@if(Auth::guard('operator')->user()->sectionCheck('payment_settings'))

<li>
    <a href="#payments" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-file-code"></i>{{ __('Payment Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="payments" data-bs-parent="#accordion">
        <li><a href="{{ route('operator-gs-payments') }}"><span>{{__('Payment Information')}}</span></a></li>
        <li><a href="{{ route('operator-currency-index') }}"><span>{{ __('Currencies') }}</span></a></li>
        <li><a href="{{route('operator-reward-index')}}"><span>{{__('Reward Information')}}</span></a></li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('social_settings'))

<li>
    <a href="#socials" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-paper-plane"></i>{{ __('Social Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="socials" data-bs-parent="#accordion">
        <li><a href="{{route('operator-sociallink-index')}}"><span>{{ __('Social Links') }}</span></a></li>
        <li><a href="{{route('operator-social-facebook')}}"><span>{{ __('Facebook Login') }}</span></a></li>
        <li><a href="{{route('operator-social-google')}}"><span>{{ __('Google Login') }}</span></a></li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('language_settings'))

<li>
    <a href="{{ route('operator-lang-index') }}" class="wave-effect">
        <i class="fas fa-language"></i>{{ __('Language Settings') }}
    </a>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('seo_tools'))

<li>
    <a href="#seoTools" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-wrench"></i>{{ __('SEO Tools') }}
    </a>
    <ul class="collapse list-unstyled" id="seoTools" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-catalog-item-popular',30) }}"><span>{{ __('Popular CatalogItems') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-seotool-analytics') }}"><span>{{ __('Google Analytics') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-seotool-keywords') }}"><span>{{ __('Website Meta Keywords') }}</span></a>
        </li>
    </ul>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('manage_staffs'))


<li>
    <a href="{{ route('operator-staff-index') }}" class=" wave-effect"><i class="fas fa-user-secret"></i>{{ __('Manage
        Staffs') }}</a>
</li>

@endif

@if(Auth::guard('operator')->user()->sectionCheck('modules'))
<li>
    <a href="{{ route('operator-module-index') }}" class=" wave-effect"><i class="fas fa-list-alt"></i>{{ __('Module
        Manager') }}</a>
</li>
@endif

@if(Auth::guard('operator')->user()->sectionCheck('mailing_list'))

<li>
    <a href="{{ route('operator-mailing-list-index') }}" class=" wave-effect"><i class="fas fa-users-cog mr-2"></i>{{
        __('Subscribers') }}</a>
</li>

@endif

@endif