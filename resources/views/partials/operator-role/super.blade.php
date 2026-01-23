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
        <li>
            <a href="{{ route('operator-purchase-create') }}"> {{ __('Pos') }}</a>
        </li>

    </ul>
</li>

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



<li>
    <a href="#income" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false"><i
            class="fas fa-hand-holding-usd"></i>{{ __('Total Earning') }}</a>
    <ul class="collapse list-unstyled" id="income" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-tax-calculate-income') }}"> {{ __('Tax Calculate') }}</a>
        </li>

        <li>
            <a href="{{ route('operator-withdraw-income') }}"> {{ __('Withdraw Earning') }}</a>
        </li>

        <li>
            <a href="{{ route('operator-commission-income') }}"> {{ __('Commission Earning') }}</a>
        </li>

    </ul>
</li>

<li>
    <a href="#accounts-menu" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-file-invoice-dollar"></i>{{ __('Accounting System') }}
    </a>
    <ul class="collapse list-unstyled" id="accounts-menu" data-bs-parent="#accordion">
        {{-- Dashboard --}}
        <li>
            <a href="{{ route('operator.accounts.index') }}"><i class="fas fa-tachometer-alt me-1"></i> {{ __('Accounts Dashboard') }}</a>
        </li>

        {{-- Parties --}}
        <li class="menu-divider"><small class="text-muted px-3">{{ __('Accounts') }}</small></li>
        <li>
            <a href="{{ route('operator.accounts.merchants') }}"><i class="fas fa-store me-1"></i> {{ __('Merchant Accounts') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.couriers') }}"><i class="fas fa-motorcycle me-1"></i> {{ __('Courier Accounts') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.shipping-companies') }}"><i class="fas fa-truck me-1"></i> {{ __('Shipping Companies') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.payment') }}"><i class="fas fa-credit-card me-1"></i> {{ __('Payment Providers') }}</a>
        </li>

        {{-- Settlements --}}
        <li class="menu-divider"><small class="text-muted px-3">{{ __('Settlements') }}</small></li>
        <li>
            <a href="{{ route('operator.accounts.settlements') }}"><i class="fas fa-money-check me-1"></i> {{ __('All Settlements') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.settlements.create') }}"><i class="fas fa-plus me-1"></i> {{ __('New Settlement') }}</a>
        </li>

        {{-- Reports --}}
        <li class="menu-divider"><small class="text-muted px-3">{{ __('Reports') }}</small></li>
        <li>
            <a href="{{ route('operator.accounts.reports.platform') }}"><i class="fas fa-chart-line me-1"></i> {{ __('Platform Revenue') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.reports.merchants-summary') }}"><i class="fas fa-users me-1"></i> {{ __('Merchants Summary') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.reports.couriers') }}"><i class="fas fa-biking me-1"></i> {{ __('Couriers Report') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.reports.shipping-companies') }}"><i class="fas fa-shipping-fast me-1"></i> {{ __('Shipping Report') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.reports.tax') }}"><i class="fas fa-receipt me-1"></i> {{ __('Tax Report') }}</a>
        </li>
        <li>
            <a href="{{ route('operator.accounts.reports.receivables-payables') }}"><i class="fas fa-balance-scale me-1"></i> {{ __('Receivables/Payables') }}</a>
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
            <a href="{{ route('operator-catalog-item-create', 'items') }}"><span>{{ __('Add New CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-catalog-item-index') }}"><span>{{ __('All CatalogItems') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-catalog-item-deactive') }}"><span>{{ __('Deactivated CatalogItem') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-catalog-item-settings') }}"><span>{{ __('CatalogItem Settings') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-catalog-item-images') }}"><span>{{ __('CatalogItem Images') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-merchant-item-images') }}"><span>{{ __('Merchant Item Images') }}</span></a>
        </li>
    </ul>
</li>

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

<li>
    <a href="#alternatives" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-exchange-alt"></i>{{ __('Alternatives') }}
    </a>
    <ul class="collapse list-unstyled" id="alternatives" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-alternative-index') }}"><span>{{ __('Manage Alternatives') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-alternative-stats') }}"><span>{{ __('Statistics') }}</span></a>
        </li>
    </ul>
</li>

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



<li>
    <a href="#couriers" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-users"></i>{{ __('Couriers') }}
    </a>
    <ul class="collapse list-unstyled" id="couriers" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-courier-index') }}"><span>{{ __('Courier List') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-courier-withdraw-index') }}"><span>{{ __('Withdraws') }}</span></a>
        </li>

    </ul>
</li>

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

<li>
    <a href="#merchantTrustBadges" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="icofont-verification-check"></i>{{ __('Merchant Trust Badges') }}
    </a>
    <ul class="collapse list-unstyled" id="merchantTrustBadges" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-trust-badge-index', 'all') }}"><span>{{ __('All Trust Badges') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-trust-badge-index', 'pending') }}"><span>{{ __('Pending Trust Badges') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="{{ route('operator-merchant-commission-index') }}" class=" wave-effect"><i
            class="fas fa-percentage"></i>{{ __('Merchant Commissions') }}</a>
</li>

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
            <a href="{{ route('operator-theme-colors') }}"><span>{{ __('Theme Colors') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator.credentials.index') }}"><span><i class="fas fa-cog me-1"></i>{{ __('System Credentials') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator.merchant-credentials.index') }}"><span><i class="fas fa-store me-1"></i>{{ __('Merchant Credentials') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-shipping-index') }}"><span>{{ __('Shipping Methods') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-contents') }}"><span>{{ __('Website Contents') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-gs-affilate') }}"><span>{{ __('Affiliate Program') }}</span></a>
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

<li>
    <a href="#homepage" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-edit"></i>{{ __('Home Page Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="homepage" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-home-page-index') }}"><span>{{ __('Home Pages') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-brand-index') }}"><span>{{ __('Brands') }}</span></a>
        </li>
    </ul>
</li>

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
    </ul>
</li>

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


@if (module('otp'))
    <li>
        <a href="#otp" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
            <i class="fas fa-sms"></i>{{ __('Sms Settings') }}
        </a>
        <ul class="collapse list-unstyled" id="otp" data-bs-parent="#accordion">
            <li><a href="{{ route('operator-otp-config') }}"><span>{{ __('OTP Configurations') }}</span></a></li>
        </ul>
    </li>
@endif






<li>
    <a href="#payments" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-file-code"></i>{{ __('Payment Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="payments" data-bs-parent="#accordion">
        <li><a href="{{ route('operator-gs-payments') }}"><span>{{ __('Payment Information') }}</span></a></li>
        <li><a href="{{ route('operator-monetary-unit-index') }}"><span>{{ __('Currencies') }}</span></a></li>
    </ul>
</li>

<li>
    <a href="#socials" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-paper-plane"></i>{{ __('Social Settings') }}
    </a>
    <ul class="collapse list-unstyled" id="socials" data-bs-parent="#accordion">
        <li><a href="{{ route('operator-network-presence-index') }}"><span>{{ __('Network Presence') }}</span></a></li>
        <li><a href="{{ route('operator-connect-config-facebook') }}"><span>{{ __('Facebook Login') }}</span></a></li>
        <li><a href="{{ route('operator-connect-config-google') }}"><span>{{ __('Google Login') }}</span></a></li>
    </ul>
</li>

<li>
    <a href="{{ route('operator-lang-index') }}" class="wave-effect">
        <i class="fas fa-language"></i>{{ __('Language Settings') }}
    </a>
</li>

<li>
    <a href="{{ route('operator.typefaces.index') }}" class=" wave-effect"><i
            class="fa fa-font"></i>{{ __('Typeface Option') }}</a>
</li>

<li>
    <a href="#seoTools" class="accordion-toggle wave-effect" data-bs-toggle="collapse" aria-expanded="false">
        <i class="fas fa-wrench"></i>{{ __('SEO Tools') }}
    </a>
    <ul class="collapse list-unstyled" id="seoTools" data-bs-parent="#accordion">
        <li>
            <a href="{{ route('operator-catalog-item-popular', 30) }}"><span>{{ __('Popular CatalogItems') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-seotool-analytics') }}"><span>{{ __('Google Analytics') }}</span></a>
        </li>
        <li>
            <a href="{{ route('operator-seotool-keywords') }}"><span>{{ __('Website Meta Keywords') }}</span></a>
        </li>
    </ul>
</li>

<li>
    <a href="{{ route('operator-staff-index') }}" class=" wave-effect"><i
            class="fas fa-user-secret"></i>{{ __('Manage
                                Staffs') }}</a>
</li>

<li>
    <a href="{{ route('operator-mailing-list-index') }}" class=" wave-effect"><i
            class="fas fa-users-cog mr-2"></i>{{ __('Subscribers') }}</a>
</li>


<li>
    <a href="{{ route('operator-role-index') }}" class=" wave-effect"><i
            class="fas fa-user-tag"></i>{{ __('Manage Roles') }}</a>
</li>

<li>
    <a href="{{ route('operator-cache-clear') }}" class=" wave-effect"><i
            class="fas fa-sync"></i>{{ __('Clear Cache') }}</a>
</li>

<li>
    <a href="{{ route('operator-module-index') }}" class=" wave-effect"><i
            class="fas fa-list-alt"></i>{{ __('Module
                                Manager') }}</a>
</li>

