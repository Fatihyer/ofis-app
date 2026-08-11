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
        Schema::create('groupinvoice_invoice', function (Blueprint $table) {
            $table->unsignedInteger('groupinvoice_id')->index();
            $table->unsignedInteger('invoice_id')->index();

            $table->primary(['groupinvoice_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupinvoice_invoice');
    }
};
