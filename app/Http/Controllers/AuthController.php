<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CashierShift;
use App\Http\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    use ApiResponse;
    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Username atau password salah', 401);
        }

        // Hapus token lama jika ada (opsional untuk keamanan)
        $user->tokens()->delete();

        $token = $user->createToken('login_token')->plainTextToken;

        $response = [
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'user_id' => $user->user_id,
                'id' => $user->user_id, // Backward compatibility
                'username' => $user->username,
                'name' => $user->name,
                'level' => $user->level,
            ]
        ];

        // Untuk kasir, buat atau ambil shift berdasarkan waktu
        if ($user->level === 'kasir' || $user->level === 'user') {
            try {
                $shift = $this->getOrCreateShiftForUser($user->user_id);
                if ($shift) {
                    $response['shift'] = [
                        'shift_id' => $shift->shift_id,
                        'start_time' => $shift->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $shift->end_time ? $shift->end_time->format('Y-m-d H:i:s') : null,
                    ];
                }
            } catch (\Exception $e) {
                // Log error tapi jangan gagalkan login
                \Log::error('Error creating shift on login: ' . $e->getMessage());
            }
        }

        return response()->json($response);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'Logout berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to logout', 500);
        }
    }

    // GANTI PASSWORD
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                // Tambahkan aturan keamanan tambahan
                'regex:/[a-z]/',      // huruf kecil
                'regex:/[A-Z]/',      // huruf besar
                'regex:/[0-9]/',      // angka
                'regex:/[@$!%*#?&]/'  // simbol spesial
            ],
        ], [
            'new_password.regex' => 'Password baru harus mengandung huruf kecil, huruf besar, angka, dan simbol.'
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Password saat ini salah', 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Logout semua sesi jika password diganti
        $user->tokens()->delete();

        return $this->successResponse(null, 'Password berhasil diubah. Silakan login ulang.');
    }

    public function getUserProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            return $this->successResponse([
                'user_id' => $user->user_id,
                'name' => $user->name,
                'username' => $user->username,
                'level' => $user->level
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user profile', 500);
        }
    }

    /**
     * Helper method untuk get atau create shift berdasarkan waktu
     * Shift 1: 09:00 - 15:00
     * Shift 2: 15:00 - 22:00 (atau sampai 10:00 hari berikutnya)
     */
    private function getOrCreateShiftForUser($userId)
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentHour = (int) $now->format('H');
        $currentDate = $now->format('Y-m-d');

        // Tentukan shift berdasarkan jam
        $shiftNumber = $this->getShiftNumber($currentHour);
        
        if (!$shiftNumber) {
            return null; // Tidak ada shift aktif (jam 10:00 - 09:00)
        }

        // Tentukan waktu mulai shift
        $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate . ' ' . $this->getShiftStartTime($shiftNumber), 'Asia/Jakarta');
        
        // Handle shift 2 yang bisa sampai jam 10:00 hari berikutnya
        if ($shiftNumber == 2 && $currentHour < 10) {
            // Shift 2 dari hari kemarin (15:00 kemarin - 10:00 hari ini)
            $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', $now->copy()->subDay()->format('Y-m-d') . ' 15:00:00', 'Asia/Jakarta');
        }

        // Cek apakah ada shift aktif untuk user ini
        $activeShift = CashierShift::where('user_id', $userId)
            ->whereNull('end_time')
            ->whereBetween('start_time', [
                $shiftStart->copy()->subDay(),
                $shiftStart->copy()->addDay()
            ])
            ->first();

        // Jika ada shift aktif, cek apakah masih dalam periode yang sama
        if ($activeShift) {
            $activeShiftStart = Carbon::parse($activeShift->start_time, 'Asia/Jakarta');
            $activeShiftHour = (int) $activeShiftStart->format('H');
            $activeShiftNumber = $this->getShiftNumber($activeShiftHour);

            // Jika shift aktif masih dalam periode yang sama, return shift tersebut
            if ($activeShiftNumber == $shiftNumber) {
                return $activeShift;
            }

            // Tutup shift lama jika sudah lewat periode
            $activeShift->update(['end_time' => $shiftStart->copy()->subMinute()]);
        }

        // Buat shift baru
        return CashierShift::create([
            'user_id' => $userId,
            'start_time' => $shiftStart,
        ]);
    }

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

    private function getShiftStartTime($shiftNumber)
    {
        return $shiftNumber == 1 ? '09:00:00' : '15:00:00';
    }
}
