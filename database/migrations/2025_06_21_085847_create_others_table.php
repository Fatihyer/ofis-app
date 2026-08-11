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
        Schema::create('others', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('acente_id');
            $table->dateTime('from');
            $table->dateTime('to')->nullable();
            $table->integer('pax')->nullable();
            $table->string('comment', 100)->nullable();
            $table->timestamps();
            $table->integer('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('others');
    }
};
