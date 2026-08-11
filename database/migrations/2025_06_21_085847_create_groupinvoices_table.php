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
        Schema::create('groupinvoices', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('tarih');
            $table->string('resmi', 10)->nullable();
            $table->timestamps();
            $table->integer('sirket_id')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupinvoices');
    }
};
