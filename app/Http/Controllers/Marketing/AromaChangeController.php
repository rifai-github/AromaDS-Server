<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\AromaChange;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\ContractSwitching;
use App\Models\Building;
use App\Models\Room;
use App\Models\MasterProduct;
use App\Models\JobSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AromaChangeController extends Controller
{
    /**
     * Display a listing of aroma changes
     */
    public function index(Request $request)
    {
        $query = AromaChange::with([
            'contract',
            'contract.customer',
            'building',
            'room',
            'requestedBy',
            'approvedBy',
            'appliedBy',
            'previousProduct',
            'newProduct'
        ]);

        // Filter by contract
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // Filter by building
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'waiting_for_approval') {
                $status = 'pending_approval';
            }
            $query->where('status', $status);
        }

        // Filter by product category (MOM6: Luxo, Artisan, Signature)
        if ($request->filled('product_category_id')) {
            $query->byProductCategory($request->product_category_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $changes = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Stats for view
        $stats = [
            'draft' => AromaChange::draft()->count(),
            'pending' => AromaChange::pending()->count(),
            'approved' => AromaChange::approved()->count(),
            'completed' => AromaChange::completed()->count(),
            'total' => AromaChange::count()
        ];

        // If AJAX request, return JSON
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $changes
            ]);
        }

        $activeContracts = $this->getActiveContractOptions();

        return view('marketing.aroma-changes.index', compact('changes', 'stats', 'activeContracts'));
    }

    /**
     * Show the form for creating a new aroma change (MOM6 Form)
     */
    public function create(Request $request)
    {
        // For initial page load - contract list is loaded via AJAX now
        $contracts = []; 
        
        // If AJAX request with contract_id, get contract details
        if ($request->ajax() && $request->filled('contract_id')) {
            $contractQuery = Contract::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->with([
                    'contractRooms.room.building',
                    'customer',
                    'quotation.quotationRooms.aromaProduct.productCategory',
                    'quotation.quotationRooms.aromaProduct.productType',
                    'quotation.quotationRooms.aromaProduct.packagingSize'
                ]);

            $contract = $contractQuery->find($request->contract_id);

            if (!$contract) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contract not found'
                ], 404);
            }

            if (
                (!$contract->contractRooms || $contract->contractRooms->isEmpty()) &&
                ContractSwitching::syncNewContractStructureIfMissing($contract, Auth::id())
            ) {
                $contract = $contractQuery->find($request->contract_id);
            }

            // Create a lookup map for aroma info from quotation rooms
            // Key: room_id, Value: aroma info
            $aromaMap = [];
            if ($contract->quotation && $contract->quotation->quotationRooms) {
                foreach ($contract->quotation->quotationRooms as $qRoom) {
                    if ($qRoom->aromaProduct) {
                        $aromaMap[$qRoom->room_id] = [
                            'code' => $this->aromaProductCode($qRoom->aromaProduct),
                            'name' => $this->aromaProductDisplayName($qRoom->aromaProduct),
                            'product_id' => $qRoom->aroma_product_id,
                            'brand_line' => $qRoom->aromaProduct->brand_line,
                            'product_name' => $qRoom->aromaProduct->name,
                            'sku' => $qRoom->aromaProduct->sku ?: $qRoom->aromaProduct->getAttribute('product_code'),
                            'packaging_size' => $qRoom->aromaProduct->packagingSize?->name,
                        ];
                    }
                }
            }

            // Add aroma info to contract rooms
            $contractRooms = $contract->contractRooms->map(function($room) use ($aromaMap) {
                $aromaInfo = $aromaMap[$room->room_id] ?? null;
                
                if ($aromaInfo) {
                    $room->aroma_code = $aromaInfo['code'];
                    $room->aroma_name = $aromaInfo['name'];
                    $room->aroma_product_id = $aromaInfo['product_id'];
                    $room->aroma_brand_line = $aromaInfo['brand_line'];
                    $room->aroma_product_name = $aromaInfo['product_name'];
                    $room->aroma_sku = $aromaInfo['sku'];
                    $room->aroma_packaging_size = $aromaInfo['packaging_size'];
                } else {
                    $room->aroma_code = ''; // No aroma set
                    $room->aroma_name = 'No Aroma';
                    $room->aroma_product_id = null;
                    $room->aroma_brand_line = null;
                    $room->aroma_product_name = null;
                    $room->aroma_sku = null;
                    $room->aroma_packaging_size = null;
                }
                
                return $room;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'contract' => $contract,
                    'contract_rooms' => $contractRooms
                ]
            ]);
        }

        return view('marketing.aroma-changes.create', compact('contracts'));
    }

    /**
     * Get contracts for dropdown (optimized)
     */
    public function getContracts(Request $request) {
        $search = $request->input('search');

        return response()->json($this->getActiveContractOptions($search, 20));
    }

    public function getAromaProducts(Request $request)
    {
        $products = MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
            ->where('is_active', true)
            ->whereNotNull('brand_line')
            ->where('brand_line', '!=', '')
            ->get()
            ->filter(fn ($product) => $this->isSelectableAromaProduct($product));

        $aromaProducts = $products
            ->groupBy(fn ($product) => $this->aromaProductGroupKey($product))
            ->map(function($group) {
                $product = $this->preferredAromaProduct($group);
                $displayName = $this->aromaProductDisplayName($product);

                return [
                    'id' => $product->id,
                    'name' => $displayName,
                    'product_name' => $product->name,
                    'sku' => $product->sku ?: $product->getAttribute('product_code'),
                    'variant' => $product->variant_name,
                    'variant_name' => $product->variant_name,
                    'brand_line' => $product->brand_line,
                    'category' => $product->productCategory?->name,
                    'packaging_size' => $product->packagingSize?->name,
                    'display_name' => $displayName,
                    'detail_label' => trim($displayName . ' - ' . ($product->brand_line ?: ''), ' -'),
                    'product_type' => 'Aroma/Variant'
                ];
            })
            ->sortBy(fn ($product) => strtolower(($product['brand_line'] ?? '') . '|' . ($product['display_name'] ?? '')))
            ->values();

        return response()->json($aromaProducts);
    }

    protected function getActiveContractOptions(?string $search = null, int $limit = 50)
    {
        $activeStatuses = ['active', 'approved', 'aktif', 'Approved', 'Active', 'Aktif'];

        $query = Contract::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->with('customer')
            ->where(function ($q) use ($activeStatuses) {
                $q->whereIn('contract_status', $activeStatuses)
                    ->orWhereIn('status', $activeStatuses);
            })
            ->select('id', 'contract_number', 'customer_id', 'contract_status', 'status')
            ->orderBy('id', 'desc');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('contract_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function($dq) use ($search) {
                        $dq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('company_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->limit($limit)->get()->map(function($contract) {
            return [
                'id' => $contract->id,
                'text' => $contract->contract_number . ' - ' . ($contract->customer->company_name ?? $contract->customer->name ?? 'Unknown Customer')
            ];
        })->values();
    }

    /**
     * Store a new aroma change (MOM6 Flow)
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'contract_room_id' => 'required|exists:contract_rooms,id',
            'new_product_type_id' => 'required', // Contains MasterProduct ID from frontend
            'change_reason' => 'required|string|max:1000',
            'change_description' => 'nullable|string|max:2000'
        ]);

        try {
            DB::beginTransaction();

            // Get contract room to fetch current aroma and details
            $contractRoom = ContractRoom::with(['room.building', 'room'])->findOrFail($request->contract_room_id);
            
            // Detect previous product category (MOM6 Requirement)
            $previousProductCategoryId = $this->detectProductCategoryId($contractRoom->aroma_code ?? '');
            
            // Resolve Previous Aroma (Must fetch from Quotation as ContractRoom might not store it)
            $previousAromaCode = $contractRoom->aroma_code ?? '';
            $previousAromaName = $contractRoom->aroma_name ?? '';
            $previousMasterProduct = null;
            
            // Fetch Quotation Data
            $contractForAroma = Contract::with([
                'quotation.quotationRooms.aromaProduct.productCategory',
                'quotation.quotationRooms.aromaProduct.productType',
                'quotation.quotationRooms.aromaProduct.packagingSize'
            ])->find($request->contract_id);
            if ($contractForAroma && $contractForAroma->quotation && $contractForAroma->quotation->quotationRooms) {
                $qRoom = $contractForAroma->quotation->quotationRooms->where('room_id', $contractRoom->room_id)->first();
                if ($qRoom && $qRoom->aromaProduct) {
                    $previousMasterProduct = $qRoom->aromaProduct;
                    $previousAromaCode = $this->aromaProductCode($qRoom->aromaProduct);
                    $previousAromaName = $this->aromaProductDisplayName($qRoom->aromaProduct);
                    $previousProductCategoryId = $qRoom->aromaProduct->product_category_id ?: $previousProductCategoryId;
                }
            }

            // Resolve new aroma details from MasterProduct (frontend sends MasterProductID as new_product_type_id)
            $masterProduct = MasterProduct::find($request->new_product_type_id);
            
            if ($masterProduct) {
                // The column 'new_product_category_id' captures the target category
                $newProductCategoryId = $masterProduct->product_category_id;
                // Also keep new_product_type_id for backward compatibility (stores MasterProduct ID in this flow)
                $newProductTypeId = $masterProduct->id; 
                $newAromaCode = $this->aromaProductCode($masterProduct);
                $newAromaName = $this->aromaProductDisplayName($masterProduct);
            } else {
                // Fallback (Should not happen if selected from list)
                $newProductCategoryId = $this->detectProductCategoryId('');
                $newProductTypeId = 0;
                $newAromaCode = 'UNKNOWN';
                $newAromaName = 'Unknown Product';
            }

            // Get contract for number generation
            $contract = Contract::find($request->contract_id);

            // Resolve Unit ID (Handle case where room has no unit, and table units empty)
            $unitId = $contractRoom->room->unit_id ?? 0;

            // Temp disable FK checks to allow unit_id=0 if units table is empty
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // MOM13: Check Brand Line for Auto-Approval
            $isAutoApproved = false;
            $approvalNotes = null;
            
            // Resolve Previous Master Product to check Brand Line
            if (!$previousMasterProduct && ($previousAromaCode || $previousAromaName)) {
                // Try to find exact match
                $query = MasterProduct::query();
                if ($previousAromaCode) {
                    $query->where('product_code', $previousAromaCode);
                }
                if ($previousAromaName) {
                    $query->orWhere('variant_name', $previousAromaName)
                          ->orWhere('name', $previousAromaName);
                }
                $previousMasterProduct = $query->first();
            }

            $this->ensureSameAromaBrandLine($previousMasterProduct, $masterProduct);
            $this->ensureDifferentAromaProduct($previousMasterProduct, $masterProduct);
            
            // Compare Brand Lines
            if ($masterProduct && $previousMasterProduct) {
                $newBrandLine = $masterProduct->brand_line;
                $prevBrandLine = $previousMasterProduct->brand_line;
                
                if ($newBrandLine && $prevBrandLine && strtolower(trim($newBrandLine)) === strtolower(trim($prevBrandLine))) {
                    $isAutoApproved = true;
                    $approvalNotes = "Auto-approved: Same Brand Line ({$newBrandLine})";
                    Log::info("Aroma Change Auto-Approved: Same Brand Line ({$newBrandLine})");
                }
            }

            // Create aroma change record
            $aromaChange = AromaChange::create([
                'change_number' => AromaChange::generateChangeNumber($contract),
                'contract_id' => $request->contract_id,
                'building_id' => $contractRoom->room->building_id, // Trusted from relation
                'unit_id' => $unitId,
                'room_id' => $contractRoom->room_id, // Trusted from relation
                'contract_room_id' => $request->contract_room_id,
                
                // Previous Data
                'previous_aroma_code' => $previousAromaCode,
                'previous_aroma_name' => $previousAromaName,
                'previous_product_type_id' => $previousProductCategoryId, // Keep for legacy
                'previous_product_category_id' => $previousProductCategoryId,
                'previous_product_id' => $previousMasterProduct ? $previousMasterProduct->id : null,
                
                // New Data
                'new_aroma' => $newAromaName,
                'new_aroma_code' => $newAromaCode,
                'new_aroma_name' => $newAromaName,
                'new_product_type_id' => $newProductTypeId, // misnomer but used as product_id
                'new_product_category_id' => $newProductCategoryId,
                'new_product_id' => $masterProduct ? $masterProduct->id : null,
                
                // Details
                'change_reason' => $request->input('change_reason'),
                'change_description' => $request->change_description,
                'change_notes' => $request->change_notes,
                'effective_schedule_id' => $request->effective_schedule_id,
                
                // Status (Auto-Approve Logic)
                'status' => $isAutoApproved ? AromaChange::STATUS_APPROVED : AromaChange::STATUS_DRAFT,
                'approval_notes' => $approvalNotes,
                'approved_by' => $isAutoApproved ? Auth::id() : null,
                'approved_at' => $isAutoApproved ? now() : null,
                
                // User tracking
                'requested_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            // Re-enable FK checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::commit();

            Log::info("Aroma Change created: {$aromaChange->change_number}", [
                'previous' => "{$aromaChange->previous_aroma_name}",
                'new' => "{$aromaChange->new_aroma_name}"
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change request created successfully',
                    'data' => $aromaChange->load(['contract', 'building', 'room'])
                ]);
            }

            return redirect()->route('marketing.aroma-changes.show', $aromaChange)
                ->with('success', 'Aroma change request created successfully');

        } catch (\InvalidArgumentException $e) {
            DB::rollback();
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1;'); } catch (\Exception $ex) {}

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            // Ensure FK checks are re-enabled in case of error
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1;'); } catch (\Exception $ex) {}
            
            Log::error("Failed to create aroma change: " . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create aroma change: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create aroma change: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified aroma change
     */
    public function show(AromaChange $aromaChange)
    {
        $aromaChange->load([
            'contract.customer',
            'building',
            'room',
            'contractRoom',
            'requestedBy',
            'approvedBy',
            'appliedBy',
            'previousProduct.productCategory',
            'previousProduct.productType',
            'previousProduct.packagingSize',
            'newProduct.productCategory',
            'newProduct.productType',
            'newProduct.packagingSize',
            'createdBy',
            'updatedBy'
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            $aromaChange->append(['status_text', 'status_badge']);
            return response()->json([
                'status' => 'success',
                'data' => $aromaChange
            ]);
        }

        return view('marketing.aroma-changes.show', compact('aromaChange'));
    }

    /**
     * Show the form for editing aroma change (before approval)
     */
    public function edit(AromaChange $aromaChange)
    {
        // Only allow edit if status is draft or rejected
        if (!in_array($aromaChange->status, [AromaChange::STATUS_DRAFT, AromaChange::STATUS_REJECTED])) {
            return back()->with('error', 'Cannot edit aroma change in current status');
        }

        $aromaChange->load(['contract', 'building', 'room', 'contractRoom']);
        $contracts = Contract::with('customer')->whereIn('status', ['active', 'approved'])->get();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'aromaChange' => $aromaChange,
                    'contracts' => $contracts
                ]
            ]);
        }

        return view('marketing.aroma-changes.edit', compact('aromaChange', 'contracts'));
    }

    /**
     * Update aroma change (before approval)
     */
    public function update(Request $request, AromaChange $aromaChange)
    {
        // Only allow update if status is draft or rejected
        if (!in_array($aromaChange->status, [AromaChange::STATUS_DRAFT, AromaChange::STATUS_REJECTED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update aroma change in current status'
            ], 403);
        }

        $request->validate([
            'new_aroma_code' => 'required|string|max:50',
            'new_aroma_name' => 'required|string|max:100',
            'new_product_type_id' => 'required', // Contains MasterProduct ID from frontend
            'change_reason' => 'required|string|max:1000',
            'change_description' => 'nullable|string|max:2000'
        ]);

        try {
            DB::beginTransaction();

            // Resolve new category details
            $masterProduct = MasterProduct::find($request->new_product_type_id);
            $newProductCategoryId = $masterProduct ? $masterProduct->product_category_id : null;

            $previousMasterProduct = $this->resolvePreviousAromaProductForChange($aromaChange);
            $this->ensureSameAromaBrandLine($previousMasterProduct, $masterProduct);
            $this->ensureDifferentAromaProduct($previousMasterProduct, $masterProduct);

            $aromaChange->update([
                'new_aroma_code' => $masterProduct ? $this->aromaProductCode($masterProduct) : $request->new_aroma_code,
                'new_aroma_name' => $masterProduct ? $this->aromaProductDisplayName($masterProduct) : $request->new_aroma_name,
                'new_product_type_id' => $request->new_product_type_id,
                'new_product_category_id' => $newProductCategoryId,
                'new_product_id' => $masterProduct ? $masterProduct->id : null,
                'change_reason' => $request->input('change_reason'),
                'change_description' => $request->change_description,
                'change_notes' => $request->change_notes,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            Log::info("Aroma Change updated: {$aromaChange->change_number}");

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change updated successfully',
                    'data' => $aromaChange->fresh(['contract', 'building', 'room'])
                ]);
            }

            return redirect()->route('marketing.aroma-changes.show', $aromaChange)
                ->with('success', 'Aroma change updated successfully');

        } catch (\InvalidArgumentException $e) {
            DB::rollback();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to update aroma change: " . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update aroma change: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update aroma change: ' . $e->getMessage());
        }
    }

    /**
     * Submit aroma change for approval (MOM6 Flow)
     */
    public function submitForApproval(AromaChange $aromaChange)
    {
        // Only allow if status is draft or rejected
        if (!in_array($aromaChange->status, [AromaChange::STATUS_DRAFT, AromaChange::STATUS_REJECTED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot submit aroma change in current status'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $aromaChange->submitForApproval();

            DB::commit();

            Log::info("Aroma Change submitted for approval: {$aromaChange->change_number}");

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change submitted for approval',
                    'data' => $aromaChange->fresh()
                ]);
            }

            return back()->with('success', 'Aroma change submitted for approval');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to submit aroma change: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to submit: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to submit: ' . $e->getMessage());
        }
    }

    /**
     * Approve aroma change (MOM6 Approval Flow)
     */
    public function approve(Request $request, AromaChange $aromaChange)
    {
        // Only allow if status is pending
        if ($aromaChange->status !== AromaChange::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot approve aroma change in current status'
            ], 403);
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // 1. Approve
            $aromaChange->approve(Auth::id(), $request->approval_notes);

            // 2. Auto-Apply (MOM Update: Apply immediately upon approval)
            $aromaChange->applyChange(Auth::id());

            DB::commit();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change approved and applied successfully',
                    'data' => $aromaChange->fresh()
                ]);
            }

            return back()->with('success', 'Aroma change approved and applied successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to approve aroma change: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to approve: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    /**
     * Reject aroma change (MOM6 Approval Flow)
     */
    public function reject(Request $request, AromaChange $aromaChange)
    {
        // Only allow if status is pending
        if ($aromaChange->status !== AromaChange::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot reject aroma change in current status'
            ], 403);
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $aromaChange->reject(Auth::id(), $request->approval_notes);

            DB::commit();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change rejected',
                    'data' => $aromaChange->fresh()
                ]);
            }

            return back()->with('success', 'Aroma change rejected');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to reject aroma change: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to reject: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to reject: ' . $e->getMessage());
        }
    }

    /**
     * Get future schedules for a contract room
     */
    public function getSchedules(Request $request)
    {
        try {
            $contractId = $request->contract_id;
            $contractRoomId = $request->contract_room_id;

            if (!$contractId || !$contractRoomId) {
                return response()->json([]);
            }

            // Get the room details
            $contractRoom = ContractRoom::find($contractRoomId);
            if (!$contractRoom) {
                return response()->json([]);
            }

            // Get the contract to find its number
            $contract = Contract::find($contractId);
            if (!$contract) {
                \Log::warning('Contract not found for ID: ' . $contractId);
                return response()->json([]);
            }

            \Log::info('Fetching schedules for contract: ' . $contract->contract_number . ' (ID: ' . $contractId . ')');

            // Find schedules for this contract
            $schedules = JobSchedule::where(function($query) use ($contract, $contractId) {
                    $query->where('contract_number', $contract->contract_number)
                          ->orWhereHas('jobAdvice', function($q) use ($contractId) {
                                $q->where('contract_id', $contractId);
                          });
                })
                ->where(function($query) use ($contractRoom) {
                    $query->where('room_id', $contractRoom->room_id)
                        ->orWhereHas('jobScheduleRooms', function($q) use ($contractRoom) {
                            $q->where('room_id', $contractRoom->room_id);
                        });
                })
                ->whereIn('status', ['scheduled', 'new_job', 'assign_team', 'assign_material']) // pending statuses
                ->whereDate('schedule_date', '>=', now())
                ->orderBy('schedule_date', 'asc')
                ->get();

            // Filter relevant schedules and format for frontend
            $formattedSchedules = $schedules
                ->unique(function($schedule) {
                    return $schedule->schedule_date->format('Y-m') . '|' . $schedule->period;
                })
                ->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'date_formatted' => $schedule->schedule_date->format('d M Y'),
                        'period' => $schedule->period, // e.g., "Service #1" or "Jan 2026"
                        'label' => $schedule->schedule_date->format('M Y') . ' (Service Period: ' . $schedule->period . ')'
                    ];
                })
                ->values();

            return response()->json($formattedSchedules);

        } catch (\Exception $e) {
            \Log::error('Error fetching schedules: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function applyChange(AromaChange $aromaChange)
    {
        // Only allow if status is approved
        if ($aromaChange->status !== AromaChange::STATUS_APPROVED) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only apply approved aroma changes'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $aromaChange->applyChange(Auth::id());

            DB::commit();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change applied successfully. Contract room updated.',
                    'data' => $aromaChange->fresh(['contractRoom'])
                ]);
            }

            return back()->with('success', 'Aroma change applied successfully. Contract room updated.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to apply aroma change: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to apply change: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to apply change: ' . $e->getMessage());
        }
    }

    /**
     * Cancel aroma change
     */
    public function cancel(AromaChange $aromaChange)
    {
        // Can only cancel draft or pending
        if (!in_array($aromaChange->status, [AromaChange::STATUS_DRAFT, AromaChange::STATUS_PENDING])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel aroma change in current status'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $aromaChange->cancel();

            DB::commit();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change cancelled',
                    'data' => $aromaChange->fresh()
                ]);
            }

            return back()->with('success', 'Aroma change cancelled');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to cancel aroma change: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to cancel: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to cancel: ' . $e->getMessage());
        }
    }

    /**
     * Delete aroma change (soft delete)
     */
    public function destroy(AromaChange $aromaChange)
    {
        // Can only delete draft or cancelled
        if (!in_array($aromaChange->status, [AromaChange::STATUS_DRAFT, AromaChange::STATUS_CANCELLED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete aroma change in current status'
            ], 403);
        }

        try {
            $aromaChange->delete();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aroma change deleted'
                ]);
            }

            return redirect()->route('marketing.aroma-changes.index')
                ->with('success', 'Aroma change deleted');

        } catch (\Exception $e) {
            Log::error("Failed to delete aroma change: " . $e->getMessage());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /**
     * Get aroma change history for a contract room (MOM6 Requirement)
     */
    public function history(Request $request)
    {
        $request->validate([
            'contract_room_id' => 'required|exists:contract_rooms,id'
        ]);

        $history = AromaChange::where('contract_room_id', $request->contract_room_id)
            ->with(['requestedBy', 'approvedBy', 'appliedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Detect product category ID based on aroma code (MOM6 Product Detection)
     * Luxo, Artisan, Signature are now Categories
     * 
     * @param string $aromaCode
     * @return int|null
     */
    private function detectProductCategoryId($aromaCode)
    {
        if (empty($aromaCode)) {
            return null;
        }

        // Try to find in master products
        $product = MasterProduct::where('product_code', $aromaCode)
            ->orWhere('name', 'like', "%{$aromaCode}%")
            ->first();

        if ($product && $product->product_category_id) {
            return $product->product_category_id;
        }

        return null; // Unknown category
    }

    private function resolvePreviousAromaProductForChange(AromaChange $aromaChange): ?MasterProduct
    {
        if ($aromaChange->previous_product_id) {
            $product = MasterProduct::find($aromaChange->previous_product_id);
            if ($product) {
                return $product;
            }
        }

        if (!$aromaChange->previous_aroma_code && !$aromaChange->previous_aroma_name) {
            return null;
        }

        $query = MasterProduct::query();

        if ($aromaChange->previous_aroma_code) {
            $query->where('product_code', $aromaChange->previous_aroma_code);
        }

        if ($aromaChange->previous_aroma_name) {
            $query->orWhere('variant_name', $aromaChange->previous_aroma_name)
                ->orWhere('name', $aromaChange->previous_aroma_name);
        }

        return $query->first();
    }

    private function ensureSameAromaBrandLine(?MasterProduct $previousProduct, ?MasterProduct $newProduct): void
    {
        if (!$previousProduct || !$newProduct) {
            throw new \InvalidArgumentException('Aroma lama atau aroma baru tidak valid. Pergantian aroma tidak bisa diproses.');
        }

        $previousBrandLine = $this->normalizeBrandLine($previousProduct->brand_line);
        $newBrandLine = $this->normalizeBrandLine($newProduct->brand_line);

        if (!$previousBrandLine || !$newBrandLine) {
            throw new \InvalidArgumentException('Brand line aroma lama atau aroma baru belum lengkap. Pergantian aroma tidak bisa diproses.');
        }

        if ($previousBrandLine !== $newBrandLine) {
            throw new \InvalidArgumentException(sprintf(
                'Aroma tidak boleh pindah brand line dari %s ke %s.',
                $previousProduct->brand_line ?: '-',
                $newProduct->brand_line ?: '-'
            ));
        }
    }

    private function ensureDifferentAromaProduct(?MasterProduct $previousProduct, ?MasterProduct $newProduct): void
    {
        if (!$previousProduct || !$newProduct) {
            return;
        }

        $sameProduct = (int) $previousProduct->id === (int) $newProduct->id;
        $sameBrandLine = $this->normalizeBrandLine($previousProduct->brand_line) === $this->normalizeBrandLine($newProduct->brand_line);
        $sameAromaName = $this->normalizeAromaName($this->aromaProductDisplayName($previousProduct)) === $this->normalizeAromaName($this->aromaProductDisplayName($newProduct));

        if ($sameProduct || ($sameBrandLine && $sameAromaName)) {
            throw new \InvalidArgumentException('Aroma baru harus berbeda dari aroma lama.');
        }
    }

    private function normalizeBrandLine(?string $brandLine): ?string
    {
        $normalized = trim(strtolower((string) $brandLine));

        return $normalized !== '' ? preg_replace('/\s+/', ' ', $normalized) : null;
    }

    private function isSelectableAromaProduct(?MasterProduct $product): bool
    {
        if (!$product) {
            return false;
        }

        $name = strtolower(trim((string) $product->name));
        $sku = strtolower(trim((string) $product->sku));
        $variant = strtolower(trim((string) $product->variant_name));
        $categoryName = strtolower($product->productCategory?->name ?? '');
        $typeName = strtolower($product->productType?->name ?? '');

        $isUnit = (bool) ($product->productCategory?->is_unit ?? $product->productType?->is_unit ?? false);
        $hasSerialNumber = (bool) ($product->productCategory?->has_serial_number ?? $product->productType?->has_serial_number ?? false);
        $isTestProduct = str_contains($name, 'test')
            || str_contains($sku, 'test')
            || str_contains($variant, 'test')
            || preg_match('/^ta\d*/i', (string) $product->sku);

        $looksLikeAroma = str_contains($name, 'fragrance')
            || str_contains($name, 'aroma')
            || str_contains($name, 'refill')
            || str_contains($name, 'scent')
            || str_contains($categoryName, 'refill')
            || str_contains($categoryName, 'aroma')
            || str_contains($categoryName, 'fragrance')
            || str_contains($categoryName, 'scent')
            || str_contains($typeName, 'aroma')
            || str_contains($typeName, 'fragrance')
            || str_contains($typeName, 'scent')
            || str_contains($typeName, 'variant')
            || str_contains($typeName, 'refill');

        return !$isUnit && !$hasSerialNumber && !$isTestProduct && $looksLikeAroma;
    }

    private function aromaProductGroupKey(MasterProduct $product): string
    {
        return implode('|', [
            $this->normalizeBrandLine($product->brand_line) ?: '',
            $this->normalizeAromaName($this->aromaProductDisplayName($product)),
        ]);
    }

    private function preferredAromaProduct($products): MasterProduct
    {
        return collect($products)
            ->sortBy(function($candidate) {
                $categoryName = strtolower($candidate->productCategory?->name ?? '');
                $packageName = strtolower(preg_replace('/\s+/', '', (string) $candidate->packagingSize?->name));

                return [
                    str_contains($categoryName, 'refill') || str_contains($categoryName, 'aroma') ? 0 : 1,
                    $packageName === '100ml' ? 0 : 1,
                    $candidate->id,
                ];
            })
            ->first();
    }

    private function aromaProductDisplayName(?MasterProduct $product): string
    {
        if (!$product) {
            return '';
        }

        $name = trim((string) $product->name);
        $nameWithoutPackaging = $this->removePackagingSuffix($name, $product->packagingSize?->name);

        return $nameWithoutPackaging
            ?: trim((string) $product->variant_name)
            ?: trim((string) $product->sku)
            ?: (string) $product->id;
    }

    private function aromaProductCode(?MasterProduct $product): string
    {
        if (!$product) {
            return '';
        }

        return trim((string) (
            $product->getAttribute('product_code')
            ?: $product->sku
            ?: $product->getAttribute('variant')
            ?: $product->variant_name
            ?: $product->id
        ));
    }

    private function removePackagingSuffix(string $name, ?string $packagingName = null): string
    {
        $cleanName = trim($name);
        $packagingName = trim((string) $packagingName);

        if ($packagingName !== '') {
            $pattern = '/\s*' . preg_quote($packagingName, '/') . '\.?$/i';
            $cleanName = trim(preg_replace($pattern, '', $cleanName));
        }

        return trim(preg_replace('/\s+\d+(?:[\.,]\d+)?\s*(ml|ltr|liter|l|gr|gram|g|kg)\.?$/i', '', $cleanName));
    }

    private function normalizeAromaName(?string $value): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim((string) $value)));
    }
}
