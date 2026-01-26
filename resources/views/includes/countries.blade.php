{{--
    Countries Dropdown Options
    Usage: @include('includes.countries') - no pre-selection (checkout)
    Usage: @include('includes.countries', ['selectedCountry' => $user->country]) - with pre-selection (profiles)

    DATA FLOW POLICY: Uses LocationHelper which delegates to LocationDataService
--}}
{!! \App\Helpers\LocationHelper::getCountriesOptionsHtml($selectedCountry ?? null) !!}
