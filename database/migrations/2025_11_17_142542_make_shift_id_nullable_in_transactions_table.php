<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Make shift_id nullable to allow admin to create transactions without shift
            $table->string('shift_id', 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Revert shift_id to not nullable
            // Note: This might fail if there are transactions with null shift_id
            $table->string('shift_id', 8)->nullable(false)->change();
        });
    }
};
