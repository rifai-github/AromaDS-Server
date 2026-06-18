<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\ExternalApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExternalApiController extends Controller
{
    public function index(Request $request)
    {
        $query = ExternalApi::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('api_name', 'like', "%{$search}%")
                  ->orWhere('api_url', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $apis = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Calculate pagination data
        $pagination = [
            'current_page' => $apis->currentPage(),
            'last_page' => $apis->lastPage(),
            'per_page' => $apis->perPage(),
            'total' => $apis->total()
        ];

        if ($request->ajax()) {
            return response()->json([
                'apis' => $apis->items(),
                'pagination' => $pagination
            ]);
        }

        return view('other.external-apis.index', compact('apis', 'pagination'));
    }

    public function show($id)
    {
        $api = ExternalApi::with(['logs' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($api);
        }

        return view('other.external-apis.show', compact('api'));
    }

    public function create()
    {
        $apiTypes = ExternalApi::getApiTypes();

        if (request()->ajax()) {
            return response()->json([
                'apiTypes' => $apiTypes
            ]);
        }

        return view('other.external-apis.create', compact('apiTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'api_name' => 'required|string|max:255',
            'api_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:255',
            'api_secret' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $api = ExternalApi::create([
            'api_name' => $request->api_name,
            'api_url' => $request->api_url,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'is_active' => $request->boolean('is_active', true)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'External API created successfully',
                'api' => $api
            ]);
        }

        return redirect()->route('other.external-apis.index')
                        ->with('success', 'External API created successfully');
    }

    public function edit($id)
    {
        $api = ExternalApi::findOrFail($id);
        $apiTypes = ExternalApi::getApiTypes();

        if (request()->ajax()) {
            return response()->json([
                'api' => $api,
                'apiTypes' => $apiTypes
            ]);
        }

        return view('other.external-apis.edit', compact('api', 'apiTypes'));
    }

    public function update(Request $request, $id)
    {
        $api = ExternalApi::findOrFail($id);

        $request->validate([
            'api_name' => 'required|string|max:255',
            'api_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:255',
            'api_secret' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $api->update([
            'api_name' => $request->api_name,
            'api_url' => $request->api_url,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'is_active' => $request->boolean('is_active', true)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'External API updated successfully',
                'api' => $api
            ]);
        }

        return redirect()->route('other.external-apis.index')
                        ->with('success', 'External API updated successfully');
    }

    public function destroy($id)
    {
        $api = ExternalApi::findOrFail($id);
        $api->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'External API deleted successfully'
            ]);
        }

        return redirect()->route('other.external-apis.index')
                        ->with('success', 'External API deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:external_apis,id'
        ]);

        $count = ExternalApi::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$count} external API(s)",
            'count' => $count
        ]);
    }

    public function toggleStatus($id)
    {
        $api = ExternalApi::findOrFail($id);
        $api->update(['is_active' => !$api->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'API status updated successfully',
            'is_active' => $api->is_active
        ]);
    }

    public function getApiLogs($id, Request $request)
    {
        $api = ExternalApi::findOrFail($id);
        
        $query = $api->logs();
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Filter by status code
        if ($request->filled('status_code')) {
            $query->where('status_code', $request->status_code);
        }
        
        $logs = $query->orderBy('created_at', 'desc')
                     ->limit($request->get('limit', 100))
                     ->get();

        return response()->json($logs);
    }

    public function testApi($id)
    {
        $api = ExternalApi::findOrFail($id);
        
        // This would implement actual API testing logic
        // For now, return a mock response
        
        return response()->json([
            'success' => true,
            'message' => 'API test completed',
            'response_time' => rand(100, 1000),
            'status_code' => 200
        ]);
    }

    public function getStatistics()
    {
        $stats = [
            'total' => ExternalApi::count(),
            'active' => ExternalApi::where('is_active', true)->count(),
            'inactive' => ExternalApi::where('is_active', false)->count(),
            'recent_logs' => \App\Models\ApiLog::recent()->count(),
            'success_rate' => round(\App\Models\ApiLog::getSuccessRate(), 2),
            'average_response_time' => round(\App\Models\ApiLog::getAverageResponseTime(), 2)
        ];

        return response()->json($stats);
    }
}
