<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->string('shift_id', 8)->primary(); // contoh: SF000001
            $table->string('user_id', 8);
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};