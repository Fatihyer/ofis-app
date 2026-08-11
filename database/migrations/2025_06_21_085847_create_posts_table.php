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
        Schema::create('posts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title', 100);
            $table->string('body', 1000)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('acente_id');
            $table->integer('pax')->nullable();
            $table->integer('child')->nullable();
            $table->smallInteger('resmi')->nullable();
            $table->smallInteger('status_id');
            $table->integer('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
