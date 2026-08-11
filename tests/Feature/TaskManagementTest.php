<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_assigned_user_can_see_own_task(): void
    {
        $creator = $this->user('manager@example.test');
        $assignee = $this->user('assignee@example.test');
        $task = Task::create([
            'title' => 'Préparer le dossier client',
            'status' => Task::STATUS_OPEN,
            'priority' => Task::PRIORITY_NORMAL,
            'created_by' => $creator->id,
        ]);
        $task->assignees()->attach($assignee->id, ['assigned_by' => $creator->id]);

        $response = $this->actingAs($assignee)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Préparer le dossier client');
    }

    public function test_user_does_not_see_unrelated_task(): void
    {
        $creator = $this->user('manager2@example.test');
        $otherUser = $this->user('other@example.test');

        Task::create([
            'title' => 'Tâche confidentielle',
            'status' => Task::STATUS_OPEN,
            'priority' => Task::PRIORITY_HIGH,
            'created_by' => $creator->id,
        ]);

        $response = $this->actingAs($otherUser)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertDontSee('Tâche confidentielle');
    }

    public function test_admin_can_see_all_tasks(): void
    {
        $creator = $this->user('manager3@example.test');
        $admin = $this->user('admin@example.test');
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->assignRole('Admin');

        Task::create([
            'title' => 'Tâche visible admin',
            'status' => Task::STATUS_OPEN,
            'priority' => Task::PRIORITY_URGENT,
            'created_by' => $creator->id,
        ]);

        $response = $this->actingAs($admin)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Tâche visible admin');
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => ucfirst(strtok($email, '@')),
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
