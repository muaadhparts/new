@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Create Settlement') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="{{ route('operator.accounts.settlements') }}">{{ __('Settlements') }}</a></li>
                    <li><a href="javascript:;">{{ __('Create') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-money-check me-2"></i>{{ __('Settlement Details') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.accounts.settlements.store') }}" method="POST">
                        @csrf

                        {{-- From Party --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('From Party') }} <span class="text-danger">*</span></label>
                            <select name="from_party_id" class="form-select @error('from_party_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select Party') }}</option>
                                @foreach($parties as $type => $group)
                                    <optgroup label="{{ $group->first()->getTypeNameAr() }}">
                                        @foreach($group as $p)
                                            <option value="{{ $p->id }}" {{ $party && $party->id == $p->id ? 'selected' : (old('from_party_id') == $p->id ? 'selected' : '') }}>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('from_party_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ __('The party making the payment') }}</small>
                        </div>

                        {{-- To Party --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('To Party') }} <span class="text-danger">*</span></label>
                            <select name="to_party_id" class="form-select @error('to_party_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select Party') }}</option>
                                <option value="{{ $platform->id }}" {{ old('to_party_id') == $platform->id ? 'selected' : '' }}>
                                    {{ $platform->name }} ({{ __('Platform') }})
                                </option>
                                @foreach($parties as $type => $group)
                                    <optgroup label="{{ $group->first()->getTypeNameAr() }}">
                                        @foreach($group as $p)
                                            <option value="{{ $p->id }}" {{ old('to_party_id') == $p->id ? 'selected' : '' }}>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('to_party_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ __('The party receiving the payment') }}</small>
                        </div>

                        {{-- Amount --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency->sign }}</span>
                                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                       step="0.01" min="0.01" value="{{ old('amount', $pendingBalance ?: '') }}" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if($party && $pendingBalance > 0)
                                <small class="text-muted">{{ __('Pending balance') }}: {{ $currency->formatAmount($pendingBalance) }}</small>
                            @endif
                        </div>

                        {{-- Payment Method --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                <option value="">{{ __('Select Method') }}</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>{{ __('Bank Transfer') }}</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>{{ __('Cash') }}</option>
                                <option value="wallet" {{ old('payment_method') == 'wallet' ? 'selected' : '' }}>{{ __('Wallet') }}</option>
                                <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>{{ __('Cheque') }}</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Payment Reference --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Payment Reference') }}</label>
                            <input type="text" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror"
                                   value="{{ old('payment_reference') }}" placeholder="{{ __('Transfer number, cheque number, etc.') }}">
                            @error('payment_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('operator.accounts.settlements') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> {{ __('Create Settlement') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Help Card --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-info-circle me-2"></i>{{ __('Help') }}
                </div>
                <div class="card-body">
                    <h6>{{ __('How Settlements Work') }}</h6>
                    <ul class="mb-0">
                        <li class="mb-2">{{ __('Select the party making the payment (From)') }}</li>
                        <li class="mb-2">{{ __('Select the party receiving the payment (To)') }}</li>
                        <li class="mb-2">{{ __('Enter the settlement amount') }}</li>
                        <li class="mb-2">{{ __('Choose the payment method') }}</li>
                        <li>{{ __('The system will automatically update balances') }}</li>
                    </ul>
                </div>
            </div>

            @if($party)
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fas fa-user me-2"></i>{{ __('Selected Party Info') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Name') }}:</td>
                            <td><strong>{{ $party->name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Type') }}:</td>
                            <td>{{ $party->getTypeNameAr() }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Pending Balance') }}:</td>
                            <td class="text-danger fw-bold">{{ $currency->formatAmount($pendingBalance) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
