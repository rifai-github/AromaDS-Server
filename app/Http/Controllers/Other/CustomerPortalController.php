<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\CustomerPortal;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerPortalController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerPortal::with(['customer']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('username', 'like', "%{$search}%")
              ->orWhere('portal_url', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $portals = $query->orderBy('created_at', 'desc')->paginate(15);

        // Calculate pagination data
        $pagination = [
            'current_page' => $portals->currentPage(),
            'last_page' => $portals->lastPage(),
            'per_page' => $portals->perPage(),
            'total' => $portals->total()
        ];

        if ($request->ajax()) {
            return response()->json([
                'portals' => $portals->items(),
                'pagination' => $pagination
            ]);
        }

        return view('other.customer-portal.index', compact('portals', 'pagination'));
    }

    public function show($id)
    {
        $portal = CustomerPortal::with(['customer', 'sessions', 'activities', 'documents', 'notifications'])
                               ->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($portal);
        }

        return view('other.customer-portal.show', compact('portal'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();

        if (request()->ajax()) {
            return response()->json([
                'customers' => $customers
            ]);
        }

        return view('other.customer-portal.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id|unique:customer_portals,customer_id',
            'username' => 'required|string|max:255|unique:customer_portals,username',
            'password' => 'required|string|min:8',
            'is_active' => 'boolean'
        ]);

        $portal = CustomerPortal::create([
            'customer_id' => $request->customer_id,
            'portal_url' => Str::slug($request->username),
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer portal created successfully',
                'portal' => $portal
            ]);
        }

        return redirect()->route('other.customer-portal.index')
                        ->with('success', 'Customer portal created successfully');
    }

    public function edit($id)
    {
        $portal = CustomerPortal::with('customer')->findOrFail($id);
        $customers = Customer::where('is_active', true)->get();

        if (request()->ajax()) {
            return response()->json([
                'portal' => $portal,
                'customers' => $customers
            ]);
        }

        return view('other.customer-portal.edit', compact('portal', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $portal = CustomerPortal::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id|unique:customer_portals,customer_id,' . $id,
            'username' => 'required|string|max:255|unique:customer_portals,username,' . $id,
            'password' => 'nullable|string|min:8',
            'is_active' => 'boolean'
        ]);

        $data = [
            'customer_id' => $request->customer_id,
            'portal_url' => Str::slug($request->username),
            'username' => $request->username,
            'is_active' => $request->boolean('is_active', true)
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $portal->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer portal updated successfully',
                'portal' => $portal
            ]);
        }

        return redirect()->route('other.customer-portal.index')
                        ->with('success', 'Customer portal updated successfully');
    }

    public function destroy($id)
    {
        $portal = CustomerPortal::findOrFail($id);
        $portal->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer portal deleted successfully'
            ]);
        }

        return redirect()->route('other.customer-portal.index')
                        ->with('success', 'Customer portal deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customer_portals,id'
        ]);

        $count = CustomerPortal::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$count} customer portal(s)",
            'count' => $count
        ]);
    }

    public function toggleStatus($id)
    {
        $portal = CustomerPortal::findOrFail($id);
        $portal->update(['is_active' => !$portal->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Portal status updated successfully',
            'is_active' => $portal->is_active
        ]);
    }

    public function getStatistics()
    {
        $stats = [
            'total' => CustomerPortal::count(),
            'active' => CustomerPortal::where('is_active', true)->count(),
            'inactive' => CustomerPortal::where('is_active', false)->count(),
            'recent' => CustomerPortal::where('created_at', '>=', now()->subDays(30))->count()
        ];

        return response()->json($stats);
    }
}
