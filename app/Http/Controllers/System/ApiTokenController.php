<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiToken::with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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
                    $query->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
                    break;
                case 'expired':
                    $query->where('expires_at', '<=', now());
                    break;
                case 'expiring_soon':
                    $query->where('expires_at', '>', now())
                          ->where('expires_at', '<=', now()->addDays(7));
                    break;
                case 'never_used':
                    $query->whereNull('last_used_at');
                    break;
            }
        }

        if ($request->filled('ability')) {
            $query->whereJsonContains('abilities', $request->ability);
        }

        $apiTokens = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Get filter options
        $users = User::where('is_active', true)->get();
        $abilities = ApiToken::getAvailableAbilities();

        return view('system.api-tokens.index', compact('apiTokens', 'users', 'abilities'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        $abilities = ApiToken::getAvailableAbilities();

        return view('system.api-tokens.create', compact('users', 'abilities'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiToken = ApiToken::create([
                'user_id' => $request->user_id,
                'name' => $request->name,
                'token' => ApiToken::generateToken(),
                'abilities' => $request->abilities,
                'expires_at' => $request->expires_at
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API token created successfully',
                    'data' => $apiToken,
                    'token' => $apiToken->token // Only return token on creation
                ]);
            }

            return redirect()->route('system.api-tokens.index')
                           ->with('success', 'API token created successfully')
                           ->with('token', $apiToken->token);

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating API token: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating API token: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $apiToken = ApiToken::with(['user'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $apiToken
            ]);
        }

        return view('system.api-tokens.show', compact('apiToken'));
    }

    public function edit($id)
    {
        $apiToken = ApiToken::with(['user'])->findOrFail($id);
        $users = User::where('is_active', true)->get();
        $abilities = ApiToken::getAvailableAbilities();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $apiToken,
                'users' => $users,
                'abilities' => $abilities
            ]);
        }

        return view('system.api-tokens.edit', compact('apiToken', 'users', 'abilities'));
    }

    public function update(Request $request, $id)
    {
        $apiToken = ApiToken::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiToken->update([
                'user_id' => $request->user_id,
                'name' => $request->name,
                'abilities' => $request->abilities,
                'expires_at' => $request->expires_at
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API token updated successfully',
                    'data' => $apiToken
                ]);
            }

            return redirect()->route('system.api-tokens.index')
                           ->with('success', 'API token updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating API token: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating API token: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $apiToken = ApiToken::findOrFail($id);

        try {
            $apiToken->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API token deleted successfully'
                ]);
            }

            return redirect()->route('system.api-tokens.index')
                           ->with('success', 'API token deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting API token: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error deleting API token: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:api_tokens,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = ApiToken::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} API token(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting API tokens: ' . $e->getMessage()
            ], 500);
        }
    }

    public function regenerateToken($id)
    {
        $apiToken = ApiToken::findOrFail($id);

        try {
            $newToken = ApiToken::generateToken();
            $apiToken->update(['token' => $newToken]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API token regenerated successfully',
                    'token' => $newToken
                ]);
            }

            return redirect()->back()
                           ->with('success', 'API token regenerated successfully')
                           ->with('token', $newToken);

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error regenerating API token: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error regenerating API token: ' . $e->getMessage());
        }
    }

    public function extendExpiry(Request $request, $id)
    {
        $apiToken = ApiToken::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'expires_at' => 'required|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiToken->update(['expires_at' => $request->expires_at]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API token expiry extended successfully'
                ]);
            }

            return redirect()->back()
                           ->with('success', 'API token expiry extended successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error extending API token expiry: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error extending API token expiry: ' . $e->getMessage());
        }
    }

    public function getUserTokens($userId)
    {
        $tokens = ApiToken::where('user_id', $userId)
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $tokens
        ]);
    }

    public function getExpiringTokens($days = 7)
    {
        $tokens = ApiToken::where('expires_at', '>', now())
                         ->where('expires_at', '<=', now()->addDays($days))
                         ->with(['user'])
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $tokens
        ]);
    }

    public function export()
    {
        $apiTokens = ApiToken::with(['user'])->get();

        $csvData = [];
        $csvData[] = ['User', 'Name', 'Abilities', 'Status', 'Last Used', 'Expires At', 'Created At'];

        foreach ($apiTokens as $token) {
            $csvData[] = [
                $token->user->name ?? 'N/A',
                $token->name,
                $token->formatted_abilities,
                $token->status_text,
                $token->last_used_formatted,
                $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : 'Never',
                $token->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'api_tokens_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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
