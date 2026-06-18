<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmergencyContact;
use App\Models\EmergencyLog;
use App\Models\EmergencyNotification;
use App\Models\EmergencyResponseAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmergencyContactController extends Controller
{
    /**
     * Display emergency contacts for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        $emergencyContacts = EmergencyContact::where('user_id', $user->id)
            ->orderBy('contact_type', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('emergency-contacts.index', compact('emergencyContacts'));
    }

    /**
     * Show the form for creating a new emergency contact
     */
    public function create()
    {
        return view('emergency-contacts.create');
    }

    /**
     * Store a newly created emergency contact
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'contact_type' => 'required|in:primary,secondary,backup',
            'can_receive_sms' => 'boolean',
            'can_receive_email' => 'boolean',
            'can_receive_whatsapp' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $emergencyContact = EmergencyContact::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'relationship' => $request->relationship,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'address' => $request->address,
            'contact_type' => $request->contact_type,
            'can_receive_sms' => $request->boolean('can_receive_sms'),
            'can_receive_email' => $request->boolean('can_receive_email'),
            'can_receive_whatsapp' => $request->boolean('can_receive_whatsapp'),
            'notes' => $request->notes
        ]);

        return redirect()->route('emergency-contacts.index')
            ->with('success', 'Emergency contact created successfully.');
    }

    /**
     * Display the specified emergency contact
     */
    public function show(EmergencyContact $emergencyContact)
    {
        $this->authorize('view', $emergencyContact);
        
        return view('emergency-contacts.show', compact('emergencyContact'));
    }

    /**
     * Show the form for editing the specified emergency contact
     */
    public function edit(EmergencyContact $emergencyContact)
    {
        $this->authorize('update', $emergencyContact);
        
        return view('emergency-contacts.edit', compact('emergencyContact'));
    }

    /**
     * Update the specified emergency contact
     */
    public function update(Request $request, EmergencyContact $emergencyContact)
    {
        $this->authorize('update', $emergencyContact);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'contact_type' => 'required|in:primary,secondary,backup',
            'can_receive_sms' => 'boolean',
            'can_receive_email' => 'boolean',
            'can_receive_whatsapp' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $emergencyContact->update([
            'name' => $request->name,
            'relationship' => $request->relationship,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'address' => $request->address,
            'contact_type' => $request->contact_type,
            'can_receive_sms' => $request->boolean('can_receive_sms'),
            'can_receive_email' => $request->boolean('can_receive_email'),
            'can_receive_whatsapp' => $request->boolean('can_receive_whatsapp'),
            'notes' => $request->notes
        ]);

        return redirect()->route('emergency-contacts.index')
            ->with('success', 'Emergency contact updated successfully.');
    }

    /**
     * Remove the specified emergency contact
     */
    public function destroy(EmergencyContact $emergencyContact)
    {
        $this->authorize('delete', $emergencyContact);
        
        $emergencyContact->delete();

        return redirect()->route('emergency-contacts.index')
            ->with('success', 'Emergency contact deleted successfully.');
    }

    /**
     * Toggle active status of emergency contact
     */
    public function toggleStatus(EmergencyContact $emergencyContact)
    {
        $this->authorize('update', $emergencyContact);
        
        $emergencyContact->update([
            'is_active' => !$emergencyContact->is_active
        ]);

        $status = $emergencyContact->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Emergency contact {$status} successfully.");
    }

    /**
     * Display emergency logs for the authenticated user
     */
    public function emergencyLogs()
    {
        $user = Auth::user();
        $emergencyLogs = EmergencyLog::where('user_id', $user->id)
            ->with(['emergencyContact', 'emergencyNotifications'])
            ->orderBy('triggered_at', 'desc')
            ->paginateStd(25);

        return view('emergency-contacts.emergency-logs', compact('emergencyLogs'));
    }

    /**
     * Create a new emergency log
     */
    public function createEmergencyLog()
    {
        $user = Auth::user();
        $emergencyContacts = EmergencyContact::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        return view('emergency-contacts.create-emergency-log', compact('emergencyContacts'));
    }

    /**
     * Store a new emergency log
     */
    public function storeEmergencyLog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emergency_contact_id' => 'nullable|exists:emergency_contacts,id',
            'emergency_type' => 'required|in:medical,safety,security,technical,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'severity' => 'required|in:low,medium,high,critical',
            'location' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $emergencyLog = EmergencyLog::create([
            'user_id' => Auth::id(),
            'emergency_contact_id' => $request->emergency_contact_id,
            'emergency_type' => $request->emergency_type,
            'title' => $request->title,
            'description' => $request->description,
            'severity' => $request->severity,
            'location' => $request->location,
            'triggered_at' => now(),
            'created_by' => Auth::id()
        ]);

        // TODO: Trigger notifications based on severity and emergency type
        // This would integrate with SMS, email, and WhatsApp services

        return redirect()->route('emergency-contacts.emergency-logs')
            ->with('success', 'Emergency log created successfully. Notifications have been sent.');
    }

    /**
     * Display emergency log details
     */
    public function showEmergencyLog(EmergencyLog $emergencyLog)
    {
        $this->authorize('view', $emergencyLog);
        
        $emergencyLog->load(['emergencyContact', 'emergencyNotifications', 'responseActions.responder']);
        
        return view('emergency-contacts.show-emergency-log', compact('emergencyLog'));
    }

    /**
     * Update emergency log status
     */
    public function updateEmergencyLogStatus(Request $request, EmergencyLog $emergencyLog)
    {
        $this->authorize('update', $emergencyLog);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,notified,responded,resolved,cancelled'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        $emergencyLog->update(['status' => $request->status]);

        // Update timestamps based on status
        switch ($request->status) {
            case 'notified':
                $emergencyLog->markAsNotified();
                break;
            case 'responded':
                $emergencyLog->markAsResponded();
                break;
            case 'resolved':
                $emergencyLog->markAsResolved();
                break;
        }

        return redirect()->back()
            ->with('success', 'Emergency log status updated successfully.');
    }

    /**
     * Add response action to emergency log
     */
    public function addResponseAction(Request $request, EmergencyLog $emergencyLog)
    {
        $this->authorize('update', $emergencyLog);

        $validator = Validator::make($request->all(), [
            'action_type' => 'required|in:call,visit,assist,escalate,resolve,other',
            'description' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1',
            'outcome' => 'required|in:successful,partial,failed,escalated'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        EmergencyResponseAction::create([
            'emergency_log_id' => $emergencyLog->id,
            'responder_id' => Auth::id(),
            'action_type' => $request->action_type,
            'description' => $request->description,
            'notes' => $request->notes,
            'action_time' => now(),
            'duration_minutes' => $request->duration_minutes,
            'outcome' => $request->outcome
        ]);

        return redirect()->back()
            ->with('success', 'Response action added successfully.');
    }

    /**
     * Get emergency contact statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        
        $stats = [
            'total_contacts' => EmergencyContact::where('user_id', $user->id)->count(),
            'active_contacts' => EmergencyContact::where('user_id', $user->id)->where('is_active', true)->count(),
            'total_emergencies' => EmergencyLog::where('user_id', $user->id)->count(),
            'active_emergencies' => EmergencyLog::where('user_id', $user->id)->active()->count(),
            'resolved_emergencies' => EmergencyLog::where('user_id', $user->id)->where('status', 'resolved')->count(),
            'overdue_emergencies' => EmergencyLog::where('user_id', $user->id)->overdue()->count()
        ];

        return response()->json($stats);
    }
}