{{--
    Countries Dropdown Options
    Usage: @include('includes.countries') - no pre-selection (checkout)
    Usage: @include('includes.countries', ['selectedCountry' => $user->country]) - with pre-selection (profiles)
--}}
@php $selectedCountry = $selectedCountry ?? null; @endphp
<option value="" {{ !$selectedCountry ? 'selected' : '' }}>{{ __('Select Country') }}</option>
@foreach (App\Models\Country::where('status',1)->get() as $data)
    <option value="{{ $data->country_name }}"
        data="{{ $data->id }}"
        rel="{{ $data->cities->count() > 0 ? 1 : 0 }}"
        rel1="{{ Auth::check() ? 1 : 0 }}"
        rel5="{{ Auth::check() ? 1 : 0 }}"
        data-href="{{ route('country.wise.city', $data->id) }}"
        {{ $selectedCountry == $data->country_name ? 'selected' : '' }}>{{ $data->country_name }}</option>
@endforeach
