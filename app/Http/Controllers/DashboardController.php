<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
public function summary(Request $request)
{
        try {
    $period = $request->input('period', 'daily'); // daily, weekly, monthly
    $today = Carbon::today();
    
    $baseQuery = Transaction::query();

    // Filter berdasarkan periode
    if ($period === 'daily') {
        $startOfDay = $today->copy()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();
        $baseQuery->whereBetween('transaction_time', [$startOfDay, $endOfDay]);
    } else if ($period === 'weekly') {
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $baseQuery->whereBetween('transaction_time', [$startOfWeek, $endOfWeek]);
    } else if ($period === 'monthly') {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $baseQuery->whereYear('transaction_time', $year)
                  ->whereMonth('transaction_time', $month);
    }

    // Calculate income based on period
    $income = $baseQuery->sum('total_amount') ?? 0;

    // For total transactions and items sold, we use period-based query if period is set
    $transactionIds = $baseQuery->pluck('transaction_id');
    $totalTransactions = count($transactionIds);
    $totalItemsSold = TransactionItem::whereIn('transaction_id', $transactionIds)
                                     ->sum('quantity') ?? 0;

    // Always return daily_income and monthly_income for backward compatibility
    $startOfDay = $today->copy()->startOfDay();
    $endOfDay = $today->copy()->endOfDay();
    $month = Carbon::now()->month;
    $year = Carbon::now()->year;
    
    $dailyIncome = Transaction::whereBetween('transaction_time', [$startOfDay, $endOfDay])
                ->sum('total_amount') ?? 0;
    $monthlyIncome = Transaction::whereYear('transaction_time', $year)
                ->whereMonth('transaction_time', $month)
                ->sum('total_amount') ?? 0;

    // Debug logging
    Log::debug('Dashboard summary calculation', [
        'period' => $period,
        'income' => $income,
        'total_transactions' => $totalTransactions,
        'total_items_sold' => $totalItemsSold
    ]);

    return response()->json([
                'daily_income' => (float) ($period === 'daily' ? $income : $dailyIncome),
                'monthly_income' => (float) ($period === 'monthly' ? $income : $monthlyIncome),
                'total_transactions' => (int) $totalTransactions,
                'total_items_sold' => (int) $totalItemsSold,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard summary error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'daily_income' => 0,
                'monthly_income' => 0,
                'total_transactions' => 0,
                'total_items_sold' => 0,
            ], 500);
        }
}

    public function latestTransactions()
    {
        try {
            $transactions = Transaction::with(['items.product'])
                ->latest('transaction_time')
            ->take(5)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'transaction_id' => $transaction->transaction_id,
                        'total_amount' => (float) $transaction->total_amount,
                        'transaction_time' => $transaction->transaction_time->toISOString(),
                    ];
                });

            return response()->json($transactions);
        } catch (\Exception $e) {
            Log::error('Latest transactions error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([], 500);
        }
    }

    public function topProducts(Request $request)
    {
        try {
        $range = $request->query('range', 'day');
        $query = TransactionItem::query();

        if ($range === 'day') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($range === 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($range === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month);
        }

        $topProducts = $query->with('product')
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        return response()->json($topProducts->map(function ($item) {
            return [
                'name' => optional($item->product)->name ?? 'Tidak diketahui',
                    'total_sold' => (int) $item->total_sold, // Fixed: changed from 'sales' to 'total_sold'
            ];
        }));
        } catch (\Exception $e) {
            Log::error('Top products error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([], 500);
        }
    }

public function lowStockProducts(Request $request)
{
    try {
        $threshold = $request->query('threshold', 10);
        
        $products = Product::whereNull('deleted_at')
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->limit(10)
            ->get(['product_id', 'name', 'stock', 'image_path']);
            
            // Format response to include minimum_stock field that frontend expects
            $formattedProducts = $products->map(function($product) use ($threshold) {
                return [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'stock' => (int) $product->stock,
                    'minimum_stock' => $threshold, // Add minimum_stock field
                    'image_path' => $product->image_path,
                    'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                ];
        });
            
            return response()->json($formattedProducts);
            
    } catch (\Exception $e) {
            Log::error('Failed to fetch low stock products: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([], 500);
    }
}

    public function salesChart(Request $request)
    {
        try {
        $range = $request->query('range', 'week');

        if ($range === 'week') {
            $from = Carbon::now()->subDays(6)->startOfDay();
            $to = Carbon::now()->endOfDay();
        } elseif ($range === 'month') {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        } else {
            $from = Carbon::today();
            $to = Carbon::today()->endOfDay();
        }

            // Use transaction_time from Transaction table via join
            $sales = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
                ->whereBetween('transactions.transaction_time', [$from, $to])
                ->selectRaw('DATE(transactions.transaction_time) as date, SUM(transaction_items.quantity * transaction_items.selling_price) as total')
            ->groupBy('date')
            ->orderBy('date')
                ->get()
            ->pluck('total', 'date');

        $dates = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $formattedDate = $date->toDateString();
            $dates[] = [
                'date' => $formattedDate,
                'total' => (float) ($sales[$formattedDate] ?? 0),
            ];
        }

        return response()->json($dates);
        } catch (\Exception $e) {
            Log::error('Sales chart error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([], 500);
    }
}
}
