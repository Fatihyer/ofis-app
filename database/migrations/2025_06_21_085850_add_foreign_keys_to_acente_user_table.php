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
        Schema::table('acente_user', function (Blueprint $table) {
            $table->foreign(['acente_id'], 'user_acente_acente_id_foreign')->references(['id'])->on('acentes')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'user_acente_user_id_foreign')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acente_user', function (Blueprint $table) {
            $table->dropForeign('user_acente_acente_id_foreign');
            $table->dropForeign('user_acente_user_id_foreign');
        });
    }
};
