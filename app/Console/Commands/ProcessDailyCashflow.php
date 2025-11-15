<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Cashflow;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessDailyCashflow extends Command
{
    protected $signature = 'cashflow:process-daily {date? : Tanggal yang akan diproses (format Y-m-d)}';
    protected $description = 'Process daily transactions into cashflow records';

    public function handle()
    {
        // Ambil tanggal dari argument atau gunakan kemarin jika tidak ada
        $date = $this->argument('date') 
            ? Carbon::parse($this->argument('date'))->toDateString() 
            : Carbon::yesterday()->toDateString();
        
        $this->info("Memproses cashflow untuk tanggal: {$date}");
        
        // Periksa apakah sudah ada cashflow untuk tanggal ini dengan kategori 'sales'
        $existingCashflow = Cashflow::where('date', $date)
            ->where('category', 'sales')
            ->exists();
            
        if ($existingCashflow) {
            if (!$this->confirm("Cashflow untuk penjualan tanggal {$date} sudah ada. Apakah ingin menghapus dan memproses ulang?")) {
                $this->info('Proses dibatalkan.');
                return;
            }
            
            // Hapus cashflow yang sudah ada untuk kategori sales
            Cashflow::where('date', $date)
                ->where('category', 'sales')
                ->delete();
                
            $this->info("Cashflow lama untuk tanggal {$date} berhasil dihapus.");
        }
        
        // Dapatkan total transaksi Cash
        $cashTotal = Transaction::whereDate('transaction_time', $date)
            ->where('payment_method', 'Cash')
            ->sum('total_amount');
            
        // Dapatkan total transaksi QRIS
        $qrisTotal = Transaction::whereDate('transaction_time', $date)
            ->where('payment_method', 'Qris')
            ->sum('total_amount');
            
        // Jika ada transaksi Cash, buat entri cashflow
        if ($cashTotal > 0) {
            Cashflow::create([
                'date' => $date,
                'description' => "Pendapatan penjualan harian (Cash) - {$date}",
                'amount' => $cashTotal,
                'type' => 'income',
                'category' => 'sales',
                'method' => 'Cash',
            ]);
            
            $this->info("Berhasil mencatat cashflow pendapatan Cash: Rp {$cashTotal}");
        } else {
            $this->info("Tidak ada transaksi Cash untuk tanggal {$date}");
        }
        
        // Jika ada transaksi QRIS, buat entri cashflow
        if ($qrisTotal > 0) {
            Cashflow::create([
                'date' => $date,
                'description' => "Pendapatan penjualan harian (QRIS) - {$date}",
                'amount' => $qrisTotal,
                'type' => 'income',
                'category' => 'sales',
                'method' => 'QRIS',
            ]);
            
            $this->info("Berhasil mencatat cashflow pendapatan QRIS: Rp {$qrisTotal}");
        } else {
            $this->info("Tidak ada transaksi QRIS untuk tanggal {$date}");
        }
        
        $this->info('Proses cashflow harian selesai.');
    }
}