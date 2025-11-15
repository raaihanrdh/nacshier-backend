<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 25px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-info {
            margin-top: 12px;
            font-size: 10px;
            opacity: 0.95;
            line-height: 1.8;
        }
        .header-info div {
            margin: 3px 0;
        }
        .summary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 12px;
            background: white;
            border-radius: 6px;
            border-left: 3px solid #2563eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .summary-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 10px;
        }
        .summary-value {
            font-weight: bold;
            color: #1e40af;
            font-size: 11px;
        }
        .summary-value.positive {
            color: #059669;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 6px;
            overflow: hidden;
        }
        th {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        th.text-right {
            text-align: right;
        }
        th.text-center {
            text-align: center;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
            color: #374151;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #f3f4f6;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            border-top: 2px solid #e5e7eb;
        }
        .footer-logo {
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 LAPORAN TRANSAKSI</h1>
        <div class="header-info">
            <div><strong>Periode:</strong> {{ ucfirst($period) }}</div>
            <div><strong>Tanggal:</strong> {{ $date_range }}</div>
            @if(!empty($filters['product']) || !empty($filters['cashier']))
                <div><strong>Filter:</strong> 
                    @if(!empty($filters['product'])) Produk: {{ $filters['product'] }} @endif
                    @if(!empty($filters['cashier'])) Kasir: {{ $filters['cashier'] }} @endif
                </div>
            @endif
            <div><strong>Dibuat pada:</strong> {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-title">📈 Ringkasan Transaksi</div>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Pendapatan:</span>
                <span class="summary-value positive">Rp {{ number_format($summary['pendapatan'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Income QRIS:</span>
                <span class="summary-value positive">Rp {{ number_format($summary['incomeQRIS'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Income Cash:</span>
                <span class="summary-value positive">Rp {{ number_format($summary['incomeCash'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Income Transfer:</span>
                <span class="summary-value positive">Rp {{ number_format($summary['incomeTransfer'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Transaksi:</span>
                <span class="summary-value">{{ number_format($summary['totalTransaksi'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Barang:</span>
                <span class="summary-value">{{ number_format($summary['totalBarang'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if(count($transactions) > 0)
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>ID Transaksi</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Kasir</th>
                <th>Metode Pembayaran</th>
                <th class="text-right">Total (Rp)</th>
                <th class="text-center">Jumlah Item</th>
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
                <td class="text-right"><strong>Rp {{ number_format($transaction['total'], 0, ',', '.') }}</strong></td>
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
        <div class="footer-logo">NaCshier - Point of Sale System</div>
        <div>Laporan ini dibuat secara otomatis oleh sistem</div>
        <div>Halaman 1</div>
    </div>
</body>
</html>
