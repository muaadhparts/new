@extends('layouts.vendor')

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4  flex-wrap align-items-center custom-gap-sm-2">
                <h4 class="text-capitalize">@lang('All Products')</h4>
                <a href="{{ route('vendor-prod-types') }}"
                    class="template-btn md-btn black-btn data-table-btn">+@lang('Add New Product')</a>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
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
                    <a href="#" class="text-capitalize"> @lang('All Products') </a>
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

                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-center">{{ __('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datas as $data)
                            <tr>


                                <td class="text-start">
                                    <div class="product-name">
                                        <img src="{{ asset(asset('assets/images/products/' . $data->photo)) }}"
                                            alt="Image" class="img-thumbnail" style="width:80px">
                                    </div>
                                </td>
                                <td class="text-start">
                                    <div class="product-name">
                                        <span class="content">
                                            {{ mb_strlen(strip_tags($data->name), 'UTF-8') > 50
                                                ? mb_substr(strip_tags($data->name), 0, 50, 'UTF-8') . '...'
                                                : strip_tags($data->name) }}
                                        </span>
                                    </div>
                                </td>


                                <td><span class="content">{{ $data->type }}</span></td>
                                <td><span class="content">{{ $data->showPrice() }}</span></td>

                                <td>
                                    @php
                                        $active = $data->status == 1 ? 'selected' : '';
                                        $deactivated = $data->status == 0 ? 'selected' : '';
                                        $activeClass = $data->status == 1 ? 'active' : 'deactive';
                                    @endphp
                                    <div class="status position-relative">
                                        <div class="dropdown-container">
                                            <select class="form-control nice-select form__control {{ $activeClass }}"
                                                id="product_status">
                                                <option
                                                    value="{{ route('vendor-prod-status', ['id1' => $data->id, 'id2' => 1]) }}"
                                                    {{ $active }}> {{ __('Activated') }}
                                                </option>
                                                <option
                                                    value="{{ route('vendor-prod-status', ['id1' => $data->id, 'id2' => 0]) }}"
                                                    {{ $deactivated }}> {{ __('Deactivated') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-icon-btns-wrapper">
                                        <a href="{{ route('vendor-prod-edit', $data->id) }}" class="view-btn edit-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <g clip-path="url(#clip0_910_50031)">
                                                    <path
                                                        d="M18.9999 12.0469C18.447 12.0469 18 12.495 18 13.0469V21.0469C18 21.5979 17.5519 22.0469 17.0001 22.0469H3C2.44794 22.0469 2.00006 21.5979 2.00006 21.0469V7.04688C2.00006 6.49591 2.44794 6.04694 3 6.04694H11.0001C11.553 6.04694 12 5.59888 12 5.047C12 4.49493 11.553 4.04688 11.0001 4.04688H3C1.34601 4.04688 0 5.39288 0 7.04688V21.0469C0 22.7009 1.34601 24.0469 3 24.0469H17.0001C18.6541 24.0469 20.0001 22.7009 20.0001 21.0469V13.0469C20.0001 12.4939 19.5529 12.0469 18.9999 12.0469Z"
                                                        fill="white" />
                                                    <path
                                                        d="M9.37515 11.1346C9.3052 11.2046 9.25815 11.2936 9.23819 11.3895L8.53122 14.9257C8.49826 15.0895 8.55026 15.2585 8.66818 15.3776C8.76321 15.4726 8.8912 15.5235 9.02231 15.5235C9.05417 15.5235 9.08731 15.5206 9.12027 15.5136L12.6553 14.8066C12.7533 14.7865 12.8423 14.7396 12.9113 14.6695L20.8233 6.75751L17.2882 3.22266L9.37515 11.1346Z"
                                                        fill="white" />
                                                    <path
                                                        d="M23.2686 0.778152C22.2937 -0.196884 20.7076 -0.196884 19.7335 0.778152L18.3496 2.16206L21.8846 5.6971L23.2686 4.313C23.7406 3.84206 24.0006 3.214 24.0006 2.54604C24.0006 1.87807 23.7406 1.25002 23.2686 0.778152Z"
                                                        fill="white" />
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_910_50031">
                                                        <rect width="24" height="24" fill="white" />
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                        </a>
                                        <a href="{{ route('front.product', $data->slug) }}" target="_blank"
                                            class="view-btn">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <g clip-path="url(#clip0_548_165891)">
                                                    <path
                                                        d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                        fill="white" />
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_548_165891">
                                                        <rect width="24" height="24" fill="white" />
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                        </a>
                                        <a data-href="{{ route('vendor-prod-delete', $data->id) }}" href="javascript:;"
                                            class="view-btn delete-btn delete_button" data-bs-toggle="modal"
                                            data-bs-target="#confirm-detete-modal">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M16 6V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H11.2C10.0799 2 9.51984 2 9.09202 2.21799C8.71569 2.40973 8.40973 2.71569 8.21799 3.09202C8 3.51984 8 4.0799 8 5.2V6M10 11.5V16.5M14 11.5V16.5M3 6H21M19 6V17.2C19 18.8802 19 19.7202 18.673 20.362C18.3854 20.9265 17.9265 21.3854 17.362 21.673C16.7202 22 15.8802 22 14.2 22H9.8C8.11984 22 7.27976 22 6.63803 21.673C6.07354 21.3854 5.6146 20.9265 5.32698 20.362C5 19.7202 5 18.8802 5 17.2V6"
                                                    stroke="white" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </a>
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
            {{ $datas->links() }}
        </div>
    </div>
@endsection

@section('script')
    <script>
        "use strict";
        $(document).on('change', '#product_status', function() {
            var link = $(this).val();
            window.location.href = link;
        });
    </script>
@endsection
