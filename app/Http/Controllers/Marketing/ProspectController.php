<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasAuditTrail;
use App\Traits\HasNotifications;

class ProspectController extends Controller
{
    use HasAuditTrail, HasNotifications, ColumnFilterTrait, AccessControlFilterTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'data.restriction']);
    }
    
    
    public function index(Request $request)
    {
        $staff = User::where('department_name', 'Marketing')->active()->get();
        
        // Get prospects with pagination and apply data restrictions
        $query = Prospect::with(['assignedTo']);

        // Apply per-column filters (table id: prospectsTable)
        $this->applyColumnFilters($query, 'prospectsTable', [
            // 0 => checkbox
            1 => ['relation' => 'assignedTo', 'column' => 'name'],
            2 => ['column' => 'follow_up_date'],
            3 => ['column' => 'company_name'],
            4 => ['column' => 'company_address'],
            5 => ['column' => 'contact_person'],
            6 => ['column' => 'contact_phone'],
            7 => ['column' => 'contact_email'],
            8 => ['column' => 'activity_notes'],
            9 => ['column' => 'updated_at'],
            10 => ['relation' => 'assignedTo', 'column' => 'name'],
        ]);
        
        // Apply access control filter
        // Uses 'assigned_to' for both created and marketing role checks
        // Uses 'assignedTo.branch_id' for branch hierarchy check (via AccessControlFilterTrait dot notation support)
        $user = auth()->user();
        $query = $this->applyAccessControlFilter($query, $user, 'assigned_to', 'assigned_to', 'assignedTo.branch_id');

        $prospects = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Prepare pagination data for view
        $pagination = [
            'current_page' => $prospects->currentPage(),
            'last_page' => $prospects->lastPage(),
            'per_page' => $prospects->perPage(),
            'total' => $prospects->total(),
            'from' => $prospects->firstItem(),
            'to' => $prospects->lastItem(),
        ];
        
        return view('marketing.prospects.index', compact('prospects', 'staff', 'pagination'));
    }

    public function pipeline()
    {
        $staff = User::where('department_name', 'Marketing')->active()->get();
        
        // Get pipeline data with pagination (using prospects for now)
        $pipeline = Prospect::with(['assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Prepare pagination data for view
        $pagination = [
            'current_page' => $pipeline->currentPage(),
            'last_page' => $pipeline->lastPage(),
            'per_page' => $pipeline->perPage(),
            'total' => $pipeline->total(),
            'from' => $pipeline->firstItem(),
            'to' => $pipeline->lastItem(),
        ];
        
        return view('marketing.pipeline.index', compact('pipeline', 'staff', 'pagination'));
    }

    public function create(Request $request)
    {
        $staff = User::active()->get();
        
        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'staff' => $staff
                ]
            ]);
        }
        
        return view('marketing.prospects.create', compact('staff'));
    }

    public function store(Request $request)
    {
        // Check permission
        if (!auth()->user()->canCreateInModule('marketing')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You do not have permission to create prospects.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'nullable|date',
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'activity_notes' => 'required|string',
            'status' => 'nullable|in:new,contacted,qualified,proposal,negotiation,closed_won,closed_lost'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['assigned_to'] = auth()->id(); // Auto assign to current user
            $data['status'] = $data['status'] ?? 'new'; // Default status
            
            $prospect = Prospect::create($data);

            // Log audit trail
            $this->logModelCreated($prospect, "New prospect created: {$prospect->company_name}");

            // Create notification for marketing team (if user has department_id)
            if (auth()->user()->department_id) {
                $this->createDepartmentNotification(
                    'New Prospect Added',
                    "A new prospect '{$prospect->company_name}' has been added to the system.",
                    auth()->user()->department_id,
                    'info',
                    ['prospect_id' => $prospect->id],
                    route('marketing.prospects.show', $prospect->id)
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Prospect berhasil ditambahkan',
                'data' => $prospect
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Handle duplicate prospect number (race condition)
            return response()->json([
                'success' => false,
                'message' => 'There was a temporary conflict with prospect number generation. Please try again.',
                'error_type' => 'duplicate'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating prospect: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Prospect $prospect)
    {
        $prospect->load(['assignedTo']);
        return response()->json($prospect);
    }

    public function edit(Prospect $prospect)
    {
        $prospect->load(['assignedTo']);
        return response()->json($prospect);
    }

    public function update(Request $request, Prospect $prospect)
    {
        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'nullable|date',
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'activity_notes' => 'required|string',
            'status' => 'nullable|in:new,contacted,qualified,proposal,negotiation,closed_won,closed_lost'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['status'] = $data['status'] ?? $prospect->status; // Keep existing status if not provided
            
            $prospect->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Prospect berhasil diperbarui',
                'data' => $prospect
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Handle duplicate prospect number
            return response()->json([
                'success' => false,
                'message' => 'A prospect with this information already exists. Please try again.',
                'error_type' => 'duplicate'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating prospect: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Prospect $prospect)
    {
        $prospect->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Prospect berhasil dihapus'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        // Handle both array and JSON string formats
        $ids = $request->ids;
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        
        $validator = Validator::make(['ids' => $ids], [
            'ids' => 'required|array',
            'ids.*' => 'exists:prospects,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = Prospect::whereIn('id', $ids)->delete();
            
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

    // AJAX methods for Pipeline modal
    public function showAjax(Prospect $prospect)
    {
        $prospect->load('assignedTo');
        
        return response()->json([
            'id' => $prospect->id,
            'company_name' => $prospect->company_name,
            'company_address' => $prospect->company_address,
            'contact_person' => $prospect->contact_person,
            'contact_phone' => $prospect->contact_phone,
            'contact_email' => $prospect->contact_email,
            'activity_notes' => $prospect->activity_notes,
            'business_description' => $prospect->business_description,
            'requirements' => $prospect->requirements,
            'budget_range' => $prospect->budget_range,
            'follow_up_date' => $prospect->follow_up_date,
            'status' => $prospect->status,
            'assigned_to_name' => $prospect->assignedTo->name ?? 'N/A'
        ]);
    }

    public function editAjax(Prospect $prospect)
    {
        $prospect->load('assignedTo');
        
        return response()->json([
            'id' => $prospect->id,
            'company_name' => $prospect->company_name,
            'company_address' => $prospect->company_address,
            'contact_person' => $prospect->contact_person,
            'contact_phone' => $prospect->contact_phone,
            'contact_email' => $prospect->contact_email,
            'activity_notes' => $prospect->activity_notes,
            'business_description' => $prospect->business_description,
            'requirements' => $prospect->requirements,
            'budget_range' => $prospect->budget_range,
            'follow_up_date' => $prospect->follow_up_date,
            'status' => $prospect->status,
            'assigned_to' => $prospect->assigned_to
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        // Get prospects statistics
        $stats = [
            'total_prospects' => Prospect::count(),
            'new_prospects' => Prospect::where('status', 'new')->count(),
            'follow_up_today' => Prospect::followUpToday()->count(),
            'follow_up_overdue' => Prospect::followUpOverdue()->count(),
            'follow_up_upcoming' => Prospect::followUpUpcoming()->count(),
        ];

        // Get recent prospects
        $recentProspects = Prospect::with('staff')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get prospects by status
        $prospectsByStatus = Prospect::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        return view('marketing.dashboard', compact('stats', 'recentProspects', 'prospectsByStatus'));
    }

}
