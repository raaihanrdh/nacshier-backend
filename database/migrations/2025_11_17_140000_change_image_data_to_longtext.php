<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Change image_data from TEXT to LONGTEXT to support larger base64 images
            if (Schema::hasColumn('products', 'image_data')) {
                $table->longText('image_data')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revert back to TEXT if needed
            if (Schema::hasColumn('products', 'image_data')) {
                $table->text('image_data')->nullable()->change();
            }
        });
    }
};

