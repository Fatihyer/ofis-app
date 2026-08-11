<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->noContent();
    }

    public function deleteAll(Request $request)
    {
        $request->user()->notifications()->delete();
        return response()->noContent();
    }
}
