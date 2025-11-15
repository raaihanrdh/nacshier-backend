<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{
    use ApiResponse;

    /**
     * Get or create shift berdasarkan waktu saat ini
     * Shift 1: 09:00 - 15:00
     * Shift 2: 15:00 - 10:00 (hari berikutnya)
     */
    public function getOrCreateShift(Request $request)
    {
        try {
            $user = $request->user();
            $now = Carbon::now('Asia/Jakarta');
            $currentHour = (int) $now->format('H');
            $currentDate = $now->format('Y-m-d');

            // Tentukan shift berdasarkan jam
            $shiftNumber = $this->getShiftNumber($currentHour);
            
            if (!$shiftNumber) {
                return $this->errorResponse('Tidak ada shift aktif pada jam ini. Shift 1: 09:00-15:00, Shift 2: 15:00-10:00 (hari berikutnya)', 400);
            }

            // Tentukan waktu mulai shift
            if ($shiftNumber == 1) {
                // Shift 1: mulai jam 09:00 hari ini
                $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' 09:00:00', 'Asia/Jakarta');
            } else {
                // Shift 2: mulai jam 15:00
                if ($currentHour >= 15) {
                    // Jika jam >= 15:00, shift 2 mulai hari ini jam 15:00
                    $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' 15:00:00', 'Asia/Jakarta');
                } else {
                    // Jika jam < 10:00, shift 2 mulai hari kemarin jam 15:00
                    $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', $now->copy()->subDay()->format('Y-m-d') . ' 15:00:00', 'Asia/Jakarta');
                }
            }

            // Cek apakah ada shift aktif untuk user ini pada periode shift saat ini
            $activeShift = CashierShift::where('user_id', $user->user_id)
                ->whereNull('end_time')
                ->whereBetween('start_time', [
                    $shiftStart->copy()->subDay(), // Cek juga shift dari hari sebelumnya
                    $shiftEnd->copy()->addDay()
                ])
                ->first();

            // Jika ada shift aktif, cek apakah masih dalam periode shift yang sama
            if ($activeShift) {
                $activeShiftStart = Carbon::parse($activeShift->start_time, 'Asia/Jakarta');
                $activeShiftDate = $activeShiftStart->format('Y-m-d');
                $activeShiftHour = (int) $activeShiftStart->format('H');
                $activeShiftNumber = $this->getShiftNumber($activeShiftHour);

                // Jika shift aktif masih dalam periode yang sama, return shift tersebut
                if ($activeShiftNumber == $shiftNumber && 
                    $activeShiftStart->between($shiftStart->copy()->subDay(), $shiftEnd->copy()->addDay())) {
                    return $this->successResponse([
                        'shift_id' => $activeShift->shift_id,
                        'shift_number' => $shiftNumber,
                        'start_time' => $activeShift->start_time,
                        'end_time' => $activeShift->end_time,
                        'is_existing' => true
                    ], 'Shift aktif ditemukan');
                }

                // Jika shift aktif sudah lewat periode, tutup shift lama
                if ($activeShiftStart->lt($shiftStart) || $activeShiftNumber != $shiftNumber) {
                    $activeShift->update(['end_time' => $shiftStart->copy()->subMinute()]);
                    Log::info("Shift lama ditutup: {$activeShift->shift_id}");
                }
            }

            // Tutup semua shift aktif yang sudah lewat waktu
            $this->closeExpiredShifts($user->user_id, $now);

            // Buat shift baru
            $newShift = CashierShift::create([
                'user_id' => $user->user_id,
                'start_time' => $shiftStart,
            ]);

            return $this->successResponse([
                'shift_id' => $newShift->shift_id,
                'shift_number' => $shiftNumber,
                'start_time' => $newShift->start_time->format('Y-m-d H:i:s'),
                'end_time' => null,
                'is_existing' => false
            ], 'Shift baru dibuat');
        } catch (\Exception $e) {
            Log::error('Get or create shift error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mendapatkan atau membuat shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tentukan nomor shift berdasarkan jam
     */
    private function getShiftNumber($hour)
    {
        // Shift 1: 09:00 - 15:00
        // Shift 2: 15:00 - 10:00 (hari berikutnya)
        if ($hour >= 9 && $hour < 15) {
            return 1;
        } elseif ($hour >= 15 || $hour < 10) {
            // Shift 2: mulai jam 15:00 sampai jam 10:00 hari berikutnya
            return 2;
        }
        return null; // Tidak ada shift aktif (jam 10:00 - 09:00)
    }

    /**
     * Get waktu mulai shift
     */
    private function getShiftStartTime($shiftNumber)
    {
        if ($shiftNumber == 1) {
            return '09:00:00';
        } elseif ($shiftNumber == 2) {
            return '15:00:00';
        }
        return '09:00:00';
    }

    /**
     * Get waktu akhir shift
     */
    private function getShiftEndTime($shiftNumber)
    {
        if ($shiftNumber == 1) {
            return '15:00:00';
        } elseif ($shiftNumber == 2) {
            return '10:00:00'; // Shift 2 berakhir jam 10:00 hari berikutnya
        }
        return '15:00:00';
    }

    /**
     * Tutup shift yang sudah lewat waktu
     */
    private function closeExpiredShifts($userId, $now)
    {
        $activeShifts = CashierShift::where('user_id', $userId)
            ->whereNull('end_time')
            ->get();

        foreach ($activeShifts as $shift) {
            $shiftStart = Carbon::parse($shift->start_time, 'Asia/Jakarta');
            $shiftDate = $shiftStart->format('Y-m-d');
            $shiftHour = (int) $shiftStart->format('H');
            $shiftNumber = $this->getShiftNumber($shiftHour);

            if (!$shiftNumber) continue;

            // Tentukan waktu akhir shift
            if ($shiftNumber == 1) {
                // Shift 1 berakhir jam 15:00 hari yang sama
                $shiftEnd = Carbon::createFromFormat('Y-m-d H:i:s', $shiftDate . ' 15:00:00', 'Asia/Jakarta');
            } else {
                // Shift 2 berakhir jam 10:00 hari berikutnya
                $nextDay = $shiftStart->copy()->addDay();
                $shiftEnd = Carbon::createFromFormat('Y-m-d H:i:s', $nextDay->format('Y-m-d') . ' 10:00:00', 'Asia/Jakarta');
            }

            // Jika sudah lewat waktu akhir shift, tutup shift
            if ($now->gte($shiftEnd)) {
                $shift->update(['end_time' => $shiftEnd]);
                Log::info("Shift expired ditutup: {$shift->shift_id}");
            }
        }
    }

    /**
     * Get shift aktif user
     */
    public function getActiveShift(Request $request)
    {
        try {
            $user = $request->user();
            $activeShift = CashierShift::getActiveShift($user->user_id);

            if (!$activeShift) {
                return $this->errorResponse('Tidak ada shift aktif', 404);
            }

            $shiftStart = Carbon::parse($activeShift->start_time, 'Asia/Jakarta');
            $shiftHour = (int) $shiftStart->format('H');
            $shiftNumber = $this->getShiftNumber($shiftHour);

            return $this->successResponse([
                'shift_id' => $activeShift->shift_id,
                'shift_number' => $shiftNumber,
                'start_time' => $activeShift->start_time,
                'end_time' => $activeShift->end_time,
            ], 'Shift aktif ditemukan');
        } catch (\Exception $e) {
            Log::error('Get active shift error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mendapatkan shift aktif', 500);
        }
    }

    /**
     * Tutup shift secara manual
     */
    public function closeShift(Request $request)
    {
        try {
            $user = $request->user();
            $activeShift = CashierShift::getActiveShift($user->user_id);

            if (!$activeShift) {
                return $this->errorResponse('Tidak ada shift aktif untuk ditutup', 404);
            }

            $activeShift->clockOut();

            return $this->successResponse([
                'shift_id' => $activeShift->shift_id,
                'end_time' => $activeShift->end_time,
            ], 'Shift berhasil ditutup');
        } catch (\Exception $e) {
            Log::error('Close shift error: ' . $e->getMessage());
            return $this->errorResponse('Gagal menutup shift', 500);
        }
    }
}

