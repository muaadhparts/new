<footer class="gs-footer-section {{ $gs->theme == 'theme3' ? 'home-3' : '' }}">
    <div class="newslatter {{ $gs->theme != 'theme1' ? 'newslatter2' : '' }}">
        <div class="container">
            <div class="row newslatter-row">
                <div class="col-lg-7 col-md-6 col-12 newslatter-area">
                    <div class="newslatter-content">
                        <h2>@lang('SIGN UP TO NEWSLATTER')</h2>
                    </div>
                </div>
                <div class="col-lg-5 col-md-6 col-12 newslatter-area">
                    <div class="newslatter-form">
                        <form action="{{ route('front.subscribe') }}" method="POST">
                            @csrf
                            <input class="news-latter-input" type="email" placeholder="@lang('Enter Your Email')" name="email">
                            <button class="newsletter-btn" type="submit">@lang('Send')</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="container">

        <div class="row footer-row gy-3">
            <div class="col-lg-3 col-md-6 col-12 left-info">
                <img class="logo" src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="">
                <a class="wow-replaced" data-wow-delay=".1s" href="tel:+11234567890">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"
                        fill="none">
                        <g clip-path="url(#clip0_241_7633)">
                            <path
                                d="M16 32C24.8366 32 32 24.8366 32 16C32 7.16344 24.8366 0 16 0C7.16344 0 0 7.16344 0 16C0 24.8366 7.16344 32 16 32Z"
                                fill="white" />
                            <path
                                d="M24 19.308C22.274 19.308 20.628 18.932 19.11 18.19C18.872 18.076 18.596 18.058 18.344 18.144C18.092 18.232 17.886 18.416 17.77 18.654L17.05 20.144C14.89 18.904 13.098 17.11 11.856 14.95L13.348 14.23C13.588 14.114 13.77 13.908 13.858 13.656C13.944 13.404 13.928 13.128 13.812 12.89C13.068 11.374 12.692 9.728 12.692 8C12.692 7.448 12.244 7 11.692 7H8C7.448 7 7 7.448 7 8C7 17.374 14.626 25 24 25C24.552 25 25 24.552 25 24V20.308C25 19.756 24.552 19.308 24 19.308Z"
                                fill="#030712" />
                        </g>
                        <defs>
                            <clipPath id="clip0_241_7633">
                                <rect width="32" height="32" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>

                    {{ $ps->phone }}</a>
                <a class="wow-replaced" data-wow-delay=".2s" href="mailto:info@example.com">

                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"
                        fill="none">
                        <g clip-path="url(#clip0_241_7638)">
                            <path
                                d="M16 32C24.8366 32 32 24.8366 32 16C32 7.16344 24.8366 0 16 0C7.16344 0 0 7.16344 0 16C0 24.8366 7.16344 32 16 32Z"
                                fill="white" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M25.5214 22.6744C25.6897 22.6742 25.8511 22.6072 25.9701 22.4882C26.0891 22.3692 26.156 22.2078 26.1562 22.0395V10.0484L16.4511 17.7553C16.3228 17.8573 16.1638 17.9129 15.9999 17.9129C15.8361 17.9129 15.677 17.8573 15.5488 17.7553L5.84375 10.0484V22.0395C5.84393 22.2079 5.91087 22.3692 6.02988 22.4882C6.14889 22.6073 6.31025 22.6742 6.47856 22.6744H25.5214ZM24.7327 9.32617L16 16.2609L7.26719 9.32617H24.7327Z"
                                fill="#030712" />
                        </g>
                        <defs>
                            <clipPath id="clip0_241_7638">
                                <rect width="32" height="32" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                    {{ $ps->email }}</a>

                <a class="wow-replaced" data-wow-delay=".3s" href="">

                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"
                        fill="none">
                        <g clip-path="url(#clip0_241_7643)">
                            <path
                                d="M16 32C24.8366 32 32 24.8366 32 16C32 7.16344 24.8366 0 16 0C7.16344 0 0 7.16344 0 16C0 24.8366 7.16344 32 16 32Z"
                                fill="white" />
                            <path
                                d="M15.9998 7.25C14.1548 7.25 12.3853 7.98295 11.0806 9.28762C9.77592 10.5923 9.04297 12.3618 9.04297 14.2069C9.04297 16.7119 11.9992 21.0594 14.0573 23.7838C14.2847 24.0836 14.5783 24.3268 14.9153 24.4942C15.2523 24.6617 15.6235 24.7488 15.9998 24.7488C16.3761 24.7488 16.7473 24.6617 17.0844 24.4942C17.4214 24.3268 17.715 24.0836 17.9423 23.7838C19.9998 21.0625 22.9567 16.7119 22.9567 14.2069C22.9567 12.3618 22.2238 10.5923 20.9191 9.28762C19.6144 7.98295 17.8449 7.25 15.9998 7.25ZM15.9998 16.1737C15.5107 16.1737 15.0326 16.0287 14.6258 15.757C14.2191 15.4852 13.9022 15.099 13.715 14.647C13.5278 14.1951 13.4788 13.6979 13.5742 13.2181C13.6697 12.7384 13.9052 12.2977 14.2511 11.9519C14.597 11.606 15.0376 11.3704 15.5174 11.275C15.9971 11.1796 16.4944 11.2286 16.9463 11.4158C17.3982 11.6029 17.7844 11.9199 18.0562 12.3266C18.3279 12.7333 18.473 13.2115 18.473 13.7006C18.4728 14.3565 18.2122 14.9854 17.7484 15.4492C17.2847 15.913 16.6557 16.1736 15.9998 16.1737Z"
                                fill="#030712" />
                        </g>
                        <defs>
                            <clipPath id="clip0_241_7643">
                                <rect width="32" height="32" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                    {{ $ps->street }}
                </a>

                <div class="social-links">
                    @foreach (DB::table('social_links')->where('user_id', 0)->where('status', 1)->get() as $link)
                        <a class="wow-replaced" data-wow-delay=".3s" href="">
                            <i class="{{ $link->icon }}"></i>
                        </a>
                    @endforeach
                </div>


            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <h5 class="wow-replaced">@lang('Product Category')</h5>
                <ul class="footer-category-links">
                    @foreach ($categories->take(6) as $cate)
                        <li class="wow-replaced" data-wow-delay=".1s"><a
                                href="{{ route('front.category', $cate->slug) }}{{ !empty(request()->input('search')) ? '?search=' . request()->input('search') : '' }}">{{ $cate->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <h5 class="wow-replaced">@lang('Customer Care')</h5>
                <ul class="footer-category-links">


                    @if ($ps->home == 1)
                        <li class="wow-replaced" data-wow-delay=".1s">
                            <a href="{{ route('front.index') }}">{{ __('Home') }}</a>
                        </li>
                    @endif
                    @if ($ps->blog == 1)
                        <li class="wow-replaced" data-wow-delay=".1s">
                            <a href="{{ route('front.blog') }}">{{ __('Blog') }}</a>
                        </li>
                    @endif
                    @if ($ps->faq == 1)
                        <li class="wow-replaced" data-wow-delay=".1s">
                            <a href="{{ route('front.faq') }}">{{ __('Faq') }}</a>
                        </li>
                    @endif
                    @foreach (DB::table('pages')->where('footer', '=', 1)->get() as $data)
                        <li class="wow-replaced" data-wow-delay=".1s"> <a
                                href="{{ route('front.vendor', $data->slug) }}">{{ $data->title }}</a></li>
                    @endforeach
                    @if ($ps->contact == 1)
                        <li class="wow-replaced" data-wow-delay=".1s">
                            <a href="{{ route('front.contact') }}">{{ __('Contact Us') }}</a>
                        </li>
                    @endif


                </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <h5 class=" wow-replaced">@lang('Recent Post')</h5>
                <div class="gs-sm-recent-post-wrapper">
                    @php
                        $footer_blogs = App\Models\Blog::where('status', 1)->orderBy('id', 'desc')->take(3)->get();
                    @endphp
                    @foreach ($footer_blogs as $footer_blog)
                        <a href="{{ route('front.blogshow', $footer_blog->slug) }}"
                            class="recent-post d-flex wow-replaced" data-wow-delay=".1s">
                            <img src="{{ asset('assets/images/blogs/' . $footer_blog->photo) }}" alt="recent post">
                            <div class="recent-post-content">
                                <h6 class="post-title">
                                    {{ mb_strlen($footer_blog->title, 'UTF-8') > 45 ? mb_substr($footer_blog->title, 0, 45, 'UTF-8') . ' ..' : $footer_blog->title }}
                                </h6>
                                <span class="post-date">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                        viewBox="0 0 18 18" fill="none">
                                        <g clip-path="url(#clip0_274_25512)">
                                            <path
                                                d="M14.25 1.5H13.5V0.75C13.5 0.551088 13.421 0.360322 13.2803 0.21967C13.1397 0.0790176 12.9489 0 12.75 0C12.5511 0 12.3603 0.0790176 12.2197 0.21967C12.079 0.360322 12 0.551088 12 0.75V1.5H6V0.75C6 0.551088 5.92098 0.360322 5.78033 0.21967C5.63968 0.0790176 5.44891 0 5.25 0C5.05109 0 4.86032 0.0790176 4.71967 0.21967C4.57902 0.360322 4.5 0.551088 4.5 0.75V1.5H3.75C2.7558 1.50119 1.80267 1.89666 1.09966 2.59966C0.396661 3.30267 0.00119089 4.2558 0 5.25L0 14.25C0.00119089 15.2442 0.396661 16.1973 1.09966 16.9003C1.80267 17.6033 2.7558 17.9988 3.75 18H14.25C15.2442 17.9988 16.1973 17.6033 16.9003 16.9003C17.6033 16.1973 17.9988 15.2442 18 14.25V5.25C17.9988 4.2558 17.6033 3.30267 16.9003 2.59966C16.1973 1.89666 15.2442 1.50119 14.25 1.5ZM1.5 5.25C1.5 4.65326 1.73705 4.08097 2.15901 3.65901C2.58097 3.23705 3.15326 3 3.75 3H14.25C14.8467 3 15.419 3.23705 15.841 3.65901C16.2629 4.08097 16.5 4.65326 16.5 5.25V6H1.5V5.25ZM14.25 16.5H3.75C3.15326 16.5 2.58097 16.2629 2.15901 15.841C1.73705 15.419 1.5 14.8467 1.5 14.25V7.5H16.5V14.25C16.5 14.8467 16.2629 15.419 15.841 15.841C15.419 16.2629 14.8467 16.5 14.25 16.5Z"
                                                fill="#BDBDBD" />
                                            <path
                                                d="M9 12.375C9.62132 12.375 10.125 11.8713 10.125 11.25C10.125 10.6287 9.62132 10.125 9 10.125C8.37868 10.125 7.875 10.6287 7.875 11.25C7.875 11.8713 8.37868 12.375 9 12.375Z"
                                                fill="#BDBDBD" />
                                            <path
                                                d="M5.25 12.375C5.87132 12.375 6.375 11.8713 6.375 11.25C6.375 10.6287 5.87132 10.125 5.25 10.125C4.62868 10.125 4.125 10.6287 4.125 11.25C4.125 11.8713 4.62868 12.375 5.25 12.375Z"
                                                fill="#BDBDBD" />
                                            <path
                                                d="M12.75 12.375C13.3713 12.375 13.875 11.8713 13.875 11.25C13.875 10.6287 13.3713 10.125 12.75 10.125C12.1287 10.125 11.625 10.6287 11.625 11.25C11.625 11.8713 12.1287 12.375 12.75 12.375Z"
                                                fill="#BDBDBD" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip1_274_25512">
                                                <rect width="18" height="18" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    {{ date('M d - Y', strtotime($footer_blog->created_at)) }}</span>
                            </div>
                        </a>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    <div class="gs-footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="footer-bottom-content">
                        <p>{{$gs->copyright}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
