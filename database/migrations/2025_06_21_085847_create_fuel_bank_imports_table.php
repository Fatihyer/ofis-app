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
        Schema::create('fuel_bank_imports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('acente_id')->nullable();
            $table->date('date')->nullable();
            $table->text('operation')->nullable();
            $table->decimal('debit', 12)->nullable();
            $table->decimal('credit', 12)->nullable();
            $table->string('currency', 10)->nullable();
            $table->date('value_date')->nullable();
            $table->string('interbank_label')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->integer('offset_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_bank_imports');
    }
};
