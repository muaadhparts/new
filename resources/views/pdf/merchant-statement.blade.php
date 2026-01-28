<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Account Statement') }} - {{ $merchant_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            direction: rtl;
        }
        .container {
            padding: 20px;
        }
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #006c35;
        }
        .header h1 {
            font-size: 24px;
            color: #006c35;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #666;
        }
        .header .company-info {
            margin-top: 10px;
            font-size: 10px;
            color: #888;
        }
        /* Merchant Info */
        .merchant-info {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .merchant-info table {
            width: 100%;
        }
        .merchant-info td {
            padding: 3px 10px;
        }
        .merchant-info .label {
            font-weight: bold;
            color: #555;
            width: 150px;
        }
        /* Summary Cards */
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-card {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .summary-card.credit {
            background: #d4edda;
            color: #155724;
        }
        .summary-card.debit {
            background: #f8d7da;
            color: #721c24;
        }
        .summary-card.balance {
            background: #cce5ff;
            color: #004085;
        }
        .summary-card h4 {
            font-size: 10px;
            margin-bottom: 5px;
        }
        .summary-card .amount {
            font-size: 16px;
            font-weight: bold;
        }
        /* Statement Table */
        .statement-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .statement-table th {
            background: #006c35;
            color: white;
            padding: 10px 8px;
            text-align: center;
            font-size: 10px;
        }
        .statement-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        .statement-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .statement-table .text-end {
            text-align: left;
        }
        .statement-table .text-center {
            text-align: center;
        }
        .statement-table .credit-amount {
            color: #155724;
            font-weight: bold;
        }
        .statement-table .debit-amount {
            color: #721c24;
            font-weight: bold;
        }
        .statement-table tfoot tr {
            background: #e9ecef;
            font-weight: bold;
        }
        .statement-table tfoot td {
            border-top: 2px solid #006c35;
            padding: 12px 8px;
        }
        /* Badge */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        .badge-cr {
            background: #28a745;
            color: white;
        }
        .badge-dr {
            background: #dc3545;
            color: white;
        }
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #888;
            text-align: center;
        }
        .footer .generated-at {
            margin-bottom: 5px;
        }
        /* Page break */
        .page-break {
            page-break-after: always;
        }
        /* Opening Balance Row */
        .opening-balance {
            background: #fff3cd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ __('Account Statement') }}</h1>
            <div class="subtitle">{{ __('كشف حساب التاجر') }}</div>
            <div class="company-info">
                {{ config('app.name') }} | {{ config('app.url') }}
            </div>
        </div>

        <!-- Merchant Info -->
        <div class="merchant-info">
            <table>
                <tr>
                    <td class="label">{{ __('Merchant Name') }}:</td>
                    <td>{{ $merchant_name }}</td>
                    <td class="label">{{ __('Statement Period') }}:</td>
                    <td>{{ $period }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Merchant ID') }}:</td>
                    <td>#{{ $merchant_id }}</td>
                    <td class="label">{{ __('Generated Date') }}:</td>
                    <td>{{ $generated_at }}</td>
                </tr>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-row">
            <div class="summary-card credit">
                <h4>{{ __('Total Credit') }}</h4>
                <div class="amount">{{ monetaryUnit()->format($total_credit) }}</div>
            </div>
            <div class="summary-card debit">
                <h4>{{ __('Total Debit') }}</h4>
                <div class="amount">{{ monetaryUnit()->format($total_debit) }}</div>
            </div>
            <div class="summary-card balance">
                <h4>{{ __('Closing Balance') }}</h4>
                <div class="amount">
                    {{ monetaryUnit()->format(abs($closing_balance)) }}
                    @if($closing_balance >= 0)
                        ({{ __('CR') }})
                    @else
                        ({{ __('DR') }})
                    @endif
                </div>
            </div>
        </div>

        <!-- Statement Table -->
        <table class="statement-table">
            <thead>
                <tr>
                    <th style="width: 70px;">{{ __('Date') }}</th>
                    <th style="width: 180px;">{{ __('Description') }}</th>
                    <th style="width: 80px;">{{ __('Reference') }}</th>
                    <th style="width: 60px;">{{ __('Status') }}</th>
                    <th style="width: 80px;">{{ __('Credit') }}</th>
                    <th style="width: 80px;">{{ __('Debit') }}</th>
                    <th style="width: 90px;">{{ __('Balance') }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="opening-balance">
                    <td>{{ $start_date ?? '-' }}</td>
                    <td colspan="3">{{ __('Opening Balance') }}</td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end">
                        {{ monetaryUnit()->format(abs($opening_balance)) }}
                        @if($opening_balance >= 0)
                            <span class="badge badge-cr">CR</span>
                        @else
                            <span class="badge badge-dr">DR</span>
                        @endif
                    </td>
                </tr>

                @forelse($statement as $entry)
                <tr>
                    <td>{{ $entry['date'] instanceof \Carbon\Carbon ? $entry['date']->format('d-m-Y') : $entry['date'] }}</td>
                    <td>{{ $entry['description'] ?? $entry['description_ar'] ?? '-' }}</td>
                    <td class="text-center">{{ $entry['purchase_number'] ?? '-' }}</td>
                    <td class="text-center">
                        @if(($entry['payment_owner'] ?? '') === 'merchant')
                            <span class="badge badge-success">{{ __('Paid') }}</span>
                        @elseif(($entry['settlement_status'] ?? '') === 'settled')
                            <span class="badge badge-success">{{ __('Settled') }}</span>
                        @else
                            <span class="badge badge-warning">{{ __('Pending') }}</span>
                        @endif
                    </td>
                    <td class="text-end credit-amount">
                        @if(($entry['credit'] ?? 0) > 0)
                            {{ monetaryUnit()->format($entry['credit']) }}
                        @endif
                    </td>
                    <td class="text-end debit-amount">
                        @if(($entry['debit'] ?? 0) > 0)
                            {{ monetaryUnit()->format($entry['debit']) }}
                        @endif
                    </td>
                    <td class="text-end">
                        {{ monetaryUnit()->format(abs($entry['balance'] ?? 0)) }}
                        @if(($entry['balance'] ?? 0) >= 0)
                            <span class="badge badge-cr">CR</span>
                        @else
                            <span class="badge badge-dr">DR</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">
                        {{ __('No transactions found for this period') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(count($statement) > 0)
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: left;">{{ __('Closing Balance') }}</td>
                    <td class="text-end credit-amount">{{ monetaryUnit()->format($total_credit) }}</td>
                    <td class="text-end debit-amount">{{ monetaryUnit()->format($total_debit) }}</td>
                    <td class="text-end">
                        {{ monetaryUnit()->format(abs($closing_balance)) }}
                        @if($closing_balance >= 0)
                            <span class="badge badge-cr">CR</span>
                        @else
                            <span class="badge badge-dr">DR</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>

        <!-- Footer -->
        <div class="footer">
            <div class="generated-at">
                {{ __('Generated on') }}: {{ $generated_at }}
            </div>
            <div>
                {{ __('This is a computer-generated document and does not require a signature.') }}
            </div>
        </div>
    </div>
</body>
</html>
