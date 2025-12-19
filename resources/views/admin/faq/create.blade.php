@extends('layouts.admin')

@section('content')

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Add New Faq') }} <a class="add-btn" href="{{ url()->previous() }}"><i class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Menu Page Settings') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('admin-faq-index') }}">{{ __('Faq') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin-faq-create') }}">{{ __('Add') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-product-content1 add-product-content2">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                        @include('alerts.admin.form-both')
                        <form id="muaadhform" action="{{ route('admin-faq-create') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.admin.form-row', [
                                'label' => __('Title'),
                                'name' => 'title',
                                'value' => old('title'),
                                'placeholder' => __('Title'),
                                'required' => true,
                                'subheading' => __('(In Any Language)')
                            ])

                            @include('components.admin.form-row', [
                                'label' => __('Description'),
                                'name' => 'details',
                                'type' => 'textarea',
                                'value' => old('details'),
                                'placeholder' => __('Description'),
                                'required' => true,
                                'class' => 'nic-edit'
                            ])

                            @include('components.admin.submit-button', [
                                'label' => __('Create FAQ')
                            ])

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
