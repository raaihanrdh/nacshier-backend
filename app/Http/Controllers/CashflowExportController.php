<?php

namespace App\Http\Controllers;

use App\Models\Cashflow;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Traits\ApiResponse;
use App\Exports\CashflowExport;

class CashflowExportController extends Controller
{
    use ApiResponse;

    public function exportPDF(Request $request)
    {
        try {
            // Get filtered cashflows
            $data = $this->getFilteredCashflows($request);
            
            // Generate PDF
            $pdf = Pdf::loadView('exports.cashflow-report', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Nama file
            $filename = $this->generateFilename($request, 'pdf');
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor PDF: ' . $e->getMessage(), 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            // Nama file
            $filename = $this->generateFilename($request, 'xlsx');
            
            return Excel::download(new CashflowExport($request), $filename);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor Excel: ' . $e->getMessage(), 500);
        }
    }

    private function getFilteredCashflows(Request $request)
    {
        $query = Cashflow::query();
        
        // Filter berdasarkan tipe jika ada
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter berdasarkan range tanggal
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        
        // Filter berdasarkan kategori
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Filter berdasarkan method
        if ($request->has('method')) {
            $query->where('method', $request->method);
        }
        
        // Filter berdasarkan search (description)
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        
        // Sort berdasarkan tanggal terbaru
        $cashflows = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
        
        // Calculate summary
        $totalIncome = $cashflows->where('type', 'income')->sum('amount');
        $totalExpense = $cashflows->where('type', 'expense')->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;
        
        return [
            'cashflows' => $cashflows,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_cashflow' => $netCashflow,
            ],
            'filters' => [
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
                'type' => $request->input('type', ''),
                'category' => $request->input('category', ''),
                'method' => $request->input('method', ''),
                'search' => $request->input('search', ''),
            ],
        ];
    }

    private function generateFilename(Request $request, $extension)
    {
        $dateRange = '';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->format('d-m-Y');
            $end = Carbon::parse($request->end_date)->format('d-m-Y');
            $dateRange = $start . '_to_' . $end;
        } elseif ($request->filled('start_date')) {
            $dateRange = Carbon::parse($request->start_date)->format('d-m-Y');
        } else {
            $dateRange = Carbon::now()->format('d-m-Y');
        }

        return "Laporan_Cashflow_{$dateRange}.{$extension}";
    }
}

