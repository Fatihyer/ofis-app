<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPlanningProfile extends Model
{
    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_PER_JOB = 'per_job';
    public const TYPE_MIXED = 'mixed';

    protected $fillable = [
        'acente_id',
        'employment_type',
        'priority_level',
        'is_priority',
        'notes',
    ];

    protected $casts = [
        'is_priority' => 'boolean',
        'priority_level' => 'integer',
    ];

    public static function employmentTypes(): array
    {
        return [
            self::TYPE_MONTHLY => 'Mensuel',
            self::TYPE_PER_JOB => 'À la course',
            self::TYPE_MIXED => 'Mixte',
        ];
    }

    public function acente(): BelongsTo
    {
        return $this->belongsTo(Acente::class);
    }
}
