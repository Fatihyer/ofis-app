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
        Schema::create('sirkets', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->string('info', 150)->nullable();
            $table->string('info2')->nullable();
            $table->string('tel', 20)->nullable();
            $table->string('email', 40)->nullable();
            $table->string('logo', 100)->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sirkets');
    }
};
