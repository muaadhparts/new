{{--
    Form Row Component - لتوحيد نمط الفورم المكرر

    Usage:
    @include('components.operator.form-row', [
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

    Component defaults applied inline (DATA_FLOW_POLICY)
--}}
<div class="row">
    <div class="{{ $colLeft ?? 'col-lg-4' }}">
        <div class="left-area">
            <h4 class="heading">{{ $label }} @if($required ?? false)*@endif</h4>
            @if($subheading ?? null)
                <p class="sub-heading">{{ $subheading }}</p>
            @endif
        </div>
    </div>
    <div class="{{ $colRight ?? 'col-lg-7' }}">
        @if(($type ?? 'text') === 'textarea')
            <textarea class="{{ $class ?? 'input-field' }}" name="{{ $name }}" placeholder="{{ $placeholder ?? '' }}" @if($required ?? false) required @endif>{{ $value ?? '' }}</textarea>
        @elseif(($type ?? 'text') === 'select')
            <select class="{{ $class ?? 'input-field' }}" name="{{ $name }}" @if($required ?? false) required @endif>
                <option value="">{{ ($placeholder ?? '') ?: __('-- Select --') }}</option>
                @foreach($options ?? [] as $option)
                    <option value="{{ $option->{$optionValue ?? 'id'} }}"
                            @if(($value ?? '') == $option->{$optionValue ?? 'id'}) selected @endif>
                        {{ $option->{$optionLabel ?? 'name'} }}
                    </option>
                @endforeach
            </select>
        @elseif(($type ?? 'text') === 'number')
            <input type="number" class="{{ $class ?? 'input-field' }}" name="{{ $name }}"
                   placeholder="{{ $placeholder ?? '' }}" value="{{ $value ?? '' }}"
                   @if($required ?? false) required @endif
                   @if(isset($min)) min="{{ $min }}" @endif
                   @if(isset($max)) max="{{ $max }}" @endif
                   @if(isset($step)) step="{{ $step }}" @endif>
        @else
            <input type="{{ $type ?? 'text' }}" class="{{ $class ?? 'input-field' }}" name="{{ $name }}"
                   placeholder="{{ $placeholder ?? '' }}" value="{{ $value ?? '' }}"
                   @if($required ?? false) required @endif>
        @endif
    </div>
</div>
