@extends('layouts.admin')
@section('styles')
<link href="{{asset('assets/admin/css/jquery-ui.css')}}" rel="stylesheet" type="text/css">
@endsection
@section('content')

				<div class="content-area">
					<div class="mr-breadcrumb">
						<div class="row">
							<div class="col-lg-12">
									<h4 class="heading">{{ __('Membership Plan Earning') }}</h4>
									<ul class="links">
										<li>
											<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
										</li>
										<li>
											<a href="javascript:;">{{ __('Total Earning') }} </a>
										</li>
										<li>
											<a href="{{ route('admin-membership-plan-income') }}">{{ __('Membership Plan Earning') }}</a>
										</li>
									</ul>
							</div>
						</div>
					</div>
					<form action="{{route('admin-membership-plan-income')}}" method="GET">

							<div class="catalogItem-area">
							<div class="row text-center p-3">
							   @include('includes.admin.form-both')
							   <div class="col-sm-6 col-lg-4 offset-lg-2 col-md-6  mt-3">
								  <input type="text"  autocomplete="off" class="form-control discount_date" value="{{$start_date != '' ? $start_date->format('d-m-Y') : ''}}"  name="start_date"  placeholder="{{ __("Enter Date") }}"  value="">
							   </div>
							   <div class="col-sm-6 col-lg-4 col-md-6  mt-3">
								  <input type="text"  autocomplete="off" class="form-control discount_date" value="{{$end_date != '' ? $end_date->format('d-m-Y') : ''}}" name="end_date"  placeholder="{{ __("Enter Date") }}"  value="">
							   </div>
							   <div class="col-sm-12 mt-3">
								  <button type="submit" class="btn btn-primary">Check</button>
								  <button type="button" id="reset" class="btn btn-primary">Reset</button>
							   </div>
							   <div class="col-lg-12 mt-2">
								  <p class="text-center"> <b> {{$start_date != '' ? $start_date->format('d-m-Y') : ''}} {{$start_date != '' && $end_date != '' ? 'To' : ''}}  {{$end_date != '' ? $end_date->format('d-m-Y') : ''}} {{__('Total Earning')}} : {{$total}}</b></p>
								  <p class="text-center"> <b>Current Month Earning : {{$current_month}}</b></p>
								  <p class="text-center"> <b>Last 30 Days Earning : {{$last_30_days}}</b></p>
							   </div>
							</div>
						 </form>
						 @include('includes.admin.form-success')
						 <div class="mr-table allproduct">
							<div class="table-responsive">
							   <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
								  <thead>
									 <tr>
										<th width="5%">{{ __('#') }}</th>
										<th width="20%">{{ __('Merchant') }}</th>
										<th width="25%">{{ __('Membership Plan') }}</th>
										<th width="15%">{{ __('Txn ID') }}</th>
										<th width="15%">{{ __('Price') }}</th>
										<th width="10%">{{ __('Method') }}</th>
										<th width="10%">{{ __('Created At') }}</th>
									 </tr>
								  </thead>
								  <tbody>
									 @foreach ($membershipPlans as $key => $membershipPlan)
									 <tr>
										<td>{{$key+1}}</td>
										<td>
										   {{ $membershipPlan->user?->shop_name ?? $membershipPlan->user?->name ?? __('N/A') }}
										</td>
										<td>
										   {{ $membershipPlan->title ?? __('N/A') }}
										</td>
										<td>
										   {{ $membershipPlan->txnid ?? __('N/A') }}
										</td>
										<td>
										   {{ $membershipPlan->currency_sign ?? $currency->sign ?? '' }}{{ number_format($membershipPlan->price, 2) }}
										</td>
										<td>
										   {{ ucfirst($membershipPlan->method ?? 'N/A') }}
										</td>
										<td>
										   {{$membershipPlan->created_at->format('d-m-Y')}}
										</td>
									 </tr>
									 @endforeach
								  </tbody>
							   </table>
							</div>
						 </div>
						 </div>
@endsection



@section('scripts')
<script type="text/javascript">
	$('#muaadhtable').DataTable();
	$(document).on('click','#reset',function(){
	$('.discount_date').val('');
	location.href = '{{route('admin-membership-plan-income')}}';
	})
</script>
@endsection
