
	<option value="">{{ __('Select Country') }}</option>

	@foreach (App\Models\Country::where('status', 1)->get() as $cdata)
		@php
			// تحديد اسم الدولة بناءً على اللغة النشطة
			$countryDisplayName = (app()->getLocale() == 'ar')
				? ($cdata->country_name_ar ?: $cdata->country_name)
				: $cdata->country_name;
		@endphp
		<option value="{{ $cdata->country_name }}"
				data="{{ $cdata->id }}"
				rel="{{ $cdata->states->count() > 0 ? 1 : 0 }}"
				data-href="{{ route('country.wise.state', $cdata->id) }}"
				{{ $data->country == $cdata->country_name ? 'selected' : '' }}>
			{{ $countryDisplayName }}
		</option>
	@endforeach