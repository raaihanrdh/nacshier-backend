<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop old image_path column if exists
            if (Schema::hasColumn('products', 'image_path')) {
                $table->dropColumn('image_path');
            }
            
            // Add new image_data column for base64 storage
            if (!Schema::hasColumn('products', 'image_data')) {
                $table->text('image_data')->nullable()->after('stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop image_data column
            if (Schema::hasColumn('products', 'image_data')) {
                $table->dropColumn('image_data');
            }
            
            // Restore image_path column
            if (!Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path', 255)->nullable()->after('stock');
            }
        });
    }
};
