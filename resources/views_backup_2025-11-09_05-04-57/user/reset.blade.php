@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="gs-deposit-section">
                        <h3>@lang('Reset Password')</h3>

                        <div class="deposit-area">
                            <div class="deposit-area-title">
                                <h4>@lang('Change Password')</h4>
                            </div>
                            <form action="{{ route('user-reset-submit') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="old-pass">@lang('Old Password')</label>
                                    <input type="password" class="form-control" id="old-pass" placeholder="@lang('Old Password')"
                                        name="cpass" required>

                                    <label for="new-pass">@lang('New Password')</label>
                                    <input type="password" class="form-control" id="new-pass" placeholder="@lang('New Password')"
                                        name="newpass" required>

                                    <label for="confrm-pass">@lang('Confirm New Password')</label>
                                    <input type="password" class="form-control" id="confrm-pass"
                                        placeholder="@lang('Confirm New Password')" name="renewpass" required>

                                    <button type="submit" class="template-btn btn-forms">
                                        @lang('Update Password')
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
@endsection
