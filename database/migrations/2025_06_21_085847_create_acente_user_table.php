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
        Schema::create('acente_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->index('user_acente_user_id_index');
            $table->unsignedInteger('acente_id')->index('user_acente_acente_id_index');

            $table->primary(['acente_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acente_user');
    }
};
