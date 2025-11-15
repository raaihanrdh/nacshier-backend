<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #10b981;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #059669;
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
            border-left: 3px solid #10b981;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .summary-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 10px;
        }
        .summary-value {
            font-weight: bold;
            font-size: 11px;
        }
        .summary-value.income {
            color: #059669;
        }
        .summary-value.expense {
            color: #dc2626;
        }
        .summary-value.net {
            color: #059669;
        }
        .summary-value.net.negative {
            color: #dc2626;
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
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
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
        .type-income {
            color: #059669;
            font-weight: bold;
        }
        .type-expense {
            color: #dc2626;
            font-weight: bold;
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
            color: #10b981;
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
        <h1>💰 LAPORAN ARUS KAS (CASHFLOW)</h1>
        <div class="header-info">
            @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                <div><strong>Periode:</strong> 
                    @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                        {{ \Carbon\Carbon::parse($filters['start_date'])->format('d F Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->format('d F Y') }}
                    @elseif(!empty($filters['start_date']))
                        Dari: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d F Y') }}
                    @elseif(!empty($filters['end_date']))
                        Sampai: {{ \Carbon\Carbon::parse($filters['end_date'])->format('d F Y') }}
                    @endif
                </div>
            @else
                <div><strong>Periode:</strong> Semua Data</div>
            @endif
            @if(!empty($filters['type']))
                <div><strong>Tipe:</strong> {{ $filters['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</div>
            @endif
            @if(!empty($filters['category']))
                <div><strong>Kategori:</strong> {{ $filters['category'] }}</div>
            @endif
            @if(!empty($filters['method']))
                <div><strong>Metode:</strong> {{ $filters['method'] }}</div>
            @endif
            @if(!empty($filters['search']))
                <div><strong>Pencarian:</strong> {{ $filters['search'] }}</div>
            @endif
            <div><strong>Dibuat pada:</strong> {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-title">📊 Ringkasan Arus Kas</div>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Pemasukan:</span>
                <span class="summary-value income">Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Pengeluaran:</span>
                <span class="summary-value expense">Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item" style="grid-column: 1 / -1;">
                <span class="summary-label">Net Cashflow:</span>
                <span class="summary-value net {{ $summary['net_cashflow'] < 0 ? 'negative' : '' }}">Rp {{ number_format($summary['net_cashflow'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if(count($cashflows) > 0)
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Metode</th>
                <th class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cashflows as $index => $cashflow)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($cashflow->date)->format('d/m/Y') }}</td>
                <td>
                    <span class="{{ $cashflow->type === 'income' ? 'type-income' : 'type-expense' }}">
                        {{ $cashflow->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                    </span>
                </td>
                <td>{{ $cashflow->category }}</td>
                <td>{{ $cashflow->description }}</td>
                <td>{{ $cashflow->method }}</td>
                <td class="text-right">
                    <strong class="{{ $cashflow->type === 'income' ? 'type-income' : 'type-expense' }}">
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
        <div class="footer-logo">NaCshier - Point of Sale System</div>
        <div>Laporan ini dibuat secara otomatis oleh sistem</div>
        <div>Halaman 1</div>
    </div>
</body>
</html>

