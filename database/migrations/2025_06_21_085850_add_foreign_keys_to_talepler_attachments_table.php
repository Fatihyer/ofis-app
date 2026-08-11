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
        Schema::table('talepler_attachments', function (Blueprint $table) {
            $table->foreign(['talep_id'], 'talepler_attachments_ibfk_1')->references(['id'])->on('talepler')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talepler_attachments', function (Blueprint $table) {
            $table->dropForeign('talepler_attachments_ibfk_1');
        });
    }
};
