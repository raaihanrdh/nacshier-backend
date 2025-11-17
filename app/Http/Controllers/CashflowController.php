<?php

namespace App\Http\Controllers;

use App\Models\Cashflow;
use App\Models\Transaction;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashflowController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $query = Cashflow::query();
        
        // Filter berdasarkan period (daily, weekly, monthly, custom, all)
        if ($request->filled('period')) {
            $period = $request->input('period');
            
            if ($period === 'daily' && $request->filled('date')) {
                $date = Carbon::parse($request->date);
                $query->whereBetween('date', [
                    $date->copy()->startOfDay()->format('Y-m-d'),
                    $date->copy()->endOfDay()->format('Y-m-d')
                ]);
            } elseif ($period === 'weekly') {
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->whereBetween('date', [
                        Carbon::parse($request->start_date)->format('Y-m-d'),
                        Carbon::parse($request->end_date)->format('Y-m-d')
                    ]);
                } else {
                    // Default: current week
                    $query->whereBetween('date', [
                        Carbon::now()->startOfWeek()->format('Y-m-d'),
                        Carbon::now()->endOfWeek()->format('Y-m-d')
                    ]);
                }
            } elseif ($period === 'monthly') {
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->whereBetween('date', [
                        Carbon::parse($request->start_date)->format('Y-m-d'),
                        Carbon::parse($request->end_date)->format('Y-m-d')
                    ]);
                } else {
                    // Default: current month
                    $query->whereBetween('date', [
                        Carbon::now()->startOfMonth()->format('Y-m-d'),
                        Carbon::now()->endOfMonth()->format('Y-m-d')
                    ]);
                }
            } elseif ($period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('date', [
                    Carbon::parse($request->start_date)->format('Y-m-d'),
                    Carbon::parse($request->end_date)->format('Y-m-d')
                ]);
            }
            // If period is 'all' or not recognized, show all cashflows
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            // Fallback: support old way (start_date and end_date without period)
            $query->whereBetween('date', [
                Carbon::parse($request->start_date)->format('Y-m-d'),
                Carbon::parse($request->end_date)->format('Y-m-d')
            ]);
        } elseif ($request->has('start_date')) {
            $query->where('date', '>=', Carbon::parse($request->start_date)->format('Y-m-d'));
        } elseif ($request->has('end_date')) {
            $query->where('date', '<=', Carbon::parse($request->end_date)->format('Y-m-d'));
        }
        
        // Filter berdasarkan tipe jika ada
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }
        
        // Filter berdasarkan kategori
        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }
        
        // Filter berdasarkan method
        if ($request->has('method') && $request->method !== '') {
            $query->where('method', $request->method);
        }
        
        // Filter berdasarkan search (description)
        if ($request->has('search') && $request->search !== '') {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        
        // Pagination
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        
        // Sort berdasarkan tanggal terbaru
        $cashflows = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'success' => true,
            'data' => $cashflows->items(),
            'meta' => [
                'current_page' => $cashflows->currentPage(),
                'last_page' => $cashflows->lastPage(),
                'per_page' => $cashflows->perPage(),
                'total' => $cashflows->total(),
            ],
            'message' => 'Cashflows retrieved successfully'
        ]);
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'nullable|exists:transactions,transaction_id',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'method' => 'required|string',
        ]);
        
        $cashflow = Cashflow::create($data);
        
        return response()->json([
            'success' => true,
            'message' => 'Cashflow created',
            'data' => $cashflow,
        ]);
    }
    
    public function show(Cashflow $cashflow)
    {
        return response()->json([
            'success' => true,
            'data' => $cashflow
        ]);
    }
    
    public function update(Request $request, Cashflow $cashflow)
    {
        $data = $request->validate([
            'transaction_id' => 'nullable|exists:transactions,transaction_id',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'method' => 'required|string',
        ]);
        
        $cashflow->update($data);
        
        return response()->json([
            'success' => true,
            'message' => 'Cashflow updated',
            'data' => $cashflow,
        ]);
    }
    
    public function destroy(Cashflow $cashflow)
    {
        $cashflow->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cashflow deleted',
        ]);
    }
    
    public function summary(Request $request)
    {
        $period = $request->input('period', 'monthly');
        $today = Carbon::today();
        
        // Default query untuk bulan ini
        $baseQuery = Cashflow::query();
        
        if ($period === 'monthly') {
            $baseQuery->whereYear('date', $today->year)
                     ->whereMonth('date', $today->month);
        } else if ($period === 'weekly') {
            $baseQuery->whereBetween('date', [
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek()
            ]);
        } else if ($period === 'daily') {
            $baseQuery->whereDate('date', $today);
        } else if ($period === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            if ($startDate && $endDate) {
                $baseQuery->whereBetween('date', [$startDate, $endDate]);
            }
        }
        
        // Dapatkan total income dan expense
        $totalIncome = (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $baseQuery)->where('type', 'expense')->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;
        
        // Dapatkan breakdown berdasarkan kategori
        $incomeByCategory = (clone $baseQuery)->where('type', 'income')
            ->groupBy('category')
            ->selectRaw('category, sum(amount) as total')
            ->get();
            
        $expenseByCategory = (clone $baseQuery)->where('type', 'expense')
            ->groupBy('category')
            ->selectRaw('category, sum(amount) as total')
            ->get();
            
        // Dapatkan breakdown berdasarkan metode pembayaran
        $incomeByMethod = (clone $baseQuery)->where('type', 'income')
            ->groupBy('method')
            ->selectRaw('method, sum(amount) as total')
            ->get();
            
        $expenseByMethod = (clone $baseQuery)->where('type', 'expense')
            ->groupBy('method')
            ->selectRaw('method, sum(amount) as total')
            ->get();
        
        // Dapatkan trend harian dalam periode
        $dailyTrend = [];
        
        if ($period === 'monthly' || $period === 'custom') {
            $startDate = $period === 'monthly' 
                ? $today->copy()->startOfMonth() 
                : Carbon::parse($request->input('start_date'));
                
            $endDate = $period === 'monthly' 
                ? $today->copy()->endOfMonth() 
                : Carbon::parse($request->input('end_date'));
                
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $date = $currentDate->toDateString();
                $dailyIncome = (clone $baseQuery)->where('type', 'income')
                    ->whereDate('date', $date)
                    ->sum('amount');
                    
                $dailyExpense = (clone $baseQuery)->where('type', 'expense')
                    ->whereDate('date', $date)
                    ->sum('amount');
                    
                $dailyTrend[] = [
                    'date' => $date,
                    'income' => $dailyIncome,
                    'expense' => $dailyExpense,
                    'net' => $dailyIncome - $dailyExpense
                ];
                
                $currentDate->addDay();
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_cashflow' => $netCashflow,
                'income_by_category' => $incomeByCategory,
                'expense_by_category' => $expenseByCategory,
                'income_by_method' => $incomeByMethod,
                'expense_by_method' => $expenseByMethod,
                'daily_trend' => $dailyTrend,
                'period' => $period
            ]
        ]);
    }
    
    public function getCategories()
    {
        $incomeCategories = Cashflow::where('type', 'income')
            ->select('category')
            ->distinct()
            ->pluck('category');
            
        $expenseCategories = Cashflow::where('type', 'expense')
            ->select('category')
            ->distinct()
            ->pluck('category');
            
        return response()->json([
            'success' => true,
            'data' => [
                'income_categories' => $incomeCategories,
                'expense_categories' => $expenseCategories
            ]
        ]);
    }
    
    public function getMethods()
    {
        $methods = Cashflow::select('method')
            ->distinct()
            ->pluck('method');
            
        return response()->json([
            'success' => true,
            'data' => $methods
        ]);
    }
}