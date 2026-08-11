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
        Schema::table('talepler', function (Blueprint $table) {
            $table->foreign(['acente_id'], 'talepler_ibfk_1')->references(['id'])->on('acentes')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['user_id'], 'talepler_ibfk_2')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            $table->dropForeign('talepler_ibfk_1');
            $table->dropForeign('talepler_ibfk_2');
        });
    }
};
