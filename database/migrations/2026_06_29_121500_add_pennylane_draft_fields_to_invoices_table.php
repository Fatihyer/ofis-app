<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'pennylane_customer_invoice_id')) {
                $table->string('pennylane_customer_invoice_id')->nullable()->after('account_id');
            }
            if (!Schema::hasColumn('invoices', 'pennylane_customer_invoice_status')) {
                $table->string('pennylane_customer_invoice_status', 50)->nullable()->after('pennylane_customer_invoice_id');
            }
            if (!Schema::hasColumn('invoices', 'pennylane_synced_at')) {
                $table->timestamp('pennylane_synced_at')->nullable()->after('pennylane_customer_invoice_status');
            }
            if (!Schema::hasColumn('invoices', 'pennylane_payload')) {
                $table->longText('pennylane_payload')->nullable()->after('pennylane_synced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['pennylane_payload', 'pennylane_synced_at', 'pennylane_customer_invoice_status', 'pennylane_customer_invoice_id'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
