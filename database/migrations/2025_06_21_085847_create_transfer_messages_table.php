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
        Schema::create('transfer_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('transfer_id')->index('transfer_messages_transfer_id_foreign');
            $table->string('to');
            $table->text('body');
            $table->string('sid')->unique('sid');
            $table->string('status')->nullable()->default('queued');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_messages');
    }
};
