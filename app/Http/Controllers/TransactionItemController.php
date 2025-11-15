<?php

namespace App\Http\Controllers;

use App\Models\TransactionItem;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

class TransactionItemController extends Controller
{
    use ApiResponse;

    // Menampilkan item transaksi berdasar transaksi ID
    public function index($transactionId)
    {
        try {
            $items = TransactionItem::where('transaction_id', $transactionId)
                    ->with('product')
                    ->get();
            return $this->successResponse($items, 'Transaction items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch transaction items', 500);
        }
    }

    // Menambahkan item ke transaksi
    public function store(Request $request, $transactionId)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,product_id',
                'quantity' => 'required|integer|min:1',
                'selling_price' => 'required|numeric|min:0',
            ]);

            $item = TransactionItem::create([
                'transaction_id' => $transactionId,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'selling_price' => $request->selling_price,
            ]);

            $item->load('product');
            return $this->successResponse($item, 'Transaction item created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add item to transaction: ' . $e->getMessage(), 500);
        }
    }

    // Update item transaksi
    public function update(Request $request, $transactionId, $itemId)
    {
        try {
            $request->validate([
                'quantity' => 'sometimes|integer|min:1',
                'selling_price' => 'sometimes|numeric|min:0',
            ]);

            $item = TransactionItem::where('transaction_id', $transactionId)->findOrFail($itemId);
            $item->update($request->only(['quantity', 'selling_price']));
            $item->load('product');

            return $this->successResponse($item, 'Transaction item updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Transaction item not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update transaction item: ' . $e->getMessage(), 500);
        }
    }

    // Menghapus item transaksi
    public function destroy($transactionId, $itemId)
    {
        try {
            $item = TransactionItem::where('transaction_id', $transactionId)->findOrFail($itemId);
            $item->delete();

            return $this->successResponse(null, 'Item deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Transaction item not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete transaction item: ' . $e->getMessage(), 500);
        }
    }
}