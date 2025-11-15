<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionExport;

class TransactionExportController extends Controller
{
    public function exportPDF(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'period' => 'required|in:daily,weekly,monthly',
                'date' => 'required_if:period,daily|date_format:Y-m-d',
                'start_date' => 'required_if:period,weekly,monthly|date_format:Y-m-d',
                'end_date' => 'required_if:period,weekly,monthly|date_format:Y-m-d',
                'product_filter' => 'nullable|string',
                'cashier_filter' => 'nullable|string',
            ]);

            // Ambil data transaksi
            $data = $this->getFilteredTransactions($request);
            
            // Generate PDF
            $pdf = Pdf::loadView('exports.transaction-report', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Nama file
            $filename = $this->generateFilename($request, 'pdf');
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'period' => 'required|in:daily,weekly,monthly',
                'date' => 'required_if:period,daily|date_format:Y-m-d',
                'start_date' => 'required_if:period,weekly,monthly|date_format:Y-m-d',
                'end_date' => 'required_if:period,weekly,monthly|date_format:Y-m-d',
                'product_filter' => 'nullable|string',
                'cashier_filter' => 'nullable|string',
            ]);

            // Nama file
            $filename = $this->generateFilename($request, 'xlsx');
            
            return Excel::download(new TransactionExport($request), $filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getFilteredTransactions(Request $request)
    {
        $query = Transaction::with(['items.product']);

        // Filter berdasarkan periode
        if ($request->period === 'daily' && $request->filled('date')) {
            $date = Carbon::parse($request->date);
            $query->whereBetween('transaction_time', [
                $date->startOfDay(),
                $date->endOfDay()
            ]);
        } elseif (($request->period === 'weekly' || $request->period === 'monthly') && 
                  $request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_time', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        $transactions = $query->orderBy('transaction_time', 'desc')->get();

        // Format data untuk frontend
        $formattedTransactions = $transactions->map(function($transaction) {
            $transactionTime = $transaction->transaction_time;
            $tanggal = $transactionTime ? $transactionTime->format('Y-m-d') : '';
            $jam = $transactionTime ? $transactionTime->format('H:i:s') : '';

            return [
                'transaction_id' => $transaction->transaction_id,
                'shift_id' => $transaction->shift_id,
                'tanggal' => $tanggal,
                'jam' => $jam,
                'kasir' => 'Kasir #' . $transaction->shift_id,
                'metodePembayaran' => $transaction->payment_method,
                'total' => $transaction->total_amount,
                'item_count' => $transaction->items->sum('quantity'),
                'barang' => $transaction->items->map(function($item) {
                    return [
                        'nama' => $item->product->name,
                        'harga' => $item->selling_price,
                        'jumlah' => $item->quantity,
                    ];
                })->toArray(),
            ];
        });

        // Filter berdasarkan produk jika ada
        if ($request->filled('product_filter')) {
            $formattedTransactions = $formattedTransactions->filter(function($trx) use ($request) {
                return collect($trx['barang'])->some(function($item) use ($request) {
                    return stripos($item['nama'], $request->product_filter) !== false;
                });
            });
        }

        // Filter berdasarkan kasir jika ada
        if ($request->filled('cashier_filter')) {
            $formattedTransactions = $formattedTransactions->filter(function($trx) use ($request) {
                return stripos($trx['kasir'], $request->cashier_filter) !== false;
            });
        }

        // Hitung summary
        $totalPendapatan = $formattedTransactions->sum('total');
        $incomeQRIS = $formattedTransactions->where('metodePembayaran', 'Qris')->sum('total');
        $incomeCash = $formattedTransactions->where('metodePembayaran', 'Cash')->sum('total');
        $incomeTransfer = $formattedTransactions->where('metodePembayaran', 'Transfer')->sum('total');
        $totalTransaksi = $formattedTransactions->count();
        $totalBarang = $formattedTransactions->sum('item_count');

        return [
            'transactions' => $formattedTransactions->values()->all(),
            'summary' => [
                'pendapatan' => $totalPendapatan,
                'incomeQRIS' => $incomeQRIS,
                'incomeCash' => $incomeCash,
                'incomeTransfer' => $incomeTransfer,
                'totalTransaksi' => $totalTransaksi,
                'totalBarang' => $totalBarang,
            ],
            'period' => $request->period,
            'date_range' => $this->getDateRangeText($request),
            'filters' => [
                'product' => $request->product_filter,
                'cashier' => $request->cashier_filter,
            ]
        ];
    }

    private function generateFilename(Request $request, string $extension): string
    {
        $periodText = [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan'
        ][$request->period] ?? 'Laporan';

        $dateText = '';
        if ($request->period === 'daily' && $request->filled('date')) {
            $dateText = Carbon::parse($request->date)->format('d-m-Y');
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->format('d-m-Y');
            $end = Carbon::parse($request->end_date)->format('d-m-Y');
            $dateText = $start . '_to_' . $end;
        }

        return "Laporan_Transaksi_{$periodText}_{$dateText}.{$extension}";
    }

    private function getDateRangeText(Request $request): string
    {
        if ($request->period === 'daily' && $request->filled('date')) {
            return Carbon::parse($request->date)->format('d F Y');
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->format('d F Y');
            $end = Carbon::parse($request->end_date)->format('d F Y');
            return $start . ' - ' . $end;
        }
        return '';
    }
}