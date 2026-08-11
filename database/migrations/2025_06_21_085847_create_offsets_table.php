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
        Schema::create('offsets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('aciklama')->nullable();
            $table->integer('b_acente_id');
            $table->integer('a_acente_id');
            $table->timestamps();
            $table->dateTime('tarih')->useCurrent();
            $table->integer('transfer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offsets');
    }
};
