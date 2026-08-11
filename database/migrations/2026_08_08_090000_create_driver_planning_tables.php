<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_planning_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('acente_id')->unique();
            $table->string('employment_type', 30)->default('per_job')->index();
            $table->unsignedTinyInteger('priority_level')->default(5)->index();
            $table->boolean('is_priority')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('acente_id')->references('id')->on('acentes')->cascadeOnDelete();
        });

        Schema::create('driver_vehicle_capabilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('acente_id');
            $table->unsignedInteger('vehicule_id');
            $table->boolean('preferred')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['acente_id', 'vehicule_id']);
            $table->foreign('acente_id')->references('id')->on('acentes')->cascadeOnDelete();
            $table->foreign('vehicule_id')->references('id')->on('vehicules')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_vehicle_capabilities');
        Schema::dropIfExists('driver_planning_profiles');
    }
};
