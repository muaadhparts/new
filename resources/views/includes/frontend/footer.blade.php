{{--
================================================================================
    MUAADH THEME - FOOTER
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/MUAADH.css
    2. DO NOT add <style> tags in Blade files - move all styles to MUAADH.css
    3. DO NOT create new CSS files - use MUAADH.css sections instead
================================================================================
--}}

<footer class="muaadh-footer">
    {{-- Main Footer --}}
    <div class="muaadh-footer-main">
        <div class="container">
            <div class="muaadh-footer-grid">
                {{-- Column 1: Logo & Contact --}}
                <div class="muaadh-footer-col">
                    <a href="{{ route('front.index') }}" class="muaadh-footer-logo">
                        <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="{{ $gs->site_name }}">
                    </a>
                    <p class="muaadh-footer-desc">
                        @lang('Your trusted source for genuine auto parts and accessories.')
                    </p>
                    <div class="muaadh-footer-contact">
                        <a href="tel:{{ $ps->phone }}" class="muaadh-footer-contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>{{ $ps->phone }}</span>
                        </a>
                        <a href="mailto:{{ $ps->email }}" class="muaadh-footer-contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>{{ $ps->email }}</span>
                        </a>
                        @if($ps->street)
                        <div class="muaadh-footer-contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ $ps->street }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Column 2: Brands --}}
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-name">@lang('Brands')</h5>
                    <ul class="muaadh-footer-links">
                        @foreach ($brands->take(6) as $brand)
                            <li>
                                <a href="{{ route('front.catalog', $brand->slug) }}">{{ app()->getLocale() == 'ar' ? ($brand->name_ar ?: $brand->name) : $brand->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Column 3: Quick Links --}}
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-name">@lang('Quick Links')</h5>
                    <ul class="muaadh-footer-links">
                        @if ($ps->home == 1)
                            <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        @endif
                        <li><a href="{{ route('front.catalog') }}">@lang('CatalogItems')</a></li>
                        @if ($ps->contact == 1)
                            <li><a href="{{ route('front.contact') }}">@lang('Contact Us')</a></li>
                        @endif
                        {{-- Using cached $footerPages from AppServiceProvider --}}
                        @foreach ($footerPages as $page)
                            <li><a href="{{ route('front.merchant', $page->slug) }}">{{ $page->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Column 4: Newsletter --}}
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-name">@lang('Newsletter')</h5>
                    <p class="muaadh-footer-newsletter-text">
                        @lang('Subscribe to get updates on new catalogItems and offers.')
                    </p>
                    <form action="{{ route('front.subscribe') }}" method="POST" class="muaadh-footer-newsletter">
                        @csrf
                        <div class="muaadh-newsletter-input-group">
                            <input type="email" name="email" placeholder="@lang('Enter your email')" required>
                            <button type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>

                    {{-- Social Links - Using cached $socialLinks from AppServiceProvider --}}
                    <div class="muaadh-footer-social">
                        @foreach ($socialLinks as $link)
                            <a href="{{ $link->link }}" target="_blank" class="muaadh-social-link">
                                <i class="{{ $link->icon }}"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Bottom --}}
    <div class="muaadh-footer-bottom">
        <div class="container">
            <div class="muaadh-footer-bottom-inner">
                <p class="muaadh-copyright">{{ $gs->copyright }}</p>
            </div>
        </div>
    </div>
</footer>
