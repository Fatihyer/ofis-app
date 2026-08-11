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
        Schema::table('groupinvoice_invoice', function (Blueprint $table) {
            $table->foreign(['groupinvoice_id'])->references(['id'])->on('groupinvoices')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['invoice_id'])->references(['id'])->on('invoices')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groupinvoice_invoice', function (Blueprint $table) {
            $table->dropForeign('groupinvoice_invoice_groupinvoice_id_foreign');
            $table->dropForeign('groupinvoice_invoice_invoice_id_foreign');
        });
    }
};
