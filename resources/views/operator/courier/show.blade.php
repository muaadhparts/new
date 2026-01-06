@extends('layouts.operator')

@section('content')

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __("Courier Details") }} <a class="add-btn" href="{{ url()->previous() }}"><i
                            class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
                    </li>
                    <li>
                        <a href="{{ route('operator-courier-index') }}">{{ __("Couriers") }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-courier-show',$data->id) }}">{{ __("Details") }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="add-catalogItem-content1 customar-details-area add-catalogItem-content2">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="user-image">
                                    @if($data->is_provider == 1)
                                    <img src="{{ $data->photo ? asset($data->photo):asset('assets/images/'.$gs->user_image)}}"
                                        alt="No Image">
                                    @else
                                    <img src="{{ $data->photo ? asset('assets/images/users/'.$data->photo):asset('assets/images/'.$gs->user_image)}}"
                                        alt="No Image">
                                    @endif
                                    <a href="javascript:;" class="btn btn-primary send" data-email="{{ $data->email }}"
                                        data-bs-toggle="modal" data-bs-target="#merchantform">{{ __("Send Message") }}</a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-responsive show-table">
                                    <table class="table">
                                        <tr>
                                            <th>{{ __("ID#") }}</th>
                                            <td>{{$data->id}}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __("Name") }}</th>
                                            <td>{{$data->name}}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __("Email") }}</th>
                                            <td>{{$data->email}}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __("Phone") }}</th>
                                            <td>{{$data->phone}}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __("Address") }}</th>
                                            <td>{{$data->address}}</td>
                                        </tr>

                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-responsive show-table">
                                    <table class="table">

                                        @if($data->country != null)
                                        <tr>
                                            <th>{{ __("Country") }}</th>
                                            <td>{{$data->country}}</td>
                                        </tr>
                                        @endif
                                        @if($data->city_id != null)
                                        <tr>
                                            <th>{{ __("City") }}</th>
                                            <td>{{$data->city->city_name}}</td>
                                        </tr>
                                        @endif
                                        @if($data->fax != null)
                                        <tr>
                                            <th>{{ __("Fax") }}</th>
                                            <td>{{$data->fax}}</td>
                                        </tr>
                                        @endif
                                        @if($data->zip != null)
                                        <tr>
                                            <th>{{ __("Zip Code") }}</th>
                                            <td>{{$data->zip}}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th>{{ __("Joined") }}</th>
                                            <td>{{$data->created_at->diffForHumans()}}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="purchase-table-wrap">
                        <div class="purchase-details-table">
                            <div class="mr-table">
                                <h4 class="title">{{ __("Items Ordered") }}</h4>
                                <div class="table-responsive">
                                    <table id="example2" class="table table-hover dt-responsive" cellspacing="0"
                                        width="100%">
                                        <thead>
                                            <tr>
                                                <th>{{ __("Purchase ID") }}</th>
                                                <th>{{ __("Purchase Date") }}</th>
                                                <th>{{ __("Purchase Amount") }}</th>
                                                <th>{{ __("Status") }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach($data->deliveries as $deliveryCourier)

                                            <tr>
                                                <td><a href="{{ route('operator-purchase-invoice',$deliveryCourier->purchase->id) }}">{{sprintf("%'.08d",
                                                        $deliveryCourier->purchase->id)}}</a></td>
                                                <td>{{ Carbon\Carbon::parse($deliveryCourier->created_at)->format('d/m/Y') }}</td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice(($deliveryCourier->purchase->pay_amount *
                                                    $deliveryCourier->purchase->currency_value),$deliveryCourier->purchase->currency_sign) }}</td>
                                                <td>{{ ucwords($deliveryCourier->status) }}</td>
                                                <td>
                                                    <a href=" {{ route('operator-purchase-show',$deliveryCourier->purchase->id) }}"
                                                        class="view-details">
                                                        <i class="fas fa-check"></i>{{ __("Details") }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MESSAGE MODAL --}}
<div class="sub-categori">
    <div class="modal" id="merchantform" tabindex="-1" role="dialog" aria-labelledby="merchantformLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="merchantformLabel">{{ __("Send Message") }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid p-0">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="contact-form">
                                    <form id="emailreply1" action="{{route('operator-send-message')}}">
                                        {{csrf_field()}}
                                        <ul>
                                            <input type="hidden" id="type" name="type" value="courier">
                                            <li>
                                                <input type="email" class="form-control eml-val" id="eml1" name="to"
                                                    placeholder="{{ __(" Email") }} *" value="" required="">
                                            </li>
                                            <li>
                                                <input type="text" class="form-control" id="subj1" name="subject"
                                                    placeholder="{{ __(" Subject") }} *" required="">
                                            </li>
                                            <li>
                                                <textarea class="form-control textarea" name="message" id="msg1"
                                                    placeholder="{{ __(" Your Message") }} *" required=""></textarea>
                                            </li>
                                        </ul>
                                        <button class="btn btn-primary" id="emlsub1" type="submit">{{ __("Send Message")
                                            }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MESSAGE MODAL ENDS --}}

@endsection

@section('scripts')

<script type="text/javascript">
    (function($) {
		"use strict";

$('#example2').dataTable( {
  "ordering": false,
      'paging'      : false,
      'lengthChange': false,
      'searching'   : false,
      'ordering'    : false,
      'info'        : false,
      'autoWidth'   : false,
      'responsive'  : true
} );

})(jQuery);

</script>


@endsection
