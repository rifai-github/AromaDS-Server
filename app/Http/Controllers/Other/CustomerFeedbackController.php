<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerFeedback::with(['customer']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('comments', 'like', "%{$search}%");
        }

        // Filter by feedback type
        if ($request->filled('type')) {
            $query->where('feedback_type', $request->type);
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $feedbacks = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Calculate pagination data
        $pagination = [
            'current_page' => $feedbacks->currentPage(),
            'last_page' => $feedbacks->lastPage(),
            'per_page' => $feedbacks->perPage(),
            'total' => $feedbacks->total()
        ];

        // Get filter options
        $customers = Customer::where('is_active', true)->get();
        $feedbackTypes = CustomerFeedback::getFeedbackTypes();

        if ($request->ajax()) {
            return response()->json([
                'feedbacks' => $feedbacks->items(),
                'pagination' => $pagination
            ]);
        }

        return view('other.customer-feedback.index', compact(
            'feedbacks', 
            'pagination', 
            'customers', 
            'feedbackTypes'
        ));
    }

    public function show($id)
    {
        $feedback = CustomerFeedback::with(['customer'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($feedback);
        }

        return view('other.customer-feedback.show', compact('feedback'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $feedbackTypes = CustomerFeedback::getFeedbackTypes();

        if (request()->ajax()) {
            return response()->json([
                'customers' => $customers,
                'feedbackTypes' => $feedbackTypes
            ]);
        }

        return view('other.customer-feedback.create', compact('customers', 'feedbackTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'feedback_type' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'required|string'
        ]);

        $feedback = CustomerFeedback::create([
            'customer_id' => $request->customer_id,
            'feedback_type' => $request->feedback_type,
            'rating' => $request->rating,
            'comments' => $request->comments
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer feedback created successfully',
                'feedback' => $feedback
            ]);
        }

        return redirect()->route('other.customer-feedback.index')
                        ->with('success', 'Customer feedback created successfully');
    }

    public function edit($id)
    {
        $feedback = CustomerFeedback::with('customer')->findOrFail($id);
        $customers = Customer::where('is_active', true)->get();
        $feedbackTypes = CustomerFeedback::getFeedbackTypes();

        if (request()->ajax()) {
            return response()->json([
                'feedback' => $feedback,
                'customers' => $customers,
                'feedbackTypes' => $feedbackTypes
            ]);
        }

        return view('other.customer-feedback.edit', compact('feedback', 'customers', 'feedbackTypes'));
    }

    public function update(Request $request, $id)
    {
        $feedback = CustomerFeedback::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'feedback_type' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'required|string'
        ]);

        $feedback->update([
            'customer_id' => $request->customer_id,
            'feedback_type' => $request->feedback_type,
            'rating' => $request->rating,
            'comments' => $request->comments
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer feedback updated successfully',
                'feedback' => $feedback
            ]);
        }

        return redirect()->route('other.customer-feedback.index')
                        ->with('success', 'Customer feedback updated successfully');
    }

    public function destroy($id)
    {
        $feedback = CustomerFeedback::findOrFail($id);
        $feedback->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer feedback deleted successfully'
            ]);
        }

        return redirect()->route('other.customer-feedback.index')
                        ->with('success', 'Customer feedback deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customer_feedback,id'
        ]);

        $count = CustomerFeedback::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$count} customer feedback(s)",
            'count' => $count
        ]);
    }

    public function getStatistics()
    {
        $stats = [
            'total' => CustomerFeedback::count(),
            'average_rating' => round(CustomerFeedback::avg('rating'), 2),
            'by_rating' => CustomerFeedback::selectRaw('rating, COUNT(*) as count')
                                         ->groupBy('rating')
                                         ->orderBy('rating')
                                         ->pluck('count', 'rating'),
            'by_type' => CustomerFeedback::selectRaw('feedback_type, COUNT(*) as count')
                                        ->groupBy('feedback_type')
                                        ->pluck('count', 'feedback_type'),
            'high_rating' => CustomerFeedback::highRating()->count(),
            'low_rating' => CustomerFeedback::lowRating()->count(),
            'recent' => CustomerFeedback::recent()->count()
        ];

        return response()->json($stats);
    }
}
