<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'whatsapp-groups.view',
        'whatsapp-groups.manage',
        'whatsapp-leads.view',
        'whatsapp-leads.manage',
        'whatsapp-groups.reply',
    ];

    public function up(): void
    {
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->unique();
            $table->string('name');
            $table->unsignedInteger('participants_count')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('analysis_enabled')->default(true)->index();
            $table->dateTime('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('whatsapp_group_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('whatsapp_group_id');
            $table->string('external_id')->unique();
            $table->string('sender_external_id')->nullable()->index();
            $table->string('sender_phone')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('direction', 20)->index();
            $table->string('message_type', 40)->default('text')->index();
            $table->text('body')->nullable();
            $table->dateTime('sent_at')->nullable()->index();
            $table->string('analysis_status', 40)->default('pending')->index();
            $table->json('raw_payload')->nullable();
            $table->unsignedBigInteger('sent_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('whatsapp_group_id')->references('id')->on('whatsapp_groups')->cascadeOnDelete();
            $table->foreign('sent_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('whatsapp_group_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('whatsapp_group_id');
            $table->unsignedBigInteger('whatsapp_group_message_id')->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->unsignedTinyInteger('score')->default(0)->index();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('sender_name')->nullable();
            $table->unsignedInteger('passenger_count')->nullable();
            $table->unsignedInteger('number_of_vehicles')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->date('service_date')->nullable()->index();
            $table->time('service_time')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('dropoff_location')->nullable();
            $table->string('request_type')->nullable();
            $table->string('duration')->nullable();
            $table->string('language', 10)->nullable();
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('possible_duplicate_of_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('normalized_hash', 64)->nullable()->index();
            $table->timestamps();

            $table->foreign('whatsapp_group_id')->references('id')->on('whatsapp_groups')->cascadeOnDelete();
            $table->foreign('whatsapp_group_message_id')->references('id')->on('whatsapp_group_messages')->nullOnDelete();
            $table->foreign('possible_duplicate_of_id')->references('id')->on('whatsapp_group_leads')->nullOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('whatsapp_quick_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::table('whatsapp_quick_replies')->insert([
            'title' => 'Disponibilité',
            'body' => "Bonjour,\n\nNous sommes disponibles pour cette prestation.\n\nPouvez-vous nous confirmer les horaires et les adresses exactes ?\n\nMerci.",
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createPermissions();
    }

    public function down(): void
    {
        $this->deletePermissions();

        Schema::dropIfExists('whatsapp_quick_replies');
        Schema::dropIfExists('whatsapp_group_leads');
        Schema::dropIfExists('whatsapp_group_messages');
        Schema::dropIfExists('whatsapp_groups');
    }

    private function createPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', ['Superadmin', 'Admin'])->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    private function deletePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
