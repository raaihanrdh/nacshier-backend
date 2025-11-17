<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Profit - NaCshier</title>
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
            margin-bottom: 25px;
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
        }
        .summary-row:last-child .summary-cell {
            border-bottom: none;
            border-top: 2px solid #000000;
            margin-top: 8px;
            padding-top: 8px;
            font-weight: bold;
        }
        .summary-label {
            font-weight: 600;
            color: #333333;
            width: 50%;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
            color: #000000;
            width: 50%;
        }
        .summary-value.income {
            color: #006600;
        }
        .summary-value.expense {
            color: #cc0000;
        }
        .summary-value.profit {
            color: #006600;
        }
        .summary-value.profit.negative {
            color: #cc0000;
        }
        
        /* Section Titles */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #000000;
            padding-bottom: 5px;
        }
        
        /* Table Formal */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        .negative {
            color: #cc0000;
            font-weight: bold;
        }
        .neutral {
            color: #333333;
        }
        
        /* Rank Badge */
        .rank-badge {
            padding: 2px 6px;
            border: 1px solid #000000;
            border-radius: 2px;
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            background: #000000;
            color: white;
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
        
        /* Insights Section */
        .insights {
            margin-top: 25px;
            border: 1px solid #000000;
            padding: 12px;
            background: #f9f9f9;
        }
        .insights-title {
            font-size: 10pt;
            font-weight: bold;
            color: #000000;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #000000;
            padding-bottom: 5px;
        }
        .insights-item {
            margin: 6px 0;
            font-size: 8pt;
            color: #333333;
            padding-left: 15px;
            position: relative;
        }
        .insights-item::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #000000;
            font-weight: bold;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN LABA RUGI</h1>
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
            <div class="report-info-row">
                <span class="report-info-label">Detail Periode:</span>
                <span class="report-info-value">{{ $start_date }} s/d {{ $end_date }}</span>
            </div>
            <div class="report-info-row">
                <span class="report-info-label">Tanggal Cetak:</span>
                <span class="report-info-value">{{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</span>
            </div>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Ringkasan Profit</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell summary-label">Gross Income</div>
                <div class="summary-cell summary-value income">Rp {{ number_format($summary['gross_income'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">HPP (COGS)</div>
                <div class="summary-cell summary-value expense">Rp {{ number_format($summary['cogs'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Gross Profit</div>
                <div class="summary-cell summary-value profit {{ $summary['gross_profit'] < 0 ? 'negative' : '' }}">
                    {{ $summary['gross_profit'] >= 0 ? '+' : '' }}Rp {{ number_format($summary['gross_profit'], 0, ',', '.') }}
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Total Expenses</div>
                <div class="summary-cell summary-value expense">Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Net Profit</div>
                <div class="summary-cell summary-value profit {{ $summary['net_profit'] < 0 ? 'negative' : '' }}">
                    {{ $summary['net_profit'] >= 0 ? '+' : '' }}Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Gross Margin</div>
                <div class="summary-cell summary-value {{ $summary['gross_margin'] < 0 ? 'negative' : 'positive' }}">
                    {{ number_format($summary['gross_margin'], 2) }}%
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell summary-label">Net Margin</div>
                <div class="summary-cell summary-value {{ $summary['net_margin'] < 0 ? 'negative' : 'positive' }}">
                    {{ number_format($summary['net_margin'], 2) }}%
                </div>
            </div>
        </div>
    </div>

    @if(isset($top_products) && $top_products->count() > 0)
    <div class="section-title">Top 10 Produk Paling Menguntungkan</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">Rank</th>
                <th>Nama Produk</th>
                <th class="text-right" style="width: 70px;">Terjual</th>
                <th class="text-right" style="width: 110px;">Revenue</th>
                <th class="text-right" style="width: 110px;">COGS</th>
                <th class="text-right" style="width: 120px;">Gross Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($top_products as $index => $product)
            <tr>
                <td class="text-center"><span class="rank-badge">{{ $index + 1 }}</span></td>
                <td><strong>{{ $product->name }}</strong></td>
                <td class="text-right neutral">{{ number_format($product->total_sold, 0, ',', '.') }}</td>
                <td class="text-right positive">Rp {{ number_format($product->revenue, 0, ',', '.') }}</td>
                <td class="text-right negative">Rp {{ number_format($product->cogs, 0, ',', '.') }}</td>
                <td class="text-right positive"><strong>Rp {{ number_format($product->gross_profit, 0, ',', '.') }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($profit_by_category) && $profit_by_category->count() > 0)
    <div class="section-title">Profit Berdasarkan Kategori</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">No</th>
                <th>Kategori</th>
                <th class="text-right" style="width: 110px;">Revenue</th>
                <th class="text-right" style="width: 110px;">COGS</th>
                <th class="text-right" style="width: 120px;">Gross Profit</th>
                <th class="text-right" style="width: 70px;">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($profit_by_category as $index => $category)
            @php
                $categoryMargin = $category->revenue > 0 ? ($category->gross_profit / $category->revenue) * 100 : 0;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $category->category_name }}</strong></td>
                <td class="text-right positive">Rp {{ number_format($category->revenue, 0, ',', '.') }}</td>
                <td class="text-right negative">Rp {{ number_format($category->cogs, 0, ',', '.') }}</td>
                <td class="text-right positive"><strong>Rp {{ number_format($category->gross_profit, 0, ',', '.') }}</strong></td>
                <td class="text-right {{ $categoryMargin >= 0 ? 'positive' : 'negative' }}">
                    <strong>{{ number_format($categoryMargin, 2) }}%</strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="insights">
        <div class="insights-title">Insights & Analisis</div>
        <div class="insights-item">
            Gross Margin: {{ number_format($summary['gross_margin'], 2) }}% 
            @if($summary['gross_margin'] >= 30)
                (Sangat Baik - Margin tinggi menunjukkan pricing strategy yang efektif)
            @elseif($summary['gross_margin'] >= 20)
                (Baik - Margin dalam range yang sehat)
            @elseif($summary['gross_margin'] >= 10)
                (Cukup - Pertimbangkan untuk review pricing atau biaya produksi)
            @else
                (Perlu Perhatian - Margin rendah, perlu evaluasi biaya dan pricing)
            @endif
        </div>
        <div class="insights-item">
            Net Profit: Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}
            @if($summary['net_profit'] > 0)
                (Profitabilitas Positif - Bisnis dalam kondisi sehat)
            @else
                (Kerugian Terdeteksi - Perlu evaluasi operasional dan biaya)
            @endif
        </div>
        @if($summary['total_expenses'] > 0)
        @php
            $expenseRatio = $summary['gross_income'] > 0 ? ($summary['total_expenses'] / $summary['gross_income']) * 100 : 0;
        @endphp
        <div class="insights-item">
            Expense Ratio: {{ number_format($expenseRatio, 2) }}% dari Gross Income
            @if($expenseRatio > 50)
                (Tinggi - Pertimbangkan optimisasi biaya operasional)
            @elseif($expenseRatio > 30)
                (Sedang - Dalam range normal)
            @else
                (Rendah - Efisiensi biaya yang baik)
            @endif
        </div>
        @endif
    </div>

    <div class="footer">
        <div class="footer-company">NaCshier - Point of Sale System</div>
        <div class="footer-info">Laporan ini dibuat secara otomatis oleh sistem</div>
        <div class="footer-info" style="margin-top: 5px;">Halaman 1 | {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</div>
    </div>
</body>
</html>
