<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'tasks.view',
        'tasks.create',
        'tasks.update',
        'tasks.delete',
        'tasks.manage',
    ];

    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('status', 40)->default('open')->index();
            $table->string('priority', 40)->default('normal')->index();
            $table->dateTime('due_at')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->nullableMorphs('taskable');
            $table->string('taskable_label')->nullable();
            $table->dateTime('completed_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });

        $this->createPermissions();
    }

    public function down(): void
    {
        $this->deletePermissions();

        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');
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

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->whereIn('name', ['Superadmin', 'Admin'])
            ->pluck('id');

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

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
