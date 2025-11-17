<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('product_id', 8)->primary(); // contoh: PR000001
            $table->string('name', 75);
            $table->string('category_id', 8)->nullable();
            $table->string('description', 255)->nullable();
            $table->integer('capital_price')->nullable();
            $table->integer('selling_price');
            $table->integer('stock')->default(0);
            $table->text('image_data')->nullable(); // Base64 image data
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                ->references('category_id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
