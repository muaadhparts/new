@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Contact')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.contact') }}">@lang('Contact')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <div class="gs-contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 wow-replaced" data-wow-delay=".1s">
                    <div class="contact-information">

                        <h3>@lang('Get in Touch')</h3>

                        @if ($ps->street != null)
                            <div class="common-wrapper address d-flex align-items-center">
                                <div class="address-icon icon-wrapper">

                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <mask id="mask0_6740_32525" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0"
                                            y="0" width="40" height="40">
                                            <rect width="40" height="40" fill="#D9D9D9" />
                                        </mask>
                                        <g mask="url(#mask0_6740_32525)">
                                            <path
                                                d="M26.625 18.3333V13.3333H21.625V10.8333H26.625V5.83325H29.125V10.8333H34.125V13.3333H29.125V18.3333H26.625ZM32.375 34.0833C29.1528 34.0833 25.9517 33.3121 22.7717 31.7699C19.5906 30.2288 16.75 28.2083 14.25 25.7083C11.75 23.2083 9.72944 20.3749 8.18833 17.2083C6.64611 14.0416 5.875 10.8333 5.875 7.58325C5.875 7.08325 6.04167 6.66659 6.375 6.33325C6.70833 5.99992 7.125 5.83325 7.625 5.83325H13.0417C13.4861 5.83325 13.8678 5.96492 14.1867 6.22825C14.5067 6.4927 14.7083 6.83325 14.7917 7.24992L15.75 12.1666C15.8056 12.5833 15.7917 12.9583 15.7083 13.2916C15.625 13.6249 15.4444 13.9166 15.1667 14.1666L11.3333 17.9166C12.6667 20.1388 14.2428 22.1527 16.0617 23.9583C17.8817 25.7638 19.9444 27.3333 22.25 28.6666L25.9583 24.9166C26.2361 24.6388 26.5628 24.4444 26.9383 24.3333C27.3128 24.2221 27.6944 24.1944 28.0833 24.2499L32.7083 25.2083C33.125 25.2916 33.4656 25.486 33.73 25.7916C33.9933 26.0971 34.125 26.4721 34.125 26.9166V32.3333C34.125 32.8333 33.9583 33.2499 33.625 33.5833C33.2917 33.9166 32.875 34.0833 32.375 34.0833ZM10.1667 15.5416L13.125 12.7083C13.1806 12.6527 13.2156 12.5899 13.23 12.5199C13.2433 12.451 13.2361 12.3888 13.2083 12.3333L12.5 8.58325C12.4722 8.49992 12.4306 8.43714 12.375 8.39492C12.3194 8.35381 12.25 8.33325 12.1667 8.33325H8.625C8.54167 8.33325 8.47889 8.35381 8.43667 8.39492C8.39556 8.43714 8.375 8.48603 8.375 8.54159C8.45833 9.68047 8.64611 10.8399 8.93833 12.0199C9.22944 13.201 9.63889 14.3749 10.1667 15.5416ZM24.5833 29.8333C25.6944 30.361 26.8472 30.7638 28.0417 31.0416C29.2361 31.3194 30.3611 31.4721 31.4167 31.4999C31.4722 31.4999 31.5211 31.4788 31.5633 31.4366C31.6044 31.3955 31.625 31.3471 31.625 31.2916V27.7916C31.625 27.7083 31.6044 27.6388 31.5633 27.5833C31.5211 27.5277 31.4583 27.486 31.375 27.4583L27.875 26.7499C27.8194 26.7221 27.7639 26.7221 27.7083 26.7499L27.5417 26.8333L24.5833 29.8333Z"
                                                fill="white" />
                                        </g>
                                    </svg>
                                </div>
                                <div class="address-details details-wrapper">
                                    <h5>@lang('Our Office Address')</h5>
                                    <h6>{{ $ps->street }}</h6>
                                </div>
                            </div>
                        @endif

                        @if ($ps->phone != null)
                            <div class="common-wrapper contact-number d-flex align-items-center">
                                <div class="number-icon icon-wrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <mask id="mask1_6740_32525" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0"
                                            y="0" width="40" height="40">
                                            <rect width="40" height="40" fill="#D9D9D9" />
                                        </mask>
                                        <g mask="url(#mask0_6740_32525)">
                                            <path
                                                d="M26.625 18.3333V13.3333H21.625V10.8333H26.625V5.83325H29.125V10.8333H34.125V13.3333H29.125V18.3333H26.625ZM32.375 34.0833C29.1528 34.0833 25.9517 33.3121 22.7717 31.7699C19.5906 30.2288 16.75 28.2083 14.25 25.7083C11.75 23.2083 9.72944 20.3749 8.18833 17.2083C6.64611 14.0416 5.875 10.8333 5.875 7.58325C5.875 7.08325 6.04167 6.66659 6.375 6.33325C6.70833 5.99992 7.125 5.83325 7.625 5.83325H13.0417C13.4861 5.83325 13.8678 5.96492 14.1867 6.22825C14.5067 6.4927 14.7083 6.83325 14.7917 7.24992L15.75 12.1666C15.8056 12.5833 15.7917 12.9583 15.7083 13.2916C15.625 13.6249 15.4444 13.9166 15.1667 14.1666L11.3333 17.9166C12.6667 20.1388 14.2428 22.1527 16.0617 23.9583C17.8817 25.7638 19.9444 27.3333 22.25 28.6666L25.9583 24.9166C26.2361 24.6388 26.5628 24.4444 26.9383 24.3333C27.3128 24.2221 27.6944 24.1944 28.0833 24.2499L32.7083 25.2083C33.125 25.2916 33.4656 25.486 33.73 25.7916C33.9933 26.0971 34.125 26.4721 34.125 26.9166V32.3333C34.125 32.8333 33.9583 33.2499 33.625 33.5833C33.2917 33.9166 32.875 34.0833 32.375 34.0833ZM10.1667 15.5416L13.125 12.7083C13.1806 12.6527 13.2156 12.5899 13.23 12.5199C13.2433 12.451 13.2361 12.3888 13.2083 12.3333L12.5 8.58325C12.4722 8.49992 12.4306 8.43714 12.375 8.39492C12.3194 8.35381 12.25 8.33325 12.1667 8.33325H8.625C8.54167 8.33325 8.47889 8.35381 8.43667 8.39492C8.39556 8.43714 8.375 8.48603 8.375 8.54159C8.45833 9.68047 8.64611 10.8399 8.93833 12.0199C9.22944 13.201 9.63889 14.3749 10.1667 15.5416ZM24.5833 29.8333C25.6944 30.361 26.8472 30.7638 28.0417 31.0416C29.2361 31.3194 30.3611 31.4721 31.4167 31.4999C31.4722 31.4999 31.5211 31.4788 31.5633 31.4366C31.6044 31.3955 31.625 31.3471 31.625 31.2916V27.7916C31.625 27.7083 31.6044 27.6388 31.5633 27.5833C31.5211 27.5277 31.4583 27.486 31.375 27.4583L27.875 26.7499C27.8194 26.7221 27.7639 26.7221 27.7083 26.7499L27.5417 26.8333L24.5833 29.8333Z"
                                                fill="white" />
                                        </g>
                                    </svg>
                                </div>
                                <div class="number-details details-wrapper">
                                    <h5>@lang('Contact Number')</h5>
                                    <h6>{{ $ps->phone }}</h6>
                                </div>
                            </div>
                        @endif

                        @if ($ps->fax != null)
                            <div class="fax-number common-wrapper d-flex align-items-center">
                                <div class="fax-icon icon-wrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <g clip-path="url(#clip0_6740_32531)">
                                            <path
                                                d="M18.3333 22.5C18.3333 21.1167 19.45 20 20.8333 20C22.2167 20 23.3333 21.1167 23.3333 22.5C23.3333 23.8833 22.2167 25 20.8333 25C19.45 25 18.3333 23.8833 18.3333 22.5ZM20.8333 33.3333C22.2167 33.3333 23.3333 32.2167 23.3333 30.8333C23.3333 29.45 22.2167 28.3333 20.8333 28.3333C19.45 28.3333 18.3333 29.45 18.3333 30.8333C18.3333 32.2167 19.45 33.3333 20.8333 33.3333ZM29.1667 25C30.55 25 31.6667 23.8833 31.6667 22.5C31.6667 21.1167 30.55 20 29.1667 20C27.7833 20 26.6667 21.1167 26.6667 22.5C26.6667 23.8833 27.7833 25 29.1667 25ZM29.1667 33.3333C30.55 33.3333 31.6667 32.2167 31.6667 30.8333C31.6667 29.45 30.55 28.3333 29.1667 28.3333C27.7833 28.3333 26.6667 29.45 26.6667 30.8333C26.6667 32.2167 27.7833 33.3333 29.1667 33.3333ZM40 6.81667V40H0V11.6667C0 8.91667 2.25 6.66667 5 6.66667H8.33333C11.0833 6.66667 13.3333 8.91667 13.3333 11.6667V13.3333H16.6667V5C16.6667 2.25 18.9167 0 21.6667 0H33.1833L40 6.81667ZM10 11.6667C10 10.75 9.25 10 8.33333 10H5C4.08333 10 3.33333 10.75 3.33333 11.6667V36.6667H10V11.6667ZM36.6667 16.6667H13.3333V36.6667H36.6667V16.6667ZM36.6667 13.3333V8.33333H31.6667V3.33333H21.6667C20.75 3.33333 20 4.08333 20 5V13.3333H36.6667Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_6740_32531">
                                                <rect width="40" height="40" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <div class="details-wrapper">
                                    <h5>@lang('Fax')</h5>
                                    <h6>{{ $ps->fax }}</h6>
                                </div>
                            </div>
                        @endif
                        @if ($ps->email != null)
                            <div class="email-address common-wrapper d-flex align-items-center">
                                <div class="email-icon icon-wrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                        viewBox="0 0 40 40" fill="none">
                                        <mask id="mask0_6740_32538" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0"
                                            y="0" width="40" height="40">
                                            <rect width="40" height="40" fill="#D9D9D9" />
                                        </mask>
                                        <g mask="url(#mask0_6740_32538)">
                                            <path
                                                d="M6.45703 27.8333C5.76259 27.7499 5.17203 27.4371 4.68536 26.8949C4.19981 26.3538 3.95703 25.7221 3.95703 24.9999V11.9166C3.95703 11.5555 4.05425 11.2149 4.2487 10.8949C4.44314 10.576 4.72092 10.3194 5.08203 10.1249L17.707 3.83325L30.082 10.1249C30.3876 10.2638 30.6309 10.486 30.812 10.7916C30.992 11.0971 31.1237 11.4166 31.207 11.7499H27.832L17.707 6.62492L6.45703 12.2083V27.8333ZM12.3737 34.1666C11.5404 34.1666 10.832 33.8749 10.2487 33.2916C9.66537 32.7083 9.3737 31.9999 9.3737 31.1666V17.1666C9.3737 16.3333 9.66537 15.6249 10.2487 15.0416C10.832 14.4583 11.5404 14.1666 12.3737 14.1666H33.0404C33.8737 14.1666 34.582 14.4583 35.1654 15.0416C35.7487 15.6249 36.0404 16.3333 36.0404 17.1666V31.1666C36.0404 31.9999 35.7487 32.7083 35.1654 33.2916C34.582 33.8749 33.8737 34.1666 33.0404 34.1666H12.3737ZM22.707 24.8333L11.8737 19.2499V31.1666C11.8737 31.3055 11.9226 31.4238 12.0204 31.5216C12.117 31.6183 12.2348 31.6666 12.3737 31.6666H33.0404C33.1793 31.6666 33.2976 31.6183 33.3954 31.5216C33.492 31.4238 33.5404 31.3055 33.5404 31.1666V19.2499L22.707 24.8333ZM22.707 22.2499L33.332 16.7499C33.2765 16.7221 33.2281 16.701 33.187 16.6866C33.1448 16.6733 33.0959 16.6666 33.0404 16.6666H12.3737C12.3181 16.6666 12.2698 16.6733 12.2287 16.6866C12.1865 16.701 12.1376 16.7221 12.082 16.7499L22.707 22.2499ZM33.5404 19.2499C33.5404 18.7499 33.5193 18.3194 33.477 17.9583C33.4359 17.5971 33.3876 17.1944 33.332 16.7499C33.2765 16.7221 33.2281 16.701 33.187 16.6866C33.1448 16.6733 33.0959 16.6666 33.0404 16.6666H12.3737C12.3181 16.6666 12.2698 16.6733 12.2287 16.6866C12.1865 16.701 12.1376 16.7221 12.082 16.7499C12.0265 17.1944 11.9776 17.5971 11.9354 17.9583C11.8943 18.3194 11.8737 18.7499 11.8737 19.2499V16.6666H33.5404V19.2499Z"
                                                fill="white" />
                                        </g>
                                    </svg>
                                </div>
                                <div class="details-wrapper">
                                    <h5>@lang('Email Address')</h5>
                                    <h6>{{ $ps->email }}</h6>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-7 wow-replaced" data-wow-delay=".1s">
                    <div class="leave-reply-section">
                        <h3>@lang('Feel free to message us')</h3>
                        <form class="form-area" action="{{ route('front.contact.submit') }}" method="POST">
                              @csrf
                            <div class="row gy-4 form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input id="name" type="text" name="name" class="form-control"
                                            placeholder="@lang('Your Name')" required="required" data-error="name is required.">
                                    </div>
                                    @error('name')
                                        <p class="my-2 text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input id="phone" type="text" name="phone" class="form-control"
                                            placeholder="@lang('Your Phone Number')" required="required">
                                    </div>
                                    @error('phone')
                                        <p class="my-2 text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input id="email" type="text" name="email" class="form-control"
                                            placeholder="@lang('Your Email')" required="required">
                                    </div>
                                    @error('email')
                                        <p class="my-2 text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <textarea id="form_message" name="message" class="form-control" placeholder="@lang('Write Your Comment')"
                                            required="required"></textarea>
                                    </div>
                                    @error('message')
                                        <p class="my-2 text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if ($gs->is_capcha == 1)
                                    <div class="form-input">
                                        {!! NoCaptcha::display() !!}
                                        {!! NoCaptcha::renderJs() !!}
                                        @error('g-recaptcha-response')
                                            <p class="my-2 text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                <input type="hidden" name="to" value="{{ $ps->contact_email }}">
                                <div class="col-md-12">
                                    <button type="submit" class="template-btn btn-forms">@lang('Send Message')</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
