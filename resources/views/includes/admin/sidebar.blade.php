<div class="gs-vendor-sidebar-wrapper d-none d-xl-block">
    <div class="gs-vendor-sidebar-logo-wrapper">
        <a href="{{ route('front.index') }}" target="_blank">
            <img src="{{ asset('assets/images/' . $gs->logo) }}" alt="logo">
        </a>
    </div>

    <ul class="gs-dashboard-user-sidebar-wrapper">
        <li class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M19.6483 21.5H13.6017C12.581 21.5 11.75 20.669 11.75 19.6483V12.1017C11.75 11.081 12.581 10.25 13.6017 10.25H19.6483C20.669 10.25 21.5 11.081 21.5 12.1017V19.6483C21.5 20.669 20.669 21.5 19.6483 21.5ZM13.6017 11.75C13.4075 11.75 13.25 11.9075 13.25 12.1017V19.6483C13.25 19.8425 13.4075 20 13.6017 20H19.6483C19.8425 20 20 19.8425 20 19.6483V12.1017C20 11.9075 19.8425 11.75 19.6483 11.75H13.6017Z"
                        fill="#1F0300" />
                </svg>
                <span class="label">@lang('Dashboard')</span>
            </a>
        </li>

        {{-- Orders Menu --}}
        <li class="has-sub-menu {{ request()->is('admin/orders*') ? 'active' : '' }}">
            <a href="#admin-collapse-orders"
                class="{{ request()->is('admin/orders*') ? '' : 'collapsed' }}"
                data-bs-toggle="collapse" aria-expanded="false" aria-controls="admin-collapse-orders">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M16.0004 9V6C16.0004 3.79086 14.2095 2 12.0004 2C9.79123 2 8.00037 3.79086 8.00037 6V9M3.59237 10.352L2.99237 16.752C2.82178 18.5717 2.73648 19.4815 3.03842 20.1843C3.30367 20.8016 3.76849 21.3121 4.35839 21.6338C5.0299 22 5.94374 22 7.77142 22H16.2293C18.057 22 18.9708 22 19.6423 21.6338C20.2322 21.3121 20.6971 20.8016 20.9623 20.1843C21.2643 19.4815 21.179 18.5717 21.0084 16.752L20.4084 10.352C20.2643 8.81535 20.1923 8.04704 19.8467 7.46616C19.5424 6.95458 19.0927 6.54511 18.555 6.28984C17.9444 6 17.1727 6 15.6293 6L8.37142 6C6.82806 6 6.05638 6 5.44579 6.28984C4.90803 6.54511 4.45838 6.95458 4.15403 7.46616C3.80846 8.04704 3.73643 8.81534 3.59237 10.352Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="label">@lang('Orders')</span>
                <i class="ms-auto fa-solid fa-angle-down angle-down"></i>
            </a>
            <ul class="sidebar-sub-menu collapse {{ request()->is('admin/orders*') ? 'show' : '' }}" id="admin-collapse-orders">
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-orders-all') }}">@lang('All Orders')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-orders-all') }}?status=pending">@lang('Pending Orders')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-orders-all') }}?status=processing">@lang('Processing Orders')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-orders-all') }}?status=completed">@lang('Completed Orders')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-orders-all') }}?status=declined">@lang('Declined Orders')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-order-create') }}">@lang('POS')</a></li>
            </ul>
        </li>

        {{-- Products Menu --}}
        <li class="has-sub-menu {{ request()->is('admin/products*') || request()->is('admin/category*') || request()->is('admin/subcategory*') || request()->is('admin/childcategory*') ? 'active' : '' }}">
            <a href="#admin-collapse-products"
                class="{{ request()->is('admin/products*') || request()->is('admin/category*') ? '' : 'collapsed' }}"
                data-bs-toggle="collapse">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M20.5 7.27734L12 11.9996M12 11.9996L3.49997 7.27734M12 11.9996L12 21.4996M21 16.0582V7.94104C21 7.5984 21 7.42708 20.9495 7.27428C20.9049 7.1391 20.8318 7.01502 20.7354 6.91033C20.6263 6.79199 20.4766 6.70879 20.177 6.54239L12.777 2.43128C12.4934 2.27372 12.3516 2.19494 12.2015 2.16406C12.0685 2.13672 11.9315 2.13672 11.7986 2.16406C11.6484 2.19494 11.5066 2.27372 11.223 2.43128L3.82297 6.54239C3.52345 6.70879 3.37369 6.792 3.26463 6.91033C3.16816 7.01502 3.09515 7.1391 3.05048 7.27428C3 7.42708 3 7.5984 3 7.94104V16.0582C3 16.4008 3 16.5721 3.05048 16.7249C3.09515 16.8601 3.16816 16.9842 3.26463 17.0889C3.37369 17.2072 3.52345 17.2904 3.82297 17.4568L11.223 21.5679C11.5066 21.7255 11.6484 21.8042 11.7986 21.8351C11.9315 21.8625 12.0685 21.8625 12.2015 21.8351C12.3516 21.8042 12.4934 21.7255 12.777 21.5679L20.177 17.4568C20.4766 17.2904 20.6263 17.2072 20.7354 17.0889C20.8318 16.9842 20.9049 16.8601 20.9495 16.7249C21 16.5721 21 16.4008 21 16.0582Z"
                        stroke="#1F0300" stroke-width="2"/>
                </svg>
                <span class="label">@lang('Products')</span>
                <i class="ms-auto fa-solid fa-angle-down angle-down"></i>
            </a>
            <ul class="sidebar-sub-menu collapse {{ request()->is('admin/products*') || request()->is('admin/category*') ? 'show' : '' }}" id="admin-collapse-products">
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-prod-types') }}">@lang('Add New Product')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-prod-index') }}">@lang('All Products')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-prod-deactive') }}">@lang('Deactivated Products')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-cat-index') }}">@lang('Categories')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-subcat-index') }}">@lang('Sub Categories')</a></li>
                <li><a class="sidebar-sub-menu-item" href="{{ route('admin-childcat-index') }}">@lang('Child Categories')</a></li>
            </ul>
        </li>

        @if(Auth::guard('admin')->user()->IsSuper())
            {{-- Vendors Management --}}
            <li class="{{ request()->is('admin/users/vendors*') ? 'active' : '' }}">
                <a href="{{ route('admin-vendor-index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M22 21V19C22 17.1362 20.7252 15.5701 19 15.126M15.5 3.29076C16.9659 3.88415 18 5.32131 18 7C18 8.67869 16.9659 10.1159 15.5 10.7092M17 21C17 19.1362 17 18.2044 16.6955 17.4693C16.2895 16.4892 15.5108 15.7105 14.5307 15.3045C13.7956 15 12.8638 15 11 15H8C6.13623 15 5.20435 15 4.46927 15.3045C3.48915 15.7105 2.71046 16.4892 2.30448 17.4693C2 18.2044 2 19.1362 2 21M13.5 7C13.5 9.20914 11.7091 11 9.5 11C7.29086 11 5.5 9.20914 5.5 7C5.5 4.79086 7.29086 3 9.5 3C11.7091 3 13.5 4.79086 13.5 7Z" stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="label">@lang('Vendors')</span>
                </a>
            </li>

            {{-- Customers --}}
            <li class="{{ request()->is('admin/users/customers*') ? 'active' : '' }}">
                <a href="{{ route('admin-user-index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M18 21C18 18.2386 15.3137 16 12 16C8.68629 16 6 18.2386 6 21M12 13C9.79086 13 8 11.2091 8 9C8 6.79086 9.79086 5 12 5C14.2091 5 16 6.79086 16 9C16 11.2091 14.2091 13 12 13Z" stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="label">@lang('Customers')</span>
                </a>
            </li>

            {{-- Settings Menu --}}
            <li class="has-sub-menu {{ request()->is('admin/general*') || request()->is('admin/payment*') || request()->is('admin/social*') ? 'active' : '' }}">
                <a href="#admin-collapse-settings" class="collapsed" data-bs-toggle="collapse">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M9.39504 19.3711L9.97949 20.6856C10.1532 21.0768 10.4368 21.4093 10.7957 21.6426C11.1547 21.8759 11.5736 22.0001 12.0017 22C12.4298 22.0001 12.8488 21.8759 13.2077 21.6426C13.5667 21.4093 13.8502 21.0768 14.0239 20.6856L14.6084 19.3711C14.8164 18.9047 15.1664 18.5159 15.6084 18.26C16.0532 18.0034 16.5677 17.8941 17.0784 17.9478L18.5084 18.1C18.934 18.145 19.3636 18.0656 19.7451 17.8713C20.1265 17.6771 20.4434 17.3763 20.6573 17.0056C20.8714 16.635 20.9735 16.2103 20.951 15.7829C20.9285 15.3555 20.7825 14.9438 20.5306 14.5978L19.6839 13.4344C19.3825 13.0171 19.2214 12.5148 19.2239 12C19.2238 11.4866 19.3864 10.9864 19.6884 10.5711L20.535 9.40778C20.7869 9.06175 20.933 8.65007 20.9554 8.22267C20.9779 7.79528 20.8759 7.37054 20.6617 7C20.4478 6.62923 20.1309 6.32849 19.7495 6.13423C19.3681 5.93997 18.9385 5.86053 18.5128 5.90556L17.0828 6.05778C16.5722 6.11141 16.0576 6.00212 15.6128 5.74556C15.1699 5.48825 14.8199 5.09736 14.6128 4.62889L14.0239 3.31444C13.8502 2.92317 13.5667 2.59072 13.2077 2.3574C12.8488 2.12408 12.4298 1.99993 12.0017 2C11.5736 1.99993 11.1547 2.12408 10.7957 2.3574C10.4368 2.59072 10.1532 2.92317 9.97949 3.31444L9.39504 4.62889C9.18797 5.09736 8.83792 5.48825 8.39504 5.74556C7.95026 6.00212 7.43571 6.11141 6.92504 6.05778L5.4906 5.90556C5.06493 5.86053 4.63534 5.93997 4.25391 6.13423C3.87249 6.32849 3.55561 6.62923 3.34171 7C3.12753 7.37054 3.02549 7.79528 3.04798 8.22267C3.07046 8.65007 3.2165 9.06175 3.46838 9.40778L4.31504 10.5711C4.61698 10.9864 4.77958 11.4866 4.77949 12C4.77958 12.5134 4.61698 13.0137 4.31504 13.4289L3.46838 14.5922C3.2165 14.9382 3.07046 15.3499 3.04798 15.7773C3.02549 16.2047 3.12753 16.6295 3.34171 17C3.55582 17.3706 3.87274 17.6712 4.25411 17.8654C4.63548 18.0596 5.06496 18.1392 5.4906 18.0944L6.9206 17.9422C7.43127 17.8886 7.94581 17.9979 8.3906 18.2544C8.83513 18.511 9.18681 18.902 9.39504 19.3711Z"
                            stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11.9999 15C13.6568 15 14.9999 13.6569 14.9999 12C14.9999 10.3431 13.6568 9 11.9999 9C10.3431 9 8.99992 10.3431 8.99992 12C8.99992 13.6569 10.3431 15 11.9999 15Z"
                            stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="label">@lang('Settings')</span>
                    <i class="ms-auto fa-solid fa-angle-down angle-down"></i>
                </a>
                <ul class="sidebar-sub-menu collapse" id="admin-collapse-settings">
                    <li><a class="sidebar-sub-menu-item" href="{{ route('admin-gs-logo') }}">@lang('Logo')</a></li>
                    <li><a class="sidebar-sub-menu-item" href="{{ route('admin-gs-fav') }}">@lang('Favicon')</a></li>
                    <li><a class="sidebar-sub-menu-item" href="{{ route('admin-gs-load') }}">@lang('Loader')</a></li>
                    <li><a class="sidebar-sub-menu-item" href="{{ route('admin-payment-index') }}">@lang('Payment Methods')</a></li>
                    <li><a class="sidebar-sub-menu-item" href="{{ route('admin-social-index') }}">@lang('Social Links')</a></li>
                </ul>
            </li>
        @endif

        {{-- Profile --}}
        <li class="{{ request()->is('admin/profile') ? 'active' : '' }}">
            <a href="{{ route('admin.profile') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M11 4.00023H6.8C5.11984 4.00023 4.27976 4.00023 3.63803 4.32721C3.07354 4.61483 2.6146 5.07377 2.32698 5.63826C2 6.27999 2 7.12007 2 8.80023V17.2002C2 18.8804 2 19.7205 2.32698 20.3622C2.6146 20.9267 3.07354 21.3856 3.63803 21.6732C4.27976 22.0002 5.11984 22.0002 6.8 22.0002H15.2C16.8802 22.0002 17.7202 22.0002 18.362 21.6732C18.9265 21.3856 19.3854 20.9267 19.673 20.3622C20 19.7205 20 18.8804 20 17.2002V13.0002M7.99997 16.0002H9.67452C10.1637 16.0002 10.4083 16.0002 10.6385 15.945C10.8425 15.896 11.0376 15.8152 11.2166 15.7055C11.4184 15.5818 11.5914 15.4089 11.9373 15.063L21.5 5.50023C22.3284 4.6718 22.3284 3.32865 21.5 2.50023C20.6716 1.6718 19.3284 1.6718 18.5 2.50022L8.93723 12.063C8.59133 12.4089 8.41838 12.5818 8.29469 12.7837C8.18504 12.9626 8.10423 13.1577 8.05523 13.3618C7.99997 13.5919 7.99997 13.8365 7.99997 14.3257V16.0002Z"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="label">@lang('Edit Profile')</span>
            </a>
        </li>

        {{-- Logout --}}
        <li>
            <a href="{{ route('admin.logout') }}">
                <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 6L21 10M21 10L17 14M21 10H8M14 2.20404C12.7252 1.43827 11.2452 1 9.66667 1C4.8802 1 1 5.02944 1 10C1 14.9706 4.8802 19 9.66667 19C11.2452 19 12.7252 18.5617 14 17.796"
                        stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="label">@lang('Logout')</span>
            </a>
        </li>
    </ul>
</div>
