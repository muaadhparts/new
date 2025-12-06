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
                        <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="{{ $gs->title }}">
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

                {{-- Column 2: Categories --}}
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-title">@lang('Categories')</h5>
                    <ul class="muaadh-footer-links">
                        @foreach ($categories->take(6) as $cate)
                            <li>
                                <a href="{{ route('front.category', $cate->slug) }}">{{ $cate->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Column 3: Quick Links --}}
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-title">@lang('Quick Links')</h5>
                    <ul class="muaadh-footer-links">
                        @if ($ps->home == 1)
                            <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        @endif
                        <li><a href="{{ route('front.category') }}">@lang('Products')</a></li>
                        @if ($ps->contact == 1)
                            <li><a href="{{ route('front.contact') }}">@lang('Contact Us')</a></li>
                        @endif
                        @foreach (DB::table('pages')->where('footer', '=', 1)->get() as $page)
                            <li><a href="{{ route('front.vendor', $page->slug) }}">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Column 4: Newsletter --}}
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-title">@lang('Newsletter')</h5>
                    <p class="muaadh-footer-newsletter-text">
                        @lang('Subscribe to get updates on new products and offers.')
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

                    {{-- Social Links --}}
                    <div class="muaadh-footer-social">
                        @php
                            $socialLinks = DB::table('social_links')->where('user_id', 0)->where('status', 1)->get();
                        @endphp
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
