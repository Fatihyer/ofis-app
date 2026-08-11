<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LogActivity extends Model
{
    protected $fillable = [
        'subject', 'post_id', 'url', 'method', 'ip', 'agent', 'user_id', 'changes'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
