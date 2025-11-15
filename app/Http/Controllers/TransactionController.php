<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Http\Traits\CalculatesProfit;
use App\Http\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Product;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    use CalculatesProfit, ApiResponse;
    // Menampilkan semua transaksi dengan total selling price dan capital price
    public function index(Request $request)
    {
        try {
            $query = Transaction::with(['items.product']);
        
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('transaction_time', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }
        
            $transactions = $query->orderBy('transaction_time', 'desc')->get();

            $formattedTransactions = $transactions->map(function($transaction) {
                return $this->formatTransactionResponse($transaction);
            });

            return $this->successResponse($formattedTransactions, 'Transactions retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Transaction index error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve transactions', 500);
        }
    }
    
    // Detail transaksi dengan total selling price dan capital price
    public function show($transaction_id)
    {
        try {
            $transaction = Transaction::with(['items.product'])
                ->where('transaction_id', $transaction_id)
                ->firstOrFail();

            $response = $this->formatTransactionResponse($transaction);
            return $this->successResponse($response, 'Transaction retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Transaction not found');
        } catch (\Exception $e) {
            Log::error('Transaction show error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve transaction', 500);
        }
    }

    // Store method tetap sama
    public function store(Request $request)
    {
        Log::info('Transaction store request:', $request->all());

        try {
            $request->validate([
                'shift_id' => 'required|string|exists:cashier_shifts,shift_id',
                'total_amount' => 'required|numeric|min:0',
                'transaction_time' => 'sometimes|date',
                'payment_method' => 'required|string|in:Cash,Qris,Transfer',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|string|exists:products,product_id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.selling_price' => 'sometimes|numeric|min:0',
            ]);

            DB::beginTransaction();

            $transactionTime = $request->has('transaction_time') 
                ? Carbon::parse($request->transaction_time)->setTimezone('Asia/Jakarta')
                : Carbon::now('Asia/Jakarta');

            $transaction = new Transaction([
                'shift_id' => $request->shift_id,
                'total_amount' => $request->total_amount,
                'transaction_time' => $transactionTime,
                'payment_method' => $request->payment_method,
            ]);
            
            $transaction->save();

            Log::info('Transaction created with ID:', ['transaction_id' => $transaction->transaction_id]);

            foreach ($request->items as $itemData) {
                $product = Product::where('product_id', $itemData['product_id'])->firstOrFail();

                if ($product->stock < $itemData['quantity']) {
                    throw new \Exception("Stok produk '{$product->name}' tidak mencukupi. Stok tersedia: {$product->stock}");
                }

                $sellingPrice = isset($itemData['selling_price']) 
                    ? floatval($itemData['selling_price'])
                    : $product->selling_price;

                $product->decrement('stock', $itemData['quantity']);

                TransactionItem::create([
                    'transaction_id' => $transaction->transaction_id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'selling_price' => $sellingPrice,
                ]);

                Log::info('Transaction item created:', [
                    'transaction_id' => $transaction->transaction_id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'selling_price' => $sellingPrice
                ]);
            }

            DB::commit();

            $transaction->load(['items.product']);
            $response = $this->formatTransactionResponse($transaction);

            Log::info('Transaction completed successfully:', ['transaction_id' => $transaction->transaction_id]);

            return $this->successResponse($response, 'Transaksi berhasil dibuat', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error:', $e->errors());
            return $this->validationErrorResponse($e->validator);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction creation failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Gagal membuat transaksi: ' . $e->getMessage(), 500);
        }
    }

    // Update method dengan total selling dan capital price
    public function update(Request $request, $transaction_id)
    {
        try {
            $request->validate([
                'total_amount' => 'sometimes|required|numeric',
                'transaction_time' => 'sometimes|required|date',
            ]);

            $transaction = Transaction::where('transaction_id', $transaction_id)->firstOrFail();
            $transaction->update($request->only(['total_amount', 'transaction_time']));
            $transaction->load(['items.product']);

            $response = $this->formatTransactionResponse($transaction);
            return $this->successResponse($response, 'Transaction updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Transaction not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            Log::error('Transaction update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($transaction_id)
    {
        try {
            $transaction = Transaction::where('transaction_id', $transaction_id)->firstOrFail();
            $transaction->items()->delete();
            $transaction->delete();

            return $this->successResponse(null, 'Transaction deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Transaction not found');
        } catch (\Exception $e) {
            Log::error('Transaction destroy error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete transaction: ' . $e->getMessage(), 500);
        }
    }

    // Report pendapatan dengan total selling dan capital price
    public function incomeReport(Request $request)
    {
        $query = Transaction::with(['items.product']);
    
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_time', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }
    
        $transactions = $query->get();

        // Hitung total selling dan capital price untuk semua transaksi
        $totalSellingPrice = 0;
        $totalCapitalPrice = 0;

        $transactionData = $transactions->map(function($transaction) use (&$totalSellingPrice, &$totalCapitalPrice) {
            $transactionSellingPrice = 0;
            $transactionCapitalPrice = 0;
            
            $items = $transaction->items->map(function($item) use (&$transactionSellingPrice, &$transactionCapitalPrice) {
                $itemSellingTotal = $item->selling_price * $item->quantity;
                $itemCapitalTotal = $item->product->capital_price * $item->quantity;
                
                $transactionSellingPrice += $itemSellingTotal;
                $transactionCapitalPrice += $itemCapitalTotal;
                
                return [
                    'product' => ['name' => $item->product->name],
                    'price' => $item->selling_price,
                    'quantity' => $item->quantity,
                ];
            });

            $totalSellingPrice += $transactionSellingPrice;
            $totalCapitalPrice += $transactionCapitalPrice;

            return [
                'transaction_id' => $transaction->transaction_id,
                'shift_id' => $transaction->shift_id,
                'transaction_time' => $transaction->transaction_time,
                'payment_method' => $transaction->payment_method,
                'total_amount' => $transaction->total_amount,
                'total_selling_price' => $transactionSellingPrice,
                'total_capital_price' => $transactionCapitalPrice,
                'profit' => $transactionSellingPrice - $transactionCapitalPrice,
                'items' => $items,
            ];
        });

        $response = [
            'pendapatan' => $transactions->sum('total_amount'),
            'total_selling_price' => $totalSellingPrice,
            'total_capital_price' => $totalCapitalPrice,
            'total_profit' => $totalSellingPrice - $totalCapitalPrice,
            'income_qris' => $transactions->where('payment_method', 'Qris')->sum('total_amount'),
            'income_cash' => $transactions->where('payment_method', 'Cash')->sum('total_amount'),
            'income_transfer' => $transactions->where('payment_method', 'Transfer')->sum('total_amount'),
            'total_transaksi' => $transactions->count(),
            'total_barang' => $transactions->sum(fn($t) => $t->items->sum('quantity')),
            'transactions' => $transactionData,
        ];

        return response()->json($response);
    }

    // Daily report dengan total selling dan capital price
    public function dailyIncomeReport(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date = $request->filled('date') 
            ? Carbon::parse($request->date, 'Asia/Jakarta')
            : Carbon::today('Asia/Jakarta');

        $transactions = Transaction::with(['items.product'])
            ->whereBetween('transaction_time', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            ])
            ->orderBy('transaction_time', 'desc')
            ->get();

        // Hitung total selling dan capital price
        $totalSellingPrice = 0;
        $totalCapitalPrice = 0;

        $transactions->each(function($transaction) use (&$totalSellingPrice, &$totalCapitalPrice) {
            $transaction->items->each(function($item) use (&$totalSellingPrice, &$totalCapitalPrice) {
                $totalSellingPrice += $item->selling_price * $item->quantity;
                $totalCapitalPrice += $item->product->capital_price * $item->quantity;
            });
        });

        // Group by hour for more detailed breakdown
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $start = $date->copy()->startOfDay()->addHours($hour);
            $end = $start->copy()->addHour();

            $hourlyTransactions = $transactions->filter(function($t) use ($start, $end) {
                return $t->transaction_time >= $start && $t->transaction_time < $end;
            });

            $hourlySelling = 0;
            $hourlyCapital = 0;

            $hourlyTransactions->each(function($transaction) use (&$hourlySelling, &$hourlyCapital) {
                $transaction->items->each(function($item) use (&$hourlySelling, &$hourlyCapital) {
                    $hourlySelling += $item->selling_price * $item->quantity;
                    $hourlyCapital += $item->product->capital_price * $item->quantity;
                });
            });

            $hourlyData[] = [
                'hour' => $start->format('H:00'),
                'total_amount' => $hourlyTransactions->sum('total_amount'),
                'total_selling_price' => $hourlySelling,
                'total_capital_price' => $hourlyCapital,
                'profit' => $hourlySelling - $hourlyCapital,
                'transaction_count' => $hourlyTransactions->count(),
            ];
        }

        $response = [
            'date' => $date->format('Y-m-d'),
            'total_income' => $transactions->sum('total_amount'),
            'total_selling_price' => $totalSellingPrice,
            'total_capital_price' => $totalCapitalPrice,
            'total_profit' => $totalSellingPrice - $totalCapitalPrice,
            'cash_income' => $transactions->where('payment_method', 'Cash')->sum('total_amount'),
            'qris_income' => $transactions->where('payment_method', 'Qris')->sum('total_amount'),
            'transfer_income' => $transactions->where('payment_method', 'Transfer')->sum('total_amount'),
            'transaction_count' => $transactions->count(),
            'item_count' => $transactions->sum(fn($t) => $t->items->sum('quantity')),
            'hourly_breakdown' => $hourlyData,
        ];

        return response()->json($response);
    }
}