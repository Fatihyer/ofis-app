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
        Schema::create('missions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transfer_id')->unique();
            $table->dateTime('hareket')->nullable();
            $table->dateTime('surplace')->nullable();
            $table->dateTime('taked')->nullable();
            $table->dateTime('finish')->nullable();
            $table->dateTime('finish_depot')->nullable();
            $table->integer('depart_km');
            $table->json('startlocalisation')->nullable();
            $table->json('surPlacelocalisation')->nullable();
            $table->bigInteger('finish_km')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('start_user_id')->nullable();
            $table->timestamps();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('finish_confirmed_at')->nullable();
            $table->tinyInteger('cleaning_status')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
