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
        Schema::create('invoicedetails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->integer('post_id');
            $table->string('comments')->nullable();
            $table->integer('kdv_id')->nullable();
            $table->double('amount', null, 0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoicedetails');
    }
};
