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
        Schema::table('acente_emails', function (Blueprint $table) {
            $table->foreign(['acente_id'], 'acente_emails_ibfk_1')->references(['id'])->on('acentes')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acente_emails', function (Blueprint $table) {
            $table->dropForeign('acente_emails_ibfk_1');
        });
    }
};
