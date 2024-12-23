<div>


{{--    <section class="gs-breadcrumb-section bg-class"--}}
{{--             data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">--}}
{{--        <div class="container">--}}
{{--            <div class="row justify-content-center content-wrapper">--}}
{{--                <div class="col-12">--}}
{{--                    <h2 class="breadcrumb-title">@lang('Product')</h2>--}}
{{--                    <ul class="bread-menu">--}}
{{--                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>--}}
{{--                        <li><a href="javascript:;">@lang('Product')  {{$sku}}</a></li>--}}
{{--                    </ul>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </section>--}}


    <div class="gs-blog-wrapper">

        <div class="container">
            <livewire:search-box/>
            <div class="row flex-column-reverse flex-lg-row">

                <div class="col-12 col-lg-12 col-xl-12 gs-main-blog-wrapper">

                    @php
                        if (request()->input('view_check') == null || request()->input('view_check') == 'list-view') {
                            $view = 'list-view';
                        } else {
                            $view = 'list-view';
                        }
                    @endphp

                            <!-- product nav wrapper -->
                    <div class=" product-nav-wrapper">
                        <h5>@lang('Total Products Found:') {{ $prods->count() }}</h5>
                        <div class="filter-wrapper">

                            <!-- list and grid view tab btns  start -->
                            <div class="btn-wrapper nav d-none d-lg-inline-block" role="tablist">
                                <button class="grid-btn check_view {{ $view == 'list-view' ? 'active' : '' }}"
                                        data-shopview="list-view" type="button" data-bs-toggle="tab"
                                        data-bs-target="#layout-list-pane" role="tab" aria-controls="layout-list-pane"
                                        aria-selected="{{ $view == 'list-view' ? 'true' : 'false' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="31" height="24"
                                         viewBox="0 0 31 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                              d="M1.33331 18.7575H3.90917C4.64356 18.7575 5.24248 19.3564 5.24248 20.0908V22.6666C5.24248 23.401 4.64356 24 3.90917 24H1.33331C0.598918 24 0 23.4011 0 22.6666V20.0908C0 19.3564 0.598918 18.7575 1.33331 18.7575ZM10.7121 0H29.44C30.1744 0 30.7734 0.598986 30.7734 1.33331V3.90917C30.7734 4.64349 30.1744 5.24248 29.44 5.24248C15.6911 5.24248 24.461 5.24248 10.7121 5.24248C9.97775 5.24248 9.37876 4.64356 9.37876 3.90917V1.33331C9.37876 0.598918 9.97775 0 10.7121 0ZM1.33331 0H3.90917C4.64356 0 5.24248 0.598986 5.24248 1.33331V3.90917C5.24248 4.64356 4.64356 5.24248 3.90917 5.24248H1.33331C0.598918 5.24248 0 4.64356 0 3.90917V1.33331C0 0.598918 0.598918 0 1.33331 0ZM10.7121 9.37869H29.44C30.1744 9.37869 30.7734 9.97768 30.7734 10.712V13.2879C30.7734 14.0222 30.1744 14.6212 29.44 14.6212C15.6911 14.6212 24.461 14.6212 10.7121 14.6212C9.97775 14.6212 9.37876 14.0223 9.37876 13.2879V10.712C9.37876 9.97761 9.97775 9.37869 10.7121 9.37869ZM1.33331 9.37869H3.90917C4.64356 9.37869 5.24248 9.97768 5.24248 10.712V13.2879C5.24248 14.0223 4.64356 14.6212 3.90917 14.6212H1.33331C0.598918 14.6212 0 14.0223 0 13.2879V10.712C0 9.97761 0.598918 9.37869 1.33331 9.37869ZM10.7121 18.7575H29.44C30.1744 18.7575 30.7734 19.3564 30.7734 20.0908V22.6666C30.7734 23.4009 30.1744 23.9999 29.44 23.9999C15.6911 23.9999 24.461 23.9999 10.7121 23.9999C9.97775 23.9999 9.37876 23.401 9.37876 22.6666V20.0908C9.37876 19.3564 9.97775 18.7575 10.7121 18.7575Z"
                                              fill="#978D8F" />
                                    </svg>
                                </button>

                            </div>
                            <!-- list and grid view tab btns  end -->
                        </div>
                    </div>



                    @if ($prods->count() == 0)
                        <!-- product nav wrapper for no data found -->
                        <div class="product-nav-wrapper d-flex justify-content-center ">
                            <h5>@lang('No Product Found')</h5>
                        </div>
                    @else
                        <!-- main content -->
                        <div class="tab-content" id="myTabContent">
                            <!-- product list view start  -->
                            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                 id="layout-list-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 gy-lg-5 mt-20 ">
                                    @foreach ($prods as $product)
                                        @include('includes.frontend.list_view_product')
                                    @endforeach
                                </div>
                            </div>


                            @if($alternatives)
                            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                 id="layout-list-pane" role="tabpanel" tabindex="0">
                                <h4 class="mt-3"> {{trans('Substitutions')}}</h4>
                                <div class="row gy-4 gy-lg-5 mt-2 ">
                                    @foreach ($alternatives as $product)
                                        @include('includes.frontend.list_view_product')
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- product grid view end  -->
                        </div>
{{--                        {{ $prods->links('includes.frontend.pagination') }}--}}
                    @endif

                </div>
            </div>
        </div>
    </div>



</div>