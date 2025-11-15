<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id', 8)->primary(); // ID unik untuk user, contoh: US000001
            $table->string('name', 50); // Nama lengkap, maksimal 50 karakter
            $table->string('username', 30)->unique(); // Username unik, maksimal 30 karakter
            $table->string('password', 255); // Password terenkripsi
            $table->enum('level', ['admin', 'kasir'])->default('kasir'); // Level akses user
            $table->rememberToken(); // Token untuk fitur "remember me"
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
