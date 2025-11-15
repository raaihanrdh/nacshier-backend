<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransactionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomStartCell, WithEvents
{
    protected $request;
    protected $summary;
    protected $totalRows;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Transaction::with(['items.product']);

        // Filter berdasarkan periode
        if ($this->request->period === 'daily' && $this->request->filled('date')) {
            $date = Carbon::parse($this->request->date);
            $query->whereBetween('transaction_time', [
                $date->startOfDay(),
                $date->endOfDay()
            ]);
        } elseif (($this->request->period === 'weekly' || $this->request->period === 'monthly') && 
                  $this->request->filled('start_date') && $this->request->filled('end_date')) {
            $query->whereBetween('transaction_time', [
                Carbon::parse($this->request->start_date)->startOfDay(),
                Carbon::parse($this->request->end_date)->endOfDay()
            ]);
        }

        $transactions = $query->orderBy('transaction_time', 'desc')->get();

        // Format data
        $formattedTransactions = $transactions->map(function($transaction) {
            $transactionTime = $transaction->transaction_time;
            return [
                'transaction_id' => $transaction->transaction_id,
                'shift_id' => $transaction->shift_id,
                'tanggal' => $transactionTime ? $transactionTime->format('Y-m-d') : '',
                'jam' => $transactionTime ? $transactionTime->format('H:i:s') : '',
                'kasir' => 'Kasir #' . $transaction->shift_id,
                'metodePembayaran' => $transaction->payment_method,
                'total' => $transaction->total_amount,
                'items' => $transaction->items->map(function($item) {
                    return $item->product->name . ' (Qty: ' . $item->quantity . ', @' . number_format($item->selling_price) . ')';
                })->implode('; '),
                'item_count' => $transaction->items->sum('quantity'),
            ];
        });

        // Apply filters
        if ($this->request->filled('product_filter')) {
            $formattedTransactions = $formattedTransactions->filter(function($trx) {
                return stripos($trx['items'], $this->request->product_filter) !== false;
            });
        }

        if ($this->request->filled('cashier_filter')) {
            $formattedTransactions = $formattedTransactions->filter(function($trx) {
                return stripos($trx['kasir'], $this->request->cashier_filter) !== false;
            });
        }

        // Calculate summary
        $this->summary = [
            'totalPendapatan' => $formattedTransactions->sum('total'),
            'incomeQRIS' => $formattedTransactions->where('metodePembayaran', 'Qris')->sum('total'),
            'incomeCash' => $formattedTransactions->where('metodePembayaran', 'Cash')->sum('total'),
            'incomeTransfer' => $formattedTransactions->where('metodePembayaran', 'Transfer')->sum('total'),
            'totalTransaksi' => $formattedTransactions->count(),
            'totalBarang' => $formattedTransactions->sum('item_count'),
        ];

        $this->totalRows = $formattedTransactions->count();

        return $formattedTransactions;
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'ID Shift',
            'Tanggal',
            'Waktu',
            'Kasir',
            'Metode Pembayaran',
            'Total (Rp)',
            'Detail Barang',
            'Jumlah Item'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction['transaction_id'],
            $transaction['shift_id'],
            $transaction['tanggal'],
            $transaction['jam'],
            $transaction['kasir'],
            $transaction['metodePembayaran'],
            $transaction['total'],
            $transaction['items'],
            $transaction['item_count']
        ];
    }

    public function startCell(): string
    {
        return 'A8'; // Mulai dari baris 8 untuk memberikan ruang untuk header laporan
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk heading tabel
            8 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '2563EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '1E40AF'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Header laporan dengan design profesional
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', '📊 LAPORAN TRANSAKSI');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
                $sheet->getStyle('A1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('1E40AF');
                $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension('1')->setRowHeight(30);
                
                $sheet->setCellValue('A2', 'Periode: ' . ucfirst($this->request->period));
                
                // Date range
                $dateRange = '';
                if ($this->request->period === 'daily' && $this->request->filled('date')) {
                    $dateRange = Carbon::parse($this->request->date)->format('d F Y');
                } elseif ($this->request->filled('start_date') && $this->request->filled('end_date')) {
                    $start = Carbon::parse($this->request->start_date)->format('d F Y');
                    $end = Carbon::parse($this->request->end_date)->format('d F Y');
                    $dateRange = $start . ' - ' . $end;
                }
                $sheet->setCellValue('A3', 'Tanggal: ' . $dateRange);
                
                // Filters
                $filters = [];
                if ($this->request->filled('product_filter')) {
                    $filters[] = 'Produk: ' . $this->request->product_filter;
                }
                if ($this->request->filled('cashier_filter')) {
                    $filters[] = 'Kasir: ' . $this->request->cashier_filter;
                }
                if (!empty($filters)) {
                    $sheet->setCellValue('A4', 'Filter: ' . implode(', ', $filters));
                }
                
                $sheet->setCellValue('A5', 'Dibuat pada: ' . Carbon::now()->format('d F Y H:i:s'));

                // Style header info
                $sheet->getStyle('A2:A5')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2:A5')->getFont()->getColor()->setRGB('1E40AF');
                
                // Auto-size columns
                foreach (range('A', 'I') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
                
                // Add borders to data
                $lastRow = 8 + $this->totalRows;
                $sheet->getStyle('A8:I' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);
                
                // Summary section dengan design profesional
                $summaryStartRow = $lastRow + 3;
                $sheet->mergeCells('A' . $summaryStartRow . ':I' . $summaryStartRow);
                $sheet->setCellValue('A' . $summaryStartRow, '📈 RINGKASAN TRANSAKSI');
                $sheet->getStyle('A' . $summaryStartRow)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $summaryStartRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
                $sheet->getStyle('A' . $summaryStartRow)->getFont()->getColor()->setRGB('1E40AF');
                $sheet->getStyle('A' . $summaryStartRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($summaryStartRow)->setRowHeight(25);
                
                $summaryData = [
                    ['Total Pendapatan:', 'Rp ' . number_format($this->summary['totalPendapatan'], 0, ',', '.')],
                    ['Income QRIS:', 'Rp ' . number_format($this->summary['incomeQRIS'], 0, ',', '.')],
                    ['Income Cash:', 'Rp ' . number_format($this->summary['incomeCash'], 0, ',', '.')],
                    ['Income Transfer:', 'Rp ' . number_format($this->summary['incomeTransfer'], 0, ',', '.')],
                    ['Total Transaksi:', number_format($this->summary['totalTransaksi'], 0, ',', '.')],
                    ['Total Barang:', number_format($this->summary['totalBarang'], 0, ',', '.')],
                ];
                
                $row = $summaryStartRow + 1;
                foreach ($summaryData as $data) {
                    $sheet->setCellValue('A' . $row, $data[0]);
                    $sheet->setCellValue('B' . $row, $data[1]);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('059669');
                    $row++;
                }
                
                // Add border to summary
                $sheet->getStyle('A' . $summaryStartRow . ':B' . ($row - 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);
            },
        ];
    }
}

