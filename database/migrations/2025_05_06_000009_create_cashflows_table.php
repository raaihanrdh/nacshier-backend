<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashflowsTable extends Migration
{
    public function up()
    {
        Schema::create('cashflows', function (Blueprint $table) {
            $table->string('cashflow_id', 8)->primary(); // contoh: CF000001
            $table->string('transaction_id', 8)->nullable();
            $table->date('date');
            $table->string('description', 100);
            $table->integer('amount');
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 30);
            $table->string('method', 30);
            $table->timestamps();

            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('transactions')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cashflows');
    }
}
