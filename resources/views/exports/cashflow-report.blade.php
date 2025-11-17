<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas - NaCshier</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #000000;
            line-height: 1.4;
            background: #ffffff;
        }
        
        /* Header Formal */
        .header {
            border-bottom: 3px solid #000000;
            padding: 15px 0;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000000;
            text-align: center;
        }
        .company-name {
            font-size: 12pt;
            text-align: center;
            margin-top: 5px;
            color: #333333;
        }
        .report-info {
            margin-top: 20px;
            padding: 10px 0;
            border-top: 1px solid #cccccc;
            border-bottom: 1px solid #cccccc;
            font-size: 8pt;
            color: #333333;
        }
        .report-info-row {
            display: table;
            width: 100%;
            margin: 3px 0;
        }
        .report-info-label {
            display: table-cell;
            width: 120px;
            font-weight: bold;
            color: #000000;
        }
        .report-info-value {
            display: table-cell;
            color: #333333;
        }
        
        /* Summary Formal */
        .summary-section {
            margin-bottom: 20px;
            border: 1px solid #000000;
            padding: 12px;
            background: #f9f9f9;
        }
        .summary-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
            border-bottom: 2px solid #000000;
            padding-bottom: 5px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin: 6px 0;
            padding: 4px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        .summary-row:last-child {
            border-bottom: none;
            border-top: 2px solid #000000;
            margin-top: 8px;
            padding-top: 8px;
            font-weight: bold;
        }
        .summary-label {
            display: table-cell;
            width: 50%;
            font-weight: 600;
            color: #333333;
        }
        .summary-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            color: #000000;
        }
        .summary-value.income {
            color: #006600;
        }
        .summary-value.expense {
            color: #cc0000;
        }
        
        /* Table Formal */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 20px;
            background: white;
            border: 2px solid #000000;
        }
        th {
            background: #000000;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #000000;
        }
        th.text-right {
            text-align: right;
        }
        th.text-center {
            text-align: center;
        }
        td {
            padding: 6px;
            border: 1px solid #000000;
            font-size: 8pt;
            color: #000000;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .type-badge {
            padding: 2px 6px;
            border: 1px solid #000000;
            border-radius: 2px;
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            background: #f9f9f9;
        }
        .type-income {
            background: #e6f3e6;
            border-color: #006600;
            color: #006600;
        }
        .type-expense {
            background: #ffe6e6;
            border-color: #cc0000;
            color: #cc0000;
        }
        .amount-income {
            color: #006600;
            font-weight: bold;
        }
        .amount-expense {
            color: #cc0000;
            font-weight: bold;
        }
        
        /* Footer Formal */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #000000;
            text-align: center;
            font-size: 7pt;
            color: #666666;
        }
        .footer-company {
            font-weight: bold;
            color: #000000;
            margin-bottom: 5px;
        }
        .footer-info {
            margin-top: 3px;
            color: #666666;
        }
        
        /* No Data */
        .no-data {
            text-align: center;
            padding: 40px;
            border: 1px solid #cccccc;
            background: #f9f9f9;
            margin: 20px 0;
            font-style: italic;
            color: #666666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN ARUS KAS</h1>
        <div class="company-name">NaCshier - Point of Sale System</div>
        <div class="report-info">
            @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                <div class="report-info-row">
                    <span class="report-info-label">Periode Laporan:</span>
                    <span class="report-info-value">
                        @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                            {{ \Carbon\Carbon::parse($filters['start_date'])->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($filters['end_date'])->format('d F Y') }}
                        @elseif(!empty($filters['start_date']))
                            Mulai: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d F Y') }}
                        @elseif(!empty($filters['end_date']))
                            Sampai: {{ \Carbon\Carbon::parse($filters['end_date'])->format('d F Y') }}
                        @endif
                    </span>
                </div>
            @else
                <div class="report-info-row">
                    <span class="report-info-label">Periode Laporan:</span>
                    <span class="report-info-value">Semua Data</span>
                </div>
            @endif
            @if(!empty($filters['type']))
                <div class="report-info-row">
                    <span class="report-info-label">Tipe Transaksi:</span>
                    <span class="report-info-value">{{ $filters['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</span>
                </div>
            @endif
            @if(!empty($filters['category']))
                <div class="report-info-row">
                    <span class="report-info-label">Kategori:</span>
                    <span class="report-info-value">{{ $filters['category'] }}</span>
                </div>
            @endif
            @if(!empty($filters['method']))
                <div class="report-info-row">
                    <span class="report-info-label">Metode Pembayaran:</span>
                    <span class="report-info-value">{{ $filters['method'] }}</span>
                </div>
            @endif
            @if(!empty($filters['search']))
                <div class="report-info-row">
                    <span class="report-info-label">Kata Kunci:</span>
                    <span class="report-info-value">{{ $filters['search'] }}</span>
                </div>
            @endif
            <div class="report-info-row">
                <span class="report-info-label">Tanggal Cetak:</span>
                <span class="report-info-value">{{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</span>
            </div>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Ringkasan Arus Kas</div>
        <div class="summary-row">
            <span class="summary-label">Total Pemasukan</span>
            <span class="summary-value income">Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Pengeluaran</span>
            <span class="summary-value expense">Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Net Cashflow</span>
            <span class="summary-value {{ $summary['net_cashflow'] < 0 ? 'expense' : 'income' }}">
                {{ $summary['net_cashflow'] >= 0 ? '+' : '' }}Rp {{ number_format($summary['net_cashflow'], 0, ',', '.') }}
            </span>
        </div>
    </div>

    @if(count($cashflows) > 0)
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 90px;">Tipe</th>
                <th style="width: 100px;">Kategori</th>
                <th>Deskripsi</th>
                <th style="width: 90px;">Metode</th>
                <th class="text-right" style="width: 120px;">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cashflows as $index => $cashflow)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($cashflow->date)->format('d/m/Y') }}</td>
                <td>
                    <span class="type-badge {{ $cashflow->type === 'income' ? 'type-income' : 'type-expense' }}">
                        {{ $cashflow->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                    </span>
                </td>
                <td>{{ $cashflow->category }}</td>
                <td>{{ $cashflow->description }}</td>
                <td>{{ $cashflow->method }}</td>
                <td class="text-right">
                    <strong class="{{ $cashflow->type === 'income' ? 'amount-income' : 'amount-expense' }}">
                        {{ $cashflow->type === 'income' ? '+' : '-' }}Rp {{ number_format($cashflow->amount, 0, ',', '.') }}
                    </strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <p>Tidak ada data arus kas untuk periode yang dipilih.</p>
    </div>
    @endif

    <div class="footer">
        <div class="footer-company">NaCshier - Point of Sale System</div>
        <div class="footer-info">Laporan ini dibuat secara otomatis oleh sistem</div>
        <div class="footer-info" style="margin-top: 5px;">Halaman 1 | {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</div>
    </div>
</body>
</html>
