<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('whatsapp_group_id');
            $table->unsignedBigInteger('whatsapp_group_message_id');
            $table->date('sale_date')->index();
            $table->string('product', 40)->index();          // mouches | parisiens | disney | autre
            $table->unsignedInteger('qty_adult')->default(0);
            $table->unsignedInteger('qty_child')->default(0);
            $table->decimal('unit_price_adult', 8, 2)->nullable();
            $table->decimal('unit_price_child', 8, 2)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('customer_raw');                   // mesajda gecen ham isim
            $table->string('customer_key')->index();          // normalize edilmis eslestirme anahtari
            $table->unsignedBigInteger('acente_id')->nullable()->index();
            $table->text('source_line')->nullable();
            $table->string('source_hash', 40);
            $table->timestamps();

            $table->unique(['whatsapp_group_message_id', 'source_hash'], 'ticket_sales_message_line_unique');
            $table->foreign('whatsapp_group_id')->references('id')->on('whatsapp_groups')->cascadeOnDelete();
            $table->foreign('whatsapp_group_message_id')->references('id')->on('whatsapp_group_messages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sales');
    }
};
