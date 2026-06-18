<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\SalesActivity;
use App\Models\Prospect;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesActivity::with(['staff', 'updater', 'prospect']);

        // Filter by staff
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by activity date
        if ($request->filled('start_date')) {
            $query->whereDate('activity_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('activity_date', '<=', $request->end_date);
        }

        // Filter by company name
        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        // Filter by activity type
        if ($request->filled('activity')) {
            $query->where('activity', 'like', '%' . $request->activity . '%');
        }

        $activities = $query->orderBy('activity_date', 'desc')->paginateStd(25)->withQueryString();

        // Prepare pagination data for view
        $pagination = [
            'current_page' => $activities->currentPage(),
            'last_page' => $activities->lastPage(),
            'per_page' => $activities->perPage(),
            'total' => $activities->total(),
            'from' => $activities->firstItem(),
            'to' => $activities->lastItem(),
        ];

        return view('marketing.sales-activities.index', compact('activities', 'pagination'));
    }

    public function create()
    {
        $staff = User::all();
        $prospects = Prospect::orderBy('company_name')->get();
        
        return view('marketing.sales-activities.create', compact('staff', 'prospects'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'activity_number' => 'nullable|string|max:255',
            'staff_id' => 'required|exists:users,id',
            'prospect_id' => 'nullable|exists:prospects,id',
            'location' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'start_hour' => 'required|date_format:H:i',
            'end_hour' => 'required|date_format:H:i|after:start_hour',
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'pic_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'activity' => 'required|string|max:500',
            'activity_result' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $activity = SalesActivity::create([
                'activity_number' => $request->activity_number ?? 'SA-' . date('Ymd') . '-' . str_pad(SalesActivity::count() + 1, 4, '0', STR_PAD_LEFT),
                'staff_id' => $request->staff_id,
                'prospect_id' => $request->prospect_id,
                'location' => $request->location,
                'activity_date' => $request->activity_date,
                'start_hour' => $request->start_hour,
                'end_hour' => $request->end_hour,
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'pic_name' => $request->pic_name,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'company_email' => $request->company_email,
                'activity' => $request->activity,
                'activity_result' => $request->activity_result,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sales activity created successfully',
                    'data' => $activity
                ]);
            }

            return redirect()->route('marketing.sales-activities.show', $activity)
                ->with('success', 'Aktivitas Penjualan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating activity: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(SalesActivity $salesActivity)
    {
        $salesActivity->load(['staff', 'updater', 'prospect']);
        return response()->json($salesActivity);
    }

    public function edit(SalesActivity $salesActivity)
    {
        $salesActivity->load(['staff', 'updater', 'prospect']);
        return response()->json($salesActivity);
    }

    public function update(Request $request, SalesActivity $salesActivity)
    {
        $validator = \Validator::make($request->all(), [
            'staff_id' => 'required|exists:users,id',
            'prospect_id' => 'nullable|exists:prospects,id',
            'location' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'start_hour' => 'required|date_format:H:i',
            'end_hour' => 'required|date_format:H:i|after:start_hour',
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'pic_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'activity' => 'required|string|max:500',
            'activity_result' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $salesActivity->update([
                'staff_id' => $request->staff_id,
                'prospect_id' => $request->prospect_id,
                'location' => $request->location,
                'activity_date' => $request->activity_date,
                'start_hour' => $request->start_hour,
                'end_hour' => $request->end_hour,
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'pic_name' => $request->pic_name,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'company_email' => $request->company_email,
                'activity' => $request->activity,
                'activity_result' => $request->activity_result,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sales activity updated successfully',
                    'data' => $salesActivity
                ]);
            }

            return redirect()->route('marketing.sales-activities.show', $salesActivity)
                ->with('success', 'Aktivitas Penjualan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating activity: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(SalesActivity $salesActivity)
    {
        try {
            $salesActivity->delete();
            return redirect()->route('marketing.sales-activities.index')
                ->with('success', 'Aktivitas Penjualan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:sales_activities,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = SalesActivity::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully hidden {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error hiding records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function myActivities(Request $request)
    {
        $query = SalesActivity::with(['staff'])
            ->where('staff_id', Auth::id());

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by activity date
        if ($request->filled('start_date')) {
            $query->whereDate('activity_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('activity_date', '<=', $request->end_date);
        }

        // Filter by company name
        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        $activities = $query->orderBy('activity_date', 'desc')->paginateStd(25);

        return view('marketing.sales-activities.my-activities', compact('activities'));
    }

    /**
     * Get prospect data for auto-fill functionality
     */
    public function getProspectData(Prospect $prospect)
    {
        return response()->json([
            'id' => $prospect->id,
            'company_name' => $prospect->company_name,
            'company_address' => $prospect->company_address,
            'contact_person' => $prospect->contact_person,
            'contact_phone' => $prospect->contact_phone,
            'contact_email' => $prospect->contact_email,
        ]);
    }

    /**
     * Get all prospects for dropdown
     */
    public function getProspects()
    {
        $prospects = Prospect::orderBy('company_name')->get(['id', 'company_name', 'contact_person']);
        return response()->json($prospects);
    }
}
