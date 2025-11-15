<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Cashflow;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfitController extends Controller
{
    /**
     * Menghitung profit dalam periode tertentu
     */
    public function calculateProfit(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:daily,weekly,monthly,yearly,custom'
        ]);

        $period = $request->input('period', 'monthly');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Set default period if not custom
        if ($period !== 'custom') {
            list($startDate, $endDate) = $this->getDateRange($period);
        }

        // Hitung total pendapatan dari transaksi
        $grossIncome = Transaction::whereBetween('transaction_time', [$startDate, $endDate])
            ->sum('total_amount');

        // Hitung total HPP (Harga Pokok Penjualan)
        $cogs = $this->calculateCOGS($startDate, $endDate);

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

        // Breakdown by product category
        $profitByCategory = $this->getProfitByCategory($startDate, $endDate);

        // Profit trend
        $profitTrend = $this->getProfitTrend($startDate, $endDate);

        // Top profitable products
        $topProducts = $this->getTopProfitableProducts($startDate, $endDate, 5);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => $this->getPeriodLabel($period, $startDate, $endDate)
                ],
                'summary' => [
                    'gross_income' => $grossIncome,
                    'cogs' => $cogs,
                    'gross_profit' => $grossProfit,
                    'total_expenses' => $totalExpenses,
                    'net_profit' => $netProfit,
                    'gross_margin' => round($grossMargin, 2),
                    'net_margin' => round($netMargin, 2),
                ],
                'by_category' => $profitByCategory,
                'profit_trend' => $profitTrend,
                'top_products' => $topProducts,
            ]
        ]);
    }

    /**
     * Menghitung Harga Pokok Penjualan (COGS)
     */
    private function calculateCOGS($startDate, $endDate)
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->whereBetween('transactions.transaction_time', [$startDate, $endDate])
            ->selectRaw('SUM(transaction_items.quantity * products.capital_price) as total_cogs')
            ->value('total_cogs') ?? 0;
    }

    /**
     * Mendapatkan profit berdasarkan kategori
     */
    private function getProfitByCategory($startDate, $endDate)
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->join('categories', 'products.category_id', '=', 'categories.category_id')
            ->whereBetween('transactions.transaction_time', [$startDate, $endDate])
            ->select(
                'categories.category_id',
                'categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity * transaction_items.selling_price) as revenue'),
                DB::raw('SUM(transaction_items.quantity * products.capital_price) as cogs'),
                DB::raw('SUM(transaction_items.quantity * (transaction_items.selling_price - products.capital_price)) as gross_profit')
            )
            ->groupBy('categories.category_id', 'categories.name')
            ->get();
    }

    /**
     * Mendapatkan trend profit harian
     */
    private function getProfitTrend($startDate, $endDate)
    {
        $trendData = [];
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate <= $endDate) {
            $date = $currentDate->toDateString();

            $dailyData = DB::table('transactions')
                ->leftJoin('transaction_items', 'transactions.transaction_id', '=', 'transaction_items.transaction_id')
                ->leftJoin('products', 'transaction_items.product_id', '=', 'products.product_id')
                ->whereDate('transactions.transaction_time', $date)
                ->select(
                    DB::raw('COALESCE(SUM(transactions.total_amount), 0) as revenue'),
                    DB::raw('COALESCE(SUM(transaction_items.quantity * products.capital_price), 0) as cogs'),
                    DB::raw('COALESCE(SUM(transactions.total_amount - (transaction_items.quantity * products.capital_price)), 0) as gross_profit')
                )
                ->first();

            $expenses = Cashflow::where('type', 'expense')
                ->whereDate('date', $date)
                ->sum('amount');

            $trendData[] = [
                'date' => $date,
                'revenue' => $dailyData->revenue,
                'cogs' => $dailyData->cogs,
                'gross_profit' => $dailyData->gross_profit,
                'expenses' => $expenses,
                'net_profit' => $dailyData->gross_profit - $expenses
            ];

            $currentDate->addDay();
        }

        return $trendData;
    }

    /**
     * Mendapatkan produk paling menguntungkan
     */
    private function getTopProfitableProducts($startDate, $endDate, $limit = 5)
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->whereBetween('transactions.transaction_time', [$startDate, $endDate])
            ->select(
                'products.product_id',
                'products.name',
                'products.category_id',
                DB::raw('SUM(transaction_items.quantity) as total_sold'),
                DB::raw('SUM(transaction_items.quantity * transaction_items.selling_price) as revenue'),
                DB::raw('SUM(transaction_items.quantity * products.capital_price) as cogs'),
                DB::raw('SUM(transaction_items.quantity * (transaction_items.selling_price - products.capital_price)) as gross_profit'),
                DB::raw('ROUND(AVG((transaction_items.selling_price - products.capital_price) / transaction_items.selling_price * 100), 2) as margin_percentage')
            )
            ->groupBy('products.product_id', 'products.name', 'products.category_id')
            ->orderByDesc('gross_profit')
            ->limit($limit)
            ->get();
    }

    /**
     * Mendapatkan rentang tanggal berdasarkan periode
     */
    private function getDateRange($period)
    {
        $today = Carbon::now();

        switch ($period) {
            case 'daily':
                return [$today->toDateString(), $today->toDateString()];
            case 'weekly':
                return [$today->startOfWeek()->toDateString(), $today->endOfWeek()->toDateString()];
            case 'monthly':
                return [$today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString()];
            case 'yearly':
                return [$today->startOfYear()->toDateString(), $today->endOfYear()->toDateString()];
            default:
                return [$today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString()];
        }
    }

    /**
     * Mendapatkan label periode
     */
    private function getPeriodLabel($period, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        switch ($period) {
            case 'daily':
                return $start->format('d M Y');
            case 'weekly':
                return $start->format('d M') . ' - ' . $end->format('d M Y');
            case 'monthly':
                return $start->format('M Y');
            case 'yearly':
                return $start->format('Y');
            default:
                return $start->format('d M Y') . ' - ' . $end->format('d M Y');
        }
    }

    /**
     * Mendapatkan profit tahunan untuk grafik
     */
    public function yearlyProfitChart()
    {
        $currentYear = Carbon::now()->year;
        $profitData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($currentYear, $month, 1)->startOfMonth();
            $endDate = Carbon::create($currentYear, $month, 1)->endOfMonth();

            $grossIncome = Transaction::whereBetween('transaction_time', [$startDate, $endDate])
                ->sum('total_amount');

            $cogs = $this->calculateCOGS($startDate, $endDate);
            $grossProfit = $grossIncome - $cogs;

            $expenses = Cashflow::where('type', 'expense')
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $netProfit = $grossProfit - $expenses;

            $profitData[] = [
                'month' => $startDate->format('M'),
                'gross_income' => $grossIncome,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $profitData
        ]);
    }

    /**
     * Mendapatkan perbandingan profit dengan periode sebelumnya
     */
    public function profitComparison()
    {
        $currentPeriod = Carbon::now();
        $previousPeriod = Carbon::now()->subMonth();

        // Hitung untuk periode saat ini
        $current = $this->calculatePeriodProfit($currentPeriod);

        // Hitung untuk periode sebelumnya
        $previous = $this->calculatePeriodProfit($previousPeriod);

        // Hitung perubahan
        $revenueChange = $this->calculateChange($current['revenue'], $previous['revenue']);
        $profitChange = $this->calculateChange($current['net_profit'], $previous['net_profit']);

        return response()->json([
            'success' => true,
            'data' => [
                'current_period' => $current,
                'previous_period' => $previous,
                'comparison' => [
                    'revenue' => $revenueChange,
                    'net_profit' => $profitChange
                ]
            ]
        ]);
    }

    private function calculatePeriodProfit($periodDate)
    {
        $startDate = $periodDate->copy()->startOfMonth();
        $endDate = $periodDate->copy()->endOfMonth();

        $grossIncome = Transaction::whereBetween('transaction_time', [$startDate, $endDate])
            ->sum('total_amount');

        $cogs = $this->calculateCOGS($startDate, $endDate);
        $grossProfit = $grossIncome - $cogs;

        $expenses = Cashflow::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $netProfit = $grossProfit - $expenses;

        return [
            'period' => $startDate->format('M Y'),
            'revenue' => $grossIncome,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit
        ];
    }

    private function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }
}