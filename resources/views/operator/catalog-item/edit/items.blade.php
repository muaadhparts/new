@extends('layouts.operator')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading"> {{ __('Edit CatalogItem') }}<a class="add-btn" href="{{ url()->previous() }}"><i
                                class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="{{ route('operator-catalog-item-index') }}">{{ __('Catalog Items') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Edit') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <form id="muaadhform" action="{{ route('operator-catalog-item-update', $data->id) }}" method="POST">
            {{ csrf_field() }}
            @include('alerts.operator.form-both')
            <div class="row">
                <div class="col-lg-8">
                    <div class="add-catalogItem-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="catalogItem-description">
                                    <div class="body-area">
                                        <div class="gocover"
                                            style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                                        </div>

                                        {{-- CatalogItem Name --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Name') }}* </h4>
                                                    <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control"
                                                    placeholder="{{ __('Enter CatalogItem Name') }}"
                                                    name="name" required="" value="{{ $data->name }}">
                                            </div>
                                        </div>

                                        {{-- Label English --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Name (English)') }}</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control"
                                                    placeholder="{{ __('Enter CatalogItem Name in English') }}"
                                                    name="label_en" value="{{ $data->label_en }}">
                                            </div>
                                        </div>

                                        {{-- Label Arabic --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Name (Arabic)') }}</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control" dir="rtl"
                                                    placeholder="{{ __('Enter CatalogItem Name in Arabic') }}"
                                                    name="label_ar" value="{{ $data->label_ar }}">
                                            </div>
                                        </div>

                                        {{-- Part Number --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Part Number') }}* </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control"
                                                    placeholder="{{ __('Enter CatalogItem Part Number') }}" name="part_number"
                                                    required="" value="{{ $data->part_number }}">
                                            </div>
                                        </div>

                                        {{-- Weight --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Weight') }}</h4>
                                                    <p class="sub-heading">{{ __('(In KG - Optional)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="number" class="form-control" step="0.01" min="0"
                                                    placeholder="{{ __('e.g 1.5') }}" name="weight" value="{{ $data->weight ?? '1.00' }}">
                                            </div>
                                        </div>

                                        {{-- Measurement --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Measurement') }}</h4>
                                                    <p class="sub-heading">{{ __('(Optional)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <select id="product_measure" class="form-control">
                                                    <option value="" {{ $data->measure == null ? 'selected' : '' }}>{{ __('None') }}</option>
                                                    <option value="Gram" {{ $data->measure == 'Gram' ? 'selected' : '' }}>{{ __('Gram') }}</option>
                                                    <option value="Kilogram" {{ $data->measure == 'Kilogram' ? 'selected' : '' }}>{{ __('Kilogram') }}</option>
                                                    <option value="Litre" {{ $data->measure == 'Litre' ? 'selected' : '' }}>{{ __('Litre') }}</option>
                                                    <option value="Pound" {{ $data->measure == 'Pound' ? 'selected' : '' }}>{{ __('Pound') }}</option>
                                                    <option value="Custom" {{ $data->measure && !in_array($data->measure, ['Gram', 'Kilogram', 'Litre', 'Pound']) ? 'selected' : '' }}>{{ __('Custom') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 {{ $data->measure && !in_array($data->measure, ['Gram', 'Kilogram', 'Litre', 'Pound', null]) ? '' : 'hidden' }}" id="measure">
                                                <input name="measure" type="text" id="measurement"
                                                    class="form-control" placeholder="{{ __('Enter Unit') }}"
                                                    value="{{ $data->measure }}">
                                            </div>
                                        </div>

                                        {{-- Youtube Video URL --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Youtube Video URL') }}</h4>
                                                    <p class="sub-heading">{{ __('(Optional)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="youtube" type="text" class="form-control"
                                                    placeholder="{{ __('Enter Youtube Video URL') }}" value="{{ $data->youtube }}">
                                            </div>
                                        </div>

                                        {{-- Tags --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Tags') }}</h4>
                                                    <p class="sub-heading">{{ __('(Optional - For Search)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul id="tags" class="myTags">
                                                    @if (!empty($data->tags))
                                                        @foreach ($data->tags as $element)
                                                            <li>{{ $element }}</li>
                                                        @endforeach
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>

                                        {{-- SEO Section --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="checkbox-wrapper">
                                                    <input type="checkbox" name="seo_check" value="1"
                                                        class="checkclick" id="allowProductSEO"
                                                        {{ $data->meta_tag != null || strip_tags($data->meta_description) != null ? 'checked' : '' }}>
                                                    <label for="allowProductSEO">{{ __('Allow CatalogItem SEO') }}</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="{{ $data->meta_tag == null && strip_tags($data->meta_description) == null ? 'showbox' : '' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('Meta Tags') }}</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <ul id="metatags" class="myTags">
                                                        @if (!empty($data->meta_tag))
                                                            @foreach ($data->meta_tag as $element)
                                                                <li>{{ $element }}</li>
                                                            @endforeach
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('Meta Description') }}</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="text-editor">
                                                        <textarea name="meta_description" class="form-control" placeholder="{{ __('Meta Description') }}">{{ $data->meta_description }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Submit Button --}}
                                        <div class="row text-center mt-4">
                                            <div class="col-12">
                                                <button class="btn btn-primary btn-lg"
                                                    type="submit">{{ __('Update CatalogItem') }}</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sidebar Info --}}
                <div class="col-lg-4">
                    <div class="add-catalogItem-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="catalogItem-description">
                                    <div class="body-area">
                                        <div class="alert alert-info">
                                            <h5><i class="fas fa-info-circle"></i> {{ __('Note') }}</h5>
                                            <p>{{ __('This form edits the catalog item card only.') }}</p>
                                            <p>{{ __('Pricing, stock, and merchant-specific details are managed separately by merchants through their own panel.') }}</p>
                                        </div>

                                        <div class="alert alert-secondary mt-3">
                                            <h6>{{ __('Catalog Item Fields:') }}</h6>
                                            <ul class="mb-0">
                                                <li>{{ __('Name (Required)') }}</li>
                                                <li>{{ __('Part Number (Required)') }}</li>
                                                <li>{{ __('Labels (EN/AR)') }}</li>
                                                <li>{{ __('Weight') }}</li>
                                                <li>{{ __('Measurement Unit') }}</li>
                                                <li>{{ __('Youtube URL') }}</li>
                                                <li>{{ __('Tags & SEO') }}</li>
                                            </ul>
                                        </div>

                                        {{-- Catalog Item Stats --}}
                                        <div class="alert alert-light mt-3">
                                            <h6>{{ __('Item Info:') }}</h6>
                                            <ul class="mb-0 list-unstyled">
                                                <li><strong>{{ __('ID') }}:</strong> {{ $data->id }}</li>
                                                <li><strong>{{ __('Views') }}:</strong> {{ $data->views }}</li>
                                                <li><strong>{{ __('Created') }}:</strong> {{ $data->created_at ? $data->created_at->format('Y-m-d') : '-' }}</li>
                                                <li><strong>{{ __('Updated') }}:</strong> {{ $data->updated_at ? $data->updated_at->format('Y-m-d') : '-' }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@section('scripts')
    @include('partials.operator.catalogItem.catalogItem-scripts')
@endsection
