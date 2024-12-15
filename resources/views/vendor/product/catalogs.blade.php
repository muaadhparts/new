@extends('layouts.vendor')

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Product Catalogs')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor-prod-index') }}" class="text-capitalize"> @lang('Products') </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize"> @lang('Product Catalogs') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Table area start  -->
        <div class="vendor-table-wrapper all-products-table-wrapper">
            <div class="user-table table-responsive position-relative">

                <table class="gs-data-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datas as $data)
                            <tr>

                                <!-- Product Name -->
                                <td class="text-start">
                                    <div class="product-name">
                                        <span class="content">
                                            {{ mb_strlen(strip_tags($data->name), 'UTF-8') > 50
                                                ? mb_substr(strip_tags($data->name), 0, 50, 'UTF-8') . '...'
                                                : strip_tags($data->name) }}
                                        </span>
                                    </div>
                                </td>
                                <!-- Product Id -->

                                <!-- Price -->
                                <td><span class="content">{{ $data->type }}</span></td>
                                <td><span class="content">{{ $data->showPrice() }}</span></td>

                                <!-- Actions -->
                                <td>
                                    <div class="table-icon-btns-wrapper">

                                        @php
                                            $ck =
                                                $user
                                                    ->products()
                                                    ->where('catalog_id', '=', $data->id)
                                                    ->count() > 0;
                                        @endphp


                                        @if ($ck)
                                            {{ __('Added To Catalog') }}
                                        @else
                                            <a href="{{ route('vendor-prod-catalog-edit', $data->id) }}"
                                                class="view-btn edit-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                    viewBox="0 0 12 12" fill="none">
                                                    <g clip-path="url(#clip0_1880_39494)">
                                                        <path
                                                            d="M11.25 5.25H6.75V0.75C6.75 0.335789 6.41421 0 6 0C5.58579 0 5.25 0.335789 5.25 0.75V5.25H0.75C0.335789 5.25 0 5.58579 0 6C0 6.41421 0.335789 6.75 0.75 6.75H5.25V11.25C5.25 11.6642 5.58579 12 6 12C6.41421 12 6.75 11.6642 6.75 11.25V6.75H11.25C11.6642 6.75 12 6.41421 12 6C12 5.58579 11.6642 5.25 11.25 5.25Z"
                                                            fill="white" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_1880_39494">
                                                            <rect width="16" height="16" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>

                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">@lang('No Products Found')</td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
        <!-- Table area end  -->
    </div>
@endsection
