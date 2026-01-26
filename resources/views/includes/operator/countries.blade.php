{{--
    Operator Countries Dropdown Options
    Usage: @include('includes.operator.countries', ['data' => $user])

    DATA FLOW POLICY: Uses LocationHelper which delegates to LocationDataService
--}}
{!! \App\Helpers\LocationHelper::getCountriesOptionsHtml($data->country ?? null) !!}
