@extends('layouts.load')
@section('content')

                        <div class="content-area no-padding">
                            <div class="add-catalogItem-content">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="catalogItem-description">
                                            <div class="body-area">
                                                <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="table-responsive show-table">
                                                        <table class="table">
                                                            <tr>
                                                                <th>{{ __('Buyer') }}</th>
                                                                <td>{{$data->user->name}}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>{{ __('Email') }}:</th>
                                                                <td>{{$data->user->email}}</td>
                                                            </tr>
                                                            @if($data->user->phone != "")
                                                            <tr>
                                                                <th>{{ __('Phone') }}:</th>
                                                                <td>{{$data->user->phone}}</td>
                                                            </tr>
                                                            @endif

                                                            <tr>
                                                                <th>{{ __('Posted at') }}:</th>
                                                                <td>{{ date('d-M-Y h:i:s',strtotime($data->created_at))}}</td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="table-responsive show-table">
                                                        <table class="table">
                                                            <tr>
                                                                <th>{{ __('CatalogItem') }}</th>
                                                                <td>{{ $data->catalogItem ? getLocalizedCatalogItemName($data->catalogItem) : __('N/A') }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>{{ __('Brand') }}</th>
                                                                <td>{{ $data->catalogItem && $data->catalogItem->brand ? getLocalizedBrandName($data->catalogItem->brand) : __('N/A') }}</td>
                                                            </tr>
                                                            @if($data->merchantItem && $data->merchantItem->id)
                                                            <tr>
                                                                <th>{{ __('Merchant') }}</th>
                                                                <td>{{ $data->merchantItem->user->shop_name ?? $data->merchantItem->user->name ?? __('N/A') }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>{{ __('Quality Brand') }}</th>
                                                                <td>{{ $data->merchantItem->qualityBrand ? getLocalizedQualityName($data->merchantItem->qualityBrand) : __('N/A') }}</td>
                                                            </tr>
                                                            @endif
                                                        </table>
                                                    </div>
                                                </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-lg-12">
                                                    <h5 class="buyer-note-title">
                                                        {{ __('Note') }}:
                                                        </h5>
                                                        <p class="buyer-note-text">
                                                            {{$data->text}}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

@endsection