<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('talep_days', 'decoucher')) {
            Schema::table('talep_days', function (Blueprint $table) {
                $table->decimal('decoucher', 10, 2)->nullable()->after('toll_currency');
            });
        }

        if (! Schema::hasColumn('talep_days', 'parking')) {
            Schema::table('talep_days', function (Blueprint $table) {
                $table->decimal('parking', 10, 2)->nullable()->after('decoucher');
            });
        }

        if (! Schema::hasColumn('talep_days', 'checkpoint')) {
            Schema::table('talep_days', function (Blueprint $table) {
                $table->decimal('checkpoint', 10, 2)->nullable()->after('parking');
            });
        }
    }

    public function down(): void
    {
        foreach (['checkpoint', 'parking', 'decoucher'] as $column) {
            if (Schema::hasColumn('talep_days', $column)) {
                Schema::table('talep_days', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
