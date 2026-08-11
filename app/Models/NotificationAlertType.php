<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAlertType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'level', 'active'];

    public function groups()
    {
        return $this->belongsToMany(NotificationGroup::class, 'notification_alert_type_group')->withTimestamps();
    }
}
