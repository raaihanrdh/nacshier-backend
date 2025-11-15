<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->string('transaction_id', 8)->primary(); // contoh: TR000001
            $table->string('shift_id', 8);
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('transaction_time');
            $table->string('payment_method', 20);
            $table->timestamps();

            $table->foreign('shift_id')
                  ->references('shift_id')
                  ->on('cashier_shifts')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
