<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['customer', 'assignedTo', 'creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('ticket_number', 'like', "%{$search}%")
              ->orWhere('subject', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by assigned to
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Calculate pagination data
        $pagination = [
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage(),
            'per_page' => $tickets->perPage(),
            'total' => $tickets->total()
        ];

        // Get filter options
        $customers = Customer::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();
        $statuses = SupportTicket::getStatuses();
        $priorities = SupportTicket::getPriorities();

        if ($request->ajax()) {
            return response()->json([
                'tickets' => $tickets->items(),
                'pagination' => $pagination
            ]);
        }

        return view('other.support-tickets.index', compact(
            'tickets', 
            'pagination', 
            'customers', 
            'users', 
            'statuses', 
            'priorities'
        ));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['customer', 'assignedTo', 'creator', 'messages.sender'])
                              ->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($ticket);
        }

        return view('other.support-tickets.show', compact('ticket'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();
        $statuses = SupportTicket::getStatuses();
        $priorities = SupportTicket::getPriorities();

        if (request()->ajax()) {
            return response()->json([
                'customers' => $customers,
                'users' => $users,
                'statuses' => $statuses,
                'priorities' => $priorities
            ]);
        }

        return view('other.support-tickets.create', compact('customers', 'users', 'statuses', 'priorities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'customer_id' => $request->customer_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'assigned_to' => $request->assigned_to,
            'created_by' => Auth::id()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Support ticket created successfully',
                'ticket' => $ticket
            ]);
        }

        return redirect()->route('other.support-tickets.index')
                        ->with('success', 'Support ticket created successfully');
    }

    public function edit($id)
    {
        $ticket = SupportTicket::with('customer')->findOrFail($id);
        $customers = Customer::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();
        $statuses = SupportTicket::getStatuses();
        $priorities = SupportTicket::getPriorities();

        if (request()->ajax()) {
            return response()->json([
                'ticket' => $ticket,
                'customers' => $customers,
                'users' => $users,
                'statuses' => $statuses,
                'priorities' => $priorities
            ]);
        }

        return view('other.support-tickets.edit', compact('ticket', 'customers', 'users', 'statuses', 'priorities'));
    }

    public function update(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $ticket->update([
            'customer_id' => $request->customer_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'assigned_to' => $request->assigned_to,
            'updated_by' => Auth::id()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Support ticket updated successfully',
                'ticket' => $ticket
            ]);
        }

        return redirect()->route('other.support-tickets.index')
                        ->with('success', 'Support ticket updated successfully');
    }

    public function destroy($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Support ticket deleted successfully'
            ]);
        }

        return redirect()->route('other.support-tickets.index')
                        ->with('success', 'Support ticket deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:support_tickets,id'
        ]);

        $count = SupportTicket::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$count} support ticket(s)",
            'count' => $count
        ]);
    }

    public function addMessage(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'message_type' => 'required|in:customer,staff,system'
        ]);

        $message = $ticket->addMessage(
            $request->message,
            $request->message_type,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Message added successfully',
            'data' => $message
        ]);
    }

    public function assignTo(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $ticket->assignTo($request->assigned_to);

        return response()->json([
            'success' => true,
            'message' => 'Ticket assigned successfully'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed'
        ]);

        $ticket->updateStatus($request->status);

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully'
        ]);
    }

    public function getStatistics()
    {
        $stats = SupportTicket::getStats();

        return response()->json($stats);
    }
}
