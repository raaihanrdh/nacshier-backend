<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

class ProfitExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomStartCell, WithEvents
{
    protected $request;
    protected $startDate;
    protected $endDate;
    protected $period;
    protected $summary;
    protected $topProducts;
    protected $profitByCategory;

    public function __construct(Request $request, $startDate, $endDate, $period)
    {
        $this->request = $request;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->period = $period;
        $this->calculateData();
    }

    private function calculateData()
    {
        // Hitung total pendapatan dari transaksi
        $grossIncome = Transaction::whereBetween('transaction_time', [$this->startDate, $this->endDate])
            ->sum('total_amount');

        // Hitung total HPP (Harga Pokok Penjualan)
        $cogs = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->whereBetween('transactions.transaction_time', [$this->startDate, $this->endDate])
            ->selectRaw('SUM(transaction_items.quantity * products.capital_price) as total_cogs')
            ->value('total_cogs') ?? 0;

        // Hitung gross profit (laba kotor)
        $grossProfit = $grossIncome - $cogs;

        // Hitung total biaya (expenses)
        $totalExpenses = Cashflow::where('type', 'expense')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->sum('amount');

        // Hitung net profit (laba bersih)
        $netProfit = $grossProfit - $totalExpenses;

        // Hitung margin
        $grossMargin = $grossIncome > 0 ? ($grossProfit / $grossIncome) * 100 : 0;
        $netMargin = $grossIncome > 0 ? ($netProfit / $grossIncome) * 100 : 0;

        $this->summary = [
            'gross_income' => $grossIncome,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'gross_margin' => round($grossMargin, 2),
            'net_margin' => round($netMargin, 2),
        ];

        // Top profitable products
        $this->topProducts = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->whereBetween('transactions.transaction_time', [$this->startDate, $this->endDate])
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
        $this->profitByCategory = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->join('products', 'transaction_items.product_id', '=', 'products.product_id')
            ->join('categories', 'products.category_id', '=', 'categories.category_id')
            ->whereBetween('transactions.transaction_time', [$this->startDate, $this->endDate])
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity * transaction_items.selling_price) as revenue'),
                DB::raw('SUM(transaction_items.quantity * products.capital_price) as cogs'),
                DB::raw('SUM(transaction_items.quantity * (transaction_items.selling_price - products.capital_price)) as gross_profit')
            )
            ->groupBy('categories.name')
            ->get();
    }

    public function collection()
    {
        // Return empty collection, data akan di-render di registerEvents
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'Terjual',
            'Revenue',
            'COGS',
            'Gross Profit'
        ];
    }

    public function map($row): array
    {
        return [];
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            8 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'F59E0B']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D97706'],
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
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', '📊 LAPORAN PROFIT');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
                $sheet->getStyle('A1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F59E0B');
                $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension('1')->setRowHeight(30);
                
                $sheet->setCellValue('A2', 'Periode: ' . ucfirst($this->period));
                $sheet->setCellValue('A3', 'Tanggal: ' . $this->startDate->format('d F Y') . ' - ' . $this->endDate->format('d F Y'));
                $sheet->setCellValue('A4', 'Dibuat pada: ' . Carbon::now()->format('d F Y H:i:s'));

                // Style header info
                $sheet->getStyle('A2:A4')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2:A4')->getFont()->getColor()->setRGB('F59E0B');

                // Summary section dengan design profesional
                $sheet->mergeCells('A6:F6');
                $sheet->setCellValue('A6', '💰 RINGKASAN PROFIT');
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A6')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF3C7');
                $sheet->getStyle('A6')->getFont()->getColor()->setRGB('F59E0B');
                $sheet->getStyle('A6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension('6')->setRowHeight(25);
                
                $summaryData = [
                    ['Gross Income:', 'Rp ' . number_format($this->summary['gross_income'], 0, ',', '.')],
                    ['HPP (COGS):', 'Rp ' . number_format($this->summary['cogs'], 0, ',', '.')],
                    ['Gross Profit:', 'Rp ' . number_format($this->summary['gross_profit'], 0, ',', '.')],
                    ['Total Expenses:', 'Rp ' . number_format($this->summary['total_expenses'], 0, ',', '.')],
                    ['Net Profit:', 'Rp ' . number_format($this->summary['net_profit'], 0, ',', '.')],
                    ['Gross Margin:', number_format($this->summary['gross_margin'], 2) . '%'],
                    ['Net Margin:', number_format($this->summary['net_margin'], 2) . '%'],
                ];
                
                $row = 7;
                foreach ($summaryData as $data) {
                    $sheet->setCellValue('A' . $row, $data[0]);
                    $sheet->setCellValue('B' . $row, $data[1]);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('F59E0B');
                    $row++;
                }
                
                // Add border to summary
                $sheet->getStyle('A6:B' . ($row - 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                // Top Products Table dengan design profesional
                $row = $row + 2;
                $sheet->mergeCells('A' . $row . ':F' . $row);
                $sheet->setCellValue('A' . $row, '🏆 TOP 10 PRODUK PALING MENGUNTUNGKAN');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF3C7');
                $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('F59E0B');
                $sheet->getStyle('A' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(25);
                $row++;

                // Headers
                $headers = ['No', 'Nama Produk', 'Terjual', 'Revenue', 'COGS', 'Gross Profit'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $col++;
                }
                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F59E0B');
                $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $row++;

                // Data
                $no = 1;
                foreach ($this->topProducts as $product) {
                    $sheet->setCellValue('A' . $row, $no);
                    $sheet->setCellValue('B' . $row, $product->name);
                    $sheet->setCellValue('C' . $row, $product->total_sold);
                    $sheet->setCellValue('D' . $row, 'Rp ' . number_format($product->revenue, 0, ',', '.'));
                    $sheet->setCellValue('E' . $row, 'Rp ' . number_format($product->cogs, 0, ',', '.'));
                    $sheet->setCellValue('F' . $row, 'Rp ' . number_format($product->gross_profit, 0, ',', '.'));
                    $row++;
                    $no++;
                }

                // Profit by Category dengan design profesional
                $row += 2;
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->setCellValue('A' . $row, '📦 PROFIT BERDASARKAN KATEGORI');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF3C7');
                $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('F59E0B');
                $sheet->getStyle('A' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(25);
                $row++;

                // Headers
                $headers = ['No', 'Kategori', 'Revenue', 'COGS', 'Gross Profit'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $col++;
                }
                $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A' . $row . ':E' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F59E0B');
                $sheet->getStyle('A' . $row . ':E' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $row++;

                // Data
                $no = 1;
                foreach ($this->profitByCategory as $category) {
                    $sheet->setCellValue('A' . $row, $no);
                    $sheet->setCellValue('B' . $row, $category->category_name);
                    $sheet->setCellValue('C' . $row, 'Rp ' . number_format($category->revenue, 0, ',', '.'));
                    $sheet->setCellValue('D' . $row, 'Rp ' . number_format($category->cogs, 0, ',', '.'));
                    $sheet->setCellValue('E' . $row, 'Rp ' . number_format($category->gross_profit, 0, ',', '.'));
                    $row++;
                    $no++;
                }

                // Auto-size columns
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}

