<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTachographTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tachograph_driver_cards')) {
            Schema::create('tachograph_driver_cards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('card_number', 32)->unique();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->string('driver_first_name')->nullable();
                $table->string('driver_last_name')->nullable();
                $table->string('preferred_language', 8)->nullable();
                $table->timestamp('last_imported_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_imports')) {
            Schema::create('tachograph_imports', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tachograph_driver_card_id')->nullable()->index();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->unsignedBigInteger('imported_by')->nullable()->index();
                $table->string('file_hash', 64)->unique();
                $table->string('original_filename')->nullable();
                $table->string('stored_path')->nullable();
                $table->string('card_number', 32)->nullable()->index();
                $table->string('driver_first_name')->nullable();
                $table->string('driver_last_name')->nullable();
                $table->text('vehicle_plates')->nullable();
                $table->string('issuing_authority')->nullable();
                $table->date('activity_from')->nullable();
                $table->date('activity_to')->nullable();
                $table->unsignedInteger('records_count')->default(0);
                $table->unsignedInteger('drives_count')->default(0);
                $table->unsignedInteger('matched_count')->default(0);
                $table->unsignedInteger('ignored_drives_count')->default(0);
                $table->unsignedInteger('total_driving_minutes')->default(0);
                $table->decimal('total_distance_km', 10, 2)->nullable();
                $table->unsignedBigInteger('duplicate_of_id')->nullable()->index();
                $table->timestamp('imported_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_drives')) {
            Schema::create('tachograph_drives', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tachograph_import_id')->index();
                $table->unsignedBigInteger('tachograph_driver_card_id')->nullable()->index();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->unsignedBigInteger('transfer_id')->nullable()->index();
                $table->string('card_number', 32)->nullable()->index();
                $table->date('source_date')->nullable()->index();
                $table->dateTime('started_at')->index();
                $table->dateTime('ended_at')->index();
                $table->unsignedInteger('duration_minutes')->default(0);
                $table->decimal('daily_distance_km', 10, 2)->nullable();
                $table->string('activity_uid', 64)->unique();
                $table->string('match_status', 32)->default('unmatched')->index();
                $table->integer('match_score')->nullable();
                $table->string('match_reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_daily_summaries')) {
            Schema::create('tachograph_daily_summaries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tachograph_import_id')->nullable()->index();
                $table->unsignedBigInteger('tachograph_driver_card_id')->nullable()->index();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->string('card_number', 32)->nullable()->index();
                $table->date('source_date')->index();
                $table->unsignedInteger('driving_minutes')->default(0);
                $table->unsignedInteger('work_minutes')->default(0);
                $table->unsignedInteger('availability_minutes')->default(0);
                $table->unsignedInteger('rest_minutes')->default(0);
                $table->unsignedInteger('unknown_minutes')->default(0);
                $table->decimal('daily_distance_km', 10, 2)->nullable();
                $table->string('summary_uid', 64)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_settings')) {
            Schema::create('tachograph_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tachograph_settings');
        Schema::dropIfExists('tachograph_daily_summaries');
        Schema::dropIfExists('tachograph_drives');
        Schema::dropIfExists('tachograph_imports');
        Schema::dropIfExists('tachograph_driver_cards');
    }
}
