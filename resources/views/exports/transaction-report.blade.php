<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi - NaCshier</title>
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
            width: 140px;
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
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 4px 0;
            border-bottom: 1px solid #e5e5e5;
            width: 50%;
        }
        .summary-row:last-child .summary-cell {
            border-bottom: none;
        }
        .summary-label {
            font-weight: 600;
            color: #333333;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
            color: #000000;
        }
        .summary-value.positive {
            color: #006600;
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
        .positive {
            color: #006600;
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
        <h1>LAPORAN TRANSAKSI</h1>
        <div class="company-name">NaCshier - Point of Sale System</div>
        <div class="report-info">
            <div class="report-info-row">
                <span class="report-info-label">Periode Laporan:</span>
                <span class="report-info-value">{{ ucfirst($period) }}</span>
            </div>
            <div class="report-info-row">
                <span class="report-info-label">Rentang Tanggal:</span>
                <span class="report-info-value">{{ $date_range }}</span>
            </div>
            @if(!empty($filters['product']))
                <div class="report-info-row">
                    <span class="report-info-label">Filter Produk:</span>
                    <span class="report-info-value">{{ $filters['product'] }}</span>
                </div>
            @endif
            @if(!empty($filters['cashier']))
                <div class="report-info-row">
                    <span class="report-info-label">Filter Kasir:</span>
                    <span class="report-info-value">{{ $filters['cashier'] }}</span>
                </div>
            @endif
            <div class="report-info-row">
                <span class="report-info-label">Tanggal Cetak:</span>
                <span class="report-info-value">{{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</span>
            </div>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Ringkasan Transaksi</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell summary-label">Total Pendapatan</div>
                <div class="summary-cell summary-value positive">Rp {{ number_format($summary['pendapatan'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Income QRIS</div>
                <div class="summary-cell summary-value positive">Rp {{ number_format($summary['incomeQRIS'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Income Cash</div>
                <div class="summary-cell summary-value positive">Rp {{ number_format($summary['incomeCash'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Income Transfer</div>
                <div class="summary-cell summary-value positive">Rp {{ number_format($summary['incomeTransfer'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Total Transaksi</div>
                <div class="summary-cell summary-value">{{ number_format($summary['totalTransaksi'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Total Barang</div>
                <div class="summary-cell summary-value">{{ number_format($summary['totalBarang'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @if(count($transactions) > 0)
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 120px;">ID Transaksi</th>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 70px;">Waktu</th>
                <th>Kasir</th>
                <th style="width: 100px;">Metode Pembayaran</th>
                <th class="text-right" style="width: 120px;">Total (Rp)</th>
                <th class="text-center" style="width: 80px;">Jumlah Item</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $transaction)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $transaction['transaction_id'] }}</strong></td>
                <td>{{ $transaction['tanggal'] }}</td>
                <td>{{ $transaction['jam'] }}</td>
                <td>{{ $transaction['kasir'] }}</td>
                <td>{{ $transaction['metodePembayaran'] }}</td>
                <td class="text-right positive"><strong>Rp {{ number_format($transaction['total'], 0, ',', '.') }}</strong></td>
                <td class="text-center">{{ $transaction['item_count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <p>Tidak ada data transaksi untuk periode yang dipilih.</p>
    </div>
    @endif

    <div class="footer">
        <div class="footer-company">NaCshier - Point of Sale System</div>
        <div class="footer-info">Laporan ini dibuat secara otomatis oleh sistem</div>
        <div class="footer-info" style="margin-top: 5px;">Halaman 1 | {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</div>
    </div>
</body>
</html>
