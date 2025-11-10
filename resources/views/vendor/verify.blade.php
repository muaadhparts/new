@extends('layouts.vendor')

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Vendor Verification')</h4>
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
                    <a href="#" class="text-capitalize"> @lang('Vendor Verification') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Vendor Verification area start  -->
        <div class="vendor-edit-profile-section-wrapper">
            <div class="gs-edit-profile-section">

                @if ($data->checkVerification())
                    <div class="alert alert-success validation" style="">
                        <p class="text-left">
                        <div class="text-center"><i class="fas fa-check-circle fa-4x"></i><br>
                            <h3>{{ __('Your Documents Submitted Successfully.') }}</h3>
                        </div>
                        </p>
                    </div>
                @else
                    <form class="edit-profile-area" action="{{ route('vendor-verify-submit') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row row-cols-1 row-cols-sm-2">
                            <div class="col-12 col-sm-12 form-group">
                                <label for="description">@lang('Description')</label>
                                <textarea id="description" class="form-control" name="text" placeholder="@lang('Enter Your Name')" rows="6"></textarea>

                                <div class="attachment-box">
                                    <p class="title">@lang('Attachment') <span class="description"> @lang('(Maximum Size is 10 MB)')</span></p>
                                    <div id="add_file">
                                        <div class="single">
                                            <div class="position-relative d-inline-block">

                                                <input type="file" name="attachments[]" id="fileInput"
                                                    class="input-file">
                                                <label for="fileInput" class="template-btn dark-btn md-btn fileName ms-0">
                                                    @lang('Choose File')
                                                </label>
                                                <!-- <span class="fileName">@lang('No file chosen')</span> -->
                                            </div>
                                            <i> <a href="javascript:;" class="attachment-remove template-btn md-btn"> <i
                                                        class="fas fa-times"></i>
                                                </a>
                                            </i>
                                        </div>
                                    </div>
                                    <button class="template-btn outline-btn lg-btn" id="new_add" type="button">+ @lang('Add More Attachment')</button>
                                </div>


                            </div>
                            <input type="hidden" name="warning"
                                value="{{ isset($verify) ? $verify->admin_warning : '0' }}" />
                            <input type="hidden" name="verify_id" value="{{ isset($verify) ? $verify->id : '0' }}" />

                            <div class="col-12 col-sm-12">
                                <button class="template-btn btn-forms" type="submit">
                                    @lang('Submit')
                                </button>
                            </div>
                        </div>
                    </form>
                @endif


            </div>
        </div>
        <!-- Vendor Verification area end  -->
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";

            function isEmpty(el) {
                return !$.trim(el.html())
            }

            // Color Section

            $("#new_add").on('click', function() {
                let random = Math.random().toString(36).substring(7);
                $("#add_file").append(
                    `
                    <div class="single">
                                    <div class="position-relative d-inline-block">
                                        <input type="file" name="attachments[]" id="${random}"  class="input-file input-file_att">
                                        <label for="${random}" class="template-btn dark-btn md-btn fileName${random}">
                                            Choose File
                                        </label>
                                    </div>
                                     <i> <a href="javascript:;" class="attachment-remove template-btn md-btn"> <i class="fas fa-times"></i> </a> </i>
                                </div>
                    `
                );
            });


            $(document).on('click', '.attachment-remove', function() {

                if ($("#add_file").children().length == 1) {
                    alert("You can't remove this");
                    return false;
                }
                $(this.parentNode).parent().remove();

            });

            // Color Section Ends


            $(document).on("change", '.input-file_att', function() {
                var filename = $(this).val().split('\\').pop(); // Get the filename only
                $(this).siblings('.fileName' + $(this).attr("id")).text(filename ||
                    "No file chosen"); // Update the corresponding label text
            });


        })(jQuery);
    </script>
@endsection
