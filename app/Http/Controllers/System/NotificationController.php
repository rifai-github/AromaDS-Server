<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(25);
        $users = User::all();

        return view('system.notifications.index', compact('notifications', 'users'));
    }

    public function create()
    {
        $users = User::all();
        return view('system.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'platform' => 'required|in:web,mobile,email,sms',
            'type' => 'required|in:info,success,warning,error,reminder',
            'action_url' => 'nullable|url',
            'send_to_all' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->send_to_all) {
                // Send notification to all users
                $users = User::where('status', 'active')->get();
                foreach ($users as $user) {
                    Notification::create([
                        'user_id' => $user->id,
                        'title' => $request->title,
                        'message' => $request->message,
                        'platform' => $request->platform,
                        'type' => $request->type,
                        'action_url' => $request->action_url,
                        'is_read' => false,
                        'created_by' => Auth::id()
                    ]);
                }
                $message = 'Notification sent to all users successfully';
            } else {
                // Send notification to specific user
                $notification = Notification::create([
                    'user_id' => $request->user_id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'platform' => $request->platform,
                    'type' => $request->type,
                    'action_url' => $request->action_url,
                    'is_read' => false,
                    'created_by' => Auth::id()
                ]);
                $message = 'Notification created successfully';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating notification: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Notification $notification)
    {
        $notification->load(['user', 'createdBy']);
        return view('system.notifications.show', compact('notification'));
    }

    public function edit(Notification $notification)
    {
        $users = User::all();
        return view('system.notifications.edit', compact('notification', 'users'));
    }

    public function update(Request $request, Notification $notification)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'platform' => 'required|in:web,mobile,email,sms',
            'type' => 'required|in:info,success,warning,error,reminder',
            'action_url' => 'nullable|url',
            'is_read' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $notification->update([
                'user_id' => $request->user_id,
                'title' => $request->title,
                'message' => $request->message,
                'platform' => $request->platform,
                'type' => $request->type,
                'action_url' => $request->action_url,
                'is_read' => $request->is_read ?? false,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification updated successfully',
                'data' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating notification: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Notification $notification)
    {
        try {
            $notification->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting notification: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Notification $notification)
    {
        try {
            $notification->markAsRead();
            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsUnread(Notification $notification)
    {
        try {
            $notification->markAsUnread();
            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as unread'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking notification as unread: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserNotifications(Request $request)
    {
        $user_id = $request->user_id ?? Auth::id();
        
        $query = Notification::where('user_id', $user_id);

        if ($request->filled('unread_only')) {
            $query->where('is_read', false);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->limit($request->limit ?? 50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user_id = $request->user_id ?? Auth::id();

        try {
            Notification::where('user_id', $user_id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_notifications = Notification::count();
        $unread_notifications = Notification::where('is_read', false)->count();
        $read_notifications = Notification::where('is_read', true)->count();

        $notifications_by_platform = Notification::selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->get();

        $notifications_by_type = Notification::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();

        $recent_notifications = Notification::with(['user', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $today_notifications = Notification::whereDate('created_at', today())->count();
        $this_week_notifications = Notification::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return view('system.notifications.dashboard', compact(
            'total_notifications',
            'unread_notifications',
            'read_notifications',
            'notifications_by_platform',
            'notifications_by_type',
            'recent_notifications',
            'today_notifications',
            'this_week_notifications'
        ));
    }

    public function bulkMarkAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Notification::whereIn('id', $request->notification_ids)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Selected notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error bulk marking notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Notification::whereIn('id', $request->notification_ids)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Selected notifications deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error bulk deleting notifications: ' . $e->getMessage()
            ], 500);
        }
    }
}
