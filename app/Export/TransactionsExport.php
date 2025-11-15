<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Transaction ID',
            'Shift ID',
            'Date',
            'Time',
            'Payment Method',
            'Total Amount',
            'Cashier',
            'Items Count',
            'Items Details'
        ];
    }

    public function map($transaction): array
    {
        $items = $transaction->items->map(function($item) {
            return sprintf(
                "%s (%d x %s)", 
                $item->product->name,
                $item->quantity,
                number_format($item->selling_price, 0)
            );
        })->implode("\n");

        return [
            $transaction->transaction_id,
            $transaction->shift_id,
            $transaction->transaction_time->format('Y-m-d'),
            $transaction->transaction_time->format('H:i:s'),
            $transaction->payment_method,
            number_format($transaction->total_amount, 0),
            $transaction->cashierShift->cashier_name ?? 'N/A',
            $transaction->items->sum('quantity'),
            $items
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A' => ['width' => 15],
            'B' => ['width' => 10],
            'C' => ['width' => 12],
            'D' => ['width' => 10],
            'E' => ['width' => 15],
            'F' => ['width' => 15],
            'G' => ['width' => 20],
            'H' => ['width' => 12],
            'I' => ['width' => 40],
        ];
    }
}