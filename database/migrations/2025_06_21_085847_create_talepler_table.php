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
        Schema::create('talepler', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index('user_id');
            $table->unsignedInteger('acente_id')->nullable()->index('acente_id');
            $table->dateTime('talep_tarihi');
            $table->string('talep_kanali');
            $table->decimal('verilen_fiyat', 10)->nullable();
            $table->boolean('relance_yapildi')->default(false);
            $table->string('konfirme_durumu')->default('Bekliyor');
            $table->text('uzun_mesaj')->nullable();
            $table->text('message_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talepler');
    }
};
