@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Blog')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.blog') }}">@lang('Blog')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>



    <div class="gs-blog-wrapper">
        <div class="container">
            <div class="row flex-column-reverse flex-lg-row">
                <div class="col-12 col-lg-4 mt-40 mt-lg-0 wow-replaced" data-wow-delay=".1s">
                    <div class="gs-blog-sidebar-wrapper">
                        <!-- search wrapper -->
                        <div class="single-blog-widget wow-replaced" data-wow-delay=".1s">
                            <h5 class="widget-title">@lang('Search')</h5>
                            <form class="search-form" action="{{ route('front.blogsearch') }}" method="GET">
                                <input class="input-box" type="text" name="search"
                                    value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}"
                                    placeholder="@lang('Find anything...')">
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


                <div class="col-12 col-lg-8 gs-main-blog-wrapper wow-replaced" data-wow-delay=".1s">
                    @forelse ($blogs as $blog)
                        <div class="gs-main-single-blog wow-replaced " data-wow-delay=".1s">
                            <div class="left-side-content">
                                <img class="blog-img"
                                    src="{{ $blog->photo ? asset('assets/images/blogs/' . $blog->photo) : asset('assets/images/noimage.png') }}"
                                    alt="blog img">
                            </div>
                            <div class="right-side-content">
                                <h4>
                                    <a class="title" href="{{ route('front.blogshow', $blog->slug) }}">
                                        {{ mb_strlen($blog->title, 'UTF-8') > 60 ? mb_substr($blog->title, 0, 60, 'UTF-8') . '..' : $blog->title }}
                                    </a>
                                </h4>
                                <p class="des">
                                    {{ mb_strlen(strip_tags($blog->details), 'UTF-8') > 150
                                        ? mb_substr(strip_tags($blog->details), 0, 150, 'UTF-8') . '...'
                                        : strip_tags($blog->details) }}
                                </p>

                                <div class="date-wrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path d="M8 2V5" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M16 2V5" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M3.5 9.09009H20.5" stroke="#292D32" stroke-width="1.5"
                                            stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                        <path
                                            d="M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z"
                                            stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15.6937 13.7H15.7027" stroke="#292D32" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15.6937 16.7H15.7027" stroke="#292D32" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M11.9945 13.7H12.0035" stroke="#292D32" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M11.9945 16.7H12.0035" stroke="#292D32" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M8.29529 13.7H8.30427" stroke="#292D32" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M8.29529 16.7H8.30427" stroke="#292D32" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>

                                    <span class="date-text">{{ date('M d - Y', strtotime($blog->created_at)) }}</span>
                                </div>

                                <a class="template-btn outlinee-btn"
                                    href="{{ route('front.blogshow', $blog->slug) }}">@lang('read more')</a>
                            </div>
                        </div>
                    @empty
                        <div class="gs-main-single-blog wow-replaced py-5 d-flex justify-content-center align-items-center"
                            data-wow-delay=".1s">
                            <img class="w-75" src="{{ asset('assets/front/images/no-blog-found.png') }}"
                                alt="no-blog-found">
                        </div>
                    @endforelse
                    {{ $blogs->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
    <!-- blog wrapper end -->
@endsection
