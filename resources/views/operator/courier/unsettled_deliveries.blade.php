@extends('layouts.operator')
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Unsettled Deliveries') }}: {{ $courier->name }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-courier-balances') }}">{{ __('Courier Balances') }}</a></li>
                    <li><a href="{{ route('operator-courier-details', $courier->id) }}">{{ $courier->name }}</a></li>
                    <li><a href="#">{{ __('Unsettled Deliveries') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <!-- Action Buttons -->
        <div class="mb-4">
            <a href="{{ route('operator-courier-create-settlement', $courier->id) }}" class="btn btn-success">
                <i class="fas fa-dollar-sign"></i> {{ __('Create Settlement for All') }}
            </a>
            <a href="{{ route('operator-courier-details', $courier->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('Back to Courier Details') }}
            </a>
        </div>

        @include('alerts.operator.form-both')

        <!-- Unsettled Deliveries Table -->
        <div class="mr-table allproduct">
            <div class="table-responsive">
                <table class="table table-hover" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Purchase') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Order Amount') }}</th>
                            <th>{{ __('Delivery Fee') }}</th>
                            <th>{{ __('Delivered At') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unsettled as $key => $delivery)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                @if($delivery->purchase)
                                    <a href="{{ route('operator-purchase-invoice', $delivery->purchase_id) }}">
                                        {{ $delivery->purchase->purchase_number ?? 'N/A' }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($delivery->purchase)
                                    {{ $delivery->purchase->customer_name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $delivery->purchase->customer_city ?? '' }}</small>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($delivery->payment_method == 'cod')
                                    <span class="badge bg-warning">{{ __('COD') }}</span>
                                @else
                                    <span class="badge bg-info">{{ __('Online') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($delivery->payment_method == 'cod')
                                    <span class="text-danger">
                                        {{ $currency->sign }}{{ number_format($delivery->purchase_amount ?? 0, 2) }}
                                    </span>
                                    <br><small class="text-muted">({{ __('Courier collected') }})</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-success">
                                {{ $currency->sign }}{{ number_format($delivery->delivery_fee ?? 0, 2) }}
                                <br><small class="text-muted">({{ __('Courier earned') }})</small>
                            </td>
                            <td>
                                @if($delivery->delivered_at)
                                    {{ $delivery->delivered_at->format('d-m-Y H:i') }}
                                @else
                                    {{ $delivery->updated_at->format('d-m-Y H:i') }}
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success">{{ __('Delivered') }}</span>
                                <br>
                                <span class="badge bg-warning">{{ __('Unsettled') }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <br>{{ __('All deliveries are settled!') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($unsettled->count() > 0)
                    <tfoot class="table-active">
                        <tr>
                            <td colspan="4" class="text-end"><strong>{{ __('Totals') }}:</strong></td>
                            <td class="text-danger">
                                <strong>{{ $currency->sign }}{{ number_format($summary['cod_total'], 2) }}</strong>
                            </td>
                            <td class="text-success">
                                <strong>{{ $currency->sign }}{{ number_format($summary['fees_total'], 2) }}</strong>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end"><strong>{{ __('Net (Fees - COD)') }}:</strong></td>
                            {{-- جميع القيم من الـ Controller - لا حسابات هنا --}}
                            <td colspan="2" class="{{ $summary['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong>{{ $currency->sign }}{{ number_format($summary['net'], 2) }}</strong>
                                @if($summary['net'] >= 0)
                                    <br><small>({{ __('Platform pays Courier') }})</small>
                                @else
                                    <br><small>({{ __('Courier pays Platform') }})</small>
                                @endif
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
