<div class="gs-vendor-sidebar-wrapper d-none d-xl-block">
    <div class="gs-vendor-sidebar-logo-wrapper">
        <a href="{{ route('front.index') }}">
            <img src="{{ asset('assets/images/' . $gs->logo) }}" alt="logo">
        </a>
    </div>
    <ul class="gs-dashboard-user-sidebar-wrapper">
        <li class="{{ request()->is('vendor/dashboard') ? 'active' : '' }}">
            <a href="{{ route('vendor.dashboard') }}">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M19.6483 21.5H13.6017C12.581 21.5 11.75 20.669 11.75 19.6483V12.1017C11.75 11.081 12.581 10.25 13.6017 10.25H19.6483C20.669 10.25 21.5 11.081 21.5 12.1017V19.6483C21.5 20.669 20.669 21.5 19.6483 21.5ZM13.6017 11.75C13.4075 11.75 13.25 11.9075 13.25 12.1017V19.6483C13.25 19.8425 13.4075 20 13.6017 20H19.6483C19.8425 20 20 19.8425 20 19.6483V12.1017C20 11.9075 19.8425 11.75 19.6483 11.75H13.6017Z"
                        fill="#1F0300" />
                    <path
                        d="M19.6483 8.75H13.6017C12.581 8.75 11.75 7.919 11.75 6.89825V2.35175C11.75 1.331 12.581 0.5 13.6017 0.5H19.6483C20.669 0.5 21.5 1.331 21.5 2.35175V6.89825C21.5 7.919 20.669 8.75 19.6483 8.75ZM13.6017 2C13.4075 2 13.25 2.1575 13.25 2.35175V6.89825C13.25 7.0925 13.4075 7.25 13.6017 7.25H19.6483C19.8425 7.25 20 7.0925 20 6.89825V2.35175C20 2.1575 19.8425 2 19.6483 2H13.6017Z"
                        fill="#1F0300" />
                    <path
                        d="M8.39825 11.75H2.35175C1.331 11.75 0.5 10.919 0.5 9.89825V2.35175C0.5 1.331 1.331 0.5 2.35175 0.5H8.39825C9.419 0.5 10.25 1.331 10.25 2.35175V9.89825C10.25 10.919 9.419 11.75 8.39825 11.75ZM2.35175 2C2.1575 2 2 2.1575 2 2.35175V9.89825C2 10.0925 2.1575 10.25 2.35175 10.25H8.39825C8.5925 10.25 8.75 10.0925 8.75 9.89825V2.35175C8.75 2.1575 8.5925 2 8.39825 2H2.35175Z"
                        fill="#1F0300" />
                    <path
                        d="M8.39825 21.5H2.35175C1.331 21.5 0.5 20.669 0.5 19.6483V15.1017C0.5 14.081 1.331 13.25 2.35175 13.25H8.39825C9.419 13.25 10.25 14.081 10.25 15.1017V19.6483C10.25 20.669 9.419 21.5 8.39825 21.5ZM2.35175 14.75C2.1575 14.75 2 14.9075 2 15.1017V19.6483C2 19.8425 2.1575 20 2.35175 20H8.39825C8.5925 20 8.75 19.8425 8.75 19.6483V15.1017C8.75 14.9075 8.5925 14.75 8.39825 14.75H2.35175Z"
                        fill="#1F0300" />
                </svg>
                <span class="label">@lang('Dashboard')</span>
            </a>
        </li>


        <li>
            <a href="{{ route('front.vendor', str_replace(' ', '-', $user->shop_name)) }}" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M2.42012 12.7132C2.28394 12.4975 2.21584 12.3897 2.17772 12.2234C2.14909 12.0985 2.14909 11.9015 2.17772 11.7766C2.21584 11.6103 2.28394 11.5025 2.42012 11.2868C3.54553 9.50484 6.8954 5 12.0004 5C17.1054 5 20.4553 9.50484 21.5807 11.2868C21.7169 11.5025 21.785 11.6103 21.8231 11.7766C21.8517 11.9015 21.8517 12.0985 21.8231 12.2234C21.785 12.3897 21.7169 12.4975 21.5807 12.7132C20.4553 14.4952 17.1054 19 12.0004 19C6.8954 19 3.54553 14.4952 2.42012 12.7132Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M12.0004 15C13.6573 15 15.0004 13.6569 15.0004 12C15.0004 10.3431 13.6573 9 12.0004 9C10.3435 9 9.0004 10.3431 9.0004 12C9.0004 13.6569 10.3435 15 12.0004 15Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span class="label">@lang('Visit Store')</span>
            </a>
        </li>
        <li class="{{ request()->is('vendor/delivery') ? 'active' : '' }}">
            <a href="{{ route('vendor.delivery.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M14 7H16.3373C16.5818 7 16.7041 7 16.8192 7.02763C16.9213 7.05213 17.0188 7.09253 17.1083 7.14736C17.2092 7.2092 17.2957 7.29568 17.4686 7.46863L21.5314 11.5314C21.7043 11.7043 21.7908 11.7908 21.8526 11.8917C21.9075 11.9812 21.9479 12.0787 21.9724 12.1808C22 12.2959 22 12.4182 22 12.6627V15.5C22 15.9659 22 16.1989 21.9239 16.3827C21.8224 16.6277 21.6277 16.8224 21.3827 16.9239C21.1989 17 20.9659 17 20.5 17M15.5 17H14M14 17V7.2C14 6.0799 14 5.51984 13.782 5.09202C13.5903 4.71569 13.2843 4.40973 12.908 4.21799C12.4802 4 11.9201 4 10.8 4H5.2C4.0799 4 3.51984 4 3.09202 4.21799C2.71569 4.40973 2.40973 4.71569 2.21799 5.09202C2 5.51984 2 6.0799 2 7.2V15C2 16.1046 2.89543 17 4 17M14 17H10M10 17C10 18.6569 8.65685 20 7 20C5.34315 20 4 18.6569 4 17M10 17C10 15.3431 8.65685 14 7 14C5.34315 14 4 15.3431 4 17M20.5 17.5C20.5 18.8807 19.3807 20 18 20C16.6193 20 15.5 18.8807 15.5 17.5C15.5 16.1193 16.6193 15 18 15C19.3807 15 20.5 16.1193 20.5 17.5Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span class="label">@lang('Delivery')</span>
            </a>
        </li>
        <li class="{{ request()->is('vendor/orders') || request()->is('vendor/order/*/show') ? 'active' : '' }}">
            <a href="{{ route('vendor-order-index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M16.0004 9V6C16.0004 3.79086 14.2095 2 12.0004 2C9.79123 2 8.00037 3.79086 8.00037 6V9M3.59237 10.352L2.99237 16.752C2.82178 18.5717 2.73648 19.4815 3.03842 20.1843C3.30367 20.8016 3.76849 21.3121 4.35839 21.6338C5.0299 22 5.94374 22 7.77142 22H16.2293C18.057 22 18.9708 22 19.6423 21.6338C20.2322 21.3121 20.6971 20.8016 20.9623 20.1843C21.2643 19.4815 21.179 18.5717 21.0084 16.752L20.4084 10.352C20.2643 8.81535 20.1923 8.04704 19.8467 7.46616C19.5424 6.95458 19.0927 6.54511 18.555 6.28984C17.9444 6 17.1727 6 15.6293 6L8.37142 6C6.82806 6 6.05638 6 5.44579 6.28984C4.90803 6.54511 4.45838 6.95458 4.15403 7.46616C3.80846 8.04704 3.73643 8.81534 3.59237 10.352Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Orders')</span>
            </a>
        </li>

        <li
            class="has-sub-menu {{ request()->is('vendor/products/types') || request()->is('vendor/products') || request()->is('vendor/products/catalogs') || request()->is('vendor/products/physical/create') || request()->is('vendor/products/digital/create') || request()->is('vendor/products/license/create') || request()->is('vendor/products/listing/create') || request()->is('vendor/products/edit/*') || request()->is('vendor/products/catalog/*') ? 'active' : '' }}">


            <a href="#vendor-collapse-product"
                class="{{ request()->is('vendor/products/types') || request()->is('vendor/products') || request()->is('vendor/products/catalogs') || request()->is('vendor/products/physical/create') || request()->is('vendor/products/digital/create') || request()->is('vendor/products/license/create') || request()->is('vendor/products/listing/create') || request()->is('vendor/products/edit/*') || request()->is('vendor/products/catalog/*') ? '' : 'collapsed' }}"
                data-bs-toggle="collapse" aria-expanded="false" aria-controls="vendor-collapse-product">


                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M20.5 7.27734L12 11.9996M12 11.9996L3.49997 7.27734M12 11.9996L12 21.4996M21 16.0582V7.94104C21 7.5984 21 7.42708 20.9495 7.27428C20.9049 7.1391 20.8318 7.01502 20.7354 6.91033C20.6263 6.79199 20.4766 6.70879 20.177 6.54239L12.777 2.43128C12.4934 2.27372 12.3516 2.19494 12.2015 2.16406C12.0685 2.13672 11.9315 2.13672 11.7986 2.16406C11.6484 2.19494 11.5066 2.27372 11.223 2.43128L3.82297 6.54239C3.52345 6.70879 3.37369 6.792 3.26463 6.91033C3.16816 7.01502 3.09515 7.1391 3.05048 7.27428C3 7.42708 3 7.5984 3 7.94104V16.0582C3 16.4008 3 16.5721 3.05048 16.7249C3.09515 16.8601 3.16816 16.9842 3.26463 17.0889C3.37369 17.2072 3.52345 17.2904 3.82297 17.4568L11.223 21.5679C11.5066 21.7255 11.6484 21.8042 11.7986 21.8351C11.9315 21.8625 12.0685 21.8625 12.2015 21.8351C12.3516 21.8042 12.4934 21.7255 12.777 21.5679L20.177 17.4568C20.4766 17.2904 20.6263 17.2072 20.7354 17.0889C20.8318 16.9842 20.9049 16.8601 20.9495 16.7249C21 16.5721 21 16.4008 21 16.0582Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M16.5 9.5L7.5 4.5" stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Products')</span>
                <i class="ms-auto fa-solid fa-angle-down angle-down"></i>
            </a>
            <ul class="sidebar-sub-menu collapse {{ request()->is('vendor/products/types') || request()->is('vendor/products') || request()->is('vendor/products/catalogs') || request()->is('vendor/products/physical/create') || request()->is('vendor/products/digital/create') || request()->is('vendor/products/license/create') || request()->is('vendor/products/listing/create') || request()->is('vendor/products/edit/*') || request()->is('vendor/products/catalog/*') ? 'show' : '' }}"
                id="vendor-collapse-product">


                <li><a class="sidebar-sub-menu-item {{ request()->is('vendor/products/types') || request()->is('vendor/products/physical/create') || request()->is('vendor/products/digital/create') || request()->is('vendor/products/license/create') || request()->is('vendor/listing/create') ? 'active' : '' }}"
                        href="{{ route('vendor-prod-types') }}">@lang('Add New Product')</a>
                </li>
                <li><a class="sidebar-sub-menu-item {{ request()->is('vendor/products') || request()->is('vendor/products/edit/*') ? 'active' : '' }}"
                        href="{{ route('vendor-prod-index') }}">@lang('All Product')</a></li>
                <li><a class="sidebar-sub-menu-item {{ request()->is('vendor/products/catalogs') || request()->is('vendor/products/catalog/*') ? 'active' : '' }}"
                        href="{{ route('admin-vendor-catalog-index') }}">@lang('Product Catalogs')</a></li>
            </ul>
        </li>








        <li class="{{ request()->is('vendor/products/import') ? 'active' : '' }}">
            <a href="{{ route('vendor-prod-import') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M16 12L12 8M12 8L8 12M12 8V17.2C12 18.5907 12 19.2861 12.5505 20.0646C12.9163 20.5819 13.9694 21.2203 14.5972 21.3054C15.5421 21.4334 15.9009 21.2462 16.6186 20.8719C19.8167 19.2036 22 15.8568 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 15.7014 4.01099 18.9331 7 20.6622"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Bulk Item Upload')</span>
            </a>
        </li>


        
        <li class="">
            <a href="{{ route('user-messages') }}">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M9 14L5.92474 17.1137C5.49579 17.548 5.28131 17.7652 5.09695 17.7805C4.93701 17.7938 4.78042 17.7295 4.67596 17.6076C4.55556 17.4672 4.55556 17.162 4.55556 16.5515V14.9916C4.55556 14.444 4.10707 14.0477 3.5652 13.9683V13.9683C2.25374 13.7762 1.22378 12.7463 1.03168 11.4348C1 11.2186 1 10.9605 1 10.4444V5.8C1 4.11984 1 3.27976 1.32698 2.63803C1.6146 2.07354 2.07354 1.6146 2.63803 1.32698C3.27976 1 4.11984 1 5.8 1H13.2C14.8802 1 15.7202 1 16.362 1.32698C16.9265 1.6146 17.3854 2.07354 17.673 2.63803C18 3.27976 18 4.11984 18 5.8V10M18 21L15.8236 19.4869C15.5177 19.2742 15.3647 19.1678 15.1982 19.0924C15.0504 19.0255 14.8951 18.9768 14.7356 18.9474C14.5558 18.9143 14.3695 18.9143 13.9969 18.9143H12.2C11.0799 18.9143 10.5198 18.9143 10.092 18.6963C9.71569 18.5046 9.40973 18.1986 9.21799 17.8223C9 17.3944 9 16.8344 9 15.7143V13.2C9 12.0799 9 11.5198 9.21799 11.092C9.40973 10.7157 9.71569 10.4097 10.092 10.218C10.5198 10 11.0799 10 12.2 10H17.8C18.9201 10 19.4802 10 19.908 10.218C20.2843 10.4097 20.5903 10.7157 20.782 11.092C21 11.5198 21 12.0799 21 13.2V15.9143C21 16.8462 21 17.3121 20.8478 17.6797C20.6448 18.1697 20.2554 18.5591 19.7654 18.762C19.3978 18.9143 18.9319 18.9143 18 18.9143V21Z"
                    stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
                <span class="label">@lang('Messages')</span>
            </a>
        </li>




        <li class="{{ request()->is('vendor/withdraw') || request()->is('vendor/withdraw/create') ? 'active' : '' }}">
            <a href="{{ route('vendor-wt-index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M10.4995 13.5002L20.9995 3.00017M10.6271 13.8282L13.2552 20.5862C13.4867 21.1816 13.6025 21.4793 13.7693 21.5662C13.9139 21.6415 14.0862 21.6416 14.2308 21.5664C14.3977 21.4797 14.5139 21.1822 14.7461 20.5871L21.3364 3.69937C21.5461 3.16219 21.6509 2.8936 21.5935 2.72197C21.5437 2.57292 21.4268 2.45596 21.2777 2.40616C21.1061 2.34883 20.8375 2.45364 20.3003 2.66327L3.41258 9.25361C2.8175 9.48584 2.51997 9.60195 2.43326 9.76886C2.35809 9.91354 2.35819 10.0858 2.43353 10.2304C2.52043 10.3972 2.81811 10.513 3.41345 10.7445L10.1715 13.3726C10.2923 13.4196 10.3527 13.4431 10.4036 13.4794C10.4487 13.5115 10.4881 13.551 10.5203 13.5961C10.5566 13.647 10.5801 13.7074 10.6271 13.8282Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Withdraw')</span>
            </a>
        </li>


        <li
            class="has-sub-menu {{ request()->is('vendor/shipping') || request()->is('vendor/shipping/*') || request()->is('vendor/package') || request()->is('vendor/package/*') || request()->is('vendor/pickup-point') || request()->is('vendor/pickup-point/*') || request()->is('vendor/social-link') || request()->is('vendor/social-link/*') ? 'active' : '' }}">
            <a href="#vendor-collapsed-settings"
                class="{{ request()->is('vendor/shipping') || request()->is('vendor/shipping/*') || request()->is('vendor/package') || request()->is('vendor/package/*') || request()->is('vendor/pickup-point') || request()->is('vendor/pickup-point/*') || request()->is('vendor/social-link') || request()->is('vendor/social-link/*') ? '' : 'collapsed' }}"
                data-bs-toggle="collapse" aria-expanded="false" aria-controls="vendor-collapsed-settings">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M9.39504 19.3711L9.97949 20.6856C10.1532 21.0768 10.4368 21.4093 10.7957 21.6426C11.1547 21.8759 11.5736 22.0001 12.0017 22C12.4298 22.0001 12.8488 21.8759 13.2077 21.6426C13.5667 21.4093 13.8502 21.0768 14.0239 20.6856L14.6084 19.3711C14.8164 18.9047 15.1664 18.5159 15.6084 18.26C16.0532 18.0034 16.5677 17.8941 17.0784 17.9478L18.5084 18.1C18.934 18.145 19.3636 18.0656 19.7451 17.8713C20.1265 17.6771 20.4434 17.3763 20.6573 17.0056C20.8714 16.635 20.9735 16.2103 20.951 15.7829C20.9285 15.3555 20.7825 14.9438 20.5306 14.5978L19.6839 13.4344C19.3825 13.0171 19.2214 12.5148 19.2239 12C19.2238 11.4866 19.3864 10.9864 19.6884 10.5711L20.535 9.40778C20.7869 9.06175 20.933 8.65007 20.9554 8.22267C20.9779 7.79528 20.8759 7.37054 20.6617 7C20.4478 6.62923 20.1309 6.32849 19.7495 6.13423C19.3681 5.93997 18.9385 5.86053 18.5128 5.90556L17.0828 6.05778C16.5722 6.11141 16.0576 6.00212 15.6128 5.74556C15.1699 5.48825 14.8199 5.09736 14.6128 4.62889L14.0239 3.31444C13.8502 2.92317 13.5667 2.59072 13.2077 2.3574C12.8488 2.12408 12.4298 1.99993 12.0017 2C11.5736 1.99993 11.1547 2.12408 10.7957 2.3574C10.4368 2.59072 10.1532 2.92317 9.97949 3.31444L9.39504 4.62889C9.18797 5.09736 8.83792 5.48825 8.39504 5.74556C7.95026 6.00212 7.43571 6.11141 6.92504 6.05778L5.4906 5.90556C5.06493 5.86053 4.63534 5.93997 4.25391 6.13423C3.87249 6.32849 3.55561 6.62923 3.34171 7C3.12753 7.37054 3.02549 7.79528 3.04798 8.22267C3.07046 8.65007 3.2165 9.06175 3.46838 9.40778L4.31504 10.5711C4.61698 10.9864 4.77958 11.4866 4.77949 12C4.77958 12.5134 4.61698 13.0137 4.31504 13.4289L3.46838 14.5922C3.2165 14.9382 3.07046 15.3499 3.04798 15.7773C3.02549 16.2047 3.12753 16.6295 3.34171 17C3.55582 17.3706 3.87274 17.6712 4.25411 17.8654C4.63548 18.0596 5.06496 18.1392 5.4906 18.0944L6.9206 17.9422C7.43127 17.8886 7.94581 17.9979 8.3906 18.2544C8.83513 18.511 9.18681 18.902 9.39504 19.3711Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M11.9999 15C13.6568 15 14.9999 13.6569 14.9999 12C14.9999 10.3431 13.6568 9 11.9999 9C10.3431 9 8.99992 10.3431 8.99992 12C8.99992 13.6569 10.3431 15 11.9999 15Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Settings')</span>
                <i class="ms-auto fa-solid fa-angle-down angle-down"></i>
            </a>
            <ul class="sidebar-sub-menu collapse {{ request()->is('vendor/shipping') || request()->is('vendor/shipping/*') || request()->is('vendor/package') || request()->is('vendor/package/*') || request()->is('vendor/pickup-point') || request()->is('vendor/pickup-point/*') || request()->is('vendor/social-link') || request()->is('vendor/social-link/*') ? 'show' : '' }}"
                id="vendor-collapsed-settings">
                @if ($gs->vendor_ship_info == 1)
                    <li class=""><a
                            class="sidebar-sub-menu-item {{ request()->is('vendor/shipping') || request()->is('vendor/shipping/*') ? 'active' : '' }}"
                            href="{{ route('vendor-shipping-index') }}">@lang('Shipping Methods')</a></li>
                    <li class=""><a
                            class="sidebar-sub-menu-item {{ request()->is('vendor/package') || request()->is('vendor/package/*') ? 'active' : '' }}"
                            href="{{ route('vendor-package-index') }}">@lang('Packagings Products')</a></li>
                    <li class=""><a
                            class="sidebar-sub-menu-item {{ request()->is('vendor/pickup-point') || request()->is('vendor/pickup-point/*') ? 'active' : '' }}"
                            href="{{ route('vendor-pickup-point-index') }}">@lang('Pickup Point')</a></li>
                @endif
                <li class=""><a
                        class="sidebar-sub-menu-item {{ request()->is('vendor/social-link') || request()->is('vendor/social-link/*') ? 'active' : '' }}"
                        href="{{ route('vendor-sociallink-index') }}">@lang('Social Links')</a>
                </li>

            </ul>
        </li>



        <li class="">
            <a href="{{ route('vendor.income') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M9.5 13.7502C9.5 14.7202 10.25 15.5002 11.17 15.5002H13.05C13.85 15.5002 14.5 14.8202 14.5 13.9702C14.5 13.0602 14.1 12.7302 13.51 12.5202L10.5 11.4702C9.91 11.2602 9.51001 10.9402 9.51001 10.0202C9.51001 9.18023 10.16 8.49023 10.96 8.49023H12.84C13.76 8.49023 14.51 9.27023 14.51 10.2402"
                        stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 7.5V16.5" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12C2 6.48 6.48 2 12 2" stroke="#292D32"
                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M17 3V7H21" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M22 2L17 7" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Total Earnings')</span>
            </a>
        </li>
        <li class="{{ request()->is('vendor/profile') ? 'active' : '' }}">
            <a href="{{ route('vendor-profile') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <path
                        d="M11 4.00023H6.8C5.11984 4.00023 4.27976 4.00023 3.63803 4.32721C3.07354 4.61483 2.6146 5.07377 2.32698 5.63826C2 6.27999 2 7.12007 2 8.80023V17.2002C2 18.8804 2 19.7205 2.32698 20.3622C2.6146 20.9267 3.07354 21.3856 3.63803 21.6732C4.27976 22.0002 5.11984 22.0002 6.8 22.0002H15.2C16.8802 22.0002 17.7202 22.0002 18.362 21.6732C18.9265 21.3856 19.3854 20.9267 19.673 20.3622C20 19.7205 20 18.8804 20 17.2002V13.0002M7.99997 16.0002H9.67452C10.1637 16.0002 10.4083 16.0002 10.6385 15.945C10.8425 15.896 11.0376 15.8152 11.2166 15.7055C11.4184 15.5818 11.5914 15.4089 11.9373 15.063L21.5 5.50023C22.3284 4.6718 22.3284 3.32865 21.5 2.50023C20.6716 1.6718 19.3284 1.6718 18.5 2.50022L8.93723 12.063C8.59133 12.4089 8.41838 12.5818 8.29469 12.7837C8.18504 12.9626 8.10423 13.1577 8.05523 13.3618C7.99997 13.5919 7.99997 13.8365 7.99997 14.3257V16.0002Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Edit Profile')</span>
            </a>
        </li>


        <li>
            <a href="{{ route('user.login') }}">
                <svg width="22" height="20" viewBox="0 0 22 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M17 6L21 10M21 10L17 14M21 10H8M14 2.20404C12.7252 1.43827 11.2452 1 9.66667 1C4.8802 1 1 5.02944 1 10C1 14.9706 4.8802 19 9.66667 19C11.2452 19 12.7252 18.5617 14 17.796"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span class="label">@lang('Logout')</span>
            </a>
        </li>
    </ul>
</div>
