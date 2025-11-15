<?php

namespace App\Http\Traits;

trait CalculatesProfit
{
    /**
     * Calculate selling price, capital price, and profit for transaction items
     * 
     * @param \Illuminate\Database\Eloquent\Collection $items
     * @return array
     */
    protected function calculateTransactionProfit($items)
    {
        $totalSellingPrice = 0;
        $totalCapitalPrice = 0;

        $formattedItems = $items->map(function ($item) use (&$totalSellingPrice, &$totalCapitalPrice) {
            $itemSellingTotal = $item->selling_price * $item->quantity;
            $itemCapitalTotal = optional($item->product)->capital_price ?? 0;
            $itemCapitalTotal *= $item->quantity;

            $totalSellingPrice += $itemSellingTotal;
            $totalCapitalPrice += $itemCapitalTotal;

            return [
                'product' => [
                    'name' => optional($item->product)->name ?? 'Tidak diketahui',
                    'capital_price' => optional($item->product)->capital_price ?? 0,
                ],
                'price' => $item->selling_price,
                'quantity' => $item->quantity,
                'subtotal_selling' => $itemSellingTotal,
                'subtotal_capital' => $itemCapitalTotal,
            ];
        });

        return [
            'items' => $formattedItems,
            'total_selling_price' => $totalSellingPrice,
            'total_capital_price' => $totalCapitalPrice,
            'profit' => $totalSellingPrice - $totalCapitalPrice,
        ];
    }

    /**
     * Format transaction response with profit calculation
     * 
     * @param \App\Models\Transaction $transaction
     * @return array
     */
    protected function formatTransactionResponse($transaction)
    {
        $profitData = $this->calculateTransactionProfit($transaction->items);

        return [
            'transaction_id' => $transaction->transaction_id,
            'shift_id' => $transaction->shift_id,
            'transaction_time' => $transaction->transaction_time,
            'payment_method' => $transaction->payment_method,
            'total_amount' => (float) $transaction->total_amount,
            'total_selling_price' => $profitData['total_selling_price'],
            'total_capital_price' => $profitData['total_capital_price'],
            'profit' => $profitData['profit'],
            'items' => $profitData['items'],
        ];
    }
}

