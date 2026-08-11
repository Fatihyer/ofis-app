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
        Schema::create('fromexcels', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('tarih');
            $table->string('aciklama', 120);
            $table->string('etiket', 25);
            $table->double('tutar', null, 0);
            $table->string('dekont', 40)->unique('dekont');
            $table->integer('acente_id');
            $table->integer('kur_id');
            $table->integer('offset_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fromexcels');
    }
};
