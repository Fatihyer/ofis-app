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
        Schema::create('dovizals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kur_id');
            $table->dateTime('tarih');
            $table->double('value', 5, 4);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dovizals');
    }
};
