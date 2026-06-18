<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportAlert;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReportAlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('reports.alerts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('reports.alerts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'report_id' => 'required|exists:reports,id',
            'condition_field' => 'required|string|max:255',
            'condition_operator' => 'required|string|in:>,<,=,!=,>=,<=',
            'condition_value' => 'required|string|max:255',
            'notification_type' => 'required|string|in:email,sms,push',
            'recipients' => 'required|string',
            'message_template' => 'nullable|string',
            'schedule' => 'nullable|string|in:daily,weekly,monthly,realtime',
            'is_active' => 'boolean'
        ]);

        $alert = ReportAlert::create([
            'name' => $request->name,
            'report_id' => $request->report_id,
            'condition_field' => $request->condition_field,
            'condition_operator' => $request->condition_operator,
            'condition_value' => $request->condition_value,
            'notification_type' => $request->notification_type,
            'recipients' => $request->recipients,
            'message_template' => $request->message_template,
            'schedule' => $request->schedule,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert created successfully',
            'data' => $alert->load('report')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ReportAlert $alert): View
    {
        return view('reports.alerts.show', compact('alert'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReportAlert $alert): View
    {
        return view('reports.alerts.edit', compact('alert'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReportAlert $alert): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'report_id' => 'required|exists:reports,id',
            'condition_field' => 'required|string|max:255',
            'condition_operator' => 'required|string|in:>,<,=,!=,>=,<=',
            'condition_value' => 'required|string|max:255',
            'notification_type' => 'required|string|in:email,sms,push',
            'recipients' => 'required|string',
            'message_template' => 'nullable|string',
            'schedule' => 'nullable|string|in:daily,weekly,monthly,realtime',
            'is_active' => 'boolean'
        ]);

        $alert->update([
            'name' => $request->name,
            'report_id' => $request->report_id,
            'condition_field' => $request->condition_field,
            'condition_operator' => $request->condition_operator,
            'condition_value' => $request->condition_value,
            'notification_type' => $request->notification_type,
            'recipients' => $request->recipients,
            'message_template' => $request->message_template,
            'schedule' => $request->schedule,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert updated successfully',
            'data' => $alert->load('report')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportAlert $alert): JsonResponse
    {
        $alert->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Alert deleted successfully'
        ]);
    }

    /**
     * Get alerts with pagination and filters
     */
    public function getAlerts(Request $request): JsonResponse
    {
        $query = ReportAlert::with('report')
            ->when($request->status, function ($q, $status) {
                if ($status === 'active') {
                    $q->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($request->type, function ($q, $type) {
                $q->where('notification_type', $type);
            })
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('condition_field', 'like', "%{$search}%");
            });

        $alerts = $query->paginateStd(25);

        return response()->json([
            'status' => 'success',
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
                'from' => $alerts->firstItem(),
                'to' => $alerts->lastItem()
            ]
        ]);
    }

    /**
     * Test alert
     */
    public function test(ReportAlert $alert): JsonResponse
    {
        // Implement alert testing logic
        return response()->json([
            'status' => 'success',
            'message' => 'Alert test completed'
        ]);
    }

    /**
     * Activate alert
     */
    public function activate(ReportAlert $alert): JsonResponse
    {
        $alert->update(['is_active' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert activated successfully'
        ]);
    }

    /**
     * Deactivate alert
     */
    public function deactivate(ReportAlert $alert): JsonResponse
    {
        $alert->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert deactivated successfully'
        ]);
    }
}
