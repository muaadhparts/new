@extends('layouts.front')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.css') }}">
@endsection
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page title -->
                    <div class="ud-page-title-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <!-- mobile sidebar trigger btn -->
                        <h3 class="ud-page-title">@lang('Tickets')</h3>
                        <button data-bs-toggle="modal" data-bs-target="#vendorform"
                            class="template-btn md-btn black-btn data-table-btn">
                            <i class="fas fa-plus"></i>@lang('Add Ticket')</button>
                    </div>

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>

                                    <th>{{ __('Subject') }}</th>
                                    <th>{{ __('Message') }}</th>
                                    <th>{{ __('Time') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>

                            </thead>
                            <tbody>
                                @forelse ($convs as $conv)
                                    <!-- table data row 1 start  -->
                                    <tr>
                                        <input type="hidden" value="{{ $conv->id }}">

                                        <td><span class="content">{{ $conv->subject }}</span></td>

                                        <td><span class="content">{{ $conv->message }}</span></td>

                                        <td><span class="content">{{ $conv->created_at->diffForHumans() }}</span>
                                        </td>

                                        <td class="view-btn-wrapper">
                                            <a href="{{ route('user-message-show', $conv->id) }}" class="view-btn mx-2">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_548_16589)">
                                                        <path
                                                            d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                            fill="white" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip6_548_16589">
                                                            <rect width="24" height="24" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </a>

                                            <a href="javascript:;" class="view-btn delete-btn remove" data-bs-toggle="modal"
                                                data-bs-target="#confirm-delete"
                                                data-href="{{ route('user-message-delete1', $conv->id) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M16 6V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H11.2C10.0799 2 9.51984 2 9.09202 2.21799C8.71569 2.40973 8.40973 2.71569 8.21799 3.09202C8 3.51984 8 4.0799 8 5.2V6M10 11.5V16.5M14 11.5V16.5M3 6H21M19 6V17.2C19 18.8802 19 19.7202 18.673 20.362C18.3854 20.9265 17.9265 21.3854 17.362 21.673C16.7202 22 15.8802 22 14.2 22H9.8C8.11984 22 7.27976 22 6.63803 21.673C6.07354 21.3854 5.6146 20.9265 5.32698 20.362C5 19.7202 5 18.8802 5 17.2V6"
                                                        stroke="white" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"></path>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">{{ __('No Data Found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                    {{ $convs->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->






    <div class="modal gs-modal fade" id="vendorform" tabindex="-1" aria-modal="true" role="dialog">
        <form action="{{ route('user-send-message') }}" method="POST"
            class="modal-dialog assign-rider-modal-dialog modal-dialog-centered emailreply">
            {{ csrf_field() }}
            <div class="modal-content assign-rider-modal-content form-group">
                <div class="modal-header w-100">
                    <h4 class="title">@lang('Add Ticket')</h4>
                    <button type="button" data-bs-dismiss="modal">
                        <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                    </button>

                </div>

                <div class="input-label-wrapper w-100">

                    <input type="text" class="form-control  border px-3 mb-4" name="subject"
                        placeholder="@lang('Subject')" required="">


                    <textarea class="form-control  border px-3 mb-4" name="message" placeholder="{{ __('Your Message') }}" required=""></textarea>

                    <input type="hidden" name="type" value="Ticket" class="form-control border">

                    <input type="hidden" name="name" value="{{ Auth::user()->name }}">


                </div>
                <button class="template-btn" data-bs-dismiss="modal" type="submit">@lang('Send Message')</button>
            </div>
        </form>
    </div>




    <div class="modal gs-modal fade" id="confirm-delete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog confirm-delete-modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-delete-modal-content form-group">
                <div class="modal-header delete-modal-header w-100">
                    <div class="title-des-wrapper">
                        <h4 class="title">@lang('Confirm Delete ?')</h4>
                        <h5 class="sub-title">{{ __('Do you want to proceed?') }}</h5>
                    </div>
                </div>
                <div class="row row-cols-2 w-100">
                    <div class="col">
                        <a class="template-btn black-btn w-100 btn-ok" href="" type="button">@lang('Delete')</a>
                    </div>
                    <div class="col">
                        <button class="template-btn w-100" data-bs-dismiss="modal"
                            type="button">@lang('Cancel')</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";

            $('#confirm-delete').on('show.bs.modal', function(e) {
                $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
            });

        })(jQuery);
    </script>
@endsection
