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
        Schema::create('acentes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique('name');
            $table->unsignedInteger('ulke_id')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->string('city')->nullable();
            $table->string('tittle')->nullable();
            $table->string('tel')->nullable();
            $table->string('email')->nullable();
            $table->string('vd')->nullable();
            $table->string('vdno')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('postal', 7)->nullable();
            $table->text('whatsapp')->nullable();
            $table->string('suivi')->nullable();
            $table->softDeletes();
            $table->boolean('airportshuttle')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acentes');
    }
};
