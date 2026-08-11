<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_at',
        'created_by',
        'taskable_type',
        'taskable_id',
        'taskable_label',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Ouverte',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_WAITING => 'En attente',
            self::STATUS_DONE => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_NORMAL => 'Normale',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }

    public static function relatedTypeOptions(): array
    {
        return [
            '' => 'Sans lien',
            \App\Models\Post::class => 'Dossier',
            \App\Models\Talep::class => 'Demande',
            \App\Models\Transfer::class => 'Transfert',
            \App\Models\Mission::class => 'Mission',
            \App\Models\Vehicule::class => 'Véhicule',
            \App\Models\Acente::class => 'Agence / prestataire',
            \App\Models\Client::class => 'Client',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (self::userCanManage($user)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('created_by', $user->id)
                ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->where('users.id', $user->id));
        });
    }

    public function isAssignedTo(User $user): bool
    {
        if (!$this->exists || !$user->exists) {
            return false;
        }

        return $this->assignees()->where('users.id', $user->id)->exists();
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function priorityLabel(): string
    {
        return self::priorities()[$this->priority] ?? $this->priority;
    }

    public static function userCanManage(User $user): bool
    {
        return $user->hasAnyRole(['Superadmin', 'Admin'])
            || $user->can('tasks.manage')
            || $user->can('tasks.update');
    }
}
