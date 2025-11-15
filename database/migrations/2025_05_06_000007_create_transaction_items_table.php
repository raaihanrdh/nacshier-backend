<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->string('item_id', 8)->primary(); // contoh: TI000001
            $table->string('transaction_id', 8);
            $table->string('product_id', 8);
            $table->integer('quantity');
            $table->decimal('selling_price', 10, 2);
            $table->timestamps();

            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('transactions')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
