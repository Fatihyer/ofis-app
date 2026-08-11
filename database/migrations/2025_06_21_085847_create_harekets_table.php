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
        Schema::create('harekets', function (Blueprint $table) {
            $table->increments('id');
            $table->text('aciklama')->nullable();
            $table->double('amount', 8, 2);
            $table->dateTime('tarih');
            $table->smallInteger('ab');
            $table->integer('kur_id');
            $table->integer('acente_id')->nullable();
            $table->integer('post_id')->nullable();
            $table->unsignedInteger('hareketable_id');
            $table->string('hareketable_type');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('payment_id')->nullable();
            $table->integer('urun_id')->nullable();
            $table->integer('adet')->nullable();
            $table->double('default_price', null, 0)->nullable();
            $table->integer('offset_id')->nullable();
            $table->integer('invoiceno')->nullable();

            $table->index(['hareketable_id', 'hareketable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harekets');
    }
};
