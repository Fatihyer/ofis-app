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
        Schema::create('transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->timestamps();
            $table->dateTime('ofis_start')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedInteger('servicetype_id');
            $table->string('from')->nullable();
            $table->string('target')->nullable();
            $table->softDeletes();
            $table->unsignedInteger('pax');
            $table->text('comments')->nullable();
            $table->unsignedInteger('vehicule_id');
            $table->integer('driver_id')->nullable();
            $table->integer('status_id')->nullable();
            $table->integer('client_status_id')->nullable();
            $table->string('dcomments', 350)->nullable();
            $table->boolean('mission')->default(false);
            $table->tinyInteger('accueil')->nullable();
            $table->integer('km')->default(0);
            $table->string('mission_url', 11)->nullable();
            $table->integer('conge')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->char('confirmation_token', 36)->nullable()->unique('confirmation_token');
            $table->boolean('is_confirmed')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
