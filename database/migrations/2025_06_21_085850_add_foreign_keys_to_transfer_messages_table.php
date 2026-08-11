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
        Schema::table('transfer_messages', function (Blueprint $table) {
            $table->foreign(['transfer_id'])->references(['id'])->on('transfers')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_messages', function (Blueprint $table) {
            $table->dropForeign('transfer_messages_transfer_id_foreign');
        });
    }
};
