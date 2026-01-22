<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Shipping Company Statement') }} - {{ $companyName }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path("fonts/DejaVuSans.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        * {
            font-family: 'DejaVu Sans', sans-serif;
        }
        body {
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
            font-size: 18px;
        }
        .header .subtitle {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
        .company-info {
            background: #f0f9ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .company-info h2 {
            margin: 0 0 5px 0;
            color: #1e40af;
            font-size: 14px;
        }
        .company-info .code {
            color: #64748b;
            font-size: 10px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 5px;
            text-align: center;
        }
        .summary-card .inner {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 8px;
        }
        .summary-card.success .inner { background: #dcfce7; border-color: #86efac; }
        .summary-card.warning .inner { background: #fef3c7; border-color: #fcd34d; }
        .summary-card.danger .inner { background: #fee2e2; border-color: #fca5a5; }
        .summary-card.info .inner { background: #dbeafe; border-color: #93c5fd; }
        .summary-card .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
        }
        .summary-card .value {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
        }
        .balance-section {
            margin-bottom: 15px;
        }
        .balance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .balance-table th, .balance-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: right;
        }
        .balance-table th {
            background: #f1f5f9;
            font-size: 9px;
            text-transform: uppercase;
        }
        .balance-table .pending { background: #fef3c7; }
        .balance-table .settled { background: #dcfce7; }
        table.statement {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.statement th {
            background: #1e40af;
            color: white;
            padding: 6px 4px;
            text-align: right;
            font-weight: bold;
            font-size: 8px;
        }
        table.statement td {
            border-bottom: 1px solid #e2e8f0;
            padding: 5px 4px;
        }
        table.statement tr:nth-child(even) {
            background: #f8fafc;
        }
        table.statement tfoot td {
            background: #1e293b;
            color: white;
            font-weight: bold;
            padding: 6px 4px;
        }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .text-warning { color: #d97706; }
        .text-info { color: #0891b2; }
        .text-end { text-align: left; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #64748b;
            text-align: center;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>{{ __('Shipping Company Statement') }}</h1>
        <div class="subtitle">
            {{ $startDate }} - {{ $endDate }}
        </div>
    </div>

    {{-- Company Info --}}
    <div class="company-info">
        <h2>{{ $companyName }}</h2>
        <div class="code">{{ __('Provider Code') }}: {{ $providerCode }}</div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-card info">
                <div class="inner">
                    <div class="label">{{ __('Total Shipments') }}</div>
                    <div class="value">{{ number_format($totalShipments) }}</div>
                </div>
            </div>
            <div class="summary-card success">
                <div class="inner">
                    <div class="label">{{ __('Shipping Fees') }}</div>
                    <div class="value">{{ $currency->sign }}{{ number_format($totalShippingFees, 2) }}</div>
                </div>
            </div>
            <div class="summary-card warning">
                <div class="inner">
                    <div class="label">{{ __('COD Collected') }}</div>
                    <div class="value">{{ $currency->sign }}{{ number_format($totalCodCollected, 2) }}</div>
                </div>
            </div>
            <div class="summary-card {{ $netBalance >= 0 ? 'success' : 'danger' }}">
                <div class="inner">
                    <div class="label">{{ __('Net Balance') }}</div>
                    <div class="value">{{ $currency->sign }}{{ number_format(abs($netBalance), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Balance Section --}}
    <div class="balance-section">
        <table class="balance-table">
            <tr>
                <th colspan="2" style="text-align: center; background: #1e40af; color: white;">{{ __('Settlement Summary') }}</th>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <strong>{{ __('Owes to Platform') }}:</strong>
                </td>
                <td class="text-danger" style="font-weight: bold;">
                    {{ $currency->sign }}{{ number_format($owesToPlatform, 2) }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>{{ __('Owes to Merchants') }}:</strong>
                </td>
                <td class="text-warning" style="font-weight: bold;">
                    {{ $currency->sign }}{{ number_format($owesToMerchant, 2) }}
                </td>
            </tr>
            <tr class="pending">
                <td>
                    <strong>{{ __('Pending to Platform') }}:</strong>
                </td>
                <td>{{ $currency->sign }}{{ number_format($pendingToPlatform, 2) }}</td>
            </tr>
            <tr class="pending">
                <td>
                    <strong>{{ __('Pending to Merchants') }}:</strong>
                </td>
                <td>{{ $currency->sign }}{{ number_format($pendingToMerchant, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Statement Table --}}
    <table class="statement">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Order #') }}</th>
                <th>{{ __('Merchant') }}</th>
                <th class="text-end">{{ __('Shipping') }}</th>
                <th class="text-end">{{ __('COD') }}</th>
                <th class="text-end">{{ __('Owes Platform') }}</th>
                <th class="text-end">{{ __('Owes Merchant') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
                <th class="text-end">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement as $entry)
            <tr>
                <td>{{ $entry['date']->format('d-m-Y') }}</td>
                <td>{{ $entry['purchase_number'] }}</td>
                <td>{{ $entry['merchant_name'] }}</td>
                <td class="text-end text-success">
                    @if($entry['shipping_fee'] > 0)
                        {{ $currency->sign }}{{ number_format($entry['shipping_fee'], 2) }}
                    @endif
                </td>
                <td class="text-end text-warning">
                    @if($entry['cod_collected'] > 0)
                        {{ $currency->sign }}{{ number_format($entry['cod_collected'], 2) }}
                    @endif
                </td>
                <td class="text-end text-danger">
                    @if($entry['owes_platform'] > 0)
                        {{ $currency->sign }}{{ number_format($entry['owes_platform'], 2) }}
                    @endif
                </td>
                <td class="text-end text-info">
                    @if($entry['owes_merchant'] > 0)
                        {{ $currency->sign }}{{ number_format($entry['owes_merchant'], 2) }}
                    @endif
                </td>
                <td class="text-center">
                    @if($entry['settlement_status'] === 'settled')
                        <span class="badge badge-success">{{ __('Settled') }}</span>
                    @else
                        <span class="badge badge-warning">{{ __('Pending') }}</span>
                    @endif
                </td>
                <td class="text-end">
                    {{ $currency->sign }}{{ number_format(abs($entry['balance']), 2) }}
                    @if($entry['balance'] >= 0)
                        <span class="badge badge-success">CR</span>
                    @else
                        <span class="badge badge-danger">DR</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">{{ __('No shipments found') }}</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($statement) > 0)
        <tfoot>
            <tr>
                <td colspan="3">{{ __('Totals') }}</td>
                <td class="text-end">{{ $currency->sign }}{{ number_format($totalShippingFees, 2) }}</td>
                <td class="text-end">{{ $currency->sign }}{{ number_format($totalCodCollected, 2) }}</td>
                <td class="text-end">{{ $currency->sign }}{{ number_format($owesToPlatform, 2) }}</td>
                <td class="text-end">{{ $currency->sign }}{{ number_format($owesToMerchant, 2) }}</td>
                <td></td>
                <td class="text-end">
                    {{ $currency->sign }}{{ number_format(abs($netBalance), 2) }}
                    @if($netBalance >= 0)
                        <span class="badge badge-success">CR</span>
                    @else
                        <span class="badge badge-danger">DR</span>
                    @endif
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- Footer --}}
    <div class="footer">
        <p>{{ __('Generated on') }}: {{ $generatedAt }}</p>
        <p>{{ __('This is an automatically generated statement') }}</p>
    </div>
</body>
</html>
