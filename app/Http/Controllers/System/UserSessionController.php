<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = UserSession::with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', "%{$request->ip_address}%");
        }

        $userSessions = $query->orderBy('last_activity', 'desc')->paginateStd(25);

        // Get filter options
        $users = User::where('is_active', true)->get();

        return view('system.user-sessions.index', compact('userSessions', 'users'));
    }

    public function show($id)
    {
        $userSession = UserSession::with(['user'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $userSession
            ]);
        }

        return view('system.user-sessions.show', compact('userSession'));
    }

    public function destroy($id)
    {
        $userSession = UserSession::findOrFail($id);

        try {
            $userSession->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session terminated successfully'
                ]);
            }

            return redirect()->route('system.user-sessions.index')
                           ->with('success', 'Session terminated successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error terminating session: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error terminating session: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:user_sessions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = UserSession::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully terminated {$count} session(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error terminating sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserSessions($userId)
    {
        $sessions = UserSession::where('user_id', $userId)
                              ->orderBy('last_activity', 'desc')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function getActiveSessions()
    {
        $sessions = UserSession::active()
                              ->with(['user'])
                              ->orderBy('last_activity', 'desc')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function getExpiredSessions()
    {
        $sessions = UserSession::expired()
                              ->with(['user'])
                              ->orderBy('last_activity', 'desc')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function cleanupExpiredSessions()
    {
        try {
            $count = UserSession::expired()->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$count} expired session(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cleaning up expired sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSessionStatistics()
    {
        $statistics = [
            'total_sessions' => UserSession::count(),
            'active_sessions' => UserSession::active()->count(),
            'expired_sessions' => UserSession::expired()->count(),
            'unique_users' => UserSession::distinct('user_id')->count('user_id'),
            'unique_ips' => UserSession::distinct('ip_address')->count('ip_address'),
            'average_duration' => UserSession::whereNotNull('last_activity')
                                           ->selectRaw('AVG(GREATEST((last_activity - UNIX_TIMESTAMP(created_at)) / 60, 0)) as avg_duration')
                                           ->value('avg_duration')
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function export()
    {
        $userSessions = UserSession::with(['user'])->get();

        $csvData = [];
        $csvData[] = ['User', 'Session ID', 'IP Address', 'User Agent', 'Last Activity', 'Duration', 'Status', 'Created At'];

        foreach ($userSessions as $session) {
            $csvData[] = [
                $session->user->name ?? 'N/A',
                $session->session_id,
                $session->ip_address,
                $session->user_agent,
                $session->last_activity->format('Y-m-d H:i:s'),
                $session->formatted_duration,
                $session->status_text,
                $session->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'user_sessions_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
