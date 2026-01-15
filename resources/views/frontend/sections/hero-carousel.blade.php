{{--
================================================================================
SECTION PARTIAL: Hero Carousel
================================================================================
Receives: $heroCarousels (collection of hero carousel items)
================================================================================
--}}

<div class="muaadh-slider-wrapper">
    @foreach($heroCarousels as $heroCarousel)
    <div class="muaadh-slide">
        <a href="{{ $heroCarousel->link ?? '#' }}">
            <img src="{{ asset('assets/images/sliders/' . $heroCarousel->photo) }}"
                 alt="{{ $heroCarousel->title ?? 'Hero Carousel' }}"
                 class="muaadh-slide-img"
                 loading="lazy">
        </a>
    </div>
    @endforeach
</div>
