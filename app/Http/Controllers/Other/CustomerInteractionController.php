<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\CustomerInteraction;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerInteractionController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerInteraction::with(['customer', 'creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('subject', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by interaction type
        if ($request->filled('type')) {
            $query->where('interaction_type', $request->type);
        }

        // Filter by creator
        if ($request->filled('creator')) {
            $query->where('created_by', $request->creator);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('interaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('interaction_date', '<=', $request->date_to);
        }

        $interactions = $query->orderBy('interaction_date', 'desc')->paginateStd(25);

        // Calculate pagination data
        $pagination = [
            'current_page' => $interactions->currentPage(),
            'last_page' => $interactions->lastPage(),
            'per_page' => $interactions->perPage(),
            'total' => $interactions->total()
        ];

        // Get filter options
        $customers = Customer::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();
        $interactionTypes = CustomerInteraction::getInteractionTypes();

        if ($request->ajax()) {
            return response()->json([
                'interactions' => $interactions->items(),
                'pagination' => $pagination
            ]);
        }

        return view('other.customer-interactions.index', compact(
            'interactions', 
            'pagination', 
            'customers', 
            'users', 
            'interactionTypes'
        ));
    }

    public function show($id)
    {
        $interaction = CustomerInteraction::with(['customer', 'creator', 'updater'])
                                         ->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($interaction);
        }

        return view('other.customer-interactions.show', compact('interaction'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $interactionTypes = CustomerInteraction::getInteractionTypes();

        if (request()->ajax()) {
            return response()->json([
                'customers' => $customers,
                'interactionTypes' => $interactionTypes
            ]);
        }

        return view('other.customer-interactions.create', compact('customers', 'interactionTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'interaction_type' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'interaction_date' => 'required|date'
        ]);

        $interaction = CustomerInteraction::create([
            'customer_id' => $request->customer_id,
            'interaction_type' => $request->interaction_type,
            'subject' => $request->subject,
            'description' => $request->description,
            'interaction_date' => $request->interaction_date,
            'created_by' => Auth::id()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer interaction created successfully',
                'interaction' => $interaction
            ]);
        }

        return redirect()->route('other.customer-interactions.index')
                        ->with('success', 'Customer interaction created successfully');
    }

    public function edit($id)
    {
        $interaction = CustomerInteraction::with('customer')->findOrFail($id);
        $customers = Customer::where('is_active', true)->get();
        $interactionTypes = CustomerInteraction::getInteractionTypes();

        if (request()->ajax()) {
            return response()->json([
                'interaction' => $interaction,
                'customers' => $customers,
                'interactionTypes' => $interactionTypes
            ]);
        }

        return view('other.customer-interactions.edit', compact('interaction', 'customers', 'interactionTypes'));
    }

    public function update(Request $request, $id)
    {
        $interaction = CustomerInteraction::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'interaction_type' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'interaction_date' => 'required|date'
        ]);

        $interaction->update([
            'customer_id' => $request->customer_id,
            'interaction_type' => $request->interaction_type,
            'subject' => $request->subject,
            'description' => $request->description,
            'interaction_date' => $request->interaction_date,
            'updated_by' => Auth::id()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer interaction updated successfully',
                'interaction' => $interaction
            ]);
        }

        return redirect()->route('other.customer-interactions.index')
                        ->with('success', 'Customer interaction updated successfully');
    }

    public function destroy($id)
    {
        $interaction = CustomerInteraction::findOrFail($id);
        $interaction->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer interaction deleted successfully'
            ]);
        }

        return redirect()->route('other.customer-interactions.index')
                        ->with('success', 'Customer interaction deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customer_interactions,id'
        ]);

        $count = CustomerInteraction::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$count} customer interaction(s)",
            'count' => $count
        ]);
    }

    public function getStatistics()
    {
        $stats = [
            'total' => CustomerInteraction::count(),
            'this_month' => CustomerInteraction::thisMonth()->count(),
            'by_type' => CustomerInteraction::selectRaw('interaction_type, COUNT(*) as count')
                                          ->groupBy('interaction_type')
                                          ->pluck('count', 'interaction_type'),
            'by_creator' => CustomerInteraction::with('creator')
                                             ->selectRaw('created_by, COUNT(*) as count')
                                             ->groupBy('created_by')
                                             ->get()
                                             ->mapWithKeys(function($item) {
                                                 return [$item->creator->name ?? 'Unknown' => $item->count];
                                             })
        ];

        return response()->json($stats);
    }
}
