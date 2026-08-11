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
        Schema::create('vehicules', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
            $table->string('capacity')->nullable();
            $table->string('plaka')->nullable();
            $table->string('yil')->nullable();
            $table->dateTime('control')->nullable();
            $table->dateTime('sigorta')->nullable();
            $table->text('sales')->nullable();
            $table->float('real', null, 0)->nullable();
            $table->float('enpanne', null, 0)->nullable();
            $table->string('hermes_uid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};
