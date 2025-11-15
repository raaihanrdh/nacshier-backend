<?php

namespace App\Exports;

use App\Models\Cashflow;
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

class CashflowExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomStartCell, WithEvents
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
        $query = Cashflow::query();
        
        // Filter berdasarkan tipe jika ada
        if ($this->request->has('type')) {
            $query->where('type', $this->request->type);
        }
        
        // Filter berdasarkan range tanggal
        if ($this->request->has('start_date') && $this->request->has('end_date')) {
            $query->whereBetween('date', [$this->request->start_date, $this->request->end_date]);
        } elseif ($this->request->has('start_date')) {
            $query->where('date', '>=', $this->request->start_date);
        } elseif ($this->request->has('end_date')) {
            $query->where('date', '<=', $this->request->end_date);
        }
        
        // Filter berdasarkan kategori
        if ($this->request->has('category')) {
            $query->where('category', $this->request->category);
        }
        
        // Filter berdasarkan method
        if ($this->request->has('method')) {
            $query->where('method', $this->request->method);
        }
        
        // Filter berdasarkan search (description)
        if ($this->request->has('search')) {
            $query->where('description', 'like', '%' . $this->request->search . '%');
        }
        
        // Sort berdasarkan tanggal terbaru
        $cashflows = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
        
        // Calculate summary
        $totalIncome = $cashflows->where('type', 'income')->sum('amount');
        $totalExpense = $cashflows->where('type', 'expense')->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;
        
        $this->summary = [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cashflow' => $netCashflow,
        ];
        
        $this->totalRows = $cashflows->count();
        
        return $cashflows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Tipe',
            'Kategori',
            'Deskripsi',
            'Metode',
            'Jumlah (Rp)'
        ];
    }

    public function map($cashflow): array
    {
        return [
            Carbon::parse($cashflow->date)->format('d/m/Y'),
            $cashflow->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $cashflow->category,
            $cashflow->description,
            $cashflow->method,
            $cashflow->amount,
        ];
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
                    'color' => ['rgb' => '10B981']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '059669'],
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
                $sheet->setCellValue('A1', '💰 LAPORAN ARUS KAS (CASHFLOW)');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
                $sheet->getStyle('A1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('10B981');
                $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension('1')->setRowHeight(30);
                
                // Date range
                $dateRange = '';
                if ($this->request->filled('start_date') && $this->request->filled('end_date')) {
                    $start = Carbon::parse($this->request->start_date)->format('d F Y');
                    $end = Carbon::parse($this->request->end_date)->format('d F Y');
                    $dateRange = $start . ' - ' . $end;
                } elseif ($this->request->filled('start_date')) {
                    $dateRange = 'Dari: ' . Carbon::parse($this->request->start_date)->format('d F Y');
                } elseif ($this->request->filled('end_date')) {
                    $dateRange = 'Sampai: ' . Carbon::parse($this->request->end_date)->format('d F Y');
                } else {
                    $dateRange = 'Semua Data';
                }
                $sheet->setCellValue('A2', 'Periode: ' . $dateRange);
                
                // Filters
                $filters = [];
                if ($this->request->filled('type')) {
                    $filters[] = 'Tipe: ' . ($this->request->type === 'income' ? 'Pemasukan' : 'Pengeluaran');
                }
                if ($this->request->filled('category')) {
                    $filters[] = 'Kategori: ' . $this->request->category;
                }
                if ($this->request->filled('method')) {
                    $filters[] = 'Metode: ' . $this->request->method;
                }
                if ($this->request->filled('search')) {
                    $filters[] = 'Pencarian: ' . $this->request->search;
                }
                if (!empty($filters)) {
                    $sheet->setCellValue('A3', 'Filter: ' . implode(', ', $filters));
                }
                
                $sheet->setCellValue('A4', 'Dibuat pada: ' . Carbon::now()->format('d F Y H:i:s'));

                // Style header info
                $sheet->getStyle('A2:A4')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2:A4')->getFont()->getColor()->setRGB('10B981');
                
                // Auto-size columns
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
                
                // Add borders to data
                $lastRow = 8 + $this->totalRows;
                $sheet->getStyle('A8:F' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);
                
                // Summary section dengan design profesional
                $summaryStartRow = $lastRow + 3;
                $sheet->mergeCells('A' . $summaryStartRow . ':F' . $summaryStartRow);
                $sheet->setCellValue('A' . $summaryStartRow, '📊 RINGKASAN ARUS KAS');
                $sheet->getStyle('A' . $summaryStartRow)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $summaryStartRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0FDF4');
                $sheet->getStyle('A' . $summaryStartRow)->getFont()->getColor()->setRGB('10B981');
                $sheet->getStyle('A' . $summaryStartRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($summaryStartRow)->setRowHeight(25);
                
                $summaryData = [
                    ['Total Pemasukan:', 'Rp ' . number_format($this->summary['total_income'], 0, ',', '.')],
                    ['Total Pengeluaran:', 'Rp ' . number_format($this->summary['total_expense'], 0, ',', '.')],
                    ['Net Cashflow:', 'Rp ' . number_format($this->summary['net_cashflow'], 0, ',', '.')],
                ];
                
                $row = $summaryStartRow + 1;
                foreach ($summaryData as $data) {
                    $sheet->setCellValue('A' . $row, $data[0]);
                    $sheet->setCellValue('B' . $row, $data[1]);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $color = $row === $summaryStartRow + 3 ? ($this->summary['net_cashflow'] >= 0 ? '059669' : 'DC2626') : '10B981';
                    $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB($color);
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

