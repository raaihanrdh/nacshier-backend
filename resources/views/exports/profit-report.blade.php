<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Profit</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header-info {
            margin-top: 5px;
            font-size: 9px;
        }
        .summary {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-label {
            font-weight: bold;
        }
        .summary-value {
            font-weight: bold;
            color: #2563eb;
        }
        .summary-value.negative {
            color: #dc2626;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #4a5568;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2d3748;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
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
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PROFIT</h1>
        <div class="header-info">
            <div>Periode: {{ ucfirst($period) }}</div>
            <div>Tanggal: {{ $date_range }}</div>
            <div>Dibuat pada: {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="section-title">RINGKASAN PROFIT</div>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Gross Income:</span>
                <span class="summary-value">Rp {{ number_format($summary['gross_income'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">HPP (COGS):</span>
                <span class="summary-value">Rp {{ number_format($summary['cogs'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Gross Profit:</span>
                <span class="summary-value {{ $summary['gross_profit'] < 0 ? 'negative' : '' }}">Rp {{ number_format($summary['gross_profit'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Expenses:</span>
                <span class="summary-value">Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Net Profit:</span>
                <span class="summary-value {{ $summary['net_profit'] < 0 ? 'negative' : '' }}">Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Gross Margin:</span>
                <span class="summary-value">{{ number_format($summary['gross_margin'], 2) }}%</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Net Margin:</span>
                <span class="summary-value {{ $summary['net_margin'] < 0 ? 'negative' : '' }}">{{ number_format($summary['net_margin'], 2) }}%</span>
            </div>
        </div>
    </div>

    @if(isset($top_products) && $top_products->count() > 0)
    <div class="section-title">TOP 10 PRODUK PALING MENGUNTUNGKAN</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th class="text-right">Terjual</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">COGS</th>
                <th class="text-right">Gross Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($top_products as $index => $product)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $product->name }}</td>
                <td class="text-right">{{ number_format($product->total_sold, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($product->revenue, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($product->cogs, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($product->gross_profit, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($profit_by_category) && $profit_by_category->count() > 0)
    <div class="section-title" style="margin-top: 20px;">PROFIT BERDASARKAN KATEGORI</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kategori</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">COGS</th>
                <th class="text-right">Gross Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($profit_by_category as $index => $category)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $category->category_name }}</td>
                <td class="text-right">Rp {{ number_format($category->revenue, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($category->cogs, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($category->gross_profit, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <div>Laporan ini dibuat secara otomatis oleh sistem NaCshier</div>
        <div>Halaman 1</div>
    </div>
</body>
</html>

