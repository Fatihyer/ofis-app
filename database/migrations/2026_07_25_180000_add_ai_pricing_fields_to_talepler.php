<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            $table->decimal('ai_suggested_total', 10, 2)->nullable()->after('system_total');
            $table->timestamp('ai_suggested_at')->nullable()->after('ai_suggested_total');
            $table->string('ai_model_version', 50)->nullable()->after('ai_suggested_at');
        });

        Schema::table('talep_days', function (Blueprint $table) {
            $table->decimal('ai_suggested_price', 10, 2)->nullable()->after('system_price');
        });
    }

    public function down(): void
    {
        Schema::table('talep_days', function (Blueprint $table) {
            $table->dropColumn('ai_suggested_price');
        });

        Schema::table('talepler', function (Blueprint $table) {
            $table->dropColumn(['ai_suggested_total', 'ai_suggested_at', 'ai_model_version']);
        });
    }
};

