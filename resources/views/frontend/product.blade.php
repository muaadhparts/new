@extends('layouts.front')

@section('content')
    <!-- breadcrumb start  -->
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Product Details')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="#">{{ $productt->name }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb end -->


    <!-- single product details content wrapper start -->
    <div class="single-product-details-content-wrapper">
        <div class="container">
            <div class="row gy-4">
                <div class="col-12">
                    <!-- product-breadcrumb -->
                    <ul class="product-breadcrumb">
                        <li><a href="{{ route('front.index') }}">@lang('home')</a></li>
                        <li><a
                                href="{{ route('front.category', $productt->category->slug) }}">{{ $productt->category->name }}</a>
                        </li>
                        @if ($productt->subcategory_id)
          
                            <li><a
                                    href="{{ route('front.category', [$productt->category->slug, $productt->subcategory->slug]) }}">{{ $productt->subcategory->name }}</a>
                            </li>
                        @endif
                        @if ($productt->childcategory_id)
             
                            <li><a
                                    href="{{ route('front.category', [$productt->category->slug, $productt->subcategory->slug, $productt->childcategory->slug]) }}">{{ $productt->childcategory->name }}</a>
                            </li>
                        @endif
                    </ul>
                </div>


                <!-- gs-product-details-gallery-wrapper -->
                <div class="col-lg-6 wow-replaced" data-wow-delay=".1s">
                    <div class="gs-product-details-gallery-wrapper">
                        <div class="product-main-slider">
                            <img src="{{ filter_var($productt->photo, FILTER_VALIDATE_URL) ? $productt->photo : asset('assets/images/products/' . $productt->photo) }}"
                                alt="Thumb Image"
                                data-zoom-image="{{ filter_var($productt->photo, FILTER_VALIDATE_URL) ? $productt->photo : asset('assets/images/products/' . $productt->photo) }}"
                                class="main-img" alt="gallery-img">
                            @foreach ($productt->galleries as $gal)
                                <img src="{{ asset('assets/images/galleries/' . $gal->photo) }}"
                                    data-image="{{ asset('assets/images/galleries/' . $gal->photo) }}" class="main-img"
                                    alt="gallery-img">
                            @endforeach
                        </div>

                        <div class="product-nav-slider">
                            <img src="{{ filter_var($productt->photo, FILTER_VALIDATE_URL) ? $productt->photo : asset('assets/images/products/' . $productt->photo) }}"
                                alt="Thumb Image"
                                data-zoom-image="{{ filter_var($productt->photo, FILTER_VALIDATE_URL) ? $productt->photo : asset('assets/images/products/' . $productt->photo) }}"
                                class="nav-img" alt="gallery-img">
                            @foreach ($productt->galleries as $gal)
                                <img src="{{ asset('assets/images/galleries/' . $gal->photo) }}"
                                    data-image="{{ asset('assets/images/galleries/' . $gal->photo) }}" class="nav-img"
                                    alt="gallery-img">
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 wow-replaced" data-wow-delay=".2s">
                    <form>
                        <!-- product-info-wrapper  -->
                        <div class="product-info-wrapper  {{ $productt->type != 'Physical' ? 'mb-3' : '' }}">
                            <h3>{{ $productt->name }}</h3>
                            <div class="price-wrapper">
                                <h5 id="sizeprice">{{ $productt->showPrice() }}</h5>
                                <h5><del>{{ $productt->showPreviousPrice() }}</del></h5>

                                @if ($productt->offPercentage() && round($productt->offPercentage()) > 0)
                                    <span class="product-badge">-{{ round($productt->offPercentage()) }}%</span>
                                @endif

                            </div>
                            <div class="rating-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="23" viewBox="0 0 24 23"
                                    fill="none">
                                    <path
                                        d="M12 0.5L14.6942 8.7918H23.4127L16.3593 13.9164L19.0534 22.2082L12 17.0836L4.94658 22.2082L7.64074 13.9164L0.587322 8.7918H9.30583L12 0.5Z"
                                        fill="#EEAE0B" />
                                </svg>
                                <span class="rating">{{ number_format($productt->ratings_avg_rating, 1) }}
                                    ({{ App\Models\Rating::ratingCount($productt->id) }} @lang('Reviews'))</span>
                            </div>
                        </div>



                        <!-- product stocks -->

                        @if (
                            $productt->ship != null ||
                                $productt->sku != null ||
                                $productt->platform != null ||
                                $productt->region != null ||
                                $productt->licence_type != null)
                            <hr>

                            <div class="product-stocks-wraper">
                                <ul>
                                    <li>
                                        @if ($productt->type == 'Physical')
                                            <span><b>@lang('Availability :') </b></span>
                                            @if ($productt->emptyStock())
                                                <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
                                            @else
                                                {{ $gs->show_stock == 0 ? '' : $productt->stock }} {{ __('In Stock') }}
                                            @endif
                                        @endif

                                    </li>


                                    @if ($productt->ship != null)
                                        <li>
                                            <span><b>@lang('Estimated Shipping Time :') </b></span>
                                            <span>{{ $productt->ship }}</span>
                                        </li>
                                    @endif
                                    @if ($productt->sku != null)
                                        <li>
                                            <span><b>@lang('Product SKU :') </b></span>
                                            <span>{{ $productt->sku }} </span>
                                        </li>
                                    @endif

                                    @if ($productt->type == 'License')
                                        @if ($productt->platform != null)
                                            <span><b>@lang('Platform:') </b></span>
                                            <span>{{ $productt->platform }} </span>
                                        @endif
                                        @if ($productt->region != null)
                                            <span><b>@lang('Region:') </b></span>
                                            <span>{{ $productt->region }} </span>
                                        @endif
                                        @if ($productt->licence_type != null)
                                            <span><b>@lang('License Type:') </b></span>
                                            <span>{{ $productt->licence_type }} </span>
                                        @endif
                                    @endif
                                </ul>
                            </div>

                        @endif


                        @if (!empty($productt->attributes))
                            @php
                                $attrArr = json_decode($productt->attributes, true);
                            @endphp
                        @endif

                        @if (!empty($attrArr))
                            <hr>
                            <div class="row gy-4">
                                @foreach ($attrArr as $attrKey => $attrVal)
                                    @if (array_key_exists('details_status', $attrVal) && $attrVal['details_status'] == 1)
                                        <div class="col-lg-6">
                                            <div class="attribute-wrapper">
                                                <span class="attribute-title">{{ str_replace('_', ' ', $attrKey) }}
                                                    :</span>
                                                <ul>
                                                    @foreach ($attrVal['values'] as $optionKey => $optionVal)
                                                        <li class="gs-radio-wrapper">
                                                            <input type="radio"
                                                                id="{{ $attrKey }}{{ $optionKey }}"
                                                                data-key="{{ $attrKey }}"
                                                                data-price = "{{ $attrVal['prices'][$optionKey] * $curr->value }}"
                                                                value="{{ $optionVal }}" name="{{ $attrKey }}"
                                                                {{ $loop->first ? 'checked' : '' }} class="cart_attr">
                                                            <label class="icon-label" for="w1">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                                    height="20" viewBox="0 0 20 20" fill="none">
                                                                    <rect x="0.5" y="0.5" width="19" height="19"
                                                                        rx="9.5" fill="#FDFDFD" />
                                                                    <rect x="0.5" y="0.5" width="19" height="19"
                                                                        rx="9.5" stroke="#EE1243" />
                                                                    <circle cx="10" cy="10" r="4"
                                                                        fill="#EE1243" />
                                                                </svg>
                                                            </label>
                                                            <label for="{{ $attrKey }}{{ $optionKey }}">
                                                                {{ $optionVal }}
                                                                @if (!empty($attrVal['prices'][$optionKey]))
                                                                    +
                                                                    {{ $curr->sign }}
                                                                    {{ $attrVal['prices'][$optionKey] * $curr->value }}
                                                                @endif
                                                            </label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <hr>
                        @endif


                        @if ($productt->stock_check == 1)
                            @if (!empty($productt->size))
                                <!-- product size -->
                                <div class="variation-wrapper variation-sizes">
                                    <span class="varition-title">@lang('Size :')</span>
                                    <ul>
                                        @foreach (array_unique($productt->size) as $key => $data1)
                                            <li class="{{ $loop->first ? 'active' : '' }} cart_size"
                                                data-price="{{ $productt->size_price[$key] * $curr->value }}">
                                                <input {{ $loop->first ? 'checked' : '' }} type="radio"
                                                    id="size_{{ $key }}" data-value="{{ $key }}"
                                                    data-key="{{ str_replace(' ', '', $data1) }}"
                                                    data-price="{{ $productt->size_price[$key] * $curr->value }}"
                                                    data-qty="{{ $productt->size_qty[$key] }}" value="{{ $key }}"
                                                    name="size">
                                                <label for="size_{{ $key }}">{{ $data1 }}</label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif


                            @if (!empty($productt->color_all))
                                <!-- product colors -->
                                <div class="variation-wrapper variation-colors">
                                    <span class="varition-title">@lang('Color :')</span>
                                    <ul>
                                        @foreach ($productt->color_all as $ckey => $color1)
                                            <li class="{{ $loop->first ? 'active' : '' }} cart_color">
                                                <input {{ $loop->first ? 'checked' : '' }} type="radio" data-price="0"
                                                    data-color="{{ $color1 }}" id="color_{{ $ckey }}"
                                                    name="colors" value="{{ $ckey }}">
                                                <label for="color_{{ $ckey }}"
                                                    data-color-code="{{ $color1 }}"
                                                    data-outline-color-code="{{ $color1 }}"></label>
                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                            @endif

                        @endif



                        @if ($productt->type == 'Physical')

                            @if (is_array($productt->size))
                                <input type="hidden" id="stock" value="{{ $productt->size_qty[0] }}">
                            @else
                                @if (!$productt->emptyStock())
                                    @if ($productt->stock_check == 1)
                                        <input type="hidden" id="stock" value="{{ $productt->size_price[0] }}">
                                    @else
                                        <input type="hidden" id="stock" value="{{ $productt->stock }}">
                                    @endif
                                @elseif($productt->type != 'Physical')
                                    <input type="hidden" id="stock" value="0">
                                @else
                                    <input type="hidden" id="stock" value="">
                                @endif
                            @endif


                            <!-- add-qty-wrapper -->
                            <div class="add-qty-wrapper">
                                <span class="varition-title">@lang('Quantity :')</span>
                                <div class="product-input-wrapper">
                                    <button class="action-btn qtminus" type="button">-</button>

                                    <input class="qty-input qttotal" type="text" readonly id="order-qty"
                                        value="{{ $productt->minimum_qty == null ? '1' : (int) $productt->minimum_qty }}">

                                    <input class="qty-input" type="hidden" id="affilate_user"
                                        value="{{ $productt->minimum_qty == null ? '1' : (int) $productt->minimum_qty }}">

                                    <input class="qty-input" type="hidden" id="product_minimum_qty"
                                        value="{{ $productt->minimum_qty == null ? '1' : (int) $productt->minimum_qty }}">
                                    <button class="action-btn qtplus" type="button">+</button>
                                </div>
                            </div>

                        @endif

                        <input type="hidden" id="product_price"
                            value="{{ round($productt->vendorPrice() * $curr->value, 2) }}">
                        <input type="hidden" id="product_id" value="{{ $productt->id }}">
                        <input type="hidden" id="curr_pos" value="{{ $gs->currency_format }}">
                        <input type="hidden" id="curr_sign" value="{{ $curr->sign }}">


                        <!-- add to cart buy btn wrapper -->
                        <div class="row row-cols-2">
                            <div class="col">
                                <button type="button" class="template-btn dark-btn w-100" id="addtodetailscart">
                                    @lang('add to cart')
                                </button>
                            </div>
                            <div class="col">
                                <button type="button" class="template-btn w-100"
                                    id="addtobycard">@lang('buy now')</button>
                            </div>
                        </div>

                        <!-- wish-compare-report-wrapper -->
                        <div class="wish-compare-report-wrapper">
                            @if (Auth::check())
                                <a href="javascript:;" data-href="{{ route('user-wishlist-add', $productt->id) }}"
                                    class="link wishlist">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                            stroke="#030712" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span class="title">@lang('Add to Wishlist')</span>
                                </a>
                            @else
                                <a href="{{ route('user.login') }}" class="link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                            stroke="#030712" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span class="title">@lang('Add to Wishlist')</span>
                                </a>
                            @endif

                            @if ($productt->type != 'Listing')
                                <a data-href="{{ route('product.compare.add', $productt->id) }}" href="javascript:;"
                                    class="link compare_product">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M18.1777 8C23.2737 8 23.2737 16 18.1777 16C13.0827 16 11.0447 8 5.43875 8C0.85375 8 0.85375 16 5.43875 16C11.0447 16 13.0828 8 18.1788 8H18.1777Z"
                                            stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span class="title">@lang('Add to Compare')</span>
                                </a>
                            @endif

                            @if ($gs->is_report == 1)
                                @if (Auth::guard('web')->check())
                                    <a href="#" class="link report-item" href="javascript:;"
                                        data-bs-toggle="modal" data-bs-target="#report-modal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M4 15C4 15 5 14 8 14C11 14 13 16 16 16C19 16 20 15 20 15V3C20 3 19 4 16 4C13 4 11 2 8 2C5 2 4 3 4 3L4 22"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <span class="title">@lang('Report This Item')</span>
                                    </a>
                                @else
                                    <a href="{{ route('user.login') }}" class="link">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M4 15C4 15 5 14 8 14C11 14 13 16 16 16C19 16 20 15 20 15V3C20 3 19 4 16 4C13 4 11 2 8 2C5 2 4 3 4 3L4 22"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <span class="title">@lang('Report This Item')</span>

                                    </a>
                                @endif
                            @endif

                        </div>

                        <hr>

                        <!-- share links -->
                        <div class="share-links social-linkss social-sharing a2a_kit a2a_kit_size_32">
                            <h4>@lang('Share:')</h4>
                            <div class="share-links-wrapper social-icons py-1 share-product social-linkss">
                                <a class="facebook a2a_button_facebook" href="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <g clip-path="url(#clip0_428_16025)">
                                            <path
                                                d="M20 40C31.0457 40 40 31.0457 40 20C40 8.9543 31.0457 0 20 0C8.9543 0 0 8.9543 0 20C0 31.0457 8.9543 40 20 40Z"
                                                fill="#3A559F" />
                                            <path
                                                d="M26.3375 17.6318L25.9352 21.1159C25.9185 21.2749 25.844 21.4222 25.7259 21.5299C25.6077 21.6375 25.4542 21.6981 25.2943 21.7H21.6579L21.6398 32.0295C21.641 32.152 21.594 32.2699 21.5089 32.358C21.4239 32.446 21.3076 32.497 21.1852 32.5H17.5011C17.4399 32.5 17.3792 32.4877 17.3229 32.4637C17.2665 32.4398 17.2156 32.4047 17.1731 32.3605C17.1307 32.3164 17.0976 32.2641 17.0758 32.2068C17.0541 32.1495 17.0441 32.0885 17.0466 32.0273V21.7H14.3193C14.2337 21.6991 14.149 21.6814 14.0702 21.6477C13.9914 21.6141 13.92 21.5653 13.8601 21.5041C13.8001 21.4429 13.7528 21.3705 13.7209 21.291C13.6889 21.2116 13.673 21.1266 13.6739 21.0409L13.6602 17.5568C13.659 17.4709 13.6748 17.3855 13.7065 17.3056C13.7383 17.2258 13.7855 17.1529 13.8455 17.0913C13.9054 17.0297 13.9769 16.9805 14.0559 16.9465C14.1348 16.9125 14.2197 16.8944 14.3057 16.8932H17.0466V13.5295C17.0466 9.625 19.3648 7.5 22.7648 7.5H25.5511C25.6373 7.50089 25.7224 7.51878 25.8016 7.55264C25.8809 7.5865 25.9526 7.63567 26.0128 7.69733C26.073 7.75898 26.1204 7.83191 26.1523 7.91193C26.1842 7.99195 26.2001 8.07749 26.1989 8.16364V11.0955C26.2001 11.1816 26.1842 11.2671 26.1523 11.3472C26.1204 11.4272 26.073 11.5001 26.0128 11.5618C25.9526 11.6234 25.8809 11.6726 25.8016 11.7065C25.7224 11.7403 25.6373 11.7582 25.5511 11.7591H23.842C21.9943 11.7591 21.6352 12.6682 21.6352 13.9818V16.8932H25.6966C25.7889 16.8943 25.88 16.915 25.9638 16.9539C26.0476 16.9928 26.1222 17.0489 26.1828 17.1187C26.2433 17.1885 26.2884 17.2703 26.3151 17.3587C26.3417 17.4471 26.3494 17.5402 26.3375 17.6318Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_428_16025">
                                                <rect width="40" height="40" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </a>

                                <a class="twitter a2a_button_twitter" href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <g clip-path="url(#clip0_428_16036)">
                                            <path
                                                d="M20 40C31.0457 40 40 31.0457 40 20C40 8.95431 31.0457 0 20 0C8.95431 0 0 8.95431 0 20C0 31.0457 8.95431 40 20 40Z"
                                                fill="#00A6DE" />
                                            <path
                                                d="M29.9599 13.9475C29.4871 14.6577 28.9095 15.2921 28.2467 15.8291C28.1631 15.8988 28.0962 15.9861 28.0506 16.0849C28.0051 16.1837 27.9822 16.2914 27.9835 16.4002V16.466C27.9758 17.8921 27.7138 19.3055 27.2099 20.6396C26.6906 22.04 25.9191 23.3332 24.9335 24.4554C23.6092 25.9631 21.9055 27.0889 19.9993 27.716C18.7468 28.1225 17.4372 28.3259 16.1204 28.3186C14.0313 28.3183 11.9794 27.7654 10.173 26.716C10.1083 26.6792 10.0573 26.6222 10.0279 26.5539C9.99844 26.4855 9.99211 26.4093 10.0099 26.337C10.0281 26.2659 10.0696 26.2028 10.1278 26.158C10.186 26.1132 10.2575 26.0891 10.3309 26.0896H10.8309C12.4185 26.0935 13.971 25.6225 15.2888 24.737C14.5035 24.6099 13.7685 24.2684 13.1649 23.7502C12.5612 23.232 12.1124 22.5572 11.8678 21.8002C11.8552 21.7623 11.853 21.7217 11.8612 21.6826C11.8694 21.6436 11.8878 21.6074 11.9145 21.5777C11.9412 21.548 11.9753 21.526 12.0133 21.5137C12.0513 21.5015 12.0919 21.4996 12.1309 21.5081C12.3704 21.5531 12.6135 21.576 12.8572 21.5765H12.9493C12.1903 21.2278 11.5486 20.6667 11.1016 19.961C10.6547 19.2554 10.4218 18.4354 10.4309 17.6002C10.4312 17.5611 10.4416 17.5227 10.4611 17.4889C10.4806 17.455 10.5085 17.4267 10.5421 17.4069C10.5758 17.387 10.614 17.3762 10.6531 17.3755C10.6922 17.3748 10.7308 17.3842 10.7651 17.4028C11.0738 17.5752 11.4051 17.7036 11.7493 17.7844C11.0689 17.1112 10.6313 16.2312 10.5051 15.2825C10.3789 14.3337 10.5712 13.3699 11.052 12.5423C11.0702 12.5105 11.0958 12.4836 11.1267 12.4639C11.1575 12.4442 11.1927 12.4323 11.2292 12.4292C11.2657 12.4261 11.3024 12.4319 11.3362 12.4461C11.3699 12.4603 11.3997 12.4824 11.423 12.5107C12.3735 13.6875 13.5506 14.6615 14.8846 15.3748C16.2186 16.0882 17.6823 16.5263 19.1888 16.6633H19.2099C19.232 16.6627 19.2538 16.6575 19.2739 16.6482C19.2939 16.6389 19.3119 16.6256 19.3267 16.6091C19.3415 16.5926 19.3527 16.5733 19.3598 16.5523C19.3668 16.5313 19.3696 16.5091 19.3678 16.487C19.309 15.9589 19.3428 15.4246 19.4678 14.9081C19.6376 14.206 19.9861 13.5598 20.4795 13.0323C20.9729 12.5047 21.5944 12.1138 22.2835 11.8975C22.7257 11.7594 23.1861 11.6885 23.6493 11.687C24.6997 11.6858 25.7142 12.0686 26.502 12.7633C26.604 12.8546 26.7361 12.9052 26.873 12.9054C26.9175 12.9045 26.9616 12.8983 27.0046 12.887C27.7448 12.7 28.4539 12.4066 29.1099 12.016C29.1491 11.9917 29.1949 11.9803 29.2409 11.9831C29.287 11.9859 29.331 12.0029 29.367 12.0317C29.403 12.0605 29.4293 12.0997 29.4421 12.144C29.455 12.1883 29.4538 12.2355 29.4388 12.2791C29.2198 12.968 28.8296 13.5901 28.3046 14.087C28.7775 13.9743 29.2391 13.8183 29.6835 13.6212C29.7274 13.6043 29.7755 13.6014 29.821 13.6131C29.8666 13.6247 29.9074 13.6502 29.9378 13.6861C29.9682 13.722 29.9866 13.7664 29.9906 13.8133C29.9945 13.8601 29.9838 13.9071 29.9599 13.9475Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_428_16036">
                                                <rect width="40" height="40" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </a>
                                <a class="linkedin a2a_button_linkedin" href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <g clip-path="url(#clip0_428_16041)">
                                            <path
                                                d="M20 40C31.0457 40 40 31.0457 40 20C40 8.95431 31.0457 0 20 0C8.95431 0 0 8.95431 0 20C0 31.0457 8.95431 40 20 40Z"
                                                fill="#0B69C7" />
                                            <path
                                                d="M15.5257 12.7289C15.5262 13.2694 15.3664 13.7979 15.0664 14.2475C14.7665 14.6971 14.3399 15.0477 13.8406 15.2547C13.3414 15.4618 12.7919 15.5161 12.2618 15.4108C11.7317 15.3055 11.2447 15.0453 10.8626 14.6631C10.4804 14.2809 10.2202 13.794 10.1149 13.2638C10.0095 12.7337 10.0639 12.1843 10.2709 11.685C10.478 11.1858 10.8285 10.7592 11.2781 10.4593C11.7277 10.1593 12.2562 9.99948 12.7967 10C13.5203 10.0007 14.214 10.2884 14.7256 10.8001C15.2372 11.3117 15.525 12.0054 15.5257 12.7289Z"
                                                fill="white" />
                                            <path
                                                d="M14.2336 16.5974H11.3599C10.9864 16.5974 10.6836 16.9002 10.6836 17.2737V29.3237C10.6836 29.6972 10.9864 30 11.3599 30H14.2336C14.6071 30 14.9099 29.6972 14.9099 29.3237V17.2737C14.9099 16.9002 14.6071 16.5974 14.2336 16.5974Z"
                                                fill="white" />
                                            <path
                                                d="M29.9336 23.5446V29.3789C29.9336 29.5436 29.8681 29.7015 29.7517 29.818C29.6352 29.9345 29.4772 29.9999 29.3125 29.9999H26.2283C26.0636 29.9999 25.9056 29.9345 25.7891 29.818C25.6727 29.7015 25.6072 29.5436 25.6072 29.3789V23.7262C25.6072 22.8815 25.852 20.042 23.402 20.042C21.502 20.042 21.1151 21.9946 21.0336 22.871V29.392C21.0301 29.5535 20.9639 29.7074 20.849 29.8208C20.734 29.9343 20.5793 29.9986 20.4178 29.9999H17.4336C17.3519 30.0003 17.271 29.9844 17.1955 29.9533C17.12 29.9223 17.0514 29.8765 16.9936 29.8188C16.9359 29.761 16.8902 29.6924 16.8591 29.6169C16.828 29.5414 16.8122 29.4605 16.8125 29.3789V17.221C16.8122 17.1392 16.828 17.0581 16.859 16.9825C16.8901 16.9068 16.9358 16.8381 16.9935 16.7801C17.0512 16.7222 17.1198 16.6762 17.1953 16.6448C17.2708 16.6134 17.3518 16.5973 17.4336 16.5973H20.4178C20.5006 16.5959 20.5828 16.611 20.6597 16.6417C20.7366 16.6724 20.8066 16.7181 20.8656 16.7762C20.9246 16.8342 20.9715 16.9035 21.0035 16.9798C21.0355 17.0562 21.052 17.1382 21.052 17.221V18.2736C21.7572 17.221 22.8046 16.3999 25.0336 16.3999C29.9625 16.3973 29.9336 21.0104 29.9336 23.5446Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_428_16041">
                                                <rect width="40" height="40" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </a>

                                <a class="instagram a2a_button_whatsapp" href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <g clip-path="url(#clip0_428_16052)">
                                            <path
                                                d="M20 40C31.0457 40 40 31.0457 40 20C40 8.95431 31.0457 0 20 0C8.95431 0 0 8.95431 0 20C0 31.0457 8.95431 40 20 40Z"
                                                fill="#2AA81A" />
                                            <path
                                                d="M27.0538 12.9447C25.3896 11.2616 23.1784 10.229 20.8196 10.0333C18.4607 9.83754 16.1096 10.4916 14.1907 11.8774C12.2718 13.2632 10.9116 15.2894 10.3557 17.5901C9.79973 19.8909 10.0847 22.3146 11.1591 24.4237L10.1065 29.5342C10.0952 29.5852 10.0947 29.6381 10.1052 29.6893C10.1156 29.7405 10.1367 29.789 10.167 29.8315C10.2104 29.896 10.2725 29.9457 10.3449 29.9739C10.4173 30.0021 10.4967 30.0075 10.5722 29.9894L15.5722 28.8026C17.6755 29.8488 20.0818 30.1148 22.3628 29.5531C24.6438 28.9915 26.6515 27.6386 28.0286 25.7354C29.4056 23.8322 30.0626 21.5021 29.8827 19.1599C29.7027 16.8176 28.6975 14.6152 27.0459 12.9447H27.0538ZM25.4907 25.4105C24.3397 26.5594 22.8575 27.3188 21.2526 27.5817C19.6477 27.8446 18.0007 27.5979 16.5433 26.8763L15.8433 26.5289L12.7722 27.2552V27.2158L13.4196 24.1184L13.0775 23.4447C12.3323 21.9833 12.0692 20.3234 12.3259 18.7032C12.5826 17.083 13.346 15.5857 14.5065 14.4263C15.9636 12.9707 17.939 12.1531 19.9986 12.1531C22.0582 12.1531 24.0335 12.9707 25.4907 14.4263L25.5249 14.4737C26.9631 15.9339 27.7659 17.9033 27.7585 19.9528C27.7511 22.0024 26.9341 23.9659 25.4854 25.4158L25.4907 25.4105Z"
                                                fill="white" />
                                            <path
                                                d="M25.221 23.1578C24.8447 23.7499 24.2473 24.4735 23.5026 24.6551C22.1868 24.9709 20.1841 24.6551 17.6762 22.3367L17.6447 22.3078C15.4631 20.2709 14.8841 18.5735 15.0131 17.2288C15.0894 16.463 15.7262 15.7735 16.2631 15.3209C16.3484 15.2489 16.4494 15.1979 16.558 15.172C16.6666 15.146 16.7798 15.1458 16.8884 15.1714C16.9971 15.1969 17.0983 15.2476 17.1839 15.3192C17.2695 15.3909 17.3372 15.4816 17.3815 15.5841L18.1894 17.4104C18.2421 17.5279 18.2619 17.6576 18.2466 17.7856C18.2313 17.9135 18.1815 18.0349 18.1026 18.1367L17.692 18.663C17.6067 18.7723 17.5559 18.9045 17.546 19.0427C17.5362 19.181 17.5678 19.3191 17.6368 19.4393C18.0267 20.0141 18.4933 20.5329 19.0236 20.9814C19.5942 21.5305 20.2412 21.9941 20.9447 22.3578C21.0731 22.4095 21.2139 22.4219 21.3494 22.3933C21.4848 22.3647 21.6087 22.2964 21.7052 22.1972L22.1762 21.7183C22.2659 21.6219 22.3804 21.5522 22.5072 21.5169C22.634 21.4816 22.7681 21.4821 22.8947 21.5183L24.8157 22.0683C24.9246 22.0993 25.0246 22.1554 25.1077 22.2322C25.1909 22.309 25.2547 22.4043 25.2942 22.5104C25.3336 22.6165 25.3476 22.7304 25.3349 22.8428C25.3221 22.9553 25.2831 23.0632 25.221 23.1578Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_428_16052">
                                                <rect width="40" height="40" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </a>

                            </div>
                        </div>
                        <script async src="https://static.addtoany.com/menu/page.js"></script>
                        <!-- store & seller -->
                        <div class="store-seller-wrapper">
                            <span> <b>@lang('Sold By :')</b>

                                @if ($productt->user_id != 0)
                                    @if (isset($productt->user))
                                        {{ $productt->user->shop_name }}
                                    @endif
                                    @if ($productt->user->checkStatus())
                                        <a class="verify-link" href="javascript:;" data-original-title="">
                                            {{ __('Verified') }} <i class="fas fa-check-circle"></i>
                                        </a>
                                    @endif
                                @else
                                    {{ App\Models\Admin::find(1)->shop_name }}
                                @endif


                            </span>
                            <span> <b>@lang('Total Items :')</b>
                                @if ($productt->user_id != 0)
                                    {{ App\Models\Product::where('user_id', '=', $productt->user_id)->get()->count() }}
                                @else
                                    {{ App\Models\Product::where('user_id', '=', 0)->get()->count() }}
                                @endif
                            </span>

                            <div class="action-btns-wrapper">

                                @if ($productt->user_id != 0)
                                    <a class="template-btn dark-outline"
                                        href="{{ route('front.vendor', str_replace(' ', '-', $productt->user->shop_name)) }}">@lang('visit store')</a>
                                @endif


                                @if ($gs->is_contact_seller == 1)


                                    @if (Auth::check())
                                        @if ($productt->user_id != 0)
                                            <a class="template-btn dark-outline" href="javascript:;"
                                                data-bs-toggle="modal" data-bs-target="#vendorform">
                                                <i class="icofont-ui-chat"></i>
                                                {{ __('Contact Seller') }}
                                            </a>
                                        @else
                                            <a class="template-btn dark-outline" href="javascript:;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#sendMessage">@lang('contact seller')</a>
                                        @endif
                                    @else
                                        <a class="template-btn dark-outline" href="{{ route('user.login') }}">
                                            <i class="icofont-ui-chat"></i>
                                            {{ __('Contact Seller') }}
                                        </a>
                                    @endif

                                @endif

                                @if ($productt->user_id != 0)
                                    @if (Auth::check())
                                        @if (Auth::user()->favorites()->where('vendor_id', '=', $productt->user_id)->get()->count() > 0)
                                            <a class="template-btn dark-outline" href="javascript:;" >
                                                <i class="fas fa-check"></i>
                                                {{ __('Favorite') }}
                                            </a>
                                        @else
                                            <a class="template-btn dark-outline favorite-prod" href="javascript:;"
                                                data-href="{{ route('user-favorite', [Auth::user()->id, $productt->user_id]) }}">
                                                <i class="icofont-plus"></i>
                                                {{ __('Add To Favorite Seller') }}
                                            </a>
                                        @endif
                                    @else
                                        <a class="template-btn dark-outline" href="{{ route('user.login') }}">
                                            <i class="icofont-plus"></i>
                                            {{ __('Add To Favorite Seller') }}
                                        </a>
                                    @endif
                                @endif

                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- single product details content wrapper end -->

    <!--  tab-product-des-wrapper start -->
    <div class="tab-product-des-wrapper wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab"
                        data-bs-target="#description-tab-pane" type="button" role="tab"
                        aria-controls="description-tab-pane" aria-selected="true">
                        @lang('Description')
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="buy-return-policy-tab" data-bs-toggle="tab"
                        data-bs-target="#buy-return-policy-tab-pane" type="button" role="tab"
                        aria-controls="buy-return-policy-tab-pane" aria-selected="false">
                        @lang('Buy / Return Policy')
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-tab-pane"
                        type="button" role="tab" aria-controls="reviews-tab-pane" aria-selected="false">
                        @lang('Reviews')
                    </button>
                </li>

                @if ($productt->whole_sell_qty != null && $productt->whole_sell_qty != '')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="whole-sell-tab" data-bs-toggle="tab"
                            data-bs-target="#whole-sell-tab-pane" type="button" role="tab"
                            aria-controls="whole-sell-tab-pane" aria-selected="false">
                            @lang('Whole sell')
                        </button>
                    </li>
                @endif

            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane show active wow-replaced" data-wow-delay=".1s" id="description-tab-pane"
                    role="tabpanel" aria-labelledby="description-tab" tabindex="0">
                    {!! clean($productt->details, ['Attr.EnableID' => true]) !!}
                </div>
                <div class="tab-pane fade" id="buy-return-policy-tab-pane" role="tabpanel"
                    aria-labelledby="buy-return-policy-tab" tabindex="0">
                    {!! clean($productt->policy, ['Attr.EnableID' => true]) !!}
                </div>

                <!-- Reviews tab content start  -->
                <div class="tab-pane fade" id="reviews-tab-pane" role="tabpanel" aria-labelledby="reviews-tab"
                    tabindex="0">
                    <div class="row review-tab-content-wrapper">
                        <div class="col-xxl-8">
                            <div id="comments">
                                <h5 class="woocommerce-Reviews-titleDDD my-3"> @lang('Ratings & Reviews')</h5>
                                <ul class="all-comments">
                                    @forelse($productt->ratings() as $review)
                                        <li>
                                            <div class="single-comment">
                                                <div class="left-area">
                                                    <img src="{{ $review->user->photo ? asset('assets/images/users/' . $review->user->photo) : asset('assets/images/' . $gs->user_image) }}"
                                                        alt="">
                                                    <p class="name text-lg">
                                                        {{ $review->user->name }}
                                                    </p>
                                                    <div class="reating-area">
                                                        <div class="stars"><span
                                                                id="star-rating">{{ $review->rating }}</span> <i
                                                                class="fas fa-star"></i></div>
                                                        <p class="date">
                                                            {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $review->review_date)->diffForHumans() }}
                                                        </p>
                                                    </div>

                                                </div>
                                                <div class="right-area">
                                                    <div class="comment-body">
                                                        <p>
                                                            {{ $review->review }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @empty
                                        <li>
                                            <div class="single-comment">
                                                <div class="left-area">
                                                    <p class="name text-lg">
                                                        @lang('No Review Found')
                                                    </p>
                                                </div>
                                            </div>
                                        </li>
                                    @endforelse
                                </ul>
                            </div>


                            @if (Auth::check())
                                <div id="review_form_wrapper">

                                    <div class="review-area">

                                        <h5 class="title">@lang('Reviews')</h5>
                                        <div class="star-area">
                                            <ul class="star-list">
                                                <li class="stars" data-val="1">
                                                    <i class="fas fa-star"></i>
                                                </li>
                                                <li class="stars" data-val="2">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </li>
                                                <li class="stars" data-val="3">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </li>
                                                <li class="stars" data-val="4">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </li>
                                                <li class="stars active" data-val="5">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>


                                    <div class="write-comment-area">

                                        <form action="{{ route('front.review.submit') }}"
                                            data-href="{{ route('front.reviews', $productt->id) }}"
                                            data-side-href="{{ route('front.side.reviews', $productt->id) }}"
                                            method="POST">
                                            @csrf
                                            <input type="hidden" id="rating" name="rating" value="5">
                                            <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
                                            <input type="hidden" name="product_id" value="{{ $productt->id }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <textarea name="review" placeholder="{{ __('Write Your Review *') }}" required></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <button class="template-btn"
                                                        type="submit">{{ __('Submit') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <h5 class="text-center">
                                    <a href="{{ route('user.login') }}" class="btn login-btn mr-1">
                                        {{ __('Login') }}
                                    </a>
                                    {{ __('To Review') }}
                                </h5>
                            @endif

                        </div>
                    </div>
                </div>
                <!-- Reviews tab content end -->





                @if ($productt->whole_sell_qty != null && $productt->whole_sell_qty != '')
                    <!-- Wholesell Tab content start  -->
                    <div class="tab-pane fade" id="whole-sell-tab-pane" role="tabpanel" aria-labelledby="whole-sell-tab"
                        tabindex="0">
                        <div class="row sholesell-tab-content-wrapper">
                            <div class="col-12 col-lg-8 col-xl-9 col-xxl-8">
                                <div class="pro-summary ">
                                    <div class="price-summary">
                                        <div class="price-summary-content">
                                            <p class="title text-center text-lg">@lang('Wholesell')</p>
                                            <ul class="price-summary-list">
                                                <li class="regular-price">
                                                    <p class="fw-medium">@lang('Quantity')</p>
                                                    <p class="fw-medium">
                                                        @lang('Discount')
                                                    </p>
                                                </li>

                                                @foreach ($productt->whole_sell_qty as $key => $data1)
                                                    <li class="selling-price">
                                                        <label>{{ $productt->whole_sell_qty[$key] }}+</label> <span><span
                                                                class="woocommerce-Price-amount amount">{{ $productt->whole_sell_discount[$key] }}%
                                                                @lang('Off')
                                                            </span>
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!--  tab-product-des-wrapper end -->




    <!-- Related Products slider start -->
    <div class="gs-product-cards-slider-area wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <h2 class="title text-center">@lang('Related Products')</h2>
            <div class="product-cards-slider">
                @foreach (App\Models\Product::where('type', $productt->type)->where('product_type', $productt->product_type)->withCount('ratings')->withAvg('ratings', 'rating')->take(12)->get() as $product)
                    @include('includes.frontend.home_product', ['class' => 'not'])
                @endforeach
            </div>
        </div>
    </div>
    <!-- Related Products slider end -->

    <!-- More Products By Seller slider start -->
    @if ($productt->user_id != 0 && $vendor_products->count() > 0)
        <div class="gs-product-cards-slider-section more-products-by-seller  wow-replaced" data-wow-delay=".1s">
            <div class="gs-product-cards-slider-area more-products-by-seller">
                <div class="container">
                    <h2 class="title text-center">@lang('More Products By Seller')</h2>
                    <div class="product-cards-slider">
                        @foreach ($vendor_products as $product)
                            @include('includes.frontend.home_product', ['class' => 'not'])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- More Products By Seller slider end -->





    <!-- Product report Modal Start -->
    @if (auth()->check())
        <div class="modal gs-modal fade" id="report-modal" tabindex="-1" aria-hidden="true">
            <form action="{{ route('product.report') }}" method="POST"
                class="modal-dialog assign-rider-modal-dialog modal-dialog-centered">
                {{ csrf_field() }}
                <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
                <input type="hidden" name="product_id" value="{{ $productt->id }}">

                <div class="modal-content assign-rider-modal-content form-group">
                    <div class="modal-header w-100">
                        <h4 class="title">{{ __('REPORT PRODUCT') }}</h4>
                        <button type="button" data-bs-dismiss="modal">
                            <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                        </button>

                    </div>
                    <!-- modal body start  -->
                    <!-- Select Rider -->
                    <div class="input-label-wrapper w-100">
                        <label>{{ __('Please give the following details') }}</label>
                        <input type="text" name="title" class="form-control mb-3"
                            placeholder="{{ __('Enter Report Title') }}" required="">

                        <textarea name="note" class="form-control border  p-3" placeholder="{{ __('Enter Report Note') }}"
                            required=""></textarea>



                    </div>

                    <!-- Assign Rider Button  -->
                    <button class="template-btn" data-bs-dismiss="modal" type="submit">{{ __('SUBMIT') }}</button>
                    <!-- modal body end  -->
                </div>
            </form>
        </div>
    @endif
    <!-- Product report Modal End -->



    {{-- MESSAGE MODAL ENDS --}}

    {{-- MESSAGE MODAL ENDS --}}


    <div class="modal gs-modal fade" id="vendorform" tabindex="-1" aria-modal="true" role="dialog">
        <form action="{{ route('user-send-message') }}" id="emailreply" method="POST"
            class="modal-dialog assign-rider-modal-dialog modal-dialog-centered emailreply">
            {{ csrf_field() }}
            <div class="modal-content assign-rider-modal-content form-group">
                <div class="modal-header w-100">
                    <h4 class="title">@lang('Send Message')</h4>
                    <button type="button" data-bs-dismiss="modal">
                        <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                    </button>

                </div>
                <!-- modal body start  -->
                <!-- Select Rider -->
                <div class="input-label-wrapper w-100">

                    <input type="text" class="form-control  border px-3 mb-4" id="eml" name="email"
                        name="subject" readonly placeholder="@lang('Select Rider')"
                        value="{{ auth()->user() ? auth()->user()->email : '' }}">
                    <input type="text" class="form-control  border px-3 mb-4" name="subject"
                        placeholder="@lang('Subject')" required="">

                    <textarea class="form-control  border px-3 mb-4" name="message" placeholder="{{ __('Your Message') }}"
                        required=""></textarea>

                    <input type="hidden" name="name" value="{{ Auth::user() ? Auth::user()->name : '' }}">
                    <input type="hidden" name="user_id" value="{{ Auth::user() ? Auth::user()->id : '' }}">
                    <input type="hidden" name="vendor_id" value="{{ $productt->user_id }}">

                </div>
                <!-- Select Pickup Point -->

                <!-- Assign Rider Button  -->
                <button class="template-btn" data-bs-dismiss="modal" type="submit">@lang('Send Message')</button>
                <!-- modal body end  -->
            </div>
        </form>
    </div>




    <div class="modal gs-modal fade" id="sendMessage" tabindex="-1" aria-modal="true" role="dialog">
        <form action="{{ route('user-send-message') }}" method="POST"
            class="modal-dialog assign-rider-modal-dialog modal-dialog-centered emailreply">
            {{ csrf_field() }}
            <div class="modal-content assign-rider-modal-content form-group">
                <div class="modal-header w-100">
                    <h4 class="title">@lang('Send Message')</h4>
                    <button type="button" data-bs-dismiss="modal">
                        <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                    </button>

                </div>
                <!-- modal body start  -->

                <div class="input-label-wrapper w-100">

                    <div class="dropdown-container">
                        <input type="text" class="form-control form__control border px-3 mb-4" name="subject"
                            placeholder="@lang('Subject')" required="">

                        <textarea class="form-control form__control border px-3 mb-4" name="message" placeholder="{{ __('Your Message') }}"
                            required=""></textarea>
                    </div>
                </div>

                <button class="template-btn" data-bs-dismiss="modal" type="submit">
                    @lang('Send Message')
                </button>
                <!-- modal body end  -->
            </div>
        </form>
    </div>





@endsection

@section('script')
    <script src="{{ asset('assets/front/js/jquery.elevatezoom.js') }}"></script>

    <!-- Initializing the slider -->


    <script type="text/javascript">
        (function($) {
            "use strict";

            //initiate the plugin and pass the id of the div containing gallery images
            $("#single-image-zoom").elevateZoom({
                gallery: 'gallery_09',
                zoomType: "inner",
                cursor: "crosshair",
                galleryActiveClass: 'active',
                imageCrossfade: true,
                loadingIcon: 'http://www.elevateweb.co.uk/spinner.gif'
            });
            //pass the images to Fancybox
            $("#single-image-zoom").bind("click", function(e) {
                var ez = $('#single-image-zoom').data('elevateZoom');
                $.fancybox(ez.getGalleryList());
                return false;
            });

            $(document).on("submit", "#emailreply", function() {
                var token = $(this).find('input[name=_token]').val();
                var subject = $(this).find('input[name=subject]').val();
                var message = $(this).find('textarea[name=message]').val();
                var email = $(this).find('input[name=email]').val();
                var name = $(this).find('input[name=name]').val();
                var user_id = $(this).find('input[name=user_id]').val();
                $('#eml').prop('disabled', true);
                $('#subj').prop('disabled', true);
                $('#msg').prop('disabled', true);
                $('#emlsub').prop('disabled', true);
                $.ajax({
                    type: 'post',
                    url: "{{ URL::to('/user/user/contact') }}",
                    data: {
                        '_token': token,
                        'subject': subject,
                        'message': message,
                        'email': email,
                        'name': name,
                        'user_id': user_id
                    },
                    success: function(data) {
                        $('#eml').prop('disabled', false);
                        $('#subj').prop('disabled', false);
                        $('#msg').prop('disabled', false);
                        $('#subj').val('');
                        $('#msg').val('');
                        $('#emlsub').prop('disabled', false);
                        if (data == 0)
                            toastr.error("Email Not Found");
                        else
                            toastr.success("Message Sent");
                        $('#vendorform').modal('hide');
                    }
                });
                return false;
            });

        })(jQuery);

        $('.add-to-affilate').on('click', function() {

            var value = $(this).data('href');
            var tempInput = document.createElement("input");
            tempInput.style = "position: absolute; left: -1000px; top: -1000px";
            tempInput.value = value;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            toastr.success('Affiliate Link Copied');

        });
    </script>
@endsection
