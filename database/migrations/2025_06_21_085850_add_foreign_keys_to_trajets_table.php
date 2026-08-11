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
        Schema::table('trajets', function (Blueprint $table) {
            $table->foreign(['transfer_id'], 'trajets_ibfk_1')->references(['id'])->on('transfers')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->dropForeign('trajets_ibfk_1');
        });
    }
};
