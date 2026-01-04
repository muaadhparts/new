<li>
    <a href="#purchase" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false"><i
            class="fas fa-hand-holding-usd"></i>{{ __('Purchases') }}</a>
    <ul class="collapse list-unstyled" id="purchase" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-purchases-all') }}"> {{ __('All Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('admin-purchases-all') }}?status=pending"> {{ __('Pending Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('admin-purchases-all') }}?status=processing"> {{ __('Processing Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('admin-purchases-all') }}?status=completed"> {{ __('Completed Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('admin-purchases-all') }}?status=declined"> {{ __('Declined Purchases') }}</a>
        </li>
        <li>
            <a href="{{ route('admin-purchase-create') }}"> {{ __('Pos') }}</a>
        </li>

    </ul>
</li>

<li>
    <a href="#menu1" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-flag"></i>{{ __('Manage Country') }}
    </a>
    <ul class="collapse list-unstyled" id="menu1" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-country-index') }}"><span>{{ __('Country') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-country-tax') }}"><span>{{ __('Manage Tax') }}</span></a>
        </li>
    </ul>
</li>



<li>
    <a href="#income" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false"><i
            class="fas fa-hand-holding-usd"></i>{{ __('Total Earning') }}</a>
    <ul class="collapse list-unstyled" id="income" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-tax-calculate-income') }}"> {{ __('Tax Calculate') }}</a>
        </li>
        <li>
            <a href="{{ route('admin-membership-plan-income') }}"> {{ __('Membership Plan Earning') }}</a>
        </li>

        <li>
            <a href="{{ route('admin-withdraw-income') }}"> {{ __('Withdraw Earning') }}</a>
        </li>

        <li>
            <a href="{{ route('admin-commission-income') }}"> {{ __('Commission Earning') }}</a>
        </li>

    </ul>
</li>


{{-- Old Category menu removed - using TreeCategories system --}}

<li>
    <a href="#menu2" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-cart"></i>{{ __('CatalogItems') }}
    </a>
    <ul class="collapse list-unstyled" id="menu2" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-catalog-item-types') }}"><span>{{ __('Add New CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-catalog-item-index') }}"><span>{{ __('All CatalogItems') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-catalog-item-deactive') }}"><span>{{ __('Deactivated CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-catalog-item-catalog-index') }}"><span>{{ __('CatalogItem Catalogs') }}</span></a>
        </li>

        <li>
            <a href="{{ route('admin-gs-catalog-item-settings') }}"><span>{{ __('CatalogItem Settings') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#affiliateprod" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-opencart"></i>{{ __('Affiliate CatalogItems') }}
    </a>
    <ul class="collapse list-unstyled" id="affiliateprod" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-import-create') }}"><span>{{ __('Add Affiliate CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-import-index') }}"><span>{{ __('All Affiliate CatalogItems') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="{{ route('admin-catalog-item-import') }}"><i class="fas fa-upload"></i>{{ __('Bulk CatalogItem Upload') }}</a>
</li>

<li>
    <a href="#menu4" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-speech-comments"></i>{{ __('CatalogItem Discussion') }}
    </a>
    <ul class="collapse list-unstyled" id="menu4" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-catalog-review-index') }}"><span>{{ __('CatalogItem Reviews') }}</span></a>
        </li>

        <li>
            <a href="{{ route('admin-abuse-flag-index') }}"><span>{{ __('Reports') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="{{ route('admin-discount-code-index') }}" class=" wave-effect"><i
            class="fas fa-percentage"></i>{{ __('Set
                                Discount Codes') }}</a>
</li>

<li>
    <a href="#menu3" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-user"></i>{{ __('Customers') }}
    </a>
    <ul class="collapse list-unstyled" id="menu3" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-user-index') }}"><span>{{ __('Customers List') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-withdraw-index') }}"><span>{{ __('Withdraws') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-user-image') }}"><span>{{ __('Customer Default Image') }}</span></a>
        </li>
    </ul>
</li>



<li>
    <a href="#couriers" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-users"></i>{{ __('Couriers') }}
    </a>
    <ul class="collapse list-unstyled" id="couriers" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-courier-index') }}"><span>{{ __('Courier List') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-courier-withdraw-index') }}"><span>{{ __('Withdraws') }}</span></a>
        </li>

    </ul>
</li>

<li>
    <a href="#customerDeposit" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-money"></i>{{ __('Customer Deposits') }}
    </a>
    <ul class="collapse list-unstyled" id="customerDeposit" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-wallet-log-index') }}"><span>{{ __('Transactions') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#merchant" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-ui-user-group"></i>{{ __('Merchants') }}
    </a>
    <ul class="collapse list-unstyled" id="merchant" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-merchant-index') }}"><span>{{ __('Merchants List') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-merchant-withdraw-index') }}"><span>{{ __('Withdraws') }}</span></a>
        </li>

    </ul>
</li>

<li>
    <a href="#merchantMembershipPlans" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-user-suited"></i>{{ __('Merchant Membership Plans') }}
    </a>
    <ul class="collapse list-unstyled" id="merchantMembershipPlans" data-bs-parent="#accordion">
        <li>
            <a
                href="{{ route('admin-merchant-membership-plans', 'completed') }}"><span>{{ __('Completed Plans') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-merchant-membership-plans', 'pending') }}"><span>{{ __('Pending Plans') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#merchantVerifications" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-verification-check"></i>{{ __('Merchant Verifications') }}
    </a>
    <ul class="collapse list-unstyled" id="merchantVerifications" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-vr-index', 'all') }}"><span>{{ __('All Verifications') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-vr-index', 'pending') }}"><span>{{ __('Pending Verifications') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="{{ route('admin-membership-plan-index') }}" class=" wave-effect"><i
            class="fas fa-dollar-sign"></i>{{ __('Merchant Membership Plans') }}</a>
</li>

<li>
    <a href="#msg" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-fw fa-newspaper"></i>{{ __('Messages') }}
    </a>
    <ul class="collapse list-unstyled" id="msg" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-support-ticket-index') }}"><span>{{ __('Tickets') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-support-ticket-dispute') }}"><span>{{ __('Disputes') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#blog" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-fw fa-newspaper"></i>{{ __('Blog') }}
    </a>
    <ul class="collapse list-unstyled" id="publication" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-article-type-index') }}"><span>{{ __('Categories') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-publication-index') }}"><span>{{ __('Posts') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-gs-publication-settings') }}"><span>{{ __('Publication Settings') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#general" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-cogs"></i>{{ __('General Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="general" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-gs-logo') }}"><span>{{ __('Logo') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-gs-fav') }}"><span>{{ __('Favicon') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-theme-colors') }}"><span>{{ __('Theme Colors') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin.credentials.index') }}"><span><i class="fas fa-cog me-1"></i>{{ __('System Credentials') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin.merchant-credentials.index') }}"><span><i class="fas fa-store me-1"></i>{{ __('Merchant Credentials') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-shipping-index') }}"><span>{{ __('Shipping Methods') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-package-index') }}"><span>{{ __('Packagings') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-pick-index') }}"><span>{{ __('Pickup Locations') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-gs-contents') }}"><span>{{ __('Website Contents') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-gs-affilate') }}"><span>{{ __('Affiliate Program') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-gs-popup') }}"><span>{{ __('Popup Banner') }}</span></a>
        </li>
        {{-- Breadcrumb Banner removed - using modern minimal design --}}
        <li>
            <a href="{{ route('admin-gs-error-banner') }}"><span>{{ __('Error Banner') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-gs-maintenance') }}"><span>{{ __('Website Maintenance') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#homepage" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-edit"></i>{{ __('Home Page Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="homepage" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-home-page-index') }}"><span>{{ __('Home Pages') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-sl-index') }}"><span>{{ __('Sliders') }}</span></a>
        </li>

        <li>
            <a href="{{ route('admin-fs-deal') }}"><span>{{ __('Deal of the day') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-best-sellers') }}"><span>{{ __('Best Sellers') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-top-rated') }}"><span>{{ __('Top Rated') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-big-save') }}"><span>{{ __('Big Save') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-trending') }}"><span>{{ __('Trending') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-featured') }}"><span>{{ __('Featured CatalogItems') }}</span></a>
        </li>

        <li>
            <a href="{{ route('admin-service-index') }}"><span>{{ __('Services') }}</span></a>
        </li>


        <li>
            <a href="{{ route('admin-brand-index') }}"><span>{{ __('Brands') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-customize') }}"><span>{{ __('Home Page Customization') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#menu" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-file-code"></i>{{ __('Menu Page Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="menu" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-help-article-index') }}"><span>{{ __('Help Article Page') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-fs-contact') }}"><span>{{ __('Contact Us Page') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-static-content-index') }}"><span>{{ __('Other Pages') }}</span></a>
        </li>

        <li>
            <a href="{{ route('admin-fs-menu-links') }}"><span>{{ __('Customize Menu Links') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="#emails" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-at"></i>{{ __('Email Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="emails" data-bs-parent="#accordion">
        <li><a href="{{ route('admin-mail-index') }}"><span>{{ __('Email Template') }}</span></a></li>
        <li><a href="{{ route('admin-mail-config') }}"><span>{{ __('Email Configurations') }}</span></a></li>
        <li><a href="{{ route('admin-group-show') }}"><span>{{ __('Group Email') }}</span></a></li>
    </ul>
</li>


@if (module('otp'))
    <li>
        <a href="#otp" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
            <i class="fas fa-sms"></i>{{ __('Sms Settings') }}
        </a>
        <ul class="collapse list-unstyled" id="otp" data-bs-parent="#accordion">
            <li><a href="{{ route('admin-otp-config') }}"><span>{{ __('OTP Configurations') }}</span></a></li>
        </ul>
    </li>
@endif






<li>
    <a href="#payments" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-file-code"></i>{{ __('Payment Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="payments" data-bs-parent="#accordion">
        <li><a href="{{ route('admin-gs-payments') }}"><span>{{ __('Payment Information') }}</span></a></li>
        <li><a href="{{ route('admin-currency-index') }}"><span>{{ __('Currencies') }}</span></a></li>
        <li><a href="{{ route('admin-reward-index') }}"><span>{{ __('Reward Information') }}</span></a></li>
    </ul>
</li>

<li>
    <a href="#socials" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-paper-plane"></i>{{ __('Social Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="socials" data-bs-parent="#accordion">
        <li><a href="{{ route('admin-sociallink-index') }}"><span>{{ __('Social Links') }}</span></a></li>
        <li><a href="{{ route('admin-social-facebook') }}"><span>{{ __('Facebook Login') }}</span></a></li>
        <li><a href="{{ route('admin-social-google') }}"><span>{{ __('Google Login') }}</span></a></li>
    </ul>
</li>

<li>
    <a href="{{ route('admin-lang-index') }}" class="wave-effect">
        <i class="fas fa-language"></i>{{ __('Language Settings') }}
    </a>
</li>

<li>
    <a href="{{ route('admin.fonts.index') }}" class=" wave-effect"><i
            class="fa fa-font"></i>{{ __('Font Option') }}</a>
</li>

<li>
    <a href="#seoTools" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-wrench"></i>{{ __('SEO Tools') }}
    </a>
    <ul class="collapse list-unstyled" id="seoTools" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('admin-catalog-item-popular', 30) }}"><span>{{ __('Popular CatalogItems') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-seotool-analytics') }}"><span>{{ __('Google Analytics') }}</span></a>
        </li>
        <li>
            <a href="{{ route('admin-seotool-keywords') }}"><span>{{ __('Website Meta Keywords') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="{{ route('admin-staff-index') }}" class=" wave-effect"><i
            class="fas fa-user-secret"></i>{{ __('Manage
                                Staffs') }}</a>
</li>

<li>
    <a href="{{ route('admin-mailing-list-index') }}" class=" wave-effect"><i
            class="fas fa-users-cog mr-2"></i>{{ __('Subscribers') }}</a>
</li>


<li>
    <a href="{{ route('admin-role-index') }}" class=" wave-effect"><i
            class="fas fa-user-tag"></i>{{ __('Manage Roles') }}</a>
</li>

<li>
    <a href="{{ route('admin-cache-clear') }}" class=" wave-effect"><i
            class="fas fa-sync"></i>{{ __('Clear Cache') }}</a>
</li>

<li>
    <a href="{{ route('admin-module-index') }}" class=" wave-effect"><i
            class="fas fa-list-alt"></i>{{ __('Module
                                Manager') }}</a>
</li>

<li>
    <a href="#sactive" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-cog"></i>{{ __('System Activation') }}
    </a>
    <ul class="collapse list-unstyled" id="sactive" data-bs-parent="#accordion">

        <li><a href="{{ route('admin-activation-form') }}"> {{ __('Activation') }}</a></li>
        <li><a href="{{ route('admin-generate-backup') }}"> {{ __('Generate Backup') }}</a></li>
    </ul>
</li>
