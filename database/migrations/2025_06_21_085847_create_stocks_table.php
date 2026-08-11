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
        Schema::create('stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id')->nullable();
            $table->integer('adet');
            $table->integer('a_acente_id');
            $table->integer('b_acente_id');
            $table->integer('urun_id');
            $table->string('aciklama')->nullable();
            $table->integer('ab');
            $table->dateTime('tarih');
            $table->timestamps();
            $table->integer('credit');
            $table->integer('offset_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
