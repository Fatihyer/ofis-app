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
        Schema::table('acente_firma', function (Blueprint $table) {
            $table->foreign(['acente_id'])->references(['id'])->on('acentes')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['firma_id'])->references(['id'])->on('firmas')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acente_firma', function (Blueprint $table) {
            $table->dropForeign('acente_firma_acente_id_foreign');
            $table->dropForeign('acente_firma_firma_id_foreign');
        });
    }
};
