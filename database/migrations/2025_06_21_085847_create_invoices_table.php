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
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sirket_id')->default(1);
            $table->integer('kur_id');
            $table->integer('acente_id');
            $table->integer('post_id');
            $table->double('amount', null, 0)->nullable();
            $table->string('detail', 1000);
            $table->dateTime('tarih');
            $table->string('resmi', 10)->nullable();
            $table->string('avoir', 11)->nullable();
            $table->smallInteger('lang')->nullable();
            $table->smallInteger('yazi')->nullable();
            $table->timestamps();
            $table->integer('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
