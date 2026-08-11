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
        Schema::table('talep_mailleri', function (Blueprint $table) {
            $table->foreign(['talep_id'])->references(['id'])->on('talepler')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talep_mailleri', function (Blueprint $table) {
            $table->dropForeign('talep_mailleri_talep_id_foreign');
        });
    }
};
