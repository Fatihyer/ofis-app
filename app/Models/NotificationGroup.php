<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationGroup extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'active'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_group_user')->withTimestamps();
    }

    public function alertTypes()
    {
        return $this->belongsToMany(NotificationAlertType::class, 'notification_alert_type_group')->withTimestamps();
    }
}
