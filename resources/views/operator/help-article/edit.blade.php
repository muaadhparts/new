@extends('layouts.operator')

@section('content')

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Edit Help Article') }} <a class="add-btn" href="{{ url()->previous() }}"><i class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Menu Page Settings') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('operator-help-article-index') }}">{{ __('Help Article') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-help-article-edit', $data->id) }}">{{ __('Edit') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-catalogItem-content1 add-catalogItem-content2">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                        @include('alerts.operator.form-both')
                        <form id="muaadhform" action="{{ route('operator-help-article-update', $data->id) }}" method="POST">
                            @csrf

                            @include('components.operator.form-row', [
                                'label' => __('Title'),
                                'name' => 'title',
                                'value' => old('title', $data->title),
                                'placeholder' => __('Title'),
                                'required' => true,
                                'subheading' => __('(In Any Language)')
                            ])

                            @include('components.operator.form-row', [
                                'label' => __('Description'),
                                'name' => 'details',
                                'type' => 'textarea',
                                'value' => old('details', $data->details),
                                'placeholder' => __('Description'),
                                'required' => true,
                                'class' => 'nic-edit'
                            ])

                            @include('components.operator.submit-button', [
                                'label' => __('Save')
                            ])

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
