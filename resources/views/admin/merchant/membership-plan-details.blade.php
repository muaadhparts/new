@extends('layouts.load')
@section('content')

                        <div class="content-area no-padding">
                            <div class="add-catalogItem-content1">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="catalogItem-description">
                                            <div class="body-area">

                                    <div class="table-responsive show-table">
                                        <table class="table">
                                            <tr>
                                                <th width="50%">{{ __("Customer ID#") }}</th>
                                                <td>{{ $membershipPlan->user->id }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Name") }}</th>
                                                <td>{{ $membershipPlan->user->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Email") }}</th>
                                                <td>{{$membershipPlan->user->email}}</td>
                                            </tr>
                                            @if($membershipPlan->user->phone != "")
                                            <tr>
                                                <th>{{ __("Phone") }}</th>
                                                <td>{{$membershipPlan->user->phone}}</td>
                                            </tr>
                                            @endif

                                            @if($membershipPlan->user->fax != "")
                                            <tr>
                                                <th>{{ __("Fax") }}</th>
                                                <td>{{$membershipPlan->user->fax}}</td>
                                            </tr>
                                            @endif

                                            @if($membershipPlan->user->address != "")
                                            <tr>
                                                <th>{{ __("Address") }}</th>
                                                <td>{{$membershipPlan->user->address}}</td>
                                            </tr>
                                            @endif

                                            @if($membershipPlan->user->city != "")
                                            <tr>
                                                <th>{{ __("City") }}</th>
                                                <td>{{$membershipPlan->user->city}}</td>
                                            </tr>
                                            @endif

                                            @if($membershipPlan->user->zip != "")
                                            <tr>
                                                <th>{{ __("Zip") }}</th>
                                                <td>{{$membershipPlan->user->zip}}</td>
                                            </tr>
                                            @endif

                                            <tr>
                                                <th>{{ __("Merchant Name") }}</th>
                                                <td>{{$membershipPlan->user->owner_name}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Merchant Phone Number") }}</th>
                                                <td>{{$membershipPlan->user->shop_number}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Merchant Shop Address") }}</th>
                                                <td>{{$membershipPlan->user->shop_address}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Merchant Registration Number") }}</th>
                                                <td>{{$membershipPlan->user->reg_number}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Merchant Message") }}</th>
                                                <td>{{$membershipPlan->user->shop_message}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Membership Plan") }}</th>
                                                <td>{{$membershipPlan->title}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Currency Code") }}</th>
                                                <td>{{$membershipPlan->currency_code}}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Cost") }}</th>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice(($membershipPlan->price * $membershipPlan->currency_value),$membershipPlan->currency_sign) }}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Duration") }}</th>
                                                <td>{{$membershipPlan->days}} {{ __("Days") }}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Details") }}</th>
                                                <td>{!!$membershipPlan->details!!}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Duration") }}</th>
                                                <td>{{$membershipPlan->days}} {{ __("Days") }}</td>
                                            </tr>

                                            <tr>
                                                <th>{{ __("Method") }}</th>
                                                <td>{{$membershipPlan->method}}</td>
                                            </tr>


                                          @if($membershipPlan->method == "Stripe")
                                            <tr>
                                                <th>{{ __("Transaction ID") }}</th>
                                                <td>{{$membershipPlan->txnid}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Charge ID") }}</th>
                                                <td>{{$membershipPlan->charge_id}}</td>
                                            </tr>
                                            @elseif($membershipPlan->method == "Paypal")
                                            <tr>
                                                <th>{{ __("Transaction ID") }}</th>
                                                <td>{{$membershipPlan->txnid}}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <th>{{ __("Purchase Time") }}</th>
                                                <td>{{$membershipPlan->created_at->diffForHumans()}}</td>
                                            </tr>

                                        </table>
                                    </div>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

@endsection
