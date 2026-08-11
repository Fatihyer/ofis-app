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
        Schema::create('hotels', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('acente_id');
            $table->dateTime('from');
            $table->dateTime('to');
            $table->integer('sng')->nullable();
            $table->integer('dbl')->nullable();
            $table->integer('trp')->nullable();
            $table->integer('qtr')->nullable();
            $table->integer('fam')->nullable();
            $table->integer('servicetype_id');
            $table->integer('pax')->nullable();
            $table->integer('chd')->nullable();
            $table->string('chdyears', 20)->nullable();
            $table->string('comment', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('post_id');
            $table->integer('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
