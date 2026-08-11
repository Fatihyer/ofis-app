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
        Schema::create('talep_mailleri', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('talep_id')->nullable()->index('talep_id');
            $table->string('mail_baslik');
            $table->text('mail_icerik')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talep_mailleri');
    }
};
