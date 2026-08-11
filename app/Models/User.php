<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens; // Eğer Sanctum kullanıyorsanız
// use Laravel\Passport\HasApiTokens; // Eğer Passport kullanıyorsanız
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;


class User extends Authenticatable
{
  use Notifiable, HasRoles, HasApiTokens, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','deleted_at','phone','last_login_at',
        'last_login_ip', 'last_seen_at'
      
    ];
    protected $dates = ['deleted_at', 'last_login_at', 'last_seen_at'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
  public function setPasswordAttribute($password)
  {   
    $this->attributes['password'] = Hash::make($password);
  }
  
  public function acentes()
    {
        return $this->belongsToMany('App\Models\Acente');
    }  
    
    public function revokeTokens() {
      $this->tokens()->delete();    
    }
    public function talepler()
{
    return $this->hasMany(Talep::class);
}

public function createdTasks()
{
    return $this->hasMany(Task::class, 'created_by');
}

public function assignedTasks()
{
    return $this->belongsToMany(Task::class, 'task_user')->withTimestamps();
}
}
