<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            $table->decimal('discount_price', 12, 2)->nullable()->after('system_total');
            $table->decimal('second_discount_price', 12, 2)->nullable()->after('discount_price');
        });
    }

    public function down(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            $table->dropColumn(['discount_price', 'second_discount_price']);
        });
    }
};
