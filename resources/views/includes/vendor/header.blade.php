<div class="positon-relative">

    <div class="gs-vendor-header gap-2 gap-md-3">
        <!-- this button will be visible on screen with the resulation of 1200px or more -->
        <button class="gs-vendor-toggle-btn header-togglee mobile-menu-toggle d-none d-xl-inline-block" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none">
                <path d="M6 24H42M6 12H42M6 36H30" stroke="#1F0300" stroke-width="4" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </button>
        <!-- this button will be visible under the 1200px resulation -->


        <div class="d-flex align-items-center gap-4">
            <a href="{{route("front.index")}}"><img class="vendor-res-header-logo d-xl-none" src="{{asset("assets/images/".$gs->logo)}}" alt="logo"></a>

            <button class="gs-vendor-toggle-btn header-toggle mobile-menu-toggle d-xl-none " type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <path d="M6 24H42M6 12H42M6 36H30" stroke="#1F0300" stroke-width="4" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
        </div>


        <!-- hearch input  -->

        <!-- icon button and user dropdown wrapper  -->
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <button id="toggle-vendor-noti" class="icon-btn icon-btn-lg p-0" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="62" height="60" viewBox="0 0 62 60"
                    fill="none">
                    <rect width="62" height="60" rx="10" fill="#F8F7F8" />
                    <path
                        d="M31.6749 45.0833C33.3796 45.0833 34.7604 43.7026 34.7604 41.9979H28.5894C28.5894 43.7026 29.9702 45.0833 31.6749 45.0833ZM40.9313 35.8269V28.1132C40.9313 23.3693 38.4089 19.4122 33.989 18.3632V17.3141C33.989 16.0336 32.9554 15 31.6749 15C30.3944 15 29.3608 16.0336 29.3608 17.3141V18.3632C24.9409 19.4122 22.4185 23.3693 22.4185 28.1132V35.8269L19.333 38.9124V40.4551H44.0168V38.9124L40.9313 35.8269Z"
                        fill="#030712" />
                    <circle cx="39.333" cy="21.667" r="5" fill="white" />
                    <circle cx="39.3333" cy="21.6663" r="3.33333" fill="#FF3D3D" />
                </svg>
            </button>
            <div class="user-dropdown-wrapper dropdown">
                <div role="button" class="user-dropdown dropdown-toggle" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar overflow-hidden">

                        @if (Auth::user()->is_provider == 1)
                            <img class="w-100 h-100"
                                src="{{ Auth::user()->photo ? asset(Auth::user()->photo) : asset('assets/images/noimage.png') }}"
                                alt="">
                        @else
                            <img class="w-100 h-100"
                                src="{{ Auth::user()->photo ? asset('assets/images/users/' . Auth::user()->photo) : asset('assets/images/noimage.png') }}"
                                alt="">
                        @endif


                    </span>
                    <div class="d-none d-md-flex flex-column gap-1">
                        <span class="user-name">{{ $user->name }}</span>
                        <span class="user-designation">@lang('Vendor')</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                        fill="none" class="d-none d-md-inline-block">
                        <path
                            d="M14.7636 4H7.64397H1.23282C0.135733 4 -0.412812 5.32565 0.364293 6.10277L6.28404 12.0225C7.23256 12.971 8.77534 12.971 9.72387 12.0225L11.9752 9.77116L15.6436 6.10277C16.4093 5.32565 15.8607 4 14.7636 4Z"
                            fill="#1B212D" />
                    </svg>
                </div>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li>
                        <a class="dropdown-item"
                            href="{{ route('front.vendor', str_replace(' ', '-', $user->shop_name)) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path
                                    d="M2.42012 12.7132C2.28394 12.4975 2.21584 12.3897 2.17772 12.2234C2.14909 12.0985 2.14909 11.9015 2.17772 11.7766C2.21584 11.6103 2.28394 11.5025 2.42012 11.2868C3.54553 9.50484 6.8954 5 12.0004 5C17.1054 5 20.4553 9.50484 21.5807 11.2868C21.7169 11.5025 21.785 11.6103 21.8231 11.7766C21.8517 11.9015 21.8517 12.0985 21.8231 12.2234C21.785 12.3897 21.7169 12.4975 21.5807 12.7132C20.4553 14.4952 17.1054 19 12.0004 19C6.8954 19 3.54553 14.4952 2.42012 12.7132Z"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M12.0004 15C13.6573 15 15.0004 13.6569 15.0004 12C15.0004 10.3431 13.6573 9 12.0004 9C10.3435 9 9.0004 10.3431 9.0004 12C9.0004 13.6569 10.3435 15 12.0004 15Z"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span>@lang('Visit Store')</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user-dashboard') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22"
                                fill="none">
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
                            <span>@lang('User Panel')</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('vendor-profile') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path
                                    d="M20 21C20 19.6044 20 18.9067 19.8278 18.3389C19.44 17.0605 18.4395 16.06 17.1611 15.6722C16.5933 15.5 15.8956 15.5 14.5 15.5H9.5C8.10444 15.5 7.40665 15.5 6.83886 15.6722C5.56045 16.06 4.56004 17.0605 4.17224 18.3389C4 18.9067 4 19.6044 4 21M16.5 7.5C16.5 9.98528 14.4853 12 12 12C9.51472 12 7.5 9.98528 7.5 7.5C7.5 5.01472 9.51472 3 12 3C14.4853 3 16.5 5.01472 16.5 7.5Z"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span>@lang('Edit Profile') </span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user-logout') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M18 8L22 12M22 12L18 16M22 12H9M15 4.20404C13.7252 3.43827 12.2452 3 10.6667 3C5.8802 3 2 7.02944 2 12C2 16.9706 5.8802 21 10.6667 21C12.2452 21 13.7252 20.5617 15 19.796"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span>@lang('Logout') </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- vendor notification -->
    <div class="gs-vendor-header-noti">
        <div class="d-flex align-items-center justify-content-between gap-4">
            <span class="title">@lang('Your Notification')</span>
            <a class="clear-btn" href="{{ route('vendor-order-notf-clear',Auth::user()->id) }}">Clear All</a>
        </div>
        <ul>
            @php
                $notifications = App\Models\UserNotification::whereUserId(auth()->id())->orderby('id','desc')->get();
            @endphp
            @forelse ($notifications as $data)
            <li>
                <span class="sm-info-1">@lang('Order has been placed')</span>
                <a href="{{ route('vendor-order-show', $data->order_number) }}"
                    class="sm-info-2 link">@lang('Order ID:')
                    {{ $data->order_number }} @lang('has been placed')</a>
                <span class="sm-info-2">{{ Carbon\Carbon::parse($data->created_at)->diffForHumans() }}</span>

            </li>
            @empty
                <li>
                    {{ __('No New Notifications.') }}
                </li>

            @endforelse

        </ul>
    </div>
</div>
