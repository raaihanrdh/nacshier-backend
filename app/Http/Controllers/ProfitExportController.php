<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Traits\ApiResponse;
use App\Exports\ProfitExport;

class ProfitExportController extends Controller
{
    use ApiResponse;

    public function exportPDF(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'period' => 'required|in:daily,weekly,monthly,yearly,custom',
                'date' => 'required_if:period,daily|date_format:Y-m-d',
                'start_date' => 'required_if:period,custom|date_format:Y-m-d',
                'end_date' => 'required_if:period,custom|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $period = $request->input('period');
            $startDate = null;
            $endDate = null;

            // Set date range berdasarkan period
            if ($period === 'daily' && $request->filled('date')) {
                $date = Carbon::parse($request->date);
                $startDate = $date->startOfDay();
                $endDate = $date->endOfDay();
            } elseif ($period === 'weekly') {
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
            } elseif ($period === 'monthly') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            } elseif ($period === 'yearly') {
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
            } elseif ($period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
            } else {
                // Default to current month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }

            // Hitung profit data
            $data = $this->calculateProfitData($startDate, $endDate, $period);

            // Generate PDF
            $pdf = Pdf::loadView('exports.profit-report', $data);
            $pdf->setPaper('A4', 'portrait');

            // Nama file
            $filename = $this->generateFilename($period, $startDate, $endDate);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor PDF: ' . $e->getMessage(), 500);
        }
    }

    private function calculateProfitData($startDate, $endDate, $period)
    {
        // Hitung total pendapatan dari transaksi
        $grossIncome = Transaction::whereBetween('transaction_time', [$startDate, $endDate])
            ->sum('total_amount');

        // Hitung total HPP (Harga Pokok Penjualan)
        $cogs = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->whereBetween('transactions.transaction_time', [$startDate, $endDate])
            ->selectRaw('SUM(transaction_items.quantity * products.capital_price) as total_cogs')
            ->value('total_cogs') ?? 0;

        // Hitung gross profit (laba kotor)
        $grossProfit = $grossIncome - $cogs;

        // Hitung total biaya (expenses)
        $totalExpenses = Cashflow::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Hitung net profit (laba bersih)
        $netProfit = $grossProfit - $totalExpenses;

        // Hitung margin
        $grossMargin = $grossIncome > 0 ? ($grossProfit / $grossIncome) * 100 : 0;
        $netMargin = $grossIncome > 0 ? ($netProfit / $grossIncome) * 100 : 0;

        // Top profitable products
        $topProducts = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->whereBetween('transactions.transaction_time', [$startDate, $endDate])
            ->select(
                'products.name',
                DB::raw('SUM(transaction_items.quantity) as total_sold'),
                DB::raw('SUM(transaction_items.quantity * transaction_items.selling_price) as revenue'),
                DB::raw('SUM(transaction_items.quantity * products.capital_price) as cogs'),
                DB::raw('SUM(transaction_items.quantity * (transaction_items.selling_price - products.capital_price)) as gross_profit')
            )
            ->groupBy('products.name')
            ->orderByDesc('gross_profit')
            ->limit(10)
            ->get();

        // Profit by category
        $profitByCategory = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->join('categories', 'products.category_id', '=', 'categories.category_id')
            ->whereBetween('transactions.transaction_time', [$startDate, $endDate])
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity * transaction_items.selling_price) as revenue'),
                DB::raw('SUM(transaction_items.quantity * products.capital_price) as cogs'),
                DB::raw('SUM(transaction_items.quantity * (transaction_items.selling_price - products.capital_price)) as gross_profit')
            )
            ->groupBy('categories.name')
            ->get();

        return [
            'period' => $period,
            'start_date' => $startDate->format('d F Y'),
            'end_date' => $endDate->format('d F Y'),
            'date_range' => $this->getDateRangeText($period, $startDate, $endDate),
            'summary' => [
                'gross_income' => $grossIncome,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'gross_margin' => round($grossMargin, 2),
                'net_margin' => round($netMargin, 2),
            ],
            'top_products' => $topProducts,
            'profit_by_category' => $profitByCategory,
        ];
    }

    private function getDateRangeText($period, $startDate, $endDate)
    {
        switch ($period) {
            case 'daily':
                return $startDate->format('d F Y');
            case 'weekly':
                return $startDate->format('d F') . ' - ' . $endDate->format('d F Y');
            case 'monthly':
                return $startDate->format('F Y');
            case 'yearly':
                return $startDate->format('Y');
            default:
                return $startDate->format('d F Y') . ' - ' . $endDate->format('d F Y');
        }
    }

    private function generateFilename($period, $startDate, $endDate)
    {
        $periodText = [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
            'custom' => 'Custom'
        ][$period] ?? 'Laporan';

        $dateText = '';
        if ($period === 'daily') {
            $dateText = $startDate->format('d-m-Y');
        } elseif ($period === 'custom') {
            $dateText = $startDate->format('d-m-Y') . '_to_' . $endDate->format('d-m-Y');
        } elseif ($period === 'monthly') {
            $dateText = $startDate->format('m-Y');
        } elseif ($period === 'yearly') {
            $dateText = $startDate->format('Y');
        } else {
            $dateText = $startDate->format('d-m-Y') . '_to_' . $endDate->format('d-m-Y');
        }

        return "Laporan_Profit_{$periodText}_{$dateText}.pdf";
    }

    public function exportExcel(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'period' => 'required|in:daily,weekly,monthly,yearly,custom',
                'date' => 'required_if:period,daily|date_format:Y-m-d',
                'start_date' => 'required_if:period,custom|date_format:Y-m-d',
                'end_date' => 'required_if:period,custom|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $period = $request->input('period');
            $startDate = null;
            $endDate = null;

            // Set date range berdasarkan period
            if ($period === 'daily' && $request->filled('date')) {
                $date = Carbon::parse($request->date);
                $startDate = $date->startOfDay();
                $endDate = $date->endOfDay();
            } elseif ($period === 'weekly') {
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
            } elseif ($period === 'monthly') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            } elseif ($period === 'yearly') {
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
            } elseif ($period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
            } else {
                // Default to current month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }

            // Nama file
            $filename = $this->generateFilenameExcel($period, $startDate, $endDate);
            
            return Excel::download(new ProfitExport($request, $startDate, $endDate, $period), $filename);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor Excel: ' . $e->getMessage(), 500);
        }
    }

    private function generateFilenameExcel($period, $startDate, $endDate)
    {
        $periodText = [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
            'custom' => 'Custom'
        ][$period] ?? 'Laporan';

        $dateText = '';
        if ($period === 'daily') {
            $dateText = $startDate->format('d-m-Y');
        } elseif ($period === 'custom') {
            $dateText = $startDate->format('d-m-Y') . '_to_' . $endDate->format('d-m-Y');
        } elseif ($period === 'monthly') {
            $dateText = $startDate->format('m-Y');
        } elseif ($period === 'yearly') {
            $dateText = $startDate->format('Y');
        } else {
            $dateText = $startDate->format('d-m-Y') . '_to_' . $endDate->format('d-m-Y');
        }

        return "Laporan_Profit_{$periodText}_{$dateText}.xlsx";
    }
}

