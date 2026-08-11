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
        Schema::create('fuel_excels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('vehicule_raw')->nullable();
            $table->string('card_raw')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->string('location')->nullable();
            $table->double('volume', null, 0)->nullable();
            $table->double('amount', null, 0)->nullable();
            $table->string('fuel_type', 100)->nullable();
            $table->json('original_data')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->integer('kilometrage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_excels');
    }
};
