{{--
    Form Row Component - لتوحيد نمط الفورم المكرر

    Usage:
    @include('components.admin.form-row', [
        'label' => __('Field Name'),
        'name' => 'field_name',
        'value' => old('field_name', $item->field_name ?? ''),
        'type' => 'text',           // text, email, number, textarea, select
        'required' => true,
        'placeholder' => __('Enter value'),
        'subheading' => __('(Optional description)'),
        'options' => $options,      // For select type
        'optionValue' => 'id',      // For select - which field to use as value
        'optionLabel' => 'name',    // For select - which field to display
    ])
--}}

@php
    $type = $type ?? 'text';
    $required = $required ?? false;
    $subheading = $subheading ?? null;
    $placeholder = $placeholder ?? '';
    $class = $class ?? 'input-field';
    $colLeft = $colLeft ?? 'col-lg-4';
    $colRight = $colRight ?? 'col-lg-7';
@endphp

<div class="row">
    <div class="{{ $colLeft }}">
        <div class="left-area">
            <h4 class="heading">{{ $label }} @if($required)*@endif</h4>
            @if($subheading)
                <p class="sub-heading">{{ $subheading }}</p>
            @endif
        </div>
    </div>
    <div class="{{ $colRight }}">
        @if($type === 'textarea')
            <textarea class="{{ $class }}" name="{{ $name }}" placeholder="{{ $placeholder }}" @if($required) required @endif>{{ $value ?? '' }}</textarea>
        @elseif($type === 'select')
            <select class="{{ $class }}" name="{{ $name }}" @if($required) required @endif>
                <option value="">{{ $placeholder ?: __('-- Select --') }}</option>
                @foreach($options ?? [] as $option)
                    <option value="{{ $option->{$optionValue ?? 'id'} }}"
                            @if(($value ?? '') == $option->{$optionValue ?? 'id'}) selected @endif>
                        {{ $option->{$optionLabel ?? 'name'} }}
                    </option>
                @endforeach
            </select>
        @elseif($type === 'number')
            <input type="number" class="{{ $class }}" name="{{ $name }}"
                   placeholder="{{ $placeholder }}" value="{{ $value ?? '' }}"
                   @if($required) required @endif
                   @if(isset($min)) min="{{ $min }}" @endif
                   @if(isset($max)) max="{{ $max }}" @endif
                   @if(isset($step)) step="{{ $step }}" @endif>
        @else
            <input type="{{ $type }}" class="{{ $class }}" name="{{ $name }}"
                   placeholder="{{ $placeholder }}" value="{{ $value ?? '' }}"
                   @if($required) required @endif>
        @endif
    </div>
</div>
