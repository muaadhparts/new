<option value="">@lang('Select Child Category')</option>
@foreach($subcat->childs as $child)
<option value="{{ $child->id }}">{{ $child->localized_name }}</option>
@endforeach
