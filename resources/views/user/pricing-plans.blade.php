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

                    <div class="gs-deposit-title d-flex align-items-center gap-4 ms-0 mb-5">
                        <a href="" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>
                        <h4>@lang('Pricing Plans')</h4>
                    </div>

                    <div class="row gy-4">
                        <div class="col-md-6">
                            <div class="gs-extra-basic-plan">
                                <h4 class="title">Basic Plan</h4>
                                <div class="circle-wrapper">
                                    <h4 class="cr-title">$150
                                    </h4>
                                    <span class="cr-sm-title">45 Days</span>
                                </div>

                                <h6 class="pricing-title">Your Benifits</h6>

                                <div class="list-wrapper">
                                    <p class="list">250 Porducts</p>
                                    <p class="list">45 days Validaity </p>
                                    <p class="list">Max 1000$ withdraw in a Month</p>
                                </div>

                                <a class="template-btn outline-btn w-100" href="#">Get Started</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="gs-extra-basic-plan">
                                <h4 class="title">Basic Plan</h4>
                                <div class="circle-wrapper">
                                    <h4 class="cr-title">$150
                                    </h4>
                                    <span class="cr-sm-title">45 Days</span>
                                </div>

                                <h6 class="pricing-title">Your Benifits</h6>

                                <div class="list-wrapper">
                                    <p class="list">250 Porducts</p>
                                    <p class="list">45 days Validaity </p>
                                    <p class="list">Max 1000$ withdraw in a Month</p>
                                </div>

                                <a class="template-btn outline-btn w-100" href="#">Get Started</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="gs-extra-basic-plan">
                                <h4 class="title">Basic Plan</h4>
                                <div class="circle-wrapper">
                                    <h4 class="cr-title">$150
                                    </h4>
                                    <span class="cr-sm-title">45 Days</span>
                                </div>

                                <h6 class="pricing-title">Your Benifits</h6>

                                <div class="list-wrapper">
                                    <p class="list">250 Porducts</p>
                                    <p class="list">45 days Validaity </p>
                                    <p class="list">Max 1000$ withdraw in a Month</p>
                                </div>

                                <a class="template-btn outline-btn w-100" href="#">Get Started</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="gs-extra-basic-plan">
                                <h4 class="title">Basic Plan</h4>
                                <div class="circle-wrapper">
                                    <h4 class="cr-title">$150
                                    </h4>
                                    <span class="cr-sm-title">45 Days</span>
                                </div>

                                <h6 class="pricing-title">Your Benifits</h6>

                                <div class="list-wrapper">
                                    <p class="list">250 Porducts</p>
                                    <p class="list">45 days Validaity </p>
                                    <p class="list">Max 1000$ withdraw in a Month</p>
                                </div>

                                <a class="template-btn outline-btn w-100" href="#">Get Started</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="gs-extra-basic-plan">
                                <h4 class="title">Basic Plan</h4>
                                <div class="circle-wrapper">
                                    <h4 class="cr-title">$150
                                    </h4>
                                    <span class="cr-sm-title">45 Days</span>
                                </div>

                                <h6 class="pricing-title">Your Benifits</h6>

                                <div class="list-wrapper">
                                    <p class="list">250 Porducts</p>
                                    <p class="list">45 days Validaity </p>
                                    <p class="list">Max 1000$ withdraw in a Month</p>
                                </div>

                                <a class="template-btn outline-btn w-100" href="#">Get Started</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
@endsection