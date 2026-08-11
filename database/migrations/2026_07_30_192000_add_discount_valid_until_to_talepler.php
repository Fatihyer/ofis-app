<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            if (!Schema::hasColumn('talepler', 'discount_valid_until')) {
                $table->date('discount_valid_until')->nullable()->after('second_discount_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            if (Schema::hasColumn('talepler', 'discount_valid_until')) {
                $table->dropColumn('discount_valid_until');
            }
        });
    }
};
