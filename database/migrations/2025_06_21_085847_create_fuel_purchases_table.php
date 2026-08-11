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
        Schema::create('fuel_purchases', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fuel_card_id');
            $table->decimal('amount', 10);
            $table->timestamp('purchase_date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('kilometer');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('vehicule_id');
            $table->text('comment')->nullable();
            $table->integer('acente_id')->nullable();
            $table->integer('volume')->nullable();
            $table->string('produit', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_purchases');
    }
};
