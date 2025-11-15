<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { margin-top: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .footer { margin-top: 30px; text-align: right; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Transaksi</h1>
        <p>Periode: {{ $startDate }} s/d {{ $endDate }}</p>
        <p>Dibuat pada: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="info">
        <p>Total Transaksi: {{ $totalTransactions }}</p>
        <p>Total Barang Terjual: {{ $totalItems }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Transaksi</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Metode Bayar</th>
                <th>Total</th>
                <th>Jumlah Barang</th>
                <th>Detail Barang</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->transaction_id }}</td>
                <td>{{ $transaction->transaction_time->format('Y-m-d') }}</td>
                <td>{{ $transaction->transaction_time->format('H:i:s') }}</td>
                <td>{{ $transaction->payment_method }}</td>
                <td style="text-align: right;">{{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                <td style="text-align: center;">{{ $transaction->items->sum('quantity') }}</td>
                <td>
                    @foreach($transaction->items as $item)
                    {{ $item->product->name }} ({{ $item->quantity }} x {{ number_format($item->selling_price, 0, ',', '.') }})<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Ringkasan Pendapatan</h3>
        <p>Total Pendapatan: {{ number_format($totalIncome, 0, ',', '.') }}</p>
        <p>Pendapatan Cash: {{ number_format($cashIncome, 0, ',', '.') }}</p>
        <p>Pendapatan QRIS: {{ number_format($qrisIncome, 0, ',', '.') }}</p>
        <p>Pendapatan Transfer: {{ number_format($transferIncome, 0, ',', '.') }}</p>
    </div>

    <div class="footer">
        Dicetak oleh: {{ auth()->user()->name ?? 'System' }}
    </div>
</body>
</html>