{{--
    Image Upload Component - لتوحيد نمط رفع الصور المكرر

    Usage:
    @include('components.admin.image-upload', [
        'label' => __('Featured Image'),
        'name' => 'photo',
        'current' => $item->photo ?? null,
        'size' => '600x600',
        'required' => true,
        'id' => 'image-upload'
    ])
--}}

@php
    $id = $id ?? 'image-upload';
    $previewId = $previewId ?? 'image-preview';
    $required = $required ?? false;
    $size = $size ?? '600x600';
    $label = $label ?? __('Current Featured Image');
    $uploadLabel = $uploadLabel ?? __('Upload Image');
    $current = $current ?? null;
    $colLeft = $colLeft ?? 'col-lg-4';
    $colRight = $colRight ?? 'col-lg-7';

    // Determine background image
    if ($current) {
        $bgImage = asset('assets/images/' . $current);
    } else {
        $bgImage = asset('assets/admin/images/upload.png');
    }
@endphp

<div class="row">
    <div class="{{ $colLeft }}">
        <div class="left-area">
            <h4 class="heading">{{ $label }} @if($required)*@endif</h4>
        </div>
    </div>
    <div class="{{ $colRight }}">
        <div class="img-upload full-width-img">
            <div id="{{ $previewId }}" class="img-preview" style="background: url({{ $bgImage }});">
                <label for="{{ $id }}" class="img-label" id="{{ $id }}-label">
                    <i class="icofont-upload-alt"></i>{{ $uploadLabel }}
                </label>
                <input type="file" name="{{ $name }}" class="img-upload" id="{{ $id }}" @if($required) required @endif>
            </div>
            <p class="text">{{ __('Prefered Size:') }} ({{ $size }}) {{ __('or Square Sized Image') }}</p>
        </div>
    </div>
</div>
