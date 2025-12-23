{{--
================================================================================
SECTION PARTIAL: Slider
================================================================================
Receives: $sliders (collection of slider items)
================================================================================
--}}

<div class="muaadh-slider-wrapper">
    @foreach($sliders as $slider)
    <div class="muaadh-slide">
        <a href="{{ $slider->link ?? '#' }}">
            <img src="{{ asset('assets/images/sliders/' . $slider->photo) }}"
                 alt="{{ $slider->title ?? 'Slider' }}"
                 class="muaadh-slide-img"
                 loading="lazy">
        </a>
    </div>
    @endforeach
</div>
