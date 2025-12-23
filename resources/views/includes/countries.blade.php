
{{--
    CRITICAL REQUIREMENT: No pre-filled values in checkout
    Always start with empty selection - customer must select country manually
    NO auto-selection based on user profile or saved data
--}}
<option value="" disabled selected >{{ __('Select Country') }}</option>
	@foreach (App\Models\Country::where('status',1)->get() as $data)
	<option value="{{ $data->country_name }}" data="{{$data->id}}" rel="{{$data->cities->count() > 0 ? 1 : 0}}" rel1="{{ Auth::check() ? 1 : 0 }}" rel5="{{ Auth::check() ? 1 : 0 }}" data-href="{{route('country.wise.city',$data->id)}}">{{ $data->country_name }}</option>
@endforeach
