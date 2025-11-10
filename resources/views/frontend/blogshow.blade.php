@extends('layouts.front')
@section('content')

    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Blog Details')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.blogshow', $blog->slug) }}">{{ $blog->title }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>



    <div class="gs-blog-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 gs-main-blog-wrapper wow-replaced" data-wow-delay=".1s">
                    <div class="gs-blog-details-wrapper">
                        <div class="gs-blog-card">
                            <img class="fea-img img-fluid" src="{{ asset('assets/images/blogs/' . $blog->photo) }}"
                                alt="blog details img">
                            <div class="meta-info-wrapper">
                                <div class="single-meta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                                            stroke="#4C3533" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                                            stroke="#4C3533" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span class="meta-title">@lang('By:') @lang('Admin')</span>
                                </div>

                                <div class="single-meta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <g clip-path="url(#clip0_6740_31235)">
                                            <path
                                                d="M19 2H18V1C18 0.734784 17.8946 0.48043 17.7071 0.292893C17.5196 0.105357 17.2652 0 17 0C16.7348 0 16.4804 0.105357 16.2929 0.292893C16.1054 0.48043 16 0.734784 16 1V2H8V1C8 0.734784 7.89464 0.48043 7.70711 0.292893C7.51957 0.105357 7.26522 0 7 0C6.73478 0 6.48043 0.105357 6.29289 0.292893C6.10536 0.48043 6 0.734784 6 1V2H5C3.67441 2.00159 2.40356 2.52888 1.46622 3.46622C0.528882 4.40356 0.00158786 5.67441 0 7L0 19C0.00158786 20.3256 0.528882 21.5964 1.46622 22.5338C2.40356 23.4711 3.67441 23.9984 5 24H19C20.3256 23.9984 21.5964 23.4711 22.5338 22.5338C23.4711 21.5964 23.9984 20.3256 24 19V7C23.9984 5.67441 23.4711 4.40356 22.5338 3.46622C21.5964 2.52888 20.3256 2.00159 19 2ZM2 7C2 6.20435 2.31607 5.44129 2.87868 4.87868C3.44129 4.31607 4.20435 4 5 4H19C19.7956 4 20.5587 4.31607 21.1213 4.87868C21.6839 5.44129 22 6.20435 22 7V8H2V7ZM19 22H5C4.20435 22 3.44129 21.6839 2.87868 21.1213C2.31607 20.5587 2 19.7956 2 19V10H22V19C22 19.7956 21.6839 20.5587 21.1213 21.1213C20.5587 21.6839 19.7956 22 19 22Z"
                                                fill="#4C3533" />
                                            <path
                                                d="M12 16.5C12.8284 16.5 13.5 15.8284 13.5 15C13.5 14.1716 12.8284 13.5 12 13.5C11.1716 13.5 10.5 14.1716 10.5 15C10.5 15.8284 11.1716 16.5 12 16.5Z"
                                                fill="#4C3533" />
                                            <path
                                                d="M7 16.5C7.82843 16.5 8.5 15.8284 8.5 15C8.5 14.1716 7.82843 13.5 7 13.5C6.17157 13.5 5.5 14.1716 5.5 15C5.5 15.8284 6.17157 16.5 7 16.5Z"
                                                fill="#4C3533" />
                                            <path
                                                d="M17 16.5C17.8284 16.5 18.5 15.8284 18.5 15C18.5 14.1716 17.8284 13.5 17 13.5C16.1716 13.5 15.5 14.1716 15.5 15C15.5 15.8284 16.1716 16.5 17 16.5Z"
                                                fill="#4C3533" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_6740_31235">
                                                <rect width="24" height="24" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    <span
                                        class="meta-title">{{ date('M d -Y', strtotime($blog->created_at),) }}</span>
                                </div>

                                <div class="single-meta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25"
                                        viewBox="0 0 24 25" fill="none">
                                        <path
                                            d="M2.42012 13.2132C2.28394 12.9975 2.21584 12.8897 2.17772 12.7234C2.14909 12.5985 2.14909 12.4015 2.17772 12.2766C2.21584 12.1103 2.28394 12.0025 2.42012 11.7868C3.54553 10.0048 6.8954 5.5 12.0004 5.5C17.1054 5.5 20.4553 10.0048 21.5807 11.7868C21.7169 12.0025 21.785 12.1103 21.8231 12.2766C21.8517 12.4015 21.8517 12.5985 21.8231 12.7234C21.785 12.8897 21.7169 12.9975 21.5807 13.2132C20.4553 14.9952 17.1054 19.5 12.0004 19.5C6.8954 19.5 3.54553 14.9952 2.42012 13.2132Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M12.0004 15.5C13.6573 15.5 15.0004 14.1569 15.0004 12.5C15.0004 10.8431 13.6573 9.5 12.0004 9.5C10.3435 9.5 9.0004 10.8431 9.0004 12.5C9.0004 14.1569 10.3435 15.5 12.0004 15.5Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <a href="#" class="meta-title">{{ $blog->category->name }}</a>
                                </div>

                                <div class="single-meta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25"
                                        viewBox="0 0 24 25" fill="none">
                                        <path d="M3.17188 7.93994L12.0019 13.0499L20.7719 7.96994" stroke="#4C3533"
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 22.11V13.04" stroke="#4C3533" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M9.93062 2.98004L4.59063 5.94004C3.38063 6.61004 2.39062 8.29004 2.39062 9.67004V15.32C2.39062 16.7 3.38063 18.38 4.59063 19.05L9.93062 22.02C11.0706 22.65 12.9406 22.65 14.0806 22.02L19.4206 19.05C20.6306 18.38 21.6206 16.7 21.6206 15.32V9.67004C21.6206 8.29004 20.6306 6.61004 19.4206 5.94004L14.0806 2.97004C12.9306 2.34004 11.0706 2.34004 9.93062 2.98004Z"
                                            stroke="#4C3533" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    @if ($blog->source)
                                        <a href="{{ $blog->source }}" target="_blank" class="meta-title">@lang('Source :')
                                            {{ $blog->source }}</a>
                                    @endif

                                </div>
                            </div>

                            <h4 class="fea-title mb-24">
                                {{ $blog->title }}
                            </h4>
                            <p class="mb-10">
                                {!! clean($blog->details, ['Attr.EnableID' => true]) !!}
                            </p>

                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4 mt-40 mt-lg-0 wow-replaced" data-wow-delay=".1s">
                    <div class="gs-blog-sidebar-wrapper right-side">
                        <!-- search wrapper -->
                        <div class="single-blog-widget wow-replaced" data-wow-delay=".1s">
                            <h5 class="widget-title">@lang('Search')</h5>
                            <form class="search-form" action="{{ route('front.blogsearch') }}" method="GET">
                                <input class="input-box" type="text" name="search" placeholder="@lang('Find anything...')">

                                <button type="submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M21 21L16.65 16.65M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z"
                                            stroke="black" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <!-- categories wrapper -->
                        <div class="single-blog-widget wow-replaced" data-wow-delay=".1s">
                            <h5 class="widget-title">@lang('Categories')</h5>
                            <ul class="cat-wrapper">
                                @foreach ($bcats as $cat)
                                    <li><a class="{{ isset($bcat) ? ($bcat->id == $cat->id ? 'active' : '') : '' }}"
                                            href="{{ route('front.blogcategory', $cat->slug) }}">{{ $cat->name }}
                                            ({{ $cat->blogs_count }})
                                        </a></li>
                                @endforeach
                            </ul>
                        </div>
                        <!-- recent post wrapper -->
                        <div class="single-blog-widget wow-replaced" data-wow-delay=".1s">
                            <h5 class="widget-title">@lang('Recent Posts')</h5>
                            <div class="gs-sm-recent-post-wrapper">


                                @foreach (App\Models\Blog::latest()->limit(4)->get() as $recent)
                                    <a href="{{ route('front.blogshow', $recent->slug) }}" class="recent-post d-flex">
                                        <img src="{{ $recent->photo ? asset('assets/images/blogs/' . $recent->photo) : asset('assets/images/noimage.png') }}"
                                            alt="recent post">
                                        <div class="recent-post-content">
                                            <h6 class="post-title">
                                                {{ mb_strlen($recent->title, 'UTF-8') > 45 ? mb_substr($recent->title, 0, 45, 'UTF-8') . '..' : $recent->title }}
                                            </h6>
                                            <span class="post-date">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 18 18" fill="none">
                                                    <g clip-path="url(#clip0_301_24779)">
                                                        <path
                                                            d="M14.25 1.5H13.5V0.75C13.5 0.551088 13.421 0.360322 13.2803 0.21967C13.1397 0.0790176 12.9489 0 12.75 0C12.5511 0 12.3603 0.0790176 12.2197 0.21967C12.079 0.360322 12 0.551088 12 0.75V1.5H6V0.75C6 0.551088 5.92098 0.360322 5.78033 0.21967C5.63968 0.0790176 5.44891 0 5.25 0C5.05109 0 4.86032 0.0790176 4.71967 0.21967C4.57902 0.360322 4.5 0.551088 4.5 0.75V1.5H3.75C2.7558 1.50119 1.80267 1.89666 1.09966 2.59966C0.396661 3.30267 0.00119089 4.2558 0 5.25L0 14.25C0.00119089 15.2442 0.396661 16.1973 1.09966 16.9003C1.80267 17.6033 2.7558 17.9988 3.75 18H14.25C15.2442 17.9988 16.1973 17.6033 16.9003 16.9003C17.6033 16.1973 17.9988 15.2442 18 14.25V5.25C17.9988 4.2558 17.6033 3.30267 16.9003 2.59966C16.1973 1.89666 15.2442 1.50119 14.25 1.5ZM1.5 5.25C1.5 4.65326 1.73705 4.08097 2.15901 3.65901C2.58097 3.23705 3.15326 3 3.75 3H14.25C14.8467 3 15.419 3.23705 15.841 3.65901C16.2629 4.08097 16.5 4.65326 16.5 5.25V6H1.5V5.25ZM14.25 16.5H3.75C3.15326 16.5 2.58097 16.2629 2.15901 15.841C1.73705 15.419 1.5 14.8467 1.5 14.25V7.5H16.5V14.25C16.5 14.8467 16.2629 15.419 15.841 15.841C15.419 16.2629 14.8467 16.5 14.25 16.5Z"
                                                            fill="#463539" />
                                                        <path
                                                            d="M9 12.375C9.62132 12.375 10.125 11.8713 10.125 11.25C10.125 10.6287 9.62132 10.125 9 10.125C8.37868 10.125 7.875 10.6287 7.875 11.25C7.875 11.8713 8.37868 12.375 9 12.375Z"
                                                            fill="#463539" />
                                                        <path
                                                            d="M5.25 12.375C5.87132 12.375 6.375 11.8713 6.375 11.25C6.375 10.6287 5.87132 10.125 5.25 10.125C4.62868 10.125 4.125 10.6287 4.125 11.25C4.125 11.8713 4.62868 12.375 5.25 12.375Z"
                                                            fill="#463539" />
                                                        <path
                                                            d="M12.75 12.375C13.3713 12.375 13.875 11.8713 13.875 11.25C13.875 10.6287 13.3713 10.125 12.75 10.125C12.1287 10.125 11.625 10.6287 11.625 11.25C11.625 11.8713 12.1287 12.375 12.75 12.375Z"
                                                            fill="#463539" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_301_24779">
                                                            <rect width="18" height="18" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                                {{ date('M d - Y', strtotime($recent->created_at)) }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <!-- tags wrapper -->
                        <div class="single-blog-widget wow-replaced" data-wow-delay=".1s">
                            <h5 class="widget-title">@lang('Popular Tags')</h5>
                            <ul class="tags-wrapper">
                                @foreach ($tags as $tag)
                                    @if (!empty($tag))
                                        <li>
                                            <a class="{{ isset($slug) ? ($slug == $tag ? 'active' : '') : '' }}"
                                                href="{{ route('front.blogtags', $tag) }}">
                                                {{ $tag }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- blog wrapper end -->
@endsection
