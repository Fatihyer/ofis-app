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
        Schema::create('acente_firma', function (Blueprint $table) {
            $table->unsignedInteger('acente_id')->index();
            $table->unsignedInteger('firma_id')->index();

            $table->primary(['acente_id', 'firma_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acente_firma');
    }
};
