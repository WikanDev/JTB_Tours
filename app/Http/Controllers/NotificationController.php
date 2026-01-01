<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    
    public function index()
    {
        
        
        Auth::user()->unreadNotifications->markAsRead();

        
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            
            
            if (isset($notification->data['link'])) {
                return redirect($notification->data['link']);
            }
        }
        return back();
    }

    
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
    
    
    public function fetchLatest()
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications()->count();
        $latest = $user->unreadNotifications()->latest()->take(5)->get();
        
        return response()->json([
            'unread_count' => $unreadCount,
            'latest' => $latest
        ]);
    }
}
