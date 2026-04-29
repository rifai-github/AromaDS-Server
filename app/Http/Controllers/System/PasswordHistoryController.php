<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PasswordHistory::with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $passwordHistories = $query->orderBy('created_at', 'desc')->paginate(25);

        // Get filter options
        $users = User::where('is_active', true)->get();

        return view('system.password-histories.index', compact('passwordHistories', 'users'));
    }

    public function show($id)
    {
        $passwordHistory = PasswordHistory::with(['user'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $passwordHistory
            ]);
        }

        return view('system.password-histories.show', compact('passwordHistory'));
    }

    public function getUserPasswordHistory($userId)
    {
        $passwordHistories = PasswordHistory::where('user_id', $userId)
                                          ->orderBy('created_at', 'desc')
                                          ->get();

        return response()->json([
            'success' => true,
            'data' => $passwordHistories
        ]);
    }

    public function getRecentPasswordChanges($days = 30)
    {
        $passwordHistories = PasswordHistory::with(['user'])
                                          ->where('created_at', '>=', now()->subDays($days))
                                          ->orderBy('created_at', 'desc')
                                          ->get();

        return response()->json([
            'success' => true,
            'data' => $passwordHistories
        ]);
    }

    public function export()
    {
        $passwordHistories = PasswordHistory::with(['user'])->get();

        $csvData = [];
        $csvData[] = ['User', 'Password Changed', 'Age', 'Created At'];

        foreach ($passwordHistories as $history) {
            $csvData[] = [
                $history->user->name ?? 'N/A',
                'Yes',
                $history->formatted_age,
                $history->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'password_histories_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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
