@extends('layouts.operator')

@section('content')

            <div class="content-area">

              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Rewards') }} </h4>
                      <ul class="links">
                        <li>
                          <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                          <a href="{{ route('operator-reward-index') }}">{{ __('Rewards') }}</a>
                        </li>
                      </ul>
                  </div>
                </div>
              </div>

              <div class="add-catalogItem-content1 add-catalogItem-content2">

                <div class="row">
                  <div class="col-lg-12">
					<div class="heading-area">
						@include('alerts.operator.form-both')
						<form id="muaadhform" action="{{route('operator-reward-info-update')}}" method="POST">
							@csrf
						<h4 class="name">
							{{ __('Global Reward Settings') }} :
						</h4>

						<div class="action-list mr-2">
							<select class="process select droplinks {{ $gs->is_reward  == 1 ? 'drop-success' : 'drop-danger' }}">
							  <option data-val="1" value="{{route('operator-gs-is_reward',1)}}" {{ $gs->is_reward == 1 ? 'selected' : '' }}>{{ __('Activated') }}</option>
							  <option data-val="0" value="{{route('operator-gs-is_reward',0)}}" {{ $gs->is_reward == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}</option>
							</select>
						  </div>

						  <span>{{__('Point Number')}}</span>

						<div class="action-list ml-2 mr-2 d-inline-block">
							<input type="number" min="1" name="reward_point" class="form-control" value="{{$gs->reward_point}}" placeholder="{{__('Points')}}">
						</div>
						{{__('equals')}} ({{ $sign->sign }})
						<div class="action-list ml-2">
							<input type="number" min="0" step="0.01" name="reward_dolar" class="form-control" value="{{$gs->reward_dolar}}" placeholder="{{__('Value')}}">
						</div>
						<div class="action-list ml-2">
							<button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
						</div>
					</form>
					</div>

					{{-- Merchant Selection --}}
					<div class="heading-area mt-4">
						<h4 class="name">{{ __('Merchant Reward Settings') }}</h4>
						<div class="action-list mr-2">
							<select class="form-control" id="merchant-select" onchange="loadMerchantRewards(this.value)">
								<option value="0" {{ $merchantId == 0 ? 'selected' : '' }}>{{ __('Platform Default') }}</option>
								@foreach($merchants as $merchant)
									<option value="{{ $merchant->id }}" {{ $merchantId == $merchant->id ? 'selected' : '' }}>
										{{ $merchant->shop_name ?: $merchant->name }} (ID: {{ $merchant->id }})
									</option>
								@endforeach
							</select>
						</div>
					</div>

                    <div class="catalogItem-description">
                      <div class="body-area">

                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

						@include('alerts.operator.form-both')
                      <form id="reward-form" action="{{route('operator-reward-update')}}" method="POST">

                        @csrf
						<input type="hidden" name="merchant_id" id="form-merchant-id" value="{{ $merchantId }}">

						@php
							$config = $datas->first();
						@endphp

						<div class="row">
							{{-- Points Earning Ratio --}}
							<div class="col-lg-6 mb-4">
								<div class="card">
									<div class="card-header bg-light">
										<h6 class="mb-0"><i class="fas fa-coins mr-2"></i>{{ __('Points Earning') }}</h6>
									</div>
									<div class="card-body">
										<p class="text-muted mb-3">{{ __('For every X amount spent, customer earns Y points') }}</p>

										<div class="row align-items-end">
											<div class="col-5">
												<label>{{ __('For every') }} ({{ $sign->sign }})</label>
												<input type="number" name="purchase_amount[]" class="form-control"
													value="{{ $config->purchase_amount ?? 100 }}" min="1" required>
											</div>
											<div class="col-2 text-center pb-2">
												<i class="fas fa-arrow-right fa-lg text-muted"></i>
											</div>
											<div class="col-5">
												<label>{{ __('Points') }}</label>
												<input type="number" name="reward[]" class="form-control"
													value="{{ $config->reward ?? 1 }}" min="1" required>
											</div>
										</div>

										<div class="alert alert-info mt-3 mb-0">
											<small>
												<i class="fas fa-info-circle mr-1"></i>
												{{ __('Example') }}: {{ __('Order') }} 500{{ $sign->sign }} =
												<strong id="example-points">{{ $config ? floor(500 / $config->purchase_amount) * $config->reward : 5 }}</strong> {{ __('points') }}
											</small>
										</div>
									</div>
								</div>
							</div>

							{{-- Point Value --}}
							<div class="col-lg-6 mb-4">
								<div class="card">
									<div class="card-header bg-light">
										<h6 class="mb-0"><i class="fas fa-tag mr-2"></i>{{ __('Point Value') }}</h6>
									</div>
									<div class="card-body">
										<p class="text-muted mb-3">{{ __('How much is each point worth when used as payment?') }}</p>

										<div class="row align-items-end">
											<div class="col-5">
												<label>1 {{ __('Point') }}</label>
												<input type="text" class="form-control bg-light" value="1" disabled>
											</div>
											<div class="col-2 text-center pb-2">
												<i class="fas fa-equals fa-lg text-muted"></i>
											</div>
											<div class="col-5">
												<label>{{ __('Value') }} ({{ $sign->sign }})</label>
												<input type="number" step="0.01" min="0.01" name="point_value" class="form-control"
													value="{{ $config->point_value ?? 1.00 }}" required>
											</div>
										</div>

										<div class="alert alert-success mt-3 mb-0">
											<small>
												<i class="fas fa-calculator mr-1"></i>
												10 {{ __('points') }} = <strong id="example-value">{{ $config ? number_format(10 * $config->point_value, 2) : '10.00' }}</strong>{{ $sign->sign }}
											</small>
										</div>
									</div>
								</div>
							</div>
						</div>


                        <div class="row">
                          <div class="col-lg-12">
                            <button class="btn btn-primary" type="submit">{{ __('Save Settings') }}</button>
                          </div>
                        </div>
                      </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

@endsection

@section('scripts')
<script type="text/javascript">

// Load merchant rewards via AJAX
function loadMerchantRewards(merchantId) {
	window.location.href = '{{ route("operator-reward-index") }}?merchant_id=' + merchantId;
}

// Update examples on input change
$('input[name="purchase_amount[]"], input[name="reward[]"], input[name="point_value"]').on('input', function() {
	updateExamples();
});

function updateExamples() {
	var purchaseAmount = parseFloat($('input[name="purchase_amount[]"]').val()) || 100;
	var reward = parseFloat($('input[name="reward[]"]').val()) || 1;
	var pointValue = parseFloat($('input[name="point_value"]').val()) || 1;

	// Example: 500 SAR order
	var examplePoints = Math.floor(500 / purchaseAmount) * reward;
	$('#example-points').text(examplePoints);

	// Example: 10 points value
	var exampleValue = (10 * pointValue).toFixed(2);
	$('#example-value').text(exampleValue);
}

</script>
@endsection
