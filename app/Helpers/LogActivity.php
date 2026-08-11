<?php

namespace App\Helpers;

use Request;
use App\Models\LogActivity as LogActivityModel;
use Illuminate\Support\Facades\Auth;
class LogActivity
{
    public static function addToLog($subject, $post_id = null, $changes = null)
    {
        $log = [];
        $log['subject'] = $subject;
        $log['post_id'] = $post_id ?: 0;
        $log['changes'] = $changes ?? null;
        $log['url'] = Request::fullUrl();
        $log['method'] = Request::method();
        $log['ip'] = Request::ip();
      
        $log['user_id'] = Auth::guard('web')->check() ? Auth::guard('web')->user()->id : 1; // Ensure correct guard
        LogActivityModel::create($log);
    }

    public static function logActivityLists()
    {
        return LogActivityModel::latest()->get();
    }

    public static function PostActivityLists($id)
    {
        return LogActivityModel::where('post_id', $id)->get();
    }
}
