<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $users = User::select('user_id', 'name', 'username', 'level')->get();
            return $this->successResponse($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users', 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->messages([
                        'password.min' => 'Password harus minimal 8 karakter.',
                        'password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
                        'password.numbers' => 'Password harus mengandung angka.',
                        'password.symbols' => 'Password harus mengandung simbol (!@#$%^&*).',
                    ])
            ],
            'level' => 'required|in:kasir,admin',
        ], [
            'password.min' => 'Password harus minimal 8 karakter.',
            'password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
            'password.numbers' => 'Password harus mengandung angka.',
            'password.symbols' => 'Password harus mengandung simbol (!@#$%^&*).',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'level' => $request->level,
            ]);

            $userData = [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'username' => $user->username,
                'level' => $user->level,
            ];

            return $this->successResponse($userData, 'User berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    public function show($user_id)
    {
        try {
            $user = User::select('user_id', 'name', 'username', 'level')->findOrFail($user_id);
            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('User not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user', 500);
        }
    }

    public function update(Request $request, $user_id)
    {
        try {
            $user = User::findOrFail($user_id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'level' => 'sometimes|required|in:kasir,admin',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $user->update($request->only(['name', 'level']));

            $userData = [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'username' => $user->username,
                'level' => $user->level,
            ];

            return $this->successResponse($userData, 'Data user berhasil diperbarui');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('User not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($user_id)
    {
        try {
            if (auth()->user()->user_id == $user_id) {
                return $this->errorResponse('Anda tidak dapat menghapus akun sendiri', 422);
            }

            $user = User::findOrFail($user_id);
            $user->delete();

            return $this->successResponse(null, 'User berhasil dihapus');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('User not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    public function resetPassword(Request $request, $user_id)
    {
        try {
            $user = User::findOrFail($user_id);

            $validator = Validator::make($request->all(), [
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->messages([
                            'password.min' => 'Password harus minimal 8 karakter.',
                            'password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
                            'password.numbers' => 'Password harus mengandung angka.',
                            'password.symbols' => 'Password harus mengandung simbol (!@#$%^&*).',
                        ])
                ],
            ], [
                'password.min' => 'Password harus minimal 8 karakter.',
                'password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
                'password.numbers' => 'Password harus mengandung angka.',
                'password.symbols' => 'Password harus mengandung simbol (!@#$%^&*).',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            $user->tokens()->delete();

            return $this->successResponse(null, 'Password user berhasil diubah');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('User not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }
}
