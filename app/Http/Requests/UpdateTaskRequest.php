<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && ($this->user()?->can('update', $task) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(array_keys(Task::statuses()))],
            'priority' => ['required', Rule::in(array_keys(Task::priorities()))],
            'due_at' => ['nullable', 'date'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer', 'exists:users,id'],
            'taskable_type' => ['nullable', 'string', Rule::in(array_keys(Task::relatedTypeOptions()))],
            'taskable_id' => ['nullable', 'integer', 'min:1'],
            'taskable_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
