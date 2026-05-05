<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\JobAssignMaterialIssue;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\MaterialIssue;
use App\Models\Team;
use App\Models\MasterProduct;
use App\Models\Customer;
use App\Models\Building;
use App\Models\Warehouse;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobAssignMaterialIssueController extends Controller
{
    use AccessControlFilterTrait;

    private function extractRentalModelTokens(?string $value): array
    {
        if (!$value) {
            return [];
        }

        preg_match_all('/[A-Z]+\s*-?\d+[A-Z0-9-]*/i', strtoupper($value), $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($token) => preg_replace('/[^A-Z0-9]/', '', $token))
            ->filter(fn ($token) => preg_match('/[A-Z]/', $token) && preg_match('/\d/', $token))
            ->unique()
            ->values()
            ->all();
    }

    private function resolvePreferredRentalDetailProduct($detail, $rental, $fallbackProduct = null)
    {
        $selectedProducts = $detail->allowedProducts
            ? $detail->allowedProducts->where('pivot.is_selected', true)->values()
            : collect();

        if ($selectedProducts->isEmpty()) {
            return $fallbackProduct;
        }

        $tokens = array_values(array_unique(array_merge(
            $this->extractRentalModelTokens($rental->rental_name ?? null),
            $this->extractRentalModelTokens($rental->rental_code ?? null)
        )));

        $scoredCandidates = $selectedProducts->map(function ($candidate) use ($tokens, $detail, $fallbackProduct) {
            $haystack = strtoupper(implode(' ', array_filter([
                $candidate->name ?? null,
                $candidate->sku ?? null,
                $candidate->variant_name ?? null,
            ])));
            $normalizedHaystack = preg_replace('/[^A-Z0-9]/', '', $haystack);

            $score = 0;
            foreach ($tokens as $token) {
                if ($token && str_contains($normalizedHaystack, $token)) {
                    $score += 100;
                }
            }

            if ($fallbackProduct && $candidate->id === $fallbackProduct->id) {
                $score += 25;
            }

            if ($detail->product_type_id && $candidate->product_type_id === $detail->product_type_id) {
                $score += 10;
            }

            if ($detail->product_category_id && $candidate->product_category_id === $detail->product_category_id) {
                $score += 5;
            }

            return [
                'product' => $candidate,
                'score' => $score,
                'sort_order' => $candidate->pivot->sort_order ?? 9999,
            ];
        })->sortBy([
            ['score', 'desc'],
            ['sort_order', 'asc'],
        ])->values();

        $bestCandidate = $scoredCandidates->first();
        if (!$bestCandidate) {
            return $fallbackProduct;
        }

        if (($bestCandidate['score'] ?? 0) > 0) {
            return $bestCandidate['product'];
        }

        if ($fallbackProduct && $selectedProducts->contains('id', $fallbackProduct->id)) {
            return $fallbackProduct;
        }

        return $selectedProducts->sortBy(fn ($product) => $product->pivot->sort_order ?? 9999)->first();
    }

    private function normalizeProductBrandLine(?string $brandLine): ?string
    {
        $normalized = trim(strtolower((string) $brandLine));

        return $normalized !== '' ? preg_replace('/\s+/', ' ', $normalized) : null;
    }

    private function productsHaveSameBrandLine(?MasterProduct $currentProduct, ?MasterProduct $newProduct): bool
    {
        $currentBrandLine = $this->normalizeProductBrandLine($currentProduct?->brand_line);
        $newBrandLine = $this->normalizeProductBrandLine($newProduct?->brand_line);

        return $currentBrandLine === $newBrandLine;
    }

    private function isAromaMaterialProduct(?MasterProduct $product): bool
    {
        if (!$product) {
            return false;
        }

        $haystack = $this->buildMaterialClassificationText([
            $product->name ?? null,
            $product->sku ?? null,
            $product->variant_name ?? null,
            $product->brand_line ?? null,
            $product->productType?->name ?? null,
            $product->productCategory?->name ?? null,
        ]);

        if ($this->containsHandSanitizerMaterialKeywords($haystack)) {
            return false;
        }

        $isUnit = (bool) ($product->productCategory?->is_unit ?? $product->productType?->is_unit ?? false);
        $hasSerialNumber = (bool) ($product->productCategory?->has_serial_number ?? $product->productType?->has_serial_number ?? false);

        return !$isUnit
            && !$hasSerialNumber
            && $this->containsAromaMaterialKeywords($haystack);
    }

    private function isQuotationAromaMaterialSlot($detail, ?MasterProduct $product = null, ?string $extraName = null): bool
    {
        $haystack = $this->buildMaterialClassificationText([
            $extraName,
            $detail->productCategory->name ?? null,
            $detail->productType->name ?? null,
            $product->name ?? null,
            $product->sku ?? null,
            $product->variant_name ?? null,
            $product->brand_line ?? null,
            $product->productCategory?->name ?? null,
            $product->productType?->name ?? null,
        ]);

        if ($this->containsHandSanitizerMaterialKeywords($haystack)) {
            return false;
        }

        return $this->containsAromaMaterialKeywords($haystack);
    }

    private function isQuotationAromaComponentName(?string $componentName): bool
    {
        $haystack = $this->buildMaterialClassificationText([$componentName]);

        return !$this->containsHandSanitizerMaterialKeywords($haystack)
            && $this->containsAromaMaterialKeywords($haystack);
    }

    private function buildMaterialClassificationText(array $parts): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim(implode(' ', array_filter($parts)))));
    }

    private function containsHandSanitizerMaterialKeywords(string $haystack): bool
    {
        return str_contains($haystack, 'hand sanitizer')
            || str_contains($haystack, 'sanitizer')
            || preg_match('/\bhs\s*refill\b/', $haystack) === 1
            || preg_match('/\bhsr[-\s]/', $haystack) === 1
            || preg_match('/\bhsd[-\s]/', $haystack) === 1;
    }

    private function containsAromaMaterialKeywords(string $haystack): bool
    {
        foreach (['aroma', 'scent', 'fragrance', 'luxo', 'artisan', 'signature'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return preg_match('/\boil\b/', $haystack) === 1;
    }

    private function resolveQuotationAromaProductForDetail($detail, ?MasterProduct $quotationProduct, ?MasterProduct $fallbackProduct): ?MasterProduct
    {
        if (!$quotationProduct) {
            return null;
        }

        $variantName = trim((string) $quotationProduct->variant_name);
        $brandLine = $this->normalizeProductBrandLine($quotationProduct->brand_line);

        $allowedProducts = $detail->allowedProducts
            ? $detail->allowedProducts->where('pivot.is_selected', true)->values()
            : collect();

        $candidates = $allowedProducts->isNotEmpty()
            ? $allowedProducts
            : MasterProduct::with(['productType', 'productCategory', 'packagingSize'])
                ->where('is_active', true)
                ->when($variantName !== '', fn ($query) => $query->where('variant_name', $variantName))
                ->when($brandLine, fn ($query) => $query->whereRaw('LOWER(TRIM(brand_line)) = ?', [$brandLine]))
                ->get();

        $targetPackagingId = $fallbackProduct?->packaging_size_id ?? $detail->masterProduct?->packaging_size_id;

        return $candidates
            ->filter(function ($candidate) use ($variantName, $brandLine) {
                if (!$this->isAromaMaterialProduct($candidate)) {
                    return false;
                }

                if ($variantName !== '' && strcasecmp(trim((string) $candidate->variant_name), $variantName) !== 0) {
                    return false;
                }

                if ($brandLine && $this->normalizeProductBrandLine($candidate->brand_line) !== $brandLine) {
                    return false;
                }

                return true;
            })
            ->sortBy(function ($candidate) use ($targetPackagingId, $quotationProduct) {
                $categoryName = strtolower($candidate->productCategory?->name ?? '');
                $productName = strtolower($candidate->name ?? '');

                return [
                    $targetPackagingId && (int) $candidate->packaging_size_id === (int) $targetPackagingId ? 0 : 1,
                    str_contains($categoryName, 'refill') ? 0 : 1,
                    str_contains($productName, 'test') ? 1 : 0,
                    (int) $candidate->id === (int) $quotationProduct->id ? 0 : 1,
                    $candidate->id,
                ];
            })
            ->first();
    }

    private function getAssignedJobAdviceRooms($jobAdvice, $jobSchedule)
    {
        if (!$jobAdvice || !$jobAdvice->rooms) {
            return collect();
        }

        $assignedRoomIds = collect();

        if ($jobSchedule) {
            $jobScheduleRoomIds = $jobSchedule->jobScheduleRooms()->pluck('id');

            if ($jobScheduleRoomIds->isNotEmpty()) {
                $assignedRoomIds = \App\Models\JobScheduleRoomRental::whereIn('job_schedule_room_id', $jobScheduleRoomIds)
                    ->pluck('job_advice_room_id')
                    ->filter()
                    ->unique()
                    ->values();
            }

            if ($assignedRoomIds->isEmpty()) {
                $assignedRoomIds = $jobSchedule->jobScheduleRooms()
                    ->pluck('job_advice_room_id')
                    ->filter()
                    ->unique()
                    ->values();
            }
        }

        if ($assignedRoomIds->isNotEmpty()) {
            return $jobAdvice->rooms->whereIn('id', $assignedRoomIds)->values();
        }

        if ($jobSchedule && !empty($jobSchedule->room_name)) {
            $matchedRooms = $jobAdvice->rooms->where('room_name', $jobSchedule->room_name)->values();
            if ($matchedRooms->isNotEmpty()) {
                return $matchedRooms;
            }
        }

        return $jobAdvice->rooms;
    }

    private function extractSavedItemComponentId(?string $notes): ?string
    {
        if (!$notes) {
            return null;
        }

        if (preg_match('/(?:ComponentID|RentalDetailID):\s*(\d+)/', $notes, $matches)) {
            return $matches[1];
        }

        return null;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = JobAssignMaterialIssue::query()->with([
            'jobAssignSchedule:id,job_schedule_id,team_id,created_at',
            'jobAssignSchedule.team:id,team_name,branch_office',
            'jobAssignSchedule.team.branch:id,name',
            'jobAssignSchedule.jobSchedule:id,job_number,job_advice_id,building_id,schedule_date',
            'jobAssignSchedule.jobSchedule.jobAdvice:id,customer_id,contract_id,quotation_id',
            'jobAssignSchedule.jobSchedule.jobAdvice.customer:id,name',
            'jobAssignSchedule.jobSchedule.jobAdvice.contract:id,quotation_id,notes_operation',
            'jobAssignSchedule.jobSchedule.building:id,nama_gedung,city_id',
            'jobAssignSchedule.jobSchedule.building.city:id,name,province_id',
            'jobAssignSchedule.jobSchedule.building.city.branches:id,name,city_id,province_id',
            'jobAssignSchedule.jobSchedule.building.city.province:id,name',
            'jobAssignSchedule.jobSchedule.building.city.province.branches:id,name,city_id,province_id',
            'materialIssue:id,warehouse_id,team_id,issue_date,status,metadata,previous_product_id,product_change_note',
            'materialIssue.team:id,team_name',
            'materialIssue.previousProduct:id,name',
            'materialIssue.warehouse:id,name,branch_id,is_active',
            'materialIssue.items:id,material_issue_id,job_assign_schedule_id,product_id,room_name,quantity,convert,bom_quantity,notes,is_copied',
            'materialIssue.items.product:id,name,sku,product_type_id,product_category_id,packaging_size_id,bom_quantity,variant_name,brand_line',
            'materialIssue.items.product.productType:id,name',
            'materialIssue.items.product.productCategory:id,name',
            'materialIssue.items.product.packagingSize:id,name',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // Filter by created_by and also by jobAssignSchedule.jobSchedule.jobAdvice.created_by/requested_by
        $user = Auth::user();
        if (!$user->hasRoleStartingWith('Management')) {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            
            // Get teams where user is leader or member
            $userTeamIds = \DB::table('teams')
                ->where('team_head_id', $user->id)
                ->pluck('id')
                ->merge(
                    \DB::table('team_members')
                        ->where('user_id', $user->id)
                        ->pluck('team_id')
                )
                ->unique()
                ->toArray();
            
            $query->where(function($q) use ($accessibleUserIds, $userTeamIds) {
                $q->whereIn('created_by', $accessibleUserIds)
                  ->orWhereHas('jobAssignSchedule.jobSchedule.jobAdvice', function($subQ) use ($accessibleUserIds) {
                      $subQ->whereIn('created_by', $accessibleUserIds)
                           ->orWhereIn('request_by', $accessibleUserIds);
                  })
                  // Include material issues for user's teams
                  ->orWhereHas('jobAssignSchedule', function($subQ) use ($userTeamIds) {
                      $subQ->whereIn('team_id', $userTeamIds);
                  });
            });
        }

        // Filter by job number
        if ($request->filled('job_number')) {
            $query->whereHas('jobAssignSchedule.jobSchedule', function($q) use ($request) {
                $q->where('job_number', 'like', "%{$request->job_number}%");
            });
        }

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->whereHas('jobAssignSchedule.jobSchedule.jobAdvice.customer', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->customer_name}%");
            });
        }

        // Filter by building
        if ($request->filled('building_name')) {
            $query->whereHas('jobAssignSchedule.jobSchedule.building', function($q) use ($request) {
                $q->where('nama_gedung', 'like', "%{$request->building_name}%");
            });
        }

        // Filter by team
        if ($request->filled('team_name')) {
            $query->where(function($q) use ($request) {
                $q->whereHas('materialIssue.team', function($sq) use ($request) {
                    $sq->where('team_name', 'like', "%{$request->team_name}%");
                })->orWhereHas('jobAssignSchedule.team', function($sq) use ($request) {
                    $sq->where('team_name', 'like', "%{$request->team_name}%");
                });
            });
        }

        // Filter by job schedule date range
        if ($request->filled('date_from')) {
            $query->whereHas('jobAssignSchedule.jobSchedule', function($q) use ($request) {
                $q->whereDate('schedule_date', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->whereHas('jobAssignSchedule.jobSchedule', function($q) use ($request) {
                $q->whereDate('schedule_date', '<=', $request->date_to);
            });
        }

        // Filter by issue date
        if ($request->filled('issue_date')) {
            $query->whereHas('materialIssue', function($q) use ($request) {
                $q->whereDate('issue_date', $request->issue_date);
            });
        }

        // Filter by room name
        if ($request->filled('room_name')) {
            $query->whereHas('materialIssue.items', function($q) use ($request) {
                $q->where('room_name', 'like', "%{$request->room_name}%");
            });
        }

        // Filter by rental name
        if ($request->filled('rental_name')) {
            $query->whereHas('materialIssue.items', function($q) use ($request) {
                $q->where('notes', 'like', "%Rental:%{$request->rental_name}%");
            });
        }

        // Filter by material/product name
        if ($request->filled('material_name')) {
            $query->whereHas('materialIssue.items.product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->material_name}%")
                    ->orWhere('sku', 'like', "%{$request->material_name}%")
                    ->orWhere('variant_name', 'like', "%{$request->material_name}%");
            });
        }

        // Filter by warehouse
        if ($request->filled('warehouse_name')) {
            $query->whereHas('materialIssue.warehouse', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->warehouse_name}%");
            });
        }

        // Filter by product type
        if ($request->filled('product_type')) {
            $query->whereHas('materialIssue.items.product.productType', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->product_type}%");
            });
        }
        
        // Filter by op notes
        if ($request->filled('notes_operation')) {
            $query->whereHas('jobAssignSchedule.jobSchedule.jobAdvice.contract', function($q) use ($request) {
                $q->where('notes_operation', 'like', "%{$request->notes_operation}%");
            });
        }

        // Filter by created by
        if ($request->filled('created_by_name')) {
            $query->whereHas('createdBy', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->created_by_name}%");
            });
        }

        // Filter by updated by
        if ($request->filled('updated_by_name')) {
            $query->whereHas('updatedBy', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->updated_by_name}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->whereHas('materialIssue', function($q) use ($request) {
                $q->where('status', $request->status);
            });
        } else {
            // Default: Hide 'issued' items (already processed)
            $query->whereHas('materialIssue', function($q) {
                $q->where('status', '!=', 'issued');
            });
        }



        // Pagination size
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $materialIssues = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $teams = Cache::remember('job-assign-material-issues:index:teams', now()->addMinutes(10), function () {
            return Team::query()
                ->select('id', 'team_name')
                ->where('active_status', true)
                ->orderBy('team_name')
                ->get();
        });

        $products = Cache::remember('job-assign-material-issues:index:products', now()->addMinutes(10), function () {
            return MasterProduct::query()
                ->select('id', 'name', 'sku', 'product_type_id', 'product_category_id', 'packaging_size_id', 'bom_quantity', 'variant_name', 'brand_line', 'last_unit_price')
                ->with([
                    'productType:id,name',
                    'productCategory:id,name',
                    'packagingSize:id,name',
                ])
                ->orderBy('name')
                ->get();
        });

        $requestReasons = $this->getCachedMasterOptions('Request Reason');
        $priorities = $this->getCachedMasterOptions('Priority');
        $statuses = $this->getCachedMasterOptions('Material Issue Status');

        $statistics = Cache::remember('job-assign-material-issues:index:statistics', now()->addSeconds(60), function () {
            return [
                'total' => JobAssignMaterialIssue::count(),
                'pending' => JobAssignMaterialIssue::whereHas('materialIssue', function ($q) {
                    $q->where('status', 'pending');
                })->count(),
                'approved' => JobAssignMaterialIssue::whereHas('materialIssue', function ($q) {
                    $q->where('status', 'approved');
                })->count(),
                'issued' => JobAssignMaterialIssue::whereHas('materialIssue', function ($q) {
                    $q->where('status', 'issued');
                })->count(),
            ];
        });

        $indexLookups = $this->buildIndexLookups($materialIssues);

        return view('operational.job-assign-material-issues.index', array_merge($indexLookups, compact(
            'materialIssues',
            'teams',
            'products',
            'requestReasons',
            'priorities',
            'statuses',
            'statistics'
        )));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $jobAssignSchedules = JobAssignSchedule::whereHas('jobSchedule', function($q) {
                $q->whereNotIn('type', ['check', 'remove', 'remove_free', 'remove free']);
            })
            ->with(['jobSchedule.jobAdvice.customer', 'jobSchedule.building', 'team'])
            ->orderBy('created_at', 'desc')
            ->get();
        $teams = Team::orderBy('team_name')->get();
        $products = MasterProduct::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'jobAssignSchedules' => $jobAssignSchedules,
                    'teams' => $teams,
                    'products' => $products,
                    'warehouses' => $warehouses
                ]
            ]);
        }

        return view('operational.job-assign-material-issues.create', compact('jobAssignSchedules', 'teams', 'products', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'job_assign_schedule_id' => 'required|exists:job_assign_schedules,id',
                'team_id' => 'required|exists:teams,id',
                'product_id' => 'required|exists:master_products,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'issue_date' => 'required|date',
                'quantity' => 'required|numeric|min:0.01',
                'unit_price' => 'required|numeric|min:0',
                'requested_by' => 'required|string|max:255',
                'request_reason' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'description' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Validate stock before creating material issue
        $stockValidation = $this->validateAndCheckStockSplit(
            $request->warehouse_id,
            $request->product_id,
            $request->quantity
        );
        
        // Store original quantity for auto-split
        $originalQuantity = $request->quantity;
        
        // If stock is insufficient but can be split, auto-copy with adjusted quantity
        if (!$stockValidation['can_fulfill'] && $stockValidation['needs_split'] && $request->input('auto_split', false)) {
            // Auto-copy: Create first material issue with available stock
            $availableStock = $stockValidation['stock_available'];
            
            // Update quantity to available stock
            $request->merge(['quantity' => $availableStock, 'original_quantity' => $originalQuantity]);
            
            // Create first material issue with available stock
            // (will continue after this block)
        } elseif (!$stockValidation['can_fulfill']) {
            // If auto_split is false or stock validation fails, return error
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => $stockValidation['message'] ?? 'Stock insufficient and needs split combination.',
                    'stock_validation' => $stockValidation,
                    'requires_split' => true,
                    'available_stock' => $stockValidation['stock_available'],
                    'required_quantity' => $request->quantity,
                    'remaining_quantity' => $request->quantity - $stockValidation['stock_available']
                ], 422);
            }
            return back()->with('error', $stockValidation['message'] ?? 'Stock insufficient and needs split combination.')
                        ->with('requires_split', true)
                        ->with('available_stock', $stockValidation['stock_available'])
                        ->with('remaining_quantity', $request->quantity - $stockValidation['stock_available'])
                        ->withInput($request->all());
        }

        try {
            DB::beginTransaction();

            // MOM10: Generate material issue number using DocumentNumberService
            // Get branch code from warehouse
            $issueNumber = $this->generateUniqueIssueNumber($request->warehouse_id);
            
            // Create MaterialIssue first
            $materialIssue = MaterialIssue::create([
                'issue_number' => $issueNumber,
                'warehouse_id' => $request->warehouse_id,
                'issued_by' => Auth::id(),
                'team_id' => $request->team_id,
                'product_id' => $request->product_id,
                'issue_date' => $request->issue_date,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_amount' => $request->quantity * $request->unit_price,
                'requested_by' => $request->requested_by,
                'request_reason' => $request->request_reason,
                'status' => 'pending',
                'priority' => $request->priority,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create JobAssignMaterialIssue (Use firstOrCreate to prevent duplicate links)
            $jobAssignMaterialIssue = JobAssignMaterialIssue::firstOrCreate(
                [
                    'job_assign_schedule_id' => $request->job_assign_schedule_id,
                    'material_issue_id' => $materialIssue->id,
                ],
                [
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
            
            // MOM11: Save material issue items dari job advice rooms (dengan quotation aroma support)
            $this->saveMaterialIssueItems($materialIssue, $jobAssignMaterialIssue);
            
            // MOM9: Auto-update job schedule status to "material_issue" when material is assigned
            $jobAssignSchedule = JobAssignSchedule::with('jobSchedule')->find($request->job_assign_schedule_id);
            
            if ($jobAssignSchedule && $jobAssignSchedule->jobSchedule) {
                $jobSchedule = $jobAssignSchedule->jobSchedule;
                
                // Check if status allows update to 'material_issue'
                // Expanded checking to be safe
                if (in_array($jobSchedule->status, ['assign_team', 'scheduled', 'new_job'])) {
                    $jobSchedule->update([
                        'status' => 'barang_dipersiapkan', // Changed from material_issue (invalid enum)
                        'updated_by' => Auth::id()
                    ]);
                }
            }
            
            // Auto-copy: If stock split is needed and auto_split is enabled, create second material issue
            if (!$stockValidation['can_fulfill'] && $stockValidation['needs_split'] && $request->input('auto_split', false)) {
                $remainingQuantity = $request->input('original_quantity', $request->quantity) - $stockValidation['stock_available'];
                
                if ($remainingQuantity > 0) {
                    // Generate unique issue number for second material issue
                    // MOM10: Generate second material issue number using DocumentNumberService
                    $secondIssueNumber = $this->generateUniqueIssueNumber($request->warehouse_id);
                    
                    // Create second MaterialIssue with remaining quantity
                    $secondMaterialIssue = MaterialIssue::create([
                        'issue_number' => $secondIssueNumber,
                        'warehouse_id' => $request->warehouse_id,
                        'issued_by' => Auth::id(),
                        'team_id' => $request->team_id,
                        'product_id' => $request->product_id,
                        'issue_date' => $request->issue_date,
                        'quantity' => $remainingQuantity,
                        'unit_price' => $request->unit_price,
                        'total_amount' => $remainingQuantity * $request->unit_price,
                        'requested_by' => $request->requested_by,
                        'request_reason' => $request->request_reason . ' (Auto-split: remaining quantity)',
                        'status' => 'pending',
                        'priority' => $request->priority,
                        'description' => ($request->description ?? '') . ' [Auto-split from ' . $materialIssue->issue_number . ']',
                        'notes' => ($request->notes ?? '') . ' | Auto-copied for remaining quantity: ' . $remainingQuantity,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    
                    // Create second JobAssignMaterialIssue
                    $secondJobAssignMaterialIssue = JobAssignMaterialIssue::create([
                        'job_assign_schedule_id' => $request->job_assign_schedule_id,
                        'material_issue_id' => $secondMaterialIssue->id,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    
                    \Log::info("Auto-copied material issue: {$materialIssue->issue_number} → {$secondMaterialIssue->issue_number} (remaining: {$remainingQuantity})");
                }
            }

            DB::commit();

            // Check if request expects JSON response (AJAX call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job assignment material issue created successfully.',
                    'data' => $jobAssignMaterialIssue->load(['jobAssignSchedule.jobSchedule.jobAdvice.customer', 'materialIssue.product'])
                ]);
            }

            return redirect()->route('operational.job-assign-material-issues.index')
                ->with('success', 'Job assignment material issue created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Check if request expects JSON response (AJAX call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create job assignment material issue: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create job assignment material issue: ' . $e->getMessage());
        }
    }

    /**
     * Get job assign schedule details for autofill
     */
    public function getJobAssignScheduleDetails($id)
    {
        try {
            $jobAssignSchedule = JobAssignSchedule::with([
                'team', 
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.allowedProducts.productType',
                'jobSchedule.jobAdvice.rooms.quotationRoom.aromaProduct'
            ])->find($id);
            
            if (!$jobAssignSchedule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job assign schedule not found'
                ], 404);
            }
            
            // Get all products from rental components for all rooms in Job Advice
            $products = collect();
            $jobAdvice = $jobAssignSchedule->jobSchedule->jobAdvice ?? null;
            
            $assignedRooms = $this->getAssignedJobAdviceRooms($jobAdvice, $jobAssignSchedule->jobSchedule ?? null);

            if ($assignedRooms->isNotEmpty()) {
                // Get list of selected aroma variants from all rooms
                $selectedAromaVariants = $assignedRooms->map(function($jaRoom) {
                    return $jaRoom->quotationRoom->aromaProduct->variant_name ?? null;
                })->filter()->unique()->toArray();

                foreach ($assignedRooms as $jaRoom) {
                    $rental = $jaRoom->rentalProduct ?? null;
                    if ($rental && $rental->rentalDetails) {
                        foreach ($rental->rentalDetails as $detail) {
                            // Get all allowed products from this detail (Material List)
                            $detailProducts = $detail->allowedProducts()
                                ->wherePivot('is_selected', true)
                                ->get();
                            
                            foreach ($detailProducts as $product) {
                                // Filter logic: aroma products must match one of the selected aromas (variants).
                                $isAromaType = $this->isAromaMaterialProduct($product);

                                if ($isAromaType && !empty($selectedAromaVariants)) {
                                    $matchFound = false;
                                    foreach ($selectedAromaVariants as $variantName) {
                                        // Match by exact variant_name OR product name contains variant name (e.g. "Black Rose 250ml" contains "Black Rose")
                                        if (($product->variant_name && $product->variant_name == $variantName) || 
                                            ($variantName && str_contains(strtolower($product->name), strtolower($variantName)))) {
                                            $matchFound = true;
                                            break;
                                        }
                                    }
                                    
                                    if (!$matchFound) {
                                        continue; // Skip if not matching any selected aroma variant
                                    }
                                }

                                // Avoid duplicates by product ID
                                if (!$products->contains('id', $product->id)) {
                                    $products->push($product);
                                }
                            }
                        }
                    }
                }
            }
            
            // Format products for dropdown
            $formattedProducts = $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->sku ?? '',
                    'last_unit_price' => $product->last_unit_price ?? 0,
                    'type' => $product->productType->name ?? ($product->category ?? '-')
                ];
            })->sortBy('name')->values();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'team_id' => $jobAssignSchedule->team_id,
                    'team_name' => $jobAssignSchedule->team->team_name ?? null,
                    'job_schedule_id' => $jobAssignSchedule->job_schedule_id,
                    'job_number' => $jobAssignSchedule->jobSchedule->job_number ?? null,
                    'customer_name' => $jobAssignSchedule->jobSchedule->jobAdvice->customer->name ?? null,
                    'assigned_date' => $jobAssignSchedule->assigned_date,
                    'status' => $jobAssignSchedule->status,
                    'products' => $formattedProducts, // Add products from rental
                    'rental_name' => $assignedRooms->count() > 0
                        ? ($assignedRooms->first()->rental_name ?? null)
                        : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get job assign schedule details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        $jobAssignMaterialIssue->load([
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.productCategory',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.masterProduct.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.contract.quotation.quotationRooms.aromaProduct.productCategory',
            'jobAssignSchedule.jobSchedule.jobAdvice.contract.quotation.quotationRooms.aromaProduct.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.contractRoom.room',
            'jobAssignSchedule.jobSchedule.jobAdvice.customer',
            'jobAssignSchedule.jobSchedule.building',
            'jobAssignSchedule.team',
            'materialIssue.team',
            'materialIssue.product.productCategory',
            'materialIssue.product.productType',
            'materialIssue.warehouse',
            'materialIssue.issuedBy',
            'materialIssue.productChangedBy', // MOM9: Load user who changed the product
            'createdBy',
            'updatedBy'
        ]);

        // MOM12: Load produk dari material_issue_items FIRST (jika ada), fallback ke rental_details
        // Ini penting agar perubahan yang sudah disimpan di material_issue_items ditampilkan!
        $rentalProducts = [];
        $materialIssue = $jobAssignMaterialIssue->materialIssue;
        
        // Check if material_issue_items exists
        $savedItems = $materialIssue && $materialIssue->items ? $materialIssue->items()->with(['product.productCategory', 'product.productType', 'product.packagingSize'])->get() : collect();
        
        if ($savedItems->count() > 0) {
            // MOM12: Use saved items from material_issue_items
            \Log::info("MOM12 SHOW: Using {$savedItems->count()} saved items from material_issue_items");
            
            $warehouse = $materialIssue->warehouse;
            
            foreach ($savedItems as $item) {
                $product = $item->product;
                if (!$product) continue;
                
                // Get stock from warehouse
                $stock = 0;
                if ($warehouse) {
                    $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                        ->where('master_product_id', $product->id)
                        ->first();
                    $stock = $warehouseProduct->quantity ?? 0;
                }
                
                $productCategoryName = $product->productCategory->name ?? $product->productType->name ?? null;
                $isUnit = ($product->productCategory && ($product->productCategory->is_unit == 1 || $product->productCategory->is_unit === true)) || 
                          ($product->productType && ($product->productType->is_unit == 1 || $product->productType->is_unit === true));
                
                // Extract rental_name and component_id from notes
                $rentalName = 'N/A';
                $componentIdFromNotes = null;
                if ($item->notes) {
                    // Format: "Room: X, Rental: Y, ComponentID: Z"
                    if (preg_match('/Rental:\s*([^,]+)/', $item->notes, $matches)) {
                        $rentalName = trim($matches[1]);
                    }
                    $componentIdFromNotes = $this->extractSavedItemComponentId($item->notes);
                }
                
                // Get updated_by user name
                $updatedByName = null;
                if ($item->updated_by) {
                    $updatedByUser = \App\Models\User::find($item->updated_by);
                    $updatedByName = $updatedByUser ? $updatedByUser->name : null;
                }
                
                $rentalProducts[] = [
                    'room_name' => $item->room_name,
                    'rental_name' => $rentalName,
                    'component_name' => $productCategoryName ?? 'N/A',
                    'component_id' => $componentIdFromNotes ?? $item->id, // Use component_id from notes if available
                    'product_type_id' => $product->product_type_id,
                    'product_category_id' => $product->product_category_id,
                    'is_unit' => $isUnit,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->sku ?? null,
                        'product_type' => null,
                        'packaging_size' => $product->packagingSize->name ?? null,
                        'packaging_size_id' => $product->packaging_size_id ?? null,
                    ],
                    'packaging_sizes' => [], // Not needed for show
                    'quantity' => $item->quantity ?? 0,
                    'convert' => $item->convert ?? 1,
                    'bom_quantity' => $item->bom_quantity ?? 0,
                    'unit_price' => $item->unit_price ?? 0,
                    'total_amount' => $item->total_price ?? 0,
                    'warehouse' => [
                        'id' => $warehouse->id ?? null,
                        'name' => $warehouse->name ?? null,
                    ],
                    'stock' => $stock,
                    'updated_at' => $item->updated_at ? $item->updated_at->toIso8601String() : null,
                    'updated_by_name' => $updatedByName,
                ];
            }
        } else {
            // Fallback to rental_details if no saved items
            \Log::info("MOM12 SHOW: No saved items, falling back to rental_details");
        }
        
        // MOM10: Load produk dari rental_details jika tidak ada saved items
        $jobAdvice = $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->jobAdvice ?? null;
        
        $assignedRooms = $this->getAssignedJobAdviceRooms($jobAdvice, $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule ?? null);

        if (count($rentalProducts) === 0 && $assignedRooms->isNotEmpty()) {
            foreach ($assignedRooms as $jaRoom) {
                $rental = $jaRoom->rentalProduct;
                if (!$rental) continue;
                
                // Load rental_details dengan relationships (termasuk packagingSize)
                $rental->load([
                    'rentalDetails.productCategory',
                    'rentalDetails.productType',
                    'rentalDetails.masterProduct.packagingSize',
                    'rentalDetails.allowedProducts.productCategory',
                    'rentalDetails.allowedProducts.productType',
                ]);
                
                // Get quotation room for aroma/variant
                // MOM9: Check quotation directly (for job advice from quotation) or through contract
                $quotationRoom = null;
                $aromaProduct = null;
                $quotation = null;
                
                // MOM9: Get quotation - either directly or through contract
                if ($jobAdvice->quotation_id) {
                    // Job advice from quotation directly
                    $quotation = $jobAdvice->quotation;
                } elseif ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                    // Job advice from contract (existing flow)
                    $quotation = $jobAdvice->contract->quotation;
                }
                
                if ($quotation) {
                    // MOM9: If job advice has quotation_room_id, use it directly
                    if ($jaRoom->quotation_room_id) {
                        $quotationRoom = $quotation->quotationRooms
                            ->where('id', $jaRoom->quotation_room_id)
                            ->first();
                    }
                    
                    // If not found and has contractRoom, try matching by room
                    if (!$quotationRoom && $jaRoom->contractRoom) {
                        $contractRoom = $jaRoom->contractRoom;
                        $room = $contractRoom->room;
                        
                        // Try by room_id first
                        $quotationRoom = $quotation->quotationRooms
                            ->where('room_id', $room->id ?? null)
                            ->first();
                        
                        // If not found, try by room_name
                        if (!$quotationRoom && $room && $room->room_name) {
                            $quotationRoom = $quotation->quotationRooms
                                ->where('room_name', $room->room_name)
                                ->first();
                        }
                    }
                    
                    // If still not found, try by JA room name
                    if (!$quotationRoom && $jaRoom->room_name) {
                        $quotationRoom = $quotation->quotationRooms
                            ->where('room_name', $jaRoom->room_name)
                            ->first();
                    }
                    
                    // Get aromaProduct from quotation room (priority over master rental)
                    if ($quotationRoom && $quotationRoom->aromaProduct) {
                        $aromaProduct = $quotationRoom->aromaProduct;
                        \Log::info("📋 Aroma dari Quotation ditemukan untuk room '{$jaRoom->room_name}': {$aromaProduct->name} (ID: {$aromaProduct->id})");
                    }
                }
                
                // Use rental_details (sinkron dengan UI Master Rental)
                \Log::info("🔍 Debug Material Issue Show - Rental: '{$rental->rental_name}' memiliki {$rental->rentalDetails->count()} rental details");
                
                foreach ($rental->rentalDetails as $detail) {
                    $product = null;
                    $productCategoryName = $detail->productCategory->name ?? $detail->productType->name ?? null;
                    
                    // PRIORITY: For aroma material slots, use aromaProduct from quotation FIRST.
                    $isAromaType = $this->isQuotationAromaMaterialSlot($detail, $detail->masterProduct, $productCategoryName);
                    
                    if ($isAromaType && $aromaProduct) {
                        // PRIORITY: Aroma dari Quotation (jasmine dari quotation) > Aroma dari Master Rental
                        $product = $this->resolveQuotationAromaProductForDetail($detail, $aromaProduct, $detail->masterProduct)
                            ?? $aromaProduct;
                        \Log::info("  ✓ Detail '{$productCategoryName}': PRIORITY - Menggunakan aromaProduct dari quotation: {$aromaProduct->name} (ID: {$aromaProduct->id})");
                    } else if ($detail->masterProduct) {
                        // Use product from rental_detail
                        $product = $detail->masterProduct;
                        \Log::info("  ✓ Detail '{$productCategoryName}': Menggunakan product dari rental_detail: {$product->name} (ID: {$product->id})");
                    } else {
                        $product = $this->resolvePreferredRentalDetailProduct($detail, $rental, null);
                        if (!$product) {
                            \Log::warning("  Detail '{$productCategoryName}' (ID: {$detail->id}) tidak memiliki product atau Material List. Detail akan di-skip.");
                            continue;
                        }
                    }
                    
                    if (!$product) {
                        continue;
                    }
                    
                    // Get stock from warehouse
                    $warehouse = $jobAssignMaterialIssue->materialIssue->warehouse;
                    $stock = 0;
                    if ($warehouse) {
                        $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                            ->where('master_product_id', $product->id)
                            ->first();
                        $stock = $warehouseProduct->quantity ?? 0;
                    }
                    
                    // Load productType dan packagingSize untuk product (jika ada)
                    $product->load(['productType', 'packagingSize']);
                    
                    // Component name harus dari ProductCategory di rental_detail (master rental), bukan dari product yang dipilih
                    // Contoh: "Aroma Diffuser" dari rental_detail, bukan dari "Aroma Oil Citrus" yang product typenya kosong
                    $componentName = $productCategoryName ?? 'N/A';
                    
                    // Product type di product array HARUS KOSONG (null) - tidak ditampilkan
                    // Component name sudah dari master rental, jadi product_type di product array tidak perlu ditampilkan
                    $productTypeInProduct = null; // Selalu kosong/null
                    
                    $rentalProducts[] = [
                        'room_name' => $jaRoom->room_name,
                        'rental_name' => $rental->rental_name,
                        'component_name' => $componentName, // Dari ProductType di rental_detail (master rental)
                        'component_id' => $detail->id, // Use detail ID
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'code' => $product->sku ?? null,
                            'product_type' => $productTypeInProduct, // Selalu kosong/null (tidak ditampilkan)
                            'packaging_size' => $product->packagingSize ? $product->packagingSize->name : null, // MOM6: Package size untuk logic stok
                        ],
                        'quantity' => $detail->quantity ?? 0,
                        'unit_price' => $product->last_unit_price ?? 0,
                        'total_amount' => ($product->last_unit_price ?? 0) * ($detail->quantity ?? 0),
                        'warehouse' => [
                            'id' => $warehouse->id ?? null,
                            'name' => $warehouse->name ?? null,
                        ],
                        'stock' => $stock,
                    ];
                }
            }
        }

        $statistics = [
            'total_quantity' => collect($rentalProducts)->sum('quantity'),
            'total_amount' => collect($rentalProducts)->sum('total_amount'),
            'total_products' => count($rentalProducts),
            'related_materials' => JobAssignMaterialIssue::where('job_assign_schedule_id', $jobAssignMaterialIssue->job_assign_schedule_id)
                ->where('id', '!=', $jobAssignMaterialIssue->id)
                ->count(),
        ];

        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $jobAssignMaterialIssue->id,
                    'issue_number' => $jobAssignMaterialIssue->materialIssue->issue_number ?? null,
                    'job_assign_schedule' => [
                        'job_schedule' => [
                            'job_number' => $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->job_number ?? null,
                            'customer' => [
                                'name' => $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->jobAdvice->customer->name ?? null
                            ],
                            'building' => [
                                'nama_gedung' => $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->building->nama_gedung ?? null
                            ]
                        ]
                    ],
                    'team' => [
                        'team_name' => $jobAssignMaterialIssue->materialIssue->team->team_name ?? null
                    ],
                    'product' => [
                        'name' => $jobAssignMaterialIssue->materialIssue->product->name ?? null
                    ],
                    'warehouse' => [
                        'id' => $jobAssignMaterialIssue->materialIssue->warehouse->id ?? null,
                        'name' => $jobAssignMaterialIssue->materialIssue->warehouse->name ?? null
                    ],
                    'issue_date' => $jobAssignMaterialIssue->materialIssue->issue_date ?? null,
                    'quantity' => $jobAssignMaterialIssue->materialIssue->quantity ?? 0,
                    'unit_price' => $jobAssignMaterialIssue->materialIssue->unit_price ?? 0,
                    'total_amount' => $jobAssignMaterialIssue->materialIssue->total_amount ?? 0,
                    'quantity_convert' => $jobAssignMaterialIssue->materialIssue->quantity_convert ?? ($jobAssignMaterialIssue->materialIssue->convert_qty ?? null),
                    'bom_qty' => $jobAssignMaterialIssue->materialIssue->bom_qty ?? ($jobAssignMaterialIssue->materialIssue->bom_quantity ?? null),
                    // MOM9: Product change history
                    'previous_product_id' => $jobAssignMaterialIssue->materialIssue->previous_product_id ?? null,
                    'previous_product' => $jobAssignMaterialIssue->materialIssue->previous_product_id ? [
                        'id' => $jobAssignMaterialIssue->materialIssue->previous_product_id,
                        'name' => \App\Models\MasterProduct::find($jobAssignMaterialIssue->materialIssue->previous_product_id)?->name ?? 'Unknown',
                    ] : null,
                    'product_changed_at' => $jobAssignMaterialIssue->materialIssue->product_changed_at ? $jobAssignMaterialIssue->materialIssue->product_changed_at->toDateTimeString() : null,
                    'product_changed_by' => $jobAssignMaterialIssue->materialIssue->productChangedBy ? [
                        'id' => $jobAssignMaterialIssue->materialIssue->productChangedBy->id,
                        'name' => $jobAssignMaterialIssue->materialIssue->productChangedBy->name,
                    ] : null,
                    'product_change_note' => $jobAssignMaterialIssue->materialIssue->product_change_note ?? null,
                    'status' => $jobAssignMaterialIssue->materialIssue->status ?? null,
                    'priority' => $jobAssignMaterialIssue->materialIssue->priority ?? null,
                    'requested_by' => $jobAssignMaterialIssue->materialIssue->requested_by ?? null,
                    'request_reason' => $jobAssignMaterialIssue->materialIssue->request_reason ?? null,
                    'description' => $jobAssignMaterialIssue->materialIssue->description ?? null,
                    'notes' => $jobAssignMaterialIssue->materialIssue->notes ?? null,
                    'metadata' => $jobAssignMaterialIssue->materialIssue->metadata ?? null, // Variant change requests
                    'rental_products' => $rentalProducts, // MOM10: Detail produk dari rental dengan stock dan warehouse
                    'statistics' => $statistics
                ]
            ]);
        }

        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('operational.job-assign-material-issues.index')
            ->with('error', 'Please use the modal system to view material issues.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        // Load same data as show method
        $jobAssignMaterialIssue->load([
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalComponents.preferredProducts.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalComponents.allowedProducts.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.contract.quotation.quotationRooms.aromaProduct',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.contractRoom.room',
            'jobAssignSchedule.jobSchedule.jobAdvice.customer',
            'jobAssignSchedule.jobSchedule.building',
            'jobAssignSchedule.team',
            'materialIssue.team',
            'materialIssue.product',
            'materialIssue.warehouse',
            'materialIssue.issuedBy',
            'materialIssue.previousProduct',
            'createdBy',
            'updatedBy'
        ]);

        // MOM12: Load produk dari rental_details, tapi gunakan nilai yang sudah disimpan di material_issue_items
        // PENTING: component_id HARUS tetap rental_detail.id agar save function bisa match!
        $rentalProducts = [];
        $materialIssue = $jobAssignMaterialIssue->materialIssue;
        $warehouse = $materialIssue->warehouse ?? null;
        
        // Build map of saved items - try ComponentID first, fallback to index-based
        $savedItemsMap = [];
        $savedItemsByRoomIndex = []; // Fallback for old data without ComponentID
        if ($materialIssue && $materialIssue->items) {
            $savedItems = $materialIssue->items()->with(['product.productType', 'product.packagingSize'])->get();
            
            // Group items by room_name for index-based fallback
            $itemsByRoom = $savedItems->groupBy('room_name');
            foreach ($itemsByRoom as $roomName => $roomItems) {
                $savedItemsByRoomIndex[$roomName] = $roomItems->values()->all();
            }
            
            foreach ($savedItems as $item) {
                // Try to extract component_id from notes (format: "Room: X, Rental: Y, ComponentID: Z")
                $componentId = $this->extractSavedItemComponentId($item->notes);
                if ($componentId) {
                    // Key by room_name + component_id for exact matching
                    $key = $item->room_name . '_' . $componentId;
                    $savedItemsMap[$key] = $item;
                    \Log::info("MOM12 EDIT: Mapped saved item with key: {$key}, product: {$item->product->name}");
                }
            }
            \Log::info("MOM12 EDIT: Built saved items map with " . count($savedItemsMap) . " exact, " . count($savedItemsByRoomIndex) . " rooms for fallback");
        }
        
        // Track index per room for fallback matching
        $roomDetailIndex = [];
        
        $jobAdvice = $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->jobAdvice ?? null;
        
        $assignedRooms = $this->getAssignedJobAdviceRooms($jobAdvice, $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule ?? null);

        if ($assignedRooms->isNotEmpty()) {
            foreach ($assignedRooms as $jaRoom) {
                $rental = $jaRoom->rentalProduct;
                if (!$rental) continue;
                
                // Load rental_details dengan relationships (termasuk packagingSize)
                $rental->load([
                    'rentalDetails.productType',
                    'rentalDetails.productCategory',
                    'rentalDetails.masterProduct.packagingSize',
                    'rentalDetails.allowedProducts.productCategory',
                    'rentalDetails.allowedProducts.productType',
                ]);
                
                // Get quotation room for aroma/variant
                // PRIORITY: Aroma dari Quotation lebih tinggi dari Master Rental
                // MOM9: Check quotation directly (for job advice from quotation) or through contract
                $quotationRoom = null;
                $aromaProduct = null;
                $quotation = null;
                
                // MOM9: Get quotation - either directly or through contract
                if ($jobAdvice->quotation_id) {
                    // Job advice from quotation directly
                    $quotation = $jobAdvice->quotation;
                } elseif ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                    // Job advice from contract (existing flow)
                    $quotation = $jobAdvice->contract->quotation;
                }
                
                if ($quotation) {
                    // MOM9: If job advice has quotation_room_id, use it directly
                    if ($jaRoom->quotation_room_id) {
                        $quotationRoom = $quotation->quotationRooms
                            ->where('id', $jaRoom->quotation_room_id)
                            ->first();
                    }
                    
                    // If not found and has contractRoom, try matching by room
                    if (!$quotationRoom && $jaRoom->contractRoom) {
                        $contractRoom = $jaRoom->contractRoom;
                        $room = $contractRoom->room;
                        
                        // Try by room_id first
                        $quotationRoom = $quotation->quotationRooms
                            ->where('room_id', $room->id ?? null)
                            ->first();
                        
                        // If not found, try by room_name
                        if (!$quotationRoom && $room && $room->room_name) {
                            $quotationRoom = $quotation->quotationRooms
                                ->where('room_name', $room->room_name)
                                ->first();
                        }
                    }
                    
                    // If still not found, try by JA room name
                    if (!$quotationRoom && $jaRoom->room_name) {
                        $quotationRoom = $quotation->quotationRooms
                            ->where('room_name', $jaRoom->room_name)
                            ->first();
                    }
                    
                    // Get aromaProduct from quotation room (priority over master rental)
                    if ($quotationRoom && $quotationRoom->aromaProduct) {
                        $aromaProduct = $quotationRoom->aromaProduct;
                        // MOM12: Ensure category/type loaded for is_unit checks later
                        if (!$aromaProduct->relationLoaded('productCategory')) $aromaProduct->load('productCategory');
                        if (!$aromaProduct->relationLoaded('productType')) $aromaProduct->load('productType');
                    }
                }
                
                // Use rental_details (sinkron dengan UI Master Rental)
                foreach ($rental->rentalDetails as $detail) {
                    $product = null;
                    $productCategoryName = $detail->productCategory->name ?? $detail->productType->name ?? null;
                    $productCategoryId = $detail->product_category_id ?? null;
                    $productTypeId = $detail->product_type_id ?? null;
                    $allowedProductIds = $detail->allowedProducts
                        ? $detail->allowedProducts
                            ->where('pivot.is_selected', true)
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->values()
                            ->all()
                        : [];
                    
                    // MOM12: Check for saved item - try exact match first, then fallback to index
                    $savedItemKey = $jaRoom->room_name . '_' . $detail->id;
                    $savedItem = $savedItemsMap[$savedItemKey] ?? null;
                    
                    // Fallback: use index-based matching for old data without ComponentID
                    if (!$savedItem && isset($savedItemsByRoomIndex[$jaRoom->room_name])) {
                        if (!isset($roomDetailIndex[$jaRoom->room_name])) {
                            $roomDetailIndex[$jaRoom->room_name] = 0;
                        }
                        $idx = $roomDetailIndex[$jaRoom->room_name];
                        
                        if (isset($savedItemsByRoomIndex[$jaRoom->room_name][$idx])) {
                            $savedItem = $savedItemsByRoomIndex[$jaRoom->room_name][$idx];
                            \Log::info("MOM12 EDIT: Found saved item via FALLBACK (index {$idx}) for room '{$jaRoom->room_name}', product: {$savedItem->product->name}");
                        }
                        $roomDetailIndex[$jaRoom->room_name]++;
                    } elseif ($savedItem) {
                        \Log::info("MOM12 EDIT: Found saved item for key '{$savedItemKey}', product: {$savedItem->product->name}");
                    } else {
                        \Log::info("MOM12 EDIT: No saved item for key '{$savedItemKey}'");
                    }
                    
                    // If saved item exists, use its product
                    if ($savedItem && $savedItem->product) {
                        $product = $savedItem->product;
                        $product->load(['productCategory', 'productType', 'packagingSize']);
                        \Log::info("MOM12 EDIT: Using saved product: {$product->name} (ID: {$product->id})");
                    } else {
                        // PRIORITY: For aroma material slots, use aromaProduct from quotation FIRST.
                        $isAromaType = $this->isQuotationAromaMaterialSlot($detail, $detail->masterProduct, $productCategoryName);
                        
                        if ($isAromaType && $aromaProduct) {
                            // PRIORITY: Aroma dari Quotation (jasmine dari quotation) > Aroma dari Master Rental
                            $product = $this->resolveQuotationAromaProductForDetail($detail, $aromaProduct, $detail->masterProduct)
                                ?? $aromaProduct;
                        } else if ($detail->masterProduct) {
                            // Use product from rental_detail
                            $product = $detail->masterProduct;
                        } else {
                            $product = $this->resolvePreferredRentalDetailProduct($detail, $rental, null);
                        }
                    }
                    
                    if (!$product) {
                        continue;
                    }
                    
                    // Load productCategory, productType dan packagingSize untuk product (jika ada)
                    $product->load(['productCategory', 'productType', 'packagingSize']);
                    
                    // Component name harus dari ProductCategory di rental_detail (master rental), bukan dari product yang dipilih
                    // Contoh: "Aroma Diffuser" dari rental_detail, bukan dari "Aroma Oil Citrus" yang product typenya kosong
                    $componentName = $productCategoryName ?? 'N/A';
                    
                    // Product type di product array HARUS KOSONG (null) - tidak ditampilkan
                    // Component name sudah dari master rental, jadi product_type di product array tidak perlu ditampilkan
                    $productTypeInProduct = null; // Selalu kosong/null
                    
                    // Get available packaging sizes ONLY for aroma product categories.
                    $packagingSizes = [];
                    $isAromaType = $this->isQuotationAromaMaterialSlot($detail, $product, $productCategoryName);
                    
                    if ($isAromaType) {
                        // Only show packaging sizes for aroma/refill/variant
                        $packagingSizes = \App\Models\PackagingSize::where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->get()
                            ->map(function($ps) {
                                return [
                                    'id' => $ps->id,
                                    'name' => $ps->name,
                                ];
                            })
                            ->toArray();
                    }
                    
                    // Get stock from warehouse
                    $warehouse = $jobAssignMaterialIssue->materialIssue->warehouse;
                    $stock = 0;
                    if ($warehouse) {
                        $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                            ->where('master_product_id', $product->id)
                            ->first();
                        $stock = $warehouseProduct->quantity ?? 0;
                    }
                    
                    // MOM12: Check if product is a unit (is_unit = true means cannot be edited)
                    $productIsUnit = ($product->productCategory && ($product->productCategory->is_unit == 1 || $product->productCategory->is_unit === true)) || 
                                     ($product->productType && ($product->productType->is_unit == 1 || $product->productType->is_unit === true));
                    
                    // MOM12: Use saved values for quantity and convert, but BOM always from master product as reference
                    $quantity = $savedItem ? ($savedItem->quantity ?? $detail->quantity ?? 0) : ($detail->quantity ?? 0);
                    $convert = $savedItem ? ($savedItem->convert ?? $detail->convert ?? 1) : ($detail->convert ?? 1);
                    // MOM13: BOM quantity ALWAYS from master product (patokan), not from saved item
                    $bomQuantity = $product->bom_quantity ?? 0;
                    
                    $rentalProducts[] = [
                        'room_name' => $jaRoom->room_name,
                        'rental_name' => $rental->rental_name,
                        'component_name' => $componentName, // Dari ProductType di rental_detail (master rental)
                        'component_id' => $detail->id, // Use detail ID - PENTING untuk save function!
                        'product_type_id' => $productTypeId, // MOM12: For filtering products dropdown
                        'product_category_id' => $productCategoryId,
                        'allowed_product_ids' => $allowedProductIds,
                        'is_unit' => $productIsUnit, // MOM12: Flag untuk disable edit jika unit
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'code' => $product->sku ?? null,
                            'product_type' => $productTypeInProduct, // Selalu kosong/null (tidak ditampilkan)
                            'packaging_size_id' => $product->packaging_size_id ?? null,
                        ],
                        'packaging_sizes' => $packagingSizes, // Available packaging sizes for this product type
                        'quantity' => $quantity, // MOM12: Use saved value if available
                        'convert' => $convert, // MOM12: Use saved value if available
                        'bom_quantity' => $bomQuantity, // MOM12: Use saved value if available
                        'unit_price' => $product->last_unit_price ?? 0,
                        'total_amount' => ($product->last_unit_price ?? 0) * $quantity,
                        'warehouse' => [
                            'id' => $warehouse->id ?? null,
                            'name' => $warehouse->name ?? null,
                        ],
                        'stock' => $stock,
                    ];
                }
            }
        }
        
        // MOM9: Filter products - hanya produk yang bukan is_unit (bisa di-edit)
        // MOM12: Include product_type_id untuk filter berdasarkan component
        $products = MasterProduct::with(['productCategory', 'productType'])
            ->where(function($q) {
                $q->where(function($subQ) {
                    $subQ->whereHas('productCategory', function($catQ) {
                        $catQ->where('is_unit', false);
                    })
                    ->orWhere(function($typeQ) {
                        $typeQ->whereHas('productType', function($subTypeQ) {
                            $subTypeQ->where('is_unit', false);
                        })
                        ->whereDoesntHave('productCategory');
                    });
                })
                ->orWhere(function($noneQ) {
                    $noneQ->whereDoesntHave('productCategory')
                          ->whereDoesntHave('productType');
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'product_type_id' => $product->product_type_id,
                    'product_category_id' => $product->product_category_id,
                    'productCategory' => $product->productCategory ? [
                        'id' => $product->productCategory->id,
                        'name' => $product->productCategory->name,
                        'is_unit' => $product->productCategory->is_unit,
                    ] : null,
                    'productType' => $product->productType ? [
                        'id' => $product->productType->id,
                        'name' => $product->productType->name,
                        'is_unit' => $product->productType->is_unit,
                    ] : null,
                ];
            });
        
        // MOM12: Load all non-unit product types for component dropdown
        $productTypes = \App\Models\ProductType::where('is_unit', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            });

        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $jobAssignMaterialIssue->id,
                    'issue_number' => $materialIssue->issue_number ?? null,
                    'warehouse' => [
                        'id' => $materialIssue->warehouse->id ?? null,
                        'name' => $materialIssue->warehouse->name ?? null
                    ],
                    'status' => $materialIssue->status ?? null,
                    'issue_date' => $materialIssue->issue_date ?? null,
                    'notes' => $materialIssue->notes ?? null,
                    'rental_products' => $rentalProducts, // Detail produk dari rental
                    'products' => $products, // List produk yang bisa dipilih (non-unit)
                    'product_types' => $productTypes, // MOM12: List component/product types untuk dropdown
                    // MOM9: Include product change history
                    'previous_product_id' => $materialIssue->previous_product_id ?? null,
                    'previous_product' => $materialIssue->previousProduct ? [
                        'id' => $materialIssue->previousProduct->id,
                        'name' => $materialIssue->previousProduct->name,
                    ] : null,
                    'product_change_note' => $materialIssue->product_change_note ?? null,
                    'product_changed_at' => $materialIssue->product_changed_at ? $materialIssue->product_changed_at->toDateTimeString() : null,
                ]
            ]);
        }

        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('operational.job-assign-material-issues.index')
            ->with('error', 'Please use the modal system to edit material issues.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        if ($jobAssignMaterialIssue->materialIssue && $jobAssignMaterialIssue->materialIssue->status === 'issued') {
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot edit issued material.'
                ], 422);
            }
            return back()->with('error', 'Cannot edit issued material.');
        }

        try {
            $request->validate([
                'issue_date' => 'required|date',
                'notes' => 'nullable|string',
                'product_change_note' => 'nullable|string', // MOM9: Note for product change
                'rental_products' => 'nullable|array',
                'rental_products.*.product_id' => 'required',
                'rental_products.*.component_id' => 'nullable',
                'rental_products.*.product_type_id' => 'nullable', // MOM12: Component type
                'rental_products.*.quantity' => 'nullable|numeric|min:0', // MOM12: Editable quantity (numeric, not integer - form sends strings)
                'rental_products.*.convert' => 'nullable|numeric|min:0', // MOM12: Convert value
                'rental_products.*.bom_quantity' => 'nullable|numeric|min:0', // MOM12: BOM quantity
                'rental_products.*.room_name' => 'nullable|string',
                'rental_products.*.packaging_size_id' => 'nullable', // MOM12: Packaging size
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        try {
            DB::beginTransaction();
            
            // MOM12 DEBUG: Log received rental products
            $rentalProducts = $request->rental_products ?? [];
            \Log::info('MOM12 DEBUG: Received rental_products from request', [
                'count' => count($rentalProducts),
                'data' => $rentalProducts
            ]);

            // Update MaterialIssue - issue_date dan notes
            if ($jobAssignMaterialIssue->materialIssue) {
                $materialIssue = $jobAssignMaterialIssue->materialIssue;
                $oldProductId = $materialIssue->product_id;
                
                $updateData = [
                    'issue_date' => $request->issue_date,
                    'notes' => $request->notes,
                    'updated_by' => Auth::id(),
                ];
                
                // MOM9: Check if any rental product has been changed
                $hasProductChange = false;
                $newProductId = null;
                $changedComponent = null;
                
                // Load original rental products to compare
                $jobAdvice = $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->jobAdvice ?? null;
                $originalRentalProducts = [];
                
                $assignedRooms = $this->getAssignedJobAdviceRooms($jobAdvice, $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule ?? null);

                if ($assignedRooms->isNotEmpty()) {
                    foreach ($assignedRooms as $jaRoom) {
                        $rental = $jaRoom->rentalProduct;
                        if (!$rental) continue;
                        
                        // PRIORITY: Aroma dari Quotation lebih tinggi dari Master Rental
                        // MOM9: Check quotation directly (for job advice from quotation) or through contract
                        $quotationRoom = null;
                        $aromaProduct = null;
                        $quotation = null;
                        
                        // MOM9: Get quotation - either directly or through contract
                        if ($jobAdvice->quotation_id) {
                            // Job advice from quotation directly
                            $quotation = $jobAdvice->quotation;
                        } elseif ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                            // Job advice from contract (existing flow)
                            $quotation = $jobAdvice->contract->quotation;
                        }
                        
                        if ($quotation) {
                            // MOM9: If job advice has quotation_room_id, use it directly
                            if ($jaRoom->quotation_room_id) {
                                $quotationRoom = $quotation->quotationRooms
                                    ->where('id', $jaRoom->quotation_room_id)
                                    ->first();
                            }
                            
                            // If not found and has contractRoom, try matching by room
                            if (!$quotationRoom && $jaRoom->contractRoom) {
                                $contractRoom = $jaRoom->contractRoom;
                                $room = $contractRoom->room;
                                
                                // Try by room_id first
                                $quotationRoom = $quotation->quotationRooms
                                    ->where('room_id', $room->id ?? null)
                                    ->first();
                                
                                // If not found, try by room_name
                                if (!$quotationRoom && $room && $room->room_name) {
                                    $quotationRoom = $quotation->quotationRooms
                                        ->where('room_name', $room->room_name)
                                        ->first();
                                }
                            }
                            
                            // If still not found, try by JA room name
                            if (!$quotationRoom && $jaRoom->room_name) {
                                $quotationRoom = $quotation->quotationRooms
                                    ->where('room_name', $jaRoom->room_name)
                                    ->first();
                            }
                            
                            // Get aromaProduct from quotation room (priority over master rental)
                            if ($quotationRoom && $quotationRoom->aromaProduct) {
                                $aromaProduct = $quotationRoom->aromaProduct;
                                \Log::info("📋 Aroma dari Quotation ditemukan untuk room '{$jaRoom->room_name}': {$aromaProduct->name} (ID: {$aromaProduct->id})");
                            }
                        }
                        
                        $components = $rental->rentalComponents()
                            ->where('is_active', true)
                            ->with(['allowedProducts' => function($query) {
                                $query->wherePivot('is_active', true)
                                      ->with('productType');
                            }])
                            ->orderBy('sort_order')
                            ->get();
                        
                        foreach ($components as $component) {
                            $product = null;
                            
                            // PRIORITY: For aroma component, use aromaProduct from quotation FIRST.
                            $isAromaComponent = $this->isQuotationAromaComponentName($component->component_name);
                            
                            if ($isAromaComponent && $aromaProduct) {
                                // PRIORITY: Aroma dari Quotation (jasmine dari quotation) > Aroma dari Master Rental (lavender)
                                $product = $aromaProduct;
                            } else {
                                // Get all active allowed products
                                $activeAllowedProducts = $component->allowedProducts->where('pivot.is_active', true);
                                
                                // Find preferred product first
                                $preferredProduct = $activeAllowedProducts->where('pivot.is_preferred', true)->first();
                                
                                if ($preferredProduct) {
                                    $product = $preferredProduct;
                                } else if ($activeAllowedProducts->count() > 0) {
                                    // Fallback to first active allowed product
                                    $product = $activeAllowedProducts->first();
                                }
                            }
                            
                            if ($product) {
                                $originalRentalProducts[$component->id] = $product->id;
                            }
                        }
                    }
                }
                
                // Check for changes
                foreach ($rentalProducts as $rentalProduct) {
                    $componentId = $rentalProduct['component_id'] ?? null;
                    $newProductIdForComponent = $rentalProduct['product_id'] ?? null;
                    
                    if ($componentId && $newProductIdForComponent) {
                        $originalProductIdForComponent = $originalRentalProducts[$componentId] ?? null;
                        
                        if ($originalProductIdForComponent && $newProductIdForComponent != $originalProductIdForComponent) {
                            $hasProductChange = true;
                            $newProductId = $newProductIdForComponent;
                            $changedComponent = $componentId;
                            break; // Take first changed product
                        }
                    }
                }
                
                // MOM11: Save product_id from rental products (first product in list)
                // If material issue doesn't have product_id yet, assign from rental
                if (!$oldProductId && !empty($rentalProducts)) {
                    // Get first product from rental products
                    $firstRentalProduct = reset($rentalProducts);
                    $firstProductId = $firstRentalProduct['product_id'] ?? null;
                    
                    if ($firstProductId) {
                        $updateData['product_id'] = $firstProductId;
                        \Log::info("Material issue {$materialIssue->issue_number} assigned product_id: {$firstProductId} from rental");
                    }
                }
                
                // MOM11: Auto-calculate quantity from rental specification if quantity is 0
                // Quantity = total number of ITEMS (not ML) from rental specification
                if ($materialIssue->quantity == 0 && $assignedRooms->isNotEmpty()) {
                    $totalItems = 0;
                    
                    foreach ($assignedRooms as $jaRoom) {
                        $rental = $jaRoom->rentalProduct;
                        if (!$rental) continue;
                        
                        // Load rental_details untuk count items
                        $rental->load(['rentalDetails.masterProduct']);
                        
                        foreach ($rental->rentalDetails as $detail) {
                            if (!$detail->masterProduct) continue;
                            
                            // Count items (qty dari rental_details)
                            $qty = $detail->quantity ?? 0; // Jumlah item (1, 2, 3, ...)
                            $totalItems += $qty;
                        }
                    }
                    
                    if ($totalItems > 0) {
                        $updateData['quantity'] = $totalItems;
                        \Log::info("Material issue {$materialIssue->issue_number} quantity updated from 0 to {$totalItems} items from rental specification");
                    }
                }
                
                // MOM9 & MOM12: Handle product change - track previous product and note
                // PENTING: Perubahan product HANYA di MaterialIssue, TIDAK mengubah Master Rental
                // Ini adalah case khusus untuk material issue tertentu saja, tidak mempengaruhi data rental yang sudah ada
                
                // MOM12: Simpan product_change_note jika ada, tanpa validasi ketat
                // Karena deteksi perubahan sulit (membandingkan dengan master vs saved)
                if (!empty($request->product_change_note)) {
                    $updateData['product_change_note'] = $request->product_change_note;
                    $updateData['product_changed_at'] = now();
                    $updateData['product_changed_by'] = Auth::id();
                    \Log::info("MOM12: Saving product_change_note: " . $request->product_change_note);
                }
                
                // Keep the old logic for tracking product_id changes at MaterialIssue level
                if ($hasProductChange && $newProductId && $oldProductId && $oldProductId != $newProductId) {
                    $updateData['previous_product_id'] = $oldProductId;
                    $updateData['product_id'] = $newProductId;
                }
                
                // MOM9: Update HANYA MaterialIssue, TIDAK mengubah Master Rental atau Rental Components
                // Master Rental tetap menggunakan product asli, perubahan hanya untuk MaterialIssue ini saja
                $materialIssue->update($updateData);
            }
            
            // MOM11: Re-save material issue items with form data (untuk update quantity dan product changes)
            $this->saveMaterialIssueItems($materialIssue, $jobAssignMaterialIssue, $rentalProducts);
            
            // Update JobAssignMaterialIssue updated_by
            $jobAssignMaterialIssue->update([
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Check if request expects JSON response (AJAX call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job assignment material issue updated successfully.',
                    'data' => $jobAssignMaterialIssue->load(['jobAssignSchedule.jobSchedule.jobAdvice.customer', 'materialIssue.product'])
                ]);
            }

            return redirect()->route('operational.job-assign-material-issues.show', $jobAssignMaterialIssue)
                ->with('success', 'Job assignment material issue updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Check if request expects JSON response (AJAX call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update job assignment material issue: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update job assignment material issue: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        if ($jobAssignMaterialIssue->materialIssue && $jobAssignMaterialIssue->materialIssue->status === 'issued') {
            return back()->with('error', 'Cannot delete issued material.');
        }

        try {
            DB::beginTransaction();
            $jobSchedule = $jobAssignMaterialIssue->jobAssignSchedule?->jobSchedule;

            // Delete the material issue first
            if ($jobAssignMaterialIssue->materialIssue) {
                $inventoryIssuing = \App\Models\InventoryIssuing::where('reference_no', $jobAssignMaterialIssue->materialIssue->issue_number)->first();
                if ($inventoryIssuing && $inventoryIssuing->status === 'pending') {
                    $inventoryIssuing->items()->delete();
                    $inventoryIssuing->delete();
                }

                $jobAssignMaterialIssue->materialIssue->delete();
            }

            // Then delete the job assign material issue
            $jobAssignMaterialIssue->delete();

            if ($jobSchedule) {
                (new \App\Services\Warehouse\InventoryIssuingService())->syncGroupedJobMaterialLifecycleFromJob($jobSchedule);
            }

            DB::commit();

            return redirect()->route('operational.job-assign-material-issues.index')
                ->with('success', 'Job assignment material issue deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to delete job assignment material issue: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete multiple resources.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:job_assign_material_issues,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            $issuedCount = 0;
            $issuedIds = [];
            
            foreach ($request->ids as $id) {
                $jobAssignMaterialIssue = JobAssignMaterialIssue::find($id);
                
                if (!$jobAssignMaterialIssue) {
                    $errors[] = "Material issue with ID {$id} not found.";
                    continue;
                }

                // Check if material is issued
                if ($jobAssignMaterialIssue->materialIssue && $jobAssignMaterialIssue->materialIssue->status === 'issued') {
                    $issuedCount++;
                    $issuedIds[] = $id;
                    $materialIssueNumber = $jobAssignMaterialIssue->materialIssue->issue_number ?? 'N/A';
                    $errors[] = "Material issue {$materialIssueNumber} (ID: {$id}) tidak bisa dihapus karena sudah ter-issue. Silakan unissue terlebih dahulu.";
                    continue;
                }

                // Delete the material issue first
                if ($jobAssignMaterialIssue->materialIssue) {
                    $inventoryIssuing = \App\Models\InventoryIssuing::where('reference_no', $jobAssignMaterialIssue->materialIssue->issue_number)->first();
                    if ($inventoryIssuing && $inventoryIssuing->status === 'pending') {
                        $inventoryIssuing->items()->delete();
                        $inventoryIssuing->delete();
                    }

                    $jobAssignMaterialIssue->materialIssue->delete();
                }

                $jobSchedule = $jobAssignMaterialIssue->jobAssignSchedule?->jobSchedule;

                // Then delete the job assign material issue
                $jobAssignMaterialIssue->delete();

                if ($jobSchedule) {
                    (new \App\Services\Warehouse\InventoryIssuingService())->syncGroupedJobMaterialLifecycleFromJob($jobSchedule);
                }

                $deletedCount++;
            }

            DB::commit();

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                if ($deletedCount > 0) {
                    $message = $deletedCount . ' material issue(s) berhasil dihapus.';
                    if ($issuedCount > 0) {
                        $message .= " {$issuedCount} material issue(s) tidak bisa dihapus karena sudah ter-issue (harus unissue terlebih dahulu).";
                    }
                    return response()->json([
                        'success' => true,
                        'count' => $deletedCount,
                        'issued_count' => $issuedCount,
                        'message' => $message,
                        'errors' => $errors
                    ]);
                } else {
                    $message = 'Tidak ada material issue yang terhapus.';
                    if ($issuedCount > 0) {
                        $message = "Semua material issue yang dipilih sudah ter-issue dan tidak bisa dihapus. Silakan unissue terlebih dahulu sebelum menghapus.";
                    }
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'errors' => $errors,
                        'issued_count' => $issuedCount
                    ], 422);
                }
            }

            return redirect()->route('operational.job-assign-material-issues.index')
                ->with('success', $deletedCount . ' material issue(s) deleted successfully.')
                ->with('errors', $errors);
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON response for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete material issues: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete material issues: ' . $e->getMessage());
        }
    }

    /**
     * Submit issue (bulk issue) for multiple material issues.
     */
    public function submitIssue(Request $request)
    {
        $request->validate([
            'material_issue_ids' => 'required|array|min:1',
            'material_issue_ids.*' => 'required|exists:job_assign_material_issues,id',
            'force_continue' => 'nullable|boolean', // For bypassing warnings
        ]);

        $jobAssignMaterialIssueIds = array_unique($request->material_issue_ids);
        $forceContinue = $request->input('force_continue', false);
        $successCount = 0;
        $errors = [];
        $warnings = [];

        $selectedJobAssignMaterialIssues = JobAssignMaterialIssue::with([
            'jobAssignSchedule.jobSchedule.room',
            'jobAssignSchedule.jobSchedule.jobScheduleRooms'
        ])->whereIn('id', $jobAssignMaterialIssueIds)->get();

        $groupedSelectionErrors = $this->validateGroupedSubmitIssueSelection($selectedJobAssignMaterialIssues);
        if (!empty($groupedSelectionErrors)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submit to issue harus mencakup semua room dalam job number yang sama.',
                    'errors' => $groupedSelectionErrors,
                ], 422);
            }

            return back()
                ->with('error', 'Submit to issue harus mencakup semua room dalam job number yang sama.')
                ->with('errors', $groupedSelectionErrors)
                ->withInput($request->all());
        }

        try {
            DB::beginTransaction();

            // MOM11: REMOVED OLD VALIDATION LOGIC
            // Old logic was calculating total ML from material_issue.quantity × package_size
            // This is WRONG because material_issue.quantity now represents TOTAL ITEMS (diffuser + aroma + cleaner)
            // NOT quantity of a single product!
            // 
            // Example of OLD WRONG LOGIC:
            // - Material Issue quantity = 6 items (1 diffuser + 1 aroma 500ml + 1 cleaner + 1 diffuser + 1 aroma 50ml + 1 cleaner)
            // - Old logic: 6 × 50ml = 300ml ❌ WRONG! (6 is total items, not 6 aroma!)
            // 
            // NEW VALIDATION is done in checkStockForRentalItems() which validates each product individually
            // Stock validation is now per-product with alternative package size suggestions
            
            \Log::info("MOM11: Skipping old ML validation logic. Using new per-product stock validation instead.");
            
            // If there are errors and user hasn't confirmed to continue, abort
            if (count($errors) > 0 && !$forceContinue) {
                DB::rollback();
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed. Please check the errors.',
                        'errors' => $errors,
                        'warnings' => $warnings,
                        'requires_confirmation' => true
                    ], 422);
                }
                
                return back()->with('error', 'Validation failed. Please check the errors.')
                            ->with('errors', $errors)
                            ->with('warnings', $warnings)
                            ->withInput($request->all());
            }

            // Process each material issue
            foreach ($jobAssignMaterialIssueIds as $jobAssignMaterialIssueId) {
                try {
                    $jobAssignMaterialIssue = JobAssignMaterialIssue::findOrFail($jobAssignMaterialIssueId);
                    
                    if (!$jobAssignMaterialIssue->materialIssue) {
                        $errors[] = "Material issue not found for ID: {$jobAssignMaterialIssueId}";
                        \Log::warning("Material issue not found for JobAssignMaterialIssue ID: {$jobAssignMaterialIssueId}");
                        continue;
                    }

                    $materialIssue = $jobAssignMaterialIssue->materialIssue;
                    
                    // MOM11: Validate product_id exists before issuing
                    if (!$materialIssue->product_id) {
                        // Try to recover by grabbing product from items
                        $firstItem = $materialIssue->items()->first();
                        
                        if ($firstItem && $firstItem->product_id) {
                            $materialIssue->update(['product_id' => $firstItem->product_id]);
                            \Log::info("Auto-repaired missing product_id for Material Issue {$materialIssue->issue_number} using product from first item (ID: {$firstItem->product_id})");
                        } else {
                            $errors[] = "❌ Material Issue {$materialIssue->issue_number} tidak memiliki produk yang di-assign.\n\nHarap periksa konfigurasi rental atau edit manual.";
                            \Log::warning("Material issue {$materialIssue->issue_number} has no product_id even after approval. Rental might not have products configured.");
                            continue;
                        }
                    }

                    if ($materialIssue->status === 'issued') {
                        $errors[] = "Material ini sudah ter issue";
                        \Log::info("Material issue {$materialIssue->issue_number} already issued, skipping.");
                        continue;
                    }

                    // Auto-approve if status is pending (for auto-created material issues)
                    if ($materialIssue->status === 'pending') {
                        $materialIssue->update([
                            'status' => 'approved',
                            'updated_by' => Auth::id()
                        ]);
                        \Log::info("Material issue {$materialIssue->issue_number} auto-approved from pending status.");
                        
                        // MOM9: Auto-update job schedule status to "barang_dipersiapkan" when material issue is approved/submitted
                        $jobAssignSchedule = $jobAssignMaterialIssue->jobAssignSchedule;
                        if ($jobAssignSchedule && $jobAssignSchedule->jobSchedule) {
                            $jobSchedule = $jobAssignSchedule->jobSchedule;
                            if (in_array($jobSchedule->status, ['assign_material', 'assign_team', 'scheduled', 'new_job'])) {
                                $jobSchedule->update([
                                    'status' => 'barang_dipersiapkan',
                                    'updated_by' => Auth::id()
                                ]);
                            }
                        }
                    }

                    // MOM11: If status is draft, must be approved first manually
                    if ($materialIssue->status === 'draft') {
                        $errors[] = "Material issue {$materialIssue->issue_number} is still in draft status. Please approve it first before submitting to issue.";
                        \Log::warning("Material issue {$materialIssue->issue_number} is draft. Must be approved first.");
                        continue;
                    }
                    
                    if ($materialIssue->status !== 'approved') {
                        $errors[] = "Material issue {$materialIssue->issue_number} must be approved before issuing. Current status: {$materialIssue->status}";
                        \Log::warning("Material issue {$materialIssue->issue_number} status is {$materialIssue->status}, not approved. Cannot issue.");
                        continue;
                    }

                    // MOM11: Check stock untuk semua items dari rental sebelum issue
                    $stockCheck = $this->checkStockForRentalItems($jobAssignMaterialIssue);
                    
                    if (!$stockCheck['can_fulfill']) {
                        // MOM Refinement: Update status to 'out_of_stock' on failure
                        $materialIssue->update([
                            'status' => 'out_of_stock',
                            'updated_by' => Auth::id()
                        ]);
                        \Log::info("Material issue {$materialIssue->issue_number} status updated to 'out_of_stock' due to insufficient stock.");

                        // Build specific error message as requested
                        $productName = $materialIssue->product->name ?? 'Unknown Product';
                        $warehouseName = $materialIssue->warehouse->name ?? 'Unknown Warehouse';
                        $errorMsg = "❌ Tidak dapat issue Material {$productName} karna Out of stock dari warehouse {$warehouseName}.\n\n";
                        
                        // Add detail warnings
                        foreach ($stockCheck['warnings'] as $warning) {
                            $errorMsg .= $warning . "\n";
                        }
                        
                        if (!empty($stockCheck['alternatives'])) {
                            $errorMsg .= "\n💡 Alternatif Package Size yang Tersedia:\n\n";
                            
                            foreach ($stockCheck['alternatives'] as $alt) {
                                $errorMsg .= "📦 {$alt['original_product']} ({$alt['original_packaging']}) di {$alt['room_name']}:\n";
                                $errorMsg .= "   Butuh: {$alt['qty_needed']}, Stock: {$alt['stock_available']}\n\n";
                                
                                foreach ($alt['suggestions'] as $suggestion) {
                                    $errorMsg .= "   ✅ Gunakan {$suggestion['items_needed']} × {$suggestion['product_name']} ({$suggestion['packaging_size']})\n";
                                    $errorMsg .= "      Stock tersedia: {$suggestion['stock_available']}\n";
                                    $errorMsg .= "      Perhitungan: {$suggestion['calculation']}\n\n";
                                }
                            }
                            
                            $errorMsg .= "💡 Silakan edit material issue dan pilih package size alternatif yang tersedia.";
                        } else {
                            $errorMsg .= "\n❌ Tidak ada alternatif package size yang tersedia dengan stock cukup.";
                        }
                        
                        $errors[] = $errorMsg;
                        \Log::warning("Stock check failed for material issue {$materialIssue->issue_number}. Status set to out_of_stock.");
                        continue;
                    }

                    // Issue the material
                    $materialIssue->issue();
                    \Log::info("Material issue {$materialIssue->issue_number} status updated to 'issued'");
                    
                    // NOTE: Status job schedule tidak di-update ke 'barang_diambil' di sini
                    // Status 'barang_diambil' hanya di-update oleh MaterialVerificationController
                    // setelah teknisi benar-benar verifikasi/mengambil material dari warehouse
                    // Alur yang benar:
                    // 1. Material Issue di-issue → Inventory Issuing dibuat (status: unprepare/ready to issue)
                    // 2. Job Schedule tetap status 'barang_dipersiapkan'
                    // 3. Teknisi verifikasi material via MaterialVerificationController@verifyMaterials
                    // 4. Inventory Issuing status berubah menjadi 'processed'
                    // 5. Job Schedule status baru berubah menjadi 'barang_diambil'

                    // AUTO-CREATE INVENTORY ISSUING (warehouse1.md: issuing berasal dari material issued)
                    // MOM11: Create inventory issuing dengan items dari rental
                    try {
                        $inventoryIssuing = $this->createInventoryIssuingFromMaterialIssue($jobAssignMaterialIssue);
                        \Log::info("Inventory issuing created for material issue {$materialIssue->issue_number}: {$inventoryIssuing->issuing_number}");
                        (new \App\Services\Warehouse\InventoryIssuingService())->syncJobScheduleStatus($inventoryIssuing);
                    } catch (\Exception $e) {
                        \Log::error("Failed to create Inventory Issuing for material issue {$materialIssue->issue_number}: " . $e->getMessage());
                        $errors[] = "Failed to create Inventory Issuing for material issue {$materialIssue->issue_number}: " . $e->getMessage();
                        (new \App\Services\Warehouse\InventoryIssuingService())->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);
                        // Don't throw, continue processing other material issues
                    }

                    // Count original occurrences for user feedback
                    $occurrences = count(array_keys($request->material_issue_ids, $jobAssignMaterialIssueId));
                    $successCount += $occurrences;
                } catch (\Exception $e) {
                    $errorMsg = "Error processing ID {$jobAssignMaterialIssueId}: " . $e->getMessage();
                    $errors[] = $errorMsg;
                    \Log::error($errorMsg . " | Trace: " . $e->getTraceAsString());
                }
            }

            DB::commit();

            // Build better error messages
            $issuedErrors = [];
            $otherErrors = [];
            
            foreach ($errors as $error) {
                if (strpos($error, 'Material ini sudah ter issue') !== false) {
                    $issuedErrors[] = $error;
                } else {
                    $otherErrors[] = $error;
                }
            }

            // Build message based on results
            if ($successCount > 0 && count($issuedErrors) > 0) {
                // Some succeeded, some already issued
                $message = "✅ Berhasil meng-issue {$successCount} material.";
                if (count($issuedErrors) > 0) {
                    $message .= "\n\nℹ️ " . count($issuedErrors) . " material sudah ter-issue sebelumnya.";
                }
                if (count($otherErrors) > 0) {
                    $message .= " " . count($otherErrors) . " error(s) occurred.";
                }
            } elseif ($successCount === 0 && count($issuedErrors) > 0) {
                // All already issued
                $message = "ℹ️ Material ini sudah ter-issue sebelumnya.";
            } elseif ($successCount > 0) {
                // All succeeded
                $message = "✅ Berhasil meng-issue {$successCount} material!";
            } else {
                // All failed - check if it's stock-related error
                $hasStockError = false;
                foreach ($errors as $error) {
                    if (stripos($error, 'Stock tidak cukup') !== false || 
                        stripos($error, 'stock') !== false ||
                        stripos($error, 'Stock tidak') !== false) {
                        $hasStockError = true;
                        break;
                    }
                }
                
                if ($hasStockError) {
                    $message = "❌ Gagal meng-issue material karena stock tidak mencukupi.\n\nSilakan periksa detail error di bawah untuk informasi lebih lanjut dan alternatif package size yang tersedia.";
                } else {
                    $message = "❌ Gagal meng-issue material. " . count($errors) . " error terjadi.";
                }
            }
            
            if (count($warnings) > 0) {
                $message .= " " . count($warnings) . " warning(s).";
            }

            // Determine response status
            $responseStatus = ($successCount > 0 || count($issuedErrors) > 0) ? 'success' : 'error';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => $responseStatus,
                    'message' => $message,
                    'data' => [
                        'success_count' => $successCount,
                        'error_count' => count($errors),
                        'warning_count' => count($warnings),
                        'issued_error_count' => count($issuedErrors),
                        'other_error_count' => count($otherErrors),
                        'errors' => $errors,
                        'warnings' => $warnings
                    ]
                ]);
            }

            if ($responseStatus === 'success') {
                return back()->with('success', $message)
                            ->with('errors', $errors)
                            ->with('warnings', $warnings);
            } else {
                return back()->with('error', $message)
                            ->with('errors', $errors)
                            ->with('warnings', $warnings);
            }
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to submit issues: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to submit issues: ' . $e->getMessage());
        }
    }

    /**
     * Issue the material.
     */
    public function issue(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        if (!$jobAssignMaterialIssue->materialIssue) {
            return back()->with('error', 'No material issue found.');
        }

        if ($jobAssignMaterialIssue->materialIssue->status === 'issued') {
            return back()->with('error', 'Material is already issued.');
        }

        if ($jobAssignMaterialIssue->materialIssue->status !== 'approved') {
            return back()->with('error', 'Material must be approved before issuing.');
        }

        try {
            DB::beginTransaction();

            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            $materialIssue->issue();

            // AUTO-CREATE INVENTORY ISSUING (warehouse1.md: issuing berasal dari material issued)
            $inventoryIssuing = $this->createInventoryIssuingFromMaterialIssue($jobAssignMaterialIssue);
            if ($inventoryIssuing) {
                (new \App\Services\Warehouse\InventoryIssuingService())->syncJobScheduleStatus($inventoryIssuing);
            } else {
                (new \App\Services\Warehouse\InventoryIssuingService())->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);
            }

            DB::commit();

            return back()->with('success', '✅ Material berhasil di-issue dan stok warehouse telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', '❌ Gagal meng-issue material: ' . $e->getMessage());
        }
    }

    /**
     * Unissue the material.
     */
    public function unissue(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        if (!$jobAssignMaterialIssue->materialIssue) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No material issue found.'
                ], 404);
            }
            return back()->with('error', 'No material issue found.');
        }

        if ($jobAssignMaterialIssue->materialIssue->status !== 'issued') {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material is not issued.'
                ], 422);
            }
            return back()->with('error', 'Material is not issued.');
        }

        try {
            DB::beginTransaction();

            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            $syncService = new \App\Services\Warehouse\InventoryIssuingService();
            
            // Validation: Check Inventory Issuing status
            $inventoryIssuing = \App\Models\InventoryIssuing::where('reference_no', $materialIssue->issue_number)->first();
            
            if ($inventoryIssuing) {
                if ($inventoryIssuing->status !== 'pending') {
                    throw new \Exception("Cannot unissue. Inventory Issuing {$inventoryIssuing->issuing_number} is already {$inventoryIssuing->status} (must be pending).");
                }

                $syncService->rollbackPostedStock($inventoryIssuing);
                
                // Delete Inventory Issuing
                $inventoryIssuing->items()->delete();
                $inventoryIssuing->delete();
                \Log::info("Deleted Inventory Issuing {$inventoryIssuing->issuing_number} during unissue.");
            } else {
                $syncService->rollbackMaterialIssueStock($materialIssue);
            }

            // Revert status to 'pending' (as per request)
            $materialIssue->update(['status' => 'pending']);

            $syncService->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);

            DB::commit();

            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Material unissued successfully, inventory issuing removed, stock returned, and job status reverted.'
                ]);
            }

            return back()->with('success', 'Material unissued successfully, inventory issuing removed, and stock reverted.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unissue material: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to return material: ' . $e->getMessage());
        }
    }

    /**
     * Bulk unissue multiple material issues.
     */
    public function bulkUnissue(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:job_assign_material_issues,id'
        ]);

        try {
            DB::beginTransaction();

            $unissuedCount = 0;
            $errors = [];

            foreach (array_unique($request->ids) as $id) {
                $jobAssignMaterialIssue = JobAssignMaterialIssue::find($id);
                
                if (!$jobAssignMaterialIssue) {
                    $errors[] = "Material issue with ID {$id} not found.";
                    continue;
                }

                if (!$jobAssignMaterialIssue->materialIssue) {
                    $errors[] = "No material issue found for ID {$id}.";
                    continue;
                }

                if ($jobAssignMaterialIssue->materialIssue->status !== 'issued') {
                    $materialIssueNumber = $jobAssignMaterialIssue->materialIssue->issue_number ?? 'N/A';
                    $errors[] = "Material issue {$materialIssueNumber} (ID: {$id}) tidak bisa di-unissue karena statusnya bukan 'issued'.";
                    continue;
                }

                $materialIssue = $jobAssignMaterialIssue->materialIssue;
                // Check Inventory Issuing status
                $inventoryIssuing = \App\Models\InventoryIssuing::where('reference_no', $jobAssignMaterialIssue->materialIssue->issue_number)->first();
                if ($inventoryIssuing && $inventoryIssuing->status !== 'pending') {
                    $materialIssueNumber = $jobAssignMaterialIssue->materialIssue->issue_number ?? 'N/A';
                    $errors[] = "Material issue {$materialIssueNumber} (ID: {$id}) has processed inventory issuing {$inventoryIssuing->issuing_number}. Cannot unissue.";
                    continue;
                }

                $materialIssue = $jobAssignMaterialIssue->materialIssue;
                
                // Delete Inv Issuing if pending
                $syncService = new \App\Services\Warehouse\InventoryIssuingService();
                if ($inventoryIssuing && $inventoryIssuing->status === 'pending') {
                    $syncService->rollbackPostedStock($inventoryIssuing);
                    $inventoryIssuing->items()->delete();
                    $inventoryIssuing->delete();
                } elseif (!$inventoryIssuing) {
                    $syncService->rollbackMaterialIssueStock($materialIssue);
                }

                // Revert Status
                $materialIssue->update(['status' => 'pending']);

                $syncService->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);

                // Count original occurrences for user feedback
                $occurrences = count(array_keys($request->ids, $id));
                $unissuedCount += $occurrences;
            }

            DB::commit();

            if ($request->ajax() || $request->expectsJson()) {
                if ($unissuedCount > 0) {
                    $message = $unissuedCount . ' material issue(s) berhasil di-unissue.';
                    if (count($errors) > 0) {
                        $message .= " " . count($errors) . " material issue(s) tidak bisa di-unissue/error.";
                    }
                    return response()->json([
                        'success' => true,
                        'count' => $unissuedCount,
                        'message' => $message,
                        'errors' => $errors
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ada material issue yang berhasil di-unissue.',
                        'errors' => $errors
                    ], 422);
                }
            }

            return redirect()->route('operational.job-assign-material-issues.index')
                ->with('success', $unissuedCount . ' material issue(s) unissued successfully.')
                ->with('errors', $errors);
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unissue material issues: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to unissue material issues: ' . $e->getMessage());
        }
    }

    /**
     * Get material issues by job number for API.
     */
    public function getMaterialIssuesByJob(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
        ]);

        $materialIssues = JobAssignMaterialIssue::whereHas('jobAssignSchedule.jobSchedule', function($q) use ($request) {
                $q->where('job_number', 'like', "%{$request->job_number}%");
            })
            ->with([
                'jobAssignSchedule.jobSchedule.jobAdvice.customer',
                'jobAssignSchedule.jobSchedule.building',
                'materialIssue.team',
                'materialIssue.product'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $materialIssues,
        ]);
    }

    /**
     * Get material issues by team for API.
     */
    public function getMaterialIssuesByTeam(Request $request)
    {
        $request->validate([
            'team_name' => 'required|string',
        ]);

        $materialIssues = JobAssignMaterialIssue::whereHas('materialIssue.team', function($q) use ($request) {
                $q->where('team_name', 'like', "%{$request->team_name}%");
            })
            ->with([
                'jobAssignSchedule.jobSchedule.jobAdvice.customer',
                'jobAssignSchedule.jobSchedule.building',
                'materialIssue.team',
                'materialIssue.product'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $materialIssues,
        ]);
    }

    /**
     * Get material issue statistics for API.
     */
    public function getMaterialIssueStatistics()
    {
        $statistics = [
            'total' => JobAssignMaterialIssue::count(),
            'pending' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'pending');
            })->count(),
            'approved' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'approved');
            })->count(),
            'issued' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'issued');
            })->count(),
            'rejected' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'rejected');
            })->count(),
            'today' => JobAssignMaterialIssue::whereDate('created_at', today())->count(),
            'this_week' => JobAssignMaterialIssue::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => JobAssignMaterialIssue::whereMonth('created_at', now()->month)->count(),
            'materials_by_status' => JobAssignMaterialIssue::select('material_issues.status', DB::raw('count(*) as count'))
                ->join('material_issues', 'job_assign_material_issues.material_issue_id', '=', 'material_issues.id')
                ->groupBy('material_issues.status')
                ->get(),
            'recent_materials' => JobAssignMaterialIssue::with([
                'jobAssignSchedule.jobSchedule.jobAdvice.customer',
                'jobAssignSchedule.jobSchedule.building',
                'materialIssue.team',
                'materialIssue.product'
            ])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Search material issues for API.
     */
    public function searchMaterialIssues(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $materialIssues = JobAssignMaterialIssue::where(function ($q) use ($request) {
                $q->whereHas('jobAssignSchedule.jobSchedule', function($subQ) use ($request) {
                    $subQ->where('job_number', 'like', "%{$request->search}%");
                })
                ->orWhereHas('jobAssignSchedule.jobSchedule.jobAdvice.customer', function($subQ) use ($request) {
                    $subQ->where('name', 'like', "%{$request->search}%");
                })
                ->orWhereHas('jobAssignSchedule.jobSchedule.building', function($subQ) use ($request) {
                    $subQ->where('nama_gedung', 'like', "%{$request->search}%");
                })
                ->orWhereHas('materialIssue.product', function($subQ) use ($request) {
                    $subQ->where('name', 'like', "%{$request->search}%");
                });
            })
            ->with([
                'jobAssignSchedule.jobSchedule.jobAdvice.customer',
                'jobAssignSchedule.jobSchedule.building',
                'materialIssue.team',
                'materialIssue.product'
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $materialIssues,
        ]);
    }

    /**
     * Generate unique issue number (MOM10)
     * @deprecated Use DocumentNumberService::generate('material_issue', ...) instead
     */
    private function generateUniqueIssueNumber($warehouseId = null)
    {
        // MOM10: Use DocumentNumberService to generate material issue number
        // Get branch code from warehouse
        $documentNumberService = new DocumentNumberService();
        return $documentNumberService->generate(
            'material_issue',
            null, // Will get from warehouse
            null,
            null,
            null,
            null,
            $warehouseId // Get branch from warehouse
        );
    }

    private function getCachedMasterOptions(string $optionName)
    {
        $cacheKey = 'job-assign-material-issues:index:options:' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $optionName));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($optionName) {
            return $this->getMasterOptions($optionName);
        });
    }

    private function buildIndexLookups($materialIssues): array
    {
        $issues = $materialIssues->getCollection();

        if ($issues->isEmpty()) {
            return [
                'rentalDetailFallbackMap' => [],
                'rentalDetailById' => [],
                'allowedProductIdsByRentalDetailId' => [],
                'branchWarehouseLookup' => [],
                'warehouseStockLookup' => [],
            ];
        }

        $items = $issues
            ->pluck('materialIssue')
            ->filter()
            ->pluck('items')
            ->flatten(1)
            ->filter();

        $fallbackRentalPairs = [];
        $directRentalDetailIds = [];

        foreach ($items as $item) {
            $notes = $item->notes ?? '';

            if ($notes && preg_match('/(?:RentalDetailID|ComponentID):\s*(\d+)/', $notes, $matches)) {
                $directRentalDetailIds[] = (int) $matches[1];
            }

            if (
                $notes
                && preg_match('/Rental:\s*([^,]+)/', $notes, $matches)
                && $item->product
                && $item->product->product_type_id
            ) {
                $fallbackRentalPairs[trim($matches[1])][(int) $item->product->product_type_id] = true;
            }
        }

        $rentalNameToId = collect();
        if (!empty($fallbackRentalPairs)) {
            $rentalNameToId = \App\Models\MasterRental::query()
                ->whereIn('rental_name', array_keys($fallbackRentalPairs))
                ->pluck('id', 'rental_name');
        }

        $directRentalDetails = collect();
        if (!empty($directRentalDetailIds)) {
            $directRentalDetails = \App\Models\RentalDetail::query()
                ->select('id', 'master_rental_id', 'product_type_id', 'product_category_id', 'bom_rental_qty')
                ->whereIn('id', array_values(array_unique($directRentalDetailIds)))
                ->get();
        }

        $fallbackRentalDetails = collect();
        $masterRentalIds = $rentalNameToId->values()->filter()->unique()->values();
        $productTypeIds = collect($fallbackRentalPairs)
            ->flatMap(function ($productTypes) {
                return array_keys($productTypes);
            })
            ->unique()
            ->values();

        if ($masterRentalIds->isNotEmpty() && $productTypeIds->isNotEmpty()) {
            $fallbackRentalDetails = \App\Models\RentalDetail::query()
                ->select('id', 'master_rental_id', 'product_type_id', 'product_category_id', 'bom_rental_qty')
                ->whereIn('master_rental_id', $masterRentalIds)
                ->whereIn('product_type_id', $productTypeIds)
                ->get();
        }

        $rentalDetailById = $directRentalDetails
            ->concat($fallbackRentalDetails)
            ->unique('id')
            ->keyBy('id')
            ->all();

        $allowedProductIdsByRentalDetailId = [];
        $rentalDetailIds = collect($rentalDetailById)->keys()->filter()->values();
        if ($rentalDetailIds->isNotEmpty()) {
            $allowedProductIdsByRentalDetailId = DB::table('rental_detail_materials')
                ->select('rental_detail_id', 'master_product_id')
                ->whereIn('rental_detail_id', $rentalDetailIds)
                ->where('is_selected', true)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('rental_detail_id')
                ->map(function ($rows) {
                    return $rows->pluck('master_product_id')->map(fn ($id) => (int) $id)->values()->all();
                })
                ->all();
        }

        $masterRentalIdToName = $rentalNameToId->flip()->all();
        $rentalDetailFallbackMap = [];
        foreach ($fallbackRentalDetails as $detail) {
            $rentalName = $masterRentalIdToName[$detail->master_rental_id] ?? null;
            if (!$rentalName) {
                continue;
            }

            $rentalDetailFallbackMap[$rentalName . '|' . $detail->product_type_id] = [
                'id' => $detail->id,
                'bom_rental_qty' => $detail->bom_rental_qty,
            ];
        }

        $branchIds = collect();
        foreach ($issues as $issue) {
            $jobSchedule = optional($issue->jobAssignSchedule)->jobSchedule;
            $city = optional(optional($jobSchedule)->building)->city;
            $team = optional($issue->jobAssignSchedule)->team;

            $cityBranch = $city && $city->relationLoaded('branches') ? $city->branches->first() : null;
            $provinceBranch = $city && $city->province && $city->province->relationLoaded('branches')
                ? $city->province->branches->first()
                : null;
            $teamBranch = $team && $team->relationLoaded('branch') ? $team->branch : null;

            foreach ([$cityBranch, $provinceBranch, $teamBranch] as $branch) {
                if ($branch) {
                    $branchIds->push($branch->id);
                }
            }
        }

        $branchWarehouseLookup = [];
        if ($branchIds->isNotEmpty()) {
            $branchWarehouseLookup = Warehouse::query()
                ->select('id', 'name', 'branch_id', 'is_active')
                ->whereIn('branch_id', $branchIds->unique()->values())
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->get()
                ->groupBy('branch_id')
                ->map(function ($warehouses) {
                    return $warehouses->first();
                })
                ->all();
        }

        $productIds = $items->pluck('product_id')->filter()->unique()->values();
        $warehouseIds = collect();

        foreach ($issues as $issue) {
            $materialIssue = $issue->materialIssue;
            if ($materialIssue && $materialIssue->warehouse_id) {
                $warehouseIds->push($materialIssue->warehouse_id);
                continue;
            }

            $jobSchedule = optional($issue->jobAssignSchedule)->jobSchedule;
            $city = optional(optional($jobSchedule)->building)->city;
            $team = optional($issue->jobAssignSchedule)->team;

            $branch = ($city && $city->relationLoaded('branches') ? $city->branches->first() : null)
                ?: ($city && $city->province && $city->province->relationLoaded('branches') ? $city->province->branches->first() : null)
                ?: ($team && $team->relationLoaded('branch') ? $team->branch : null);

            if ($branch && isset($branchWarehouseLookup[$branch->id])) {
                $warehouseIds->push($branchWarehouseLookup[$branch->id]->id);
            }
        }

        $warehouseStockLookup = [];
        if ($warehouseIds->isNotEmpty() && $productIds->isNotEmpty()) {
            $warehouseStockLookup = \App\Models\WarehouseProduct::query()
                ->select('warehouse_id', 'master_product_id', 'quantity')
                ->whereIn('warehouse_id', $warehouseIds->unique()->values())
                ->whereIn('master_product_id', $productIds)
                ->get()
                ->mapWithKeys(function ($stock) {
                    return [$stock->warehouse_id . ':' . $stock->master_product_id => $stock->quantity ?? 0];
                })
                ->all();
        }

        return [
            'rentalDetailFallbackMap' => $rentalDetailFallbackMap,
            'rentalDetailById' => $rentalDetailById,
            'allowedProductIdsByRentalDetailId' => $allowedProductIdsByRentalDetailId,
            'branchWarehouseLookup' => $branchWarehouseLookup,
            'warehouseStockLookup' => $warehouseStockLookup,
        ];
    }

    /**
     * Get master options by name
     */
    private function getMasterOptions($optionName)
    {
        $masterOption = MasterOption::where('name', $optionName)->first();
        
        if (!$masterOption) {
            // Return fallback options if master option doesn't exist
            switch ($optionName) {
                case 'Request Reason':
                    return collect([
                        (object)['option_name' => 'Installation', 'option_description' => 'Installation'],
                        (object)['option_name' => 'Maintenance', 'option_description' => 'Maintenance'],
                        (object)['option_name' => 'Repair', 'option_description' => 'Repair'],
                        (object)['option_name' => 'Replacement', 'option_description' => 'Replacement'],
                        (object)['option_name' => 'Emergency', 'option_description' => 'Emergency'],
                        (object)['option_name' => 'Other', 'option_description' => 'Other'],
                    ]);
                case 'Priority':
                    return collect([
                        (object)['option_name' => 'Low', 'option_description' => 'Low'],
                        (object)['option_name' => 'Medium', 'option_description' => 'Medium'],
                        (object)['option_name' => 'High', 'option_description' => 'High'],
                        (object)['option_name' => 'Urgent', 'option_description' => 'Urgent'],
                    ]);
                case 'Material Issue Status':
                    return collect([
                        (object)['option_name' => 'Draft', 'option_description' => 'Draft'],
                        (object)['option_name' => 'Pending', 'option_description' => 'Pending'],
                        (object)['option_name' => 'Approved', 'option_description' => 'Approved'],
                        (object)['option_name' => 'Issued', 'option_description' => 'Issued'],
                        (object)['option_name' => 'Received', 'option_description' => 'Received'],
                        (object)['option_name' => 'Rejected', 'option_description' => 'Rejected'],
                        (object)['option_name' => 'Out of Stock', 'option_description' => 'Out of Stock'],
                    ]);
                default:
                    return collect();
            }
        }
        
        return $masterOption->optionDetails()->where('is_active', true)->get();
    }

    /**
     * Validate stock and handle stock split combination if needed
     * 
     * "ketika butuh 150ml, bisa klik stock 100ml, masih kurang 50ml"
     * "baris itu akan di-copy sehingga ada data baru, tinggal pilih yang 50ml biar genap"
     * "jika lebih dari itu (misal dua kali 100ml = 200ml, sedangkan rental hanya 150ml) maka gagal"
     * 
     * @param int $warehouseId
     * @param int $productId
     * @param float $requiredQuantity
     * @return array ['can_fulfill' => bool, 'stock_available' => float, 'needs_split' => bool, 'split_combinations' => array]
     */
    private function validateAndCheckStockSplit($warehouseId, $productId, $requiredQuantity)
    {
        // Get stock for the requested product
        $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouseId)
            ->where('master_product_id', $productId)
            ->first();

        $stockAvailable = $warehouseProduct ? $warehouseProduct->quantity : 0;

        // If stock is sufficient, no split needed
        if ($stockAvailable >= $requiredQuantity) {
            return [
                'can_fulfill' => true,
                'stock_available' => $stockAvailable,
                'needs_split' => false,
                'split_combinations' => []
            ];
        }

        // If stock is insufficient, check if we can find alternative products to combine
        // This requires finding products with same product type or compatible products
        // For now, we'll return that split is needed but not automatically create it
        // User should manually create second material issue with remaining quantity
        return [
            'can_fulfill' => false,
            'stock_available' => $stockAvailable,
            'needs_split' => true,
            'split_combinations' => [],
            'message' => "Stock insufficient. Available: {$stockAvailable}, Required: {$requiredQuantity}. Please create additional material issue for remaining quantity: " . ($requiredQuantity - $stockAvailable)
        ];
    }

    /**
     * MOM11: Check stock untuk semua items dari material_issue_items (bukan rental!)
     * Items sudah tersimpan di database dengan product yang sudah di-customize dari quotation
     * 
     * @param JobAssignMaterialIssue $jobAssignMaterialIssue
     * @return array ['can_fulfill' => bool, 'items' => array, 'warnings' => array, 'alternatives' => array]
     */
    private function checkStockForRentalItems(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        $jobAssignMaterialIssue->load([
            'materialIssue.warehouse',
            'materialIssue.items.product.packagingSize',
            'materialIssue.items.product.productCategory',
            'materialIssue.items.product.productType'
        ]);
        
        $materialIssue = $jobAssignMaterialIssue->materialIssue;
        $warehouse = $materialIssue->warehouse;
        
        if (!$warehouse) {
            return [
                'can_fulfill' => false,
                'items' => [],
                'warnings' => ['Warehouse tidak ditemukan'],
                'alternatives' => []
            ];
        }
        
        // Get items from material_issue_items (bukan dari rental!)
        $materialIssueItems = $materialIssue->items;
        
        if ($materialIssueItems->isEmpty()) {
            return [
                'can_fulfill' => false,
                'items' => [],
                'warnings' => ['Material issue items tidak ditemukan. Silakan edit material issue untuk generate items.'],
                'alternatives' => []
            ];
        }
        
        $items = [];
        $warnings = [];
        $alternatives = [];
        $canFulfillAll = true;
        
        foreach ($materialIssueItems as $item) {
            $product = $item->product;
            if (!$product) continue;
            
            $qtyNeeded = $item->quantity ?? 0;
            
            // Get stock
            $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                ->where('master_product_id', $product->id)
                ->first();
            $stockAvailable = $warehouseProduct ? $warehouseProduct->quantity : 0;
            
            $itemData = [
                'room_name' => $item->room_name,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_type' => $product->productType->name ?? null,
                'packaging_size' => $product->packagingSize->name ?? 'Unit',
                'qty_needed' => $qtyNeeded,
                'stock_available' => $stockAvailable,
                'can_fulfill' => $stockAvailable >= $qtyNeeded
            ];
            
            $items[] = $itemData;
            
            // Check if stock insufficient
            if ($stockAvailable < $qtyNeeded) {
                $canFulfillAll = false;
                
                // Check if this is aroma type (need alternative package size)
                $productTypeName = $product->productType->name ?? null;
                $isAromaType = $productTypeName && (
                    stripos($productTypeName, 'aroma') !== false || 
                    stripos($productTypeName, 'refill') !== false ||
                    stripos($productTypeName, 'variant') !== false
                );
                
                if ($isAromaType && $product->packagingSize) {
                    // Find alternative package sizes
                    $currentPackagingSize = $product->packagingSize;
                    preg_match('/(\d+)/', $currentPackagingSize->name, $matches);
                    $currentSizeML = isset($matches[1]) ? (int)$matches[1] : 0;
                    
                    if ($currentSizeML > 0) {
                        // Get all products with same product type/category but different packaging size
                        $alternativeProducts = \App\Models\MasterProduct::where(function($q) use ($product) {
                                if ($product->product_category_id) {
                                    $q->where('product_category_id', $product->product_category_id);
                                } else {
                                    $q->where('product_type_id', $product->product_type_id);
                                }
                            })
                            ->where('id', '!=', $product->id)
                            ->whereNotNull('packaging_size_id')
                            ->with(['productCategory', 'productType', 'packagingSize'])
                            ->get();
                        
                        $alternativeSuggestions = [];
                        
                        foreach ($alternativeProducts as $altProduct) {
                            if (!$altProduct->packagingSize) continue;
                            
                            // Get stock for alternative
                            $altWarehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                                ->where('master_product_id', $altProduct->id)
                                ->first();
                            $altStock = $altWarehouseProduct ? $altWarehouseProduct->quantity : 0;
                            
                            if ($altStock <= 0) continue;
                            
                            // Calculate how many items needed
                            preg_match('/(\d+)/', $altProduct->packagingSize->name, $altMatches);
                            $altSizeML = isset($altMatches[1]) ? (int)$altMatches[1] : 0;
                            
                            if ($altSizeML <= 0) continue;
                            
                            // Calculate: berapa item alternative yang dibutuhkan untuk replace 1 item current
                            $itemsNeeded = ceil($currentSizeML / $altSizeML);
                            $totalItemsNeeded = $itemsNeeded * $qtyNeeded;
                            
                            if ($altStock >= $totalItemsNeeded) {
                                $alternativeSuggestions[] = [
                                    'product_id' => $altProduct->id,
                                    'product_name' => $altProduct->name,
                                    'packaging_size' => $altProduct->packagingSize->name,
                                    'size_ml' => $altSizeML,
                                    'items_needed' => $totalItemsNeeded,
                                    'stock_available' => $altStock,
                                    'calculation' => "{$qtyNeeded} × {$currentPackagingSize->name} = {$totalItemsNeeded} × {$altProduct->packagingSize->name}"
                                ];
                            }
                        }
                        
                        if (!empty($alternativeSuggestions)) {
                            $alternatives[] = [
                                'original_product' => $product->name,
                                'original_packaging' => $currentPackagingSize->name,
                                'room_name' => $item->room_name,
                                'qty_needed' => $qtyNeeded,
                                'stock_available' => $stockAvailable,
                                'suggestions' => $alternativeSuggestions
                            ];
                        }
                    }
                }
                
                $packagingSizeName = $product->packagingSize ? $product->packagingSize->name : 'Unit';
                $warnings[] = "❌ Stock tidak cukup: {$product->name} ({$packagingSizeName}) di {$item->room_name}. Butuh: {$qtyNeeded}, Stock: {$stockAvailable}";
            }
        }
        
        return [
            'can_fulfill' => $canFulfillAll,
            'items' => $items,
            'warnings' => $warnings,
            'alternatives' => $alternatives
        ];
    }

    /**
     * MOM11: Update warehouse stock when material is issued (untuk SEMUA items dari material_issue_items)
     */
    private function updateWarehouseStockOnIssue(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            $jobAssignMaterialIssue->load([
                'materialIssue.warehouse',
                'materialIssue.items.product'
            ]);
            
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            $warehouse = $materialIssue->warehouse;
            
            if (!$warehouse) {
                \Log::warning("Material issue {$materialIssue->issue_number} has no warehouse. Skipping stock update.");
                return;
            }
            
            // Get items from material_issue_items (bukan dari rental!)
            $materialIssueItems = $materialIssue->items;
            
            if ($materialIssueItems->isEmpty()) {
                \Log::warning("Material issue {$materialIssue->issue_number} has no items. Skipping stock update.");
                return;
            }
            
            // Loop semua items dan update stock
            foreach ($materialIssueItems as $item) {
                $product = $item->product;
                if (!$product) continue;
                
                $qtyToIssue = $item->quantity ?? 0;
                if ($qtyToIssue <= 0) continue;
                
                // Find or create warehouse product record
                $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                    ->where('master_product_id', $product->id)
                    ->first();

                if (!$warehouseProduct) {
                    // Create new warehouse product record with 0 stock
                    $warehouseProduct = \App\Models\WarehouseProduct::create([
                        'warehouse_id' => $warehouse->id,
                        'master_product_id' => $product->id,
                        'quantity' => 0,
                        'minimum_stock' => 0,
                        'maximum_stock' => 1000,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                // Update stock quantity (decrease)
                $newQuantity = $warehouseProduct->quantity - $qtyToIssue;
                $warehouseProduct->update([
                    'quantity' => $newQuantity,
                    'updated_by' => Auth::id(),
                ]);
                
                \Log::info("Stock updated: Product {$product->name} (ID: {$product->id}), Warehouse {$warehouse->name}, Qty: {$warehouseProduct->quantity} → {$newQuantity} (-{$qtyToIssue})");

                // Create inventory movement record
                $movementData = [
                    'warehouse_id' => $warehouse->id,
                    'master_product_id' => $product->id,
                    'movement_type' => 'out',
                    'quantity' => -abs($qtyToIssue), // Ensure negative for 'out' movement
                    'notes' => "Material issued for job. Issue Number: {$materialIssue->issue_number}, Room: {$item->room_name}, Product: {$product->name}",
                    'created_by' => Auth::id(),
                ];
                
                // Add optional columns only if they exist in database
                try {
                    $columns = Schema::getColumnListing('inventory_movements');
                    
                    // Add movement_date if exists
                    if (in_array('movement_date', $columns)) {
                        $movementData['movement_date'] = $materialIssue->issue_date ?? now()->toDateString();
                    }
                    
                    // Add reference_no if exists
                    if (in_array('reference_no', $columns)) {
                        $movementData['reference_no'] = $materialIssue->issue_number ?? "MI-{$materialIssue->id}";
                    }
                    
                    // Add reference_type if exists
                    if (in_array('reference_type', $columns)) {
                        $movementData['reference_type'] = 'material_issue';
                    }
                    
                    // Add reference_id if exists
                    if (in_array('reference_id', $columns)) {
                        $movementData['reference_id'] = $materialIssue->id;
                    }
                    
                    // Add movement_no if exists
                    if (in_array('movement_no', $columns)) {
                        $movementData['movement_no'] = 'ISS-' . ($materialIssue->issue_number ?? date('Ymd') . '-' . $materialIssue->id);
                    }
                } catch (\Exception $e) {
                    // If schema check fails, just use minimal columns
                    \Log::warning("Could not check inventory_movements columns: " . $e->getMessage());
                }
                
                \App\Models\InventoryMovement::create($movementData);
            }
            
            \Log::info("Warehouse stock updated for all items in material issue: {$materialIssue->issue_number}");

        } catch (\Exception $e) {
            \Log::error("Failed to update warehouse stock on material issue {$materialIssue->issue_number}: " . $e->getMessage());
            throw $e; // Re-throw to rollback transaction
        }
    }

    /**
     * MOM11: Create Inventory Issuing record from MaterialIssue with items dari material_issue_items
     */
    private function createInventoryIssuingFromMaterialIssue(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            $jobAssignMaterialIssue->load([
                'materialIssue.warehouse',
                'materialIssue.items.product'
            ]);
            
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            
            // Prevent duplicate issuing for the same material issue
            $existing = \App\Models\InventoryIssuing::where('reference_no', $materialIssue->issue_number)->first();
            if ($existing) {
                \Log::info("Inventory Issuing already exists for Material Issue {$materialIssue->issue_number}: {$existing->issuing_number}");
                return $existing;
            }

            $warehouse = $materialIssue->warehouse;
            if (!$warehouse) {
                throw new \Exception("Warehouse not found for Material Issue {$materialIssue->issue_number}");
            }
            
            $branchId = $warehouse->branch_id;

            // MOM11: Generate issuing number using DocumentNumberService with IIS code
            $documentNumberService = new DocumentNumberService();
            $issuingNumber = $documentNumberService->generate(
                'inventory_issuing',
                null, // Will get from warehouse
                null,
                null,
                null,
                null,
                $materialIssue->warehouse_id
            );
            
            // warehouse1.md: "statusnya, jika baru di buat maka statusnya adalah Un-prepared"
            // Use 'pending' status as it's the initial status for new inventory issuing
            $issuing = \App\Models\InventoryIssuing::create([
                'issuing_number' => $issuingNumber,
                'inventory_request_id' => null,
                'branch_id' => $branchId,
                'warehouse_id' => $materialIssue->warehouse_id,
                'issue_date' => $materialIssue->issue_date ?? now()->toDateString(),
                'reference_no' => $materialIssue->issue_number,
                'requested_by' => $materialIssue->requested_by ?? auth()->id(),
                'issued_by' => $materialIssue->issued_by ?? auth()->id(),
                'received_by' => null,
                'status' => 'pending', // Un-prepared status (warehouse1.md: status baru di buat adalah Un-prepared)
                'remarks' => trim(($materialIssue->description ?: '') . ' ' . ($materialIssue->notes ?: '')) ?: null,
                'issued_at' => null, // Not issued yet, will be set when ready to issue
                'created_by' => auth()->id(),
            ]);
            
            \Log::info("Created Inventory Issuing: {$issuing->issuing_number} for Material Issue: {$materialIssue->issue_number}");

            // MOM11: Create issuing items dari material_issue_items (bukan dari rental!)
            // Items sudah tersimpan di database dengan product yang sudah di-customize dari quotation
            $materialIssueItems = $materialIssue->items;
            
            if ($materialIssueItems->isEmpty()) {
                \Log::warning("Material issue {$materialIssue->issue_number} has no items. Cannot create inventory issuing items.");
            } else {
                foreach ($materialIssueItems as $item) {
                    $product = $item->product;
                    if (!$product) continue;
                    
                    $qty = $item->quantity ?? 0;
                    if ($qty <= 0) continue;
                    
                    // Create inventory issuing item
                    $issuing->items()->create([
                        'job_assign_schedule_id' => $item->job_assign_schedule_id,
                        'room_name' => $item->room_name,
                        'product_id' => $product->id,
                        'quantity_requested' => $qty,
                        'quantity_issued' => $qty,
                        'quantity_received' => 0,
                        'unit_price' => $item->unit_price ?? 0,
                        'total_price' => $item->total_price ?? 0,
                        'notes' => "Room: {$item->room_name}, Product: {$product->name}",
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                    
                    \Log::info("Created Inventory Issuing Item: Product {$product->name} (ID: {$product->id}), Qty: {$qty}, Room: {$item->room_name}");
                }
            }

            return $issuing;
        } catch (\Throwable $e) {
            \Log::error('Failed to create Inventory Issuing from MaterialIssue '.$jobAssignMaterialIssue->materialIssue->issue_number.': '.$e->getMessage());
            throw $e;
        }
    }

    private function validateGroupedSubmitIssueSelection($selectedJobAssignMaterialIssues): array
    {
        $errors = [];

        $selectedGroups = $selectedJobAssignMaterialIssues
            ->map(function ($jobAssignMaterialIssue) {
                $jobSchedule = $jobAssignMaterialIssue->jobAssignSchedule?->jobSchedule;
                if (!$jobSchedule) {
                    return null;
                }

                return [
                    'group_key' => $this->buildSubmitIssueGroupingKey($jobSchedule),
                    'job_schedule_id' => $jobSchedule->id,
                    'job_number' => $jobSchedule->job_number,
                ];
            })
            ->filter()
            ->groupBy('group_key');

        foreach ($selectedGroups as $groupRows) {
            $seedJobId = $groupRows->pluck('job_schedule_id')->first();
            $seedJob = JobSchedule::with('room')->find($seedJobId);

            if (!$seedJob) {
                continue;
            }

            $siblingJobs = $this->resolveSubmitIssueSiblingJobs($seedJob);
            $selectedJobIds = $groupRows->pluck('job_schedule_id')->unique()->values();

            $jobsWithMaterialIssue = JobAssignMaterialIssue::whereHas('jobAssignSchedule', function ($query) use ($siblingJobs) {
                $query->whereIn('job_schedule_id', $siblingJobs->pluck('id'));
            })
            ->pluck('job_assign_schedule_id')
            ->unique();

            $jobScheduleIdsWithMaterialIssue = JobAssignSchedule::whereIn('id', $jobsWithMaterialIssue)
                ->pluck('job_schedule_id')
                ->unique();

            $missingJobs = $siblingJobs->filter(function ($job) use ($jobScheduleIdsWithMaterialIssue) {
                return !$jobScheduleIdsWithMaterialIssue->contains($job->id);
            });

            if ($missingJobs->isNotEmpty()) {
                $errors[] = sprintf(
                    'Job %s belum bisa Submit to Issue karena room berikut belum di-Material Assign: %s.',
                    $seedJob->job_number ?: 'tanpa nomor job',
                    $missingJobs->map(fn ($job) => $this->formatJobRoomLabel($job))->implode(', ')
                );
                continue;
            }

            $unselectedJobs = $siblingJobs->filter(function ($job) use ($selectedJobIds) {
                return !$selectedJobIds->contains($job->id);
            });

            if ($unselectedJobs->isNotEmpty()) {
                $errors[] = sprintf(
                    'Job %s harus di-submit sekaligus untuk semua room. Room yang belum ikut: %s.',
                    $seedJob->job_number ?: 'tanpa nomor job',
                    $unselectedJobs->map(fn ($job) => $this->formatJobRoomLabel($job))->implode(', ')
                );
            }
        }

        return array_values(array_unique($errors));
    }

    private function resolveSubmitIssueSiblingJobs(JobSchedule $jobSchedule)
    {
        $query = JobSchedule::query()
            ->with('room')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled');

        if (!empty($jobSchedule->job_number)) {
            return $query->where('job_number', $jobSchedule->job_number)->get();
        }

        $query->where('job_advice_id', $jobSchedule->job_advice_id)
            ->where('building_id', $jobSchedule->building_id)
            ->where('type', $jobSchedule->type);

        if ($jobSchedule->period !== null) {
            $query->where('period', $jobSchedule->period);
        } else {
            $query->whereNull('period');
        }

        return $query->get();
    }

    private function buildSubmitIssueGroupingKey(JobSchedule $jobSchedule): string
    {
        if (!empty($jobSchedule->job_number)) {
            return 'job-number:' . $jobSchedule->job_number;
        }

        return implode('|', [
            $jobSchedule->job_advice_id ?? 'no-ja',
            $jobSchedule->building_id ?? 'no-building',
            $jobSchedule->type ?? 'no-type',
            $jobSchedule->period ?? 'no-period',
        ]);
    }

    private function formatJobRoomLabel(JobSchedule $jobSchedule): string
    {
        return $jobSchedule->room_name
            ?? $jobSchedule->room?->room_name
            ?? ('Job ID ' . $jobSchedule->id);
    }

    /**
     * Update warehouse stock when material is returned (Berdasarkan BRD)
     */
    private function updateWarehouseStockOnReturn(MaterialIssue $materialIssue)
    {
        try {
            $materialIssue->load('items');
            
            if ($materialIssue->items->isEmpty()) {
                // Fallback for legacy data without items? Or just log warning?
                // Assuming items exist if issued properly
                \Log::warning("Material issue {$materialIssue->issue_number} has no items to return stock.");
                return;
            }

            foreach ($materialIssue->items as $item) {
                if (!$item->product_id || $item->quantity <= 0) continue;

                // Find warehouse product record
                $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $materialIssue->warehouse_id)
                    ->where('master_product_id', $item->product_id)
                    ->first();

                if (!$warehouseProduct) {
                    // Create new warehouse product record if not exists
                    $warehouseProduct = \App\Models\WarehouseProduct::create([
                        'warehouse_id' => $materialIssue->warehouse_id,
                        'master_product_id' => $item->product_id,
                        'quantity' => 0,
                        'minimum_stock' => 0,
                        'maximum_stock' => 1000, 
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                // Update stock quantity (increase)
                $warehouseProduct->increment('quantity', $item->quantity);

                // Create inventory movement record
                $movementData = [
                    'warehouse_id' => $materialIssue->warehouse_id,
                    'master_product_id' => $item->product_id,
                    'movement_type' => 'in', // Return
                    'quantity' => $item->quantity,
                    'notes' => "Material returned/unissued. Issue Number: {$materialIssue->issue_number}",
                    'created_by' => Auth::id(),
                ];
                
                try {
                    $columns = Schema::getColumnListing('inventory_movements');
                    if (in_array('movement_date', $columns)) $movementData['movement_date'] = now()->toDateString();
                    if (in_array('reference_no', $columns)) $movementData['reference_no'] = $materialIssue->issue_number;
                    if (in_array('reference_type', $columns)) $movementData['reference_type'] = 'material_return';
                    if (in_array('reference_id', $columns)) $movementData['reference_id'] = $materialIssue->id;
                    if (in_array('movement_no', $columns)) $movementData['movement_no'] = 'RET-' . ($materialIssue->issue_number ?? date('Ymd') . '-' . $materialIssue->id);
                } catch (\Exception $e) {
                    // Ignore schema check errors
                }
                
                \App\Models\InventoryMovement::create($movementData);
                
                \Log::info("Warehouse stock returned: Product {$item->product_id}, Qty +{$item->quantity}, Warehouse {$materialIssue->warehouse_id}");
            }

        } catch (\Exception $e) {
            \Log::error("Failed to update warehouse stock on material return {$materialIssue->issue_number}: " . $e->getMessage());
            throw $e; 
        }
    }

    /**
     * Approve material issue
     */
    public function approve(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            
            if (!$materialIssue) {
                return response()->json([
                    'status' => 'error',
                    'message' => '❌ Material issue tidak ditemukan.'
                ], 404);
            }
            
            if ($materialIssue->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ℹ️ Material issue sudah di-approve sebelumnya.'
                ], 400);
            }
            
            if ($materialIssue->status === 'issued') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ℹ️ Material issue sudah ter-issue.'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // MOM11: Auto-save product from rental if not assigned yet
            $updateData = [
                'status' => 'approved',
                'approved_by' => Auth::user()->name,
                'approved_date' => now(),
                'updated_by' => Auth::id()
            ];
            
            // If product_id is null, auto-assign from rental
            if (!$materialIssue->product_id) {
                $jobAdvice = $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->jobAdvice ?? null;
                
                $assignedRooms = $this->getAssignedJobAdviceRooms($jobAdvice, $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule ?? null);

                if ($assignedRooms->isNotEmpty()) {
                    foreach ($assignedRooms as $jaRoom) {
                        $rental = $jaRoom->rentalProduct;
                        if (!$rental) continue;
                        
                        // Get quotation for aroma product
                        $quotation = null;
                        if ($jobAdvice->quotation_id) {
                            $quotation = $jobAdvice->quotation;
                        } elseif ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                            $quotation = $jobAdvice->contract->quotation;
                        }
                        
                        $aromaProduct = null;
                        if ($quotation) {
                            $quotationRoom = null;
                            if ($jaRoom->quotation_room_id) {
                                $quotationRoom = $quotation->quotationRooms->where('id', $jaRoom->quotation_room_id)->first();
                            }
                            if ($quotationRoom && $quotationRoom->aromaProduct) {
                                $aromaProduct = $quotationRoom->aromaProduct;
                            }
                        }
                        
                        // Get first component's product
                        $components = $rental->rentalComponents()
                            ->where('is_active', true)
                            ->with(['allowedProducts' => function($query) {
                                $query->wherePivot('is_active', true);
                            }])
                            ->orderBy('sort_order')
                            ->get();
                        
                        foreach ($components as $component) {
                            $product = null;
                            
                            // Check if aroma component.
                            $isAromaComponent = $this->isQuotationAromaComponentName($component->component_name);
                            
                            if ($isAromaComponent && $aromaProduct) {
                                $product = $aromaProduct;
                            } else {
                                $activeAllowedProducts = $component->allowedProducts->where('pivot.is_active', true);
                                $preferredProduct = $activeAllowedProducts->where('pivot.is_preferred', true)->first();
                                
                                if ($preferredProduct) {
                                    $product = $preferredProduct;
                                } else if ($activeAllowedProducts->count() > 0) {
                                    $product = $activeAllowedProducts->first();
                                }
                            }
                            
                            // Assign first product found
                            if ($product) {
                                $updateData['product_id'] = $product->id;
                                \Log::info("Material issue {$materialIssue->issue_number} auto-assigned product_id: {$product->id} ({$product->name}) from rental on approve");
                                break 2; // Break both loops
                            }
                        }
                    }
                }
            }
            
            $materialIssue->update($updateData);
            
            // MOM11: Save material issue items when approving (untuk stock validation saat issue)
            $this->saveMaterialIssueItems($materialIssue, $jobAssignMaterialIssue);
            \Log::info("Material issue items saved during approval for {$materialIssue->issue_number}");

            (new \App\Services\Warehouse\InventoryIssuingService())->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Material issue berhasil di-approve!',
                'data' => $materialIssue
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error approving material issue: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal meng-approve material issue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unapprove material issue (change status from approved back to draft)
     */
    public function unapprove(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            
            if (!$materialIssue) {
                return response()->json([
                    'status' => 'error',
                    'message' => '❌ Material issue tidak ditemukan.'
                ], 404);
            }
            
            if ($materialIssue->status === 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ℹ️ Material issue sudah dalam status draft.'
                ], 400);
            }
            
            if ($materialIssue->status === 'issued') {
                return response()->json([
                    'status' => 'error',
                    'message' => '❌ Material issue sudah ter-issue. Tidak bisa diubah ke draft. Gunakan "Unissue" terlebih dahulu.'
                ], 400);
            }
            
            DB::beginTransaction();
            
            $materialIssue->update([
                'status' => 'draft',
                'approved_by' => null,
                'approved_date' => null,
                'updated_by' => Auth::id()
            ]);
            
            \Log::info("Material issue {$materialIssue->issue_number} status changed from approved to draft by user " . Auth::id());
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Material issue berhasil diubah ke status draft!',
                'data' => $materialIssue
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error unapproving material issue: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah status material issue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOM11: Helper function untuk save material issue items dari job advice rooms
     * Items ini ambil dari quotation (untuk aroma) atau dari rental (untuk non-aroma)
     * MOM12: Jika formData tersedia, gunakan product_id dari form (untuk edit)
     * 
     * @param MaterialIssue $materialIssue
     * @param JobAssignMaterialIssue $jobAssignMaterialIssue
     * @param array|null $formData - Optional form data from edit request (rental_products)
     * @return void
     */
    private function saveMaterialIssueItems(MaterialIssue $materialIssue, JobAssignMaterialIssue $jobAssignMaterialIssue, ?array $formData = null)
    {
        // Delete existing items first
        $materialIssue->items()->delete();
        
        $jobAssignMaterialIssue->load([
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.masterProduct.packagingSize',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.allowedProducts.productType',
            'jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.allowedProducts.productCategory'
        ]);
        
        $jobAdvice = $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule->jobAdvice ?? null;
        if (!$jobAdvice || !$jobAdvice->rooms) {
            \Log::warning("Material issue {$materialIssue->issue_number} has no job advice or rooms. Cannot save items.");
            return;
        }

        $assignedRooms = $this->getAssignedJobAdviceRooms($jobAdvice, $jobAssignMaterialIssue->jobAssignSchedule->jobSchedule ?? null);
        if ($assignedRooms->isEmpty()) {
            \Log::warning("Material issue {$materialIssue->issue_number} has no assigned job advice rooms for this job schedule.");
            return;
        }
        
        // MOM12: Build a map of form data by room_name for quick lookup
        $formDataMap = [];
        if (!empty($formData)) {
            \Log::info("MOM12 DEBUG: Building form data map from " . count($formData) . " items");
            foreach ($formData as $index => $item) {
                $roomName = $item['room_name'] ?? null;
                $componentId = $item['component_id'] ?? null;
                \Log::info("MOM12 DEBUG: Item {$index} - room_name: {$roomName}, component_id: {$componentId}, product_id: " . ($item['product_id'] ?? 'null') . ", quantity: " . ($item['quantity'] ?? 'null'));
                
                if ($roomName && $componentId) {
                    // Key by room_name + component_id to handle multiple components per room
                    $key = $roomName . '_' . $componentId;
                    $formDataMap[$key] = $item;
                    \Log::info("MOM12 DEBUG: Added to map with key: {$key}");
                }
            }
            \Log::info("MOM12 DEBUG: Form data map built with " . count($formDataMap) . " entries", array_keys($formDataMap));
        } else {
            \Log::debug("MOM12 DEBUG: formData is empty or null!");
        }
        
        $totalItems = 0;
        
        foreach ($assignedRooms as $jaRoom) {
            // Get quotation room untuk ambil aroma_product_id yang sudah di-customize
            // MOM11: Ada 3 cara untuk dapat quotation room:
            // 1. Langsung dari job_advice_room.quotation_room_id (jika job dari quotation)
            // 2. Via contract: job_advice.contract_id → contract.quotation_id → match by room_name
            // 3. Fallback: match by room_name saja
            
            $quotationRoom = null;
            
            // Cara 1: Direct dari job_advice_room.quotation_room_id
            if ($jaRoom->quotation_room_id) {
                $quotationRoom = \App\Models\QuotationRoom::find($jaRoom->quotation_room_id);
            }
            
            // Cara 2: Via contract
            if (!$quotationRoom && $jobAdvice->contract_id) {
                $contract = \App\Models\Contract::find($jobAdvice->contract_id);
                if ($contract && $contract->quotation_id) {
                    // Match by room_name
                    $quotationRoom = \App\Models\QuotationRoom::where('quotation_id', $contract->quotation_id)
                        ->where('room_name', $jaRoom->room_name)
                        ->first();
                }
            }
            
            // Cara 3: Fallback - match by room_name dari job_advice.quotation_id
            if (!$quotationRoom && $jobAdvice->quotation_id) {
                $quotationRoom = \App\Models\QuotationRoom::where('quotation_id', $jobAdvice->quotation_id)
                    ->where('room_name', $jaRoom->room_name)
                    ->first();
            }
            
            $rental = $jaRoom->rentalProduct;
            if (!$rental) continue;
            
            // Track if we have substituted aroma for THIS room
            $hasSubstitutedAroma = false;
            
            foreach ($rental->rentalDetails as $detail) {
                $product = $this->resolvePreferredRentalDetailProduct($detail, $rental, $detail->masterProduct);
                if (!$product) continue;

                $qty = $detail->quantity ?? 0;
                
                if ($qty <= 0) continue;
                
                // MOM11: Check if this is aroma type - use quotation's aroma_product_id instead of master rental
                $productTypeName = $detail->productType->name ?? null;
                $productName = $product->name ?? '';
                $categoryName = $product->productCategory->name ?? '';
                
                $isAromaType = $this->isQuotationAromaMaterialSlot($detail, $product, $productTypeName ?: $categoryName ?: $productName);
                
                // MOM12: Check if form data has changes for this room+component
                $formKey = $jaRoom->room_name . '_' . $detail->id;
                \Log::info("MOM12 DEBUG: Looking for key: {$formKey} (room: {$jaRoom->room_name}, detail_id: {$detail->id})");
                // \Log::info("MOM12 DEBUG: Available keys in map: " . implode(', ', array_keys($formDataMap)));
                
                $useFormData = false;
                $userManuallyChangedProduct = false;
                $formQty = $qty;
                $formConvert = $detail->convert ?? 1;
                $formBom = $product->bom_quantity ?? 0;
                
                $keyFound = isset($formDataMap[$formKey]);
                
                if ($keyFound) {
                    $formItem = $formDataMap[$formKey];
                    
                    // Check for product change
                    if (!empty($formItem['product_id'])) {
                        $formProductId = $formItem['product_id'];
                        
                        // Check if user actually changed the product from the master rental default
                        if ($formProductId != $product->id) {
                            $formProduct = \App\Models\MasterProduct::with('productType')->find($formProductId);
                            if ($formProduct) {
                                $isUnit = $formProduct->productType->is_unit ?? false;
                                
                                if (!$isUnit) {
                                    // Non-unit product - allow change
                                    $product = $formProduct;
                                    $useFormData = true;
                                    $userManuallyChangedProduct = true;
                                    \Log::info("MOM12: Using form product: {$product->name} (ID: {$product->id}) for room {$jaRoom->room_name}, component {$detail->id}");
                                } else {
                                    \Log::warning("MOM12: Cannot change unit product. Keeping original: {$product->name}");
                                }
                            }
                        }
                    }
                    
                    // Use quantity from form if available
                    if (isset($formItem['quantity']) && is_numeric($formItem['quantity'])) {
                        $formQty = (int) $formItem['quantity'];
                        $useFormData = true;
                    }
                    
                    // Use convert from form if available
                    if (isset($formItem['convert']) && is_numeric($formItem['convert'])) {
                        $formConvert = (float) $formItem['convert'];
                        $useFormData = true;
                    }
                    
                    // MOM13: BOM quantity is ALWAYS from master product (patokan), not from form input
                    // User cannot override BOM - it must come from master product
                    // (removed the code that allowed form to override bom_quantity)
                }
                
                // Use customized product from quotation if available
                // MOM12 FIX: Allow substitution even if useFormData is true, AS LONG AS the user didn't manually pick a different product
                if (!$userManuallyChangedProduct && $isAromaType && $quotationRoom && $quotationRoom->aroma_product_id) {
                    $quotationProduct = \App\Models\MasterProduct::with(['productType', 'productCategory', 'packagingSize'])
                        ->find($quotationRoom->aroma_product_id);
                    $customProduct = $this->resolveQuotationAromaProductForDetail($detail, $quotationProduct, $product);
                    if ($customProduct) {
                        $product = $customProduct; // Swap to quotation aroma
                        $hasSubstitutedAroma = true;
                        
                        \Log::info("Using quotation aroma product: {$product->name} for room {$jaRoom->room_name} (Overriding rental default)");
                    } elseif ($quotationProduct) {
                        \Log::warning("Quotation aroma product could not be matched to an allowed refill product for room {$jaRoom->room_name}", [
                            'quotation_product_id' => $quotationProduct->id,
                            'quotation_product_name' => $quotationProduct->name,
                            'variant_name' => $quotationProduct->variant_name,
                            'rental_detail_id' => $detail->id,
                        ]);
                    }
                }
                
                // Use form values if available, otherwise use original
                $finalQty = $useFormData && $formQty > 0 ? $formQty : $qty;
                $finalConvert = $useFormData ? $formConvert : ($detail->convert ?? 1);
                // MOM13: BOM ALWAYS from current product (master product), not from saved values or form input
                $finalBom = $product->bom_quantity ?? 0;
                
                // Create material issue item
                $materialIssue->items()->create([
                    'job_assign_schedule_id' => $jobAssignMaterialIssue->job_assign_schedule_id, // MOM34 Fix: Fill assignment ID for mobile sync
                    'product_id' => $product->id,
                    'room_name' => $jaRoom->room_name,
                    'quantity' => $finalQty,
                    'convert' => $finalConvert,
                    'bom_quantity' => $finalBom,
                    'unit_price' => $product->last_unit_price ?? 0,
                    'total_price' => ($product->last_unit_price ?? 0) * $finalQty,
                    'notes' => "Room: {$jaRoom->room_name}, Rental: {$rental->rental_name}, ComponentID: {$detail->id}",
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                
                $totalItems += $finalQty;
            }

            // Do not append quotation aroma if the selected rental detail has no aroma/refill slot.
            // Material Assign must mirror Master Rental Details exactly.
        }
        
        // Update material issue quantity to total items
        $materialIssue->update([
            'quantity' => $totalItems,
            'updated_by' => auth()->id(),
        ]);
        
        \Log::info("Material issue {$materialIssue->issue_number} items saved. Total items: {$totalItems}");
    }

    /**
     * Get products by variant name
     * Auto-populate products in Material Issue based on room's variant
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductsByVariant(Request $request)
    {
        try {
            $variantName = $request->input('variant_name');
            $packagingSizeId = $request->input('packaging_size_id');
            
            if (!$variantName) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant name is required'
                ], 422);
            }
            
            // Find products matching the variant
            $query = MasterProduct::where('variant_name', $variantName)
                ->where('is_active', true)
                ->with(['productType', 'packagingSize']);
            
            // Filter by packaging size if provided
            if ($packagingSizeId) {
                $query->where('packaging_size_id', $packagingSizeId);
            }
            
            $products = $query->orderBy('name')->get();
            
            // Get available packaging sizes for this variant
            $packagingSizes = MasterProduct::where('variant_name', $variantName)
                ->where('is_active', true)
                ->whereNotNull('packaging_size_id')
                ->with('packagingSize')
                ->get()
                ->pluck('packagingSize')
                ->filter()
                ->unique('id')
                ->values();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'variant_name' => $variantName,
                    'products' => $products->map(function($p) {
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'sku' => $p->sku,
                            'variant_name' => $p->variant_name,
                            'brand_line' => $p->brand_line,
                            'packaging_size' => $p->packagingSize ? $p->packagingSize->name : null,
                            'packaging_size_id' => $p->packaging_size_id,
                            'product_type' => $p->productType ? $p->productType->name : null,
                            'unit' => $p->unit,
                            'last_unit_price' => $p->last_unit_price ?? 0,
                        ];
                    }),
                    'packaging_sizes' => $packagingSizes,
                    'total_products' => $products->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get products by variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request variant change for a room in Material Issue
     * Staff can request, requires Manager+ approval if variant is different
     * 
     * @param Request $request
     * @param JobAssignMaterialIssue $jobAssignMaterialIssue
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestVariantChange(Request $request, JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            // Check permission
            $user = Auth::user();
            if (!$user->hasPermission('material-issues.variant.change')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to request variant change.'
                ], 403);
            }
            
            $request->validate([
                'room_name' => 'required|string',
                'current_variant' => 'nullable|string',
                'new_variant' => 'required|string',
                'change_reason' => 'nullable|string|max:1000',
            ]);
            
            $currentVariant = $request->input('current_variant');
            $newVariant = $request->input('new_variant');
            $roomName = $request->input('room_name');
            
            DB::beginTransaction();
            
            // Check if variant is same (auto-approve) or different (needs manager approval)
            $needsApproval = strtolower(trim($currentVariant ?? '')) !== strtolower(trim($newVariant));
            
            // Determine initial status
            $status = $needsApproval ? 'pending_approval' : 'approved';
            
            // If auto-approved (same variant), user can self-approve
            $approvedBy = !$needsApproval ? $user->id : null;
            $approvedAt = !$needsApproval ? now() : null;
            
            // Store variant change request in material_issue metadata
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            
            // Debug logging
            \Log::info('Variant Change Request Debug', [
                'job_assign_material_issue_id' => $jobAssignMaterialIssue->id,
                'material_issue_id' => $jobAssignMaterialIssue->material_issue_id,
                'material_issue_exists' => $materialIssue ? true : false,
                'room_name' => $roomName,
                'new_variant' => $newVariant,
            ]);
            
            // Check if materialIssue exists
            if (!$materialIssue) {
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material Issue not found for this Job Assign. Please ensure Material Issue is created first.'
                ], 404);
            }
            
            // Get existing metadata or create new
            $metadata = $materialIssue->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            }
            
            // Add variant change request
            $variantChangeRequest = [
                'id' => uniqid('vcr_'),
                'room_name' => $roomName,
                'current_variant' => $currentVariant,
                'new_variant' => $newVariant,
                'change_reason' => $request->input('change_reason'),
                'status' => $status,
                'needs_approval' => $needsApproval,
                'requested_by' => $user->id,
                'requested_by_name' => $user->name,
                'requested_at' => now()->toIso8601String(),
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt ? $approvedAt->toIso8601String() : null,
            ];
            
            // Add to variant_change_requests array
            $metadata['variant_change_requests'] = $metadata['variant_change_requests'] ?? [];
            $metadata['variant_change_requests'][] = $variantChangeRequest;
            
            $materialIssue->update([
                'metadata' => $metadata,
                'updated_by' => $user->id,
            ]);
            
            // If auto-approved, apply the change immediately
            if (!$needsApproval) {
                $this->applyVariantChange($materialIssue, $roomName, $newVariant);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => $needsApproval 
                    ? 'Variant change request submitted for approval.' 
                    : 'Variant change applied successfully (same variant - auto-approved).',
                'data' => [
                    'request_id' => $variantChangeRequest['id'],
                    'needs_approval' => $needsApproval,
                    'status' => $status,
                    'current_variant' => $currentVariant,
                    'new_variant' => $newVariant,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Variant change request failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit variant change request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve variant change (Manager+ only)
     * 
     * @param Request $request
     * @param JobAssignMaterialIssue $jobAssignMaterialIssue
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveVariantChange(Request $request, JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            // Check permission - only managers and above can approve
            $user = Auth::user();
            
            // Get role name correctly (User model has roles() relationship, not role())
            $roleName = $user->getEffectiveRole() ?? ($user->roles->first()->name ?? ($user->role_name ?? 'NONE'));
            
            // Debug: Log user role info
            \Log::info('Variant Approval Permission Check', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_name' => $roleName,
                'has_permission' => $user->hasPermission('material-issues.variant.approve'),
            ]);
            
            // Multi-level Approval logic (Check for permission operational.material-issues.variant.approve)
        if (!$user->hasPermission('operational.material-issues.variant.approve') && $user->id > 5) {
            // Check if user is the assigned supervisor of one of the related jobs
            $supervisorCheck = $jobAssignMaterialIssue->jobSchedule()
                ->where('supervisor_id', $user->id)
                ->exists();

            if (!$supervisorCheck) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Only management and assigned supervisors can approve variant changes.'
                ], 403);
            }
        }
    
            $request->validate([
                'request_id' => 'required|string',
                'action' => 'required|in:approve,reject',
                'approval_notes' => 'nullable|string|max:1000',
            ]);
            
            $requestId = $request->input('request_id');
            $action = $request->input('action');
            
            DB::beginTransaction();
            
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            $metadata = $materialIssue->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            }
            
            // Find and update the variant change request
            $variantChangeRequests = $metadata['variant_change_requests'] ?? [];
            $found = false;
            $variantChangeRequest = null;
            
            foreach ($variantChangeRequests as $index => $vcr) {
                if ($vcr['id'] === $requestId) {
                    $found = true;
                    $variantChangeRequest = $vcr;
                    
                    // Update status
                    $variantChangeRequests[$index]['status'] = $action === 'approve' ? 'approved' : 'rejected';
                    $variantChangeRequests[$index]['approved_by'] = $user->id;
                    $variantChangeRequests[$index]['approved_by_name'] = $user->name;
                    $variantChangeRequests[$index]['approved_at'] = now()->toIso8601String();
                    $variantChangeRequests[$index]['approval_notes'] = $request->input('approval_notes');
                    
                    break;
                }
            }
            
            if (!$found) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant change request not found.'
                ], 404);
            }
            
            $metadata['variant_change_requests'] = $variantChangeRequests;
            
            $materialIssue->update([
                'metadata' => $metadata,
                'updated_by' => $user->id,
            ]);
            
            // If approved, apply the change
            if ($action === 'approve') {
                $this->applyVariantChange(
                    $materialIssue, 
                    $variantChangeRequest['room_name'], 
                    $variantChangeRequest['new_variant']
                );
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => $action === 'approve' 
                    ? 'Variant change approved and applied successfully.' 
                    : 'Variant change rejected.',
                'data' => [
                    'request_id' => $requestId,
                    'action' => $action,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Variant change approval failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process variant change: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply variant change to material issue items
     * Updates products for the specified room to match the new variant
     * 
     * @param MaterialIssue $materialIssue
     * @param string $roomName
     * @param string $newVariant
     */
    private function applyVariantChange(MaterialIssue $materialIssue, string $roomName, string $newVariant)
    {
        // Get material issue items for this room
        $items = $materialIssue->items()->where('room_name', $roomName)->get();
        
        foreach ($items as $item) {
            $currentProduct = $item->product;
            if (!$currentProduct) continue;
            
            // Find replacement product with same product_category/product_type but new variant
            $replacementProduct = MasterProduct::where('variant_name', $newVariant)
                ->where(function($q) use ($currentProduct) {
                    if ($currentProduct->product_category_id) {
                        $q->where('product_category_id', $currentProduct->product_category_id);
                    } else {
                        $q->where('product_type_id', $currentProduct->product_type_id);
                    }
                })
                ->where('is_active', true)
                // Prefer same packaging size
                ->orderByRaw("CASE WHEN packaging_size_id = ? THEN 0 ELSE 1 END", [$currentProduct->packaging_size_id])
                ->first();
            
            if ($replacementProduct) {
                $item->update([
                    'product_id' => $replacementProduct->id,
                    'unit_price' => $replacementProduct->last_unit_price ?? 0,
                    'total_price' => ($replacementProduct->last_unit_price ?? 0) * $item->quantity,
                    'notes' => $item->notes . " | Variant changed to: {$newVariant}",
                    'updated_by' => auth()->id(),
                ]);
                
                \Log::info("Variant change applied: Room {$roomName}, Product {$currentProduct->name} -> {$replacementProduct->name}");
            } else {
                $productCategoryId = $currentProduct->product_category_id ?? $currentProduct->product_type_id;
                \Log::warning("No replacement product found for variant {$newVariant} with category/type ID {$productCategoryId}");
            }
        }
    }

    /**
     * Get pending variant change requests for a material issue
     * 
     * @param JobAssignMaterialIssue $jobAssignMaterialIssue
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingVariantChanges(JobAssignMaterialIssue $jobAssignMaterialIssue)
    {
        try {
            $materialIssue = $jobAssignMaterialIssue->materialIssue;
            $metadata = $materialIssue->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            }
            
            $variantChangeRequests = $metadata['variant_change_requests'] ?? [];
            
            // Filter pending requests
            $pendingRequests = array_filter($variantChangeRequests, function($vcr) {
                return $vcr['status'] === 'pending_approval';
            });
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'pending_requests' => array_values($pendingRequests),
                    'total_pending' => count($pendingRequests),
                    'all_requests' => $variantChangeRequests,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get pending variant changes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available brand lines and variants from Master Options
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrandLinesAndVariants()
    {
        try {
            // Get Brand Lines master option (ID 42)
            $brandLinesOption = MasterOption::where('name', 'Brand Lines')
                ->where('is_active', true)
                ->with(['optionDetails' => function($q) {
                    $q->where('is_active', true)->orderBy('option_name');
                }])
                ->first();
            
            // Get Variants master option (ID 43)
            $variantsOption = MasterOption::where('name', 'Product Variants')
                ->where('is_active', true)
                ->with(['optionDetails' => function($q) {
                    $q->where('is_active', true)
                      ->with('parentOption')
                      ->orderBy('option_name');
                }])
                ->first();
            
            $brandLines = [];
            if ($brandLinesOption && $brandLinesOption->optionDetails) {
                $brandLines = $brandLinesOption->optionDetails->map(function($detail) {
                    return [
                        'id' => $detail->id,
                        'value' => $detail->option_name,
                        'code' => $detail->code,
                        'children' => [], // Will be populated if variants have parent_option_id
                    ];
                })->unique('value')->values();
            }
            
            $variants = [];
            if ($variantsOption && $variantsOption->optionDetails) {
                $variants = $variantsOption->optionDetails->map(function($detail) {
                    return [
                        'id' => $detail->id,
                        'value' => $detail->option_name,
                        'code' => $detail->code,
                        'brand_line_id' => $detail->parent_option_id,
                        'brand_line_name' => $detail->parentOption ? $detail->parentOption->option_name : null,
                    ];
                })->unique('value')->values();
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'brand_lines' => $brandLines,
                    'variants' => $variants,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get brand lines and variants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy Material Item for package conversion
     * User can split a material into smaller packages
     */
    public function copyMaterial(Request $request)
    {
        try {
            $request->validate([
                'item_id' => 'required|exists:material_issue_items,id'
            ]);
            
            $originalItem = \App\Models\MaterialIssueItem::with('product')->findOrFail($request->item_id);
            $materialIssue = $originalItem->materialIssue;
            
            if (!$materialIssue) {
                return response()->json(['status' => 'error', 'message' => 'Material issue not found'], 404);
            }

            // 1. Extract RentalDetailID or match by Name/Type (Fallback)
            $rentalDetailId = null;
            if ($originalItem->notes && preg_match('/(?:RentalDetailID|ComponentID):\s*(\d+)/', $originalItem->notes, $matches)) {
                $rentalDetailId = $matches[1];
            }

            // Fallback matching if ID not in notes
            if (!$rentalDetailId && $originalItem->product) {
                $rentalName = '-';
                if ($originalItem->notes && preg_match('/Rental:\s*([^,]+)/', $originalItem->notes, $innerMatches)) {
                    $rentalName = trim($innerMatches[1]);
                }

                if ($rentalName !== '-') {
                    $masterRental = \App\Models\MasterRental::where('rental_name', $rentalName)->first();
                    if ($masterRental) {
                        $rentalDetailId = \App\Models\RentalDetail::where('master_rental_id', $masterRental->id)
                            ->where(function($q) use ($originalItem) {
                                if ($originalItem->product->product_category_id) {
                                    $q->where('product_category_id', $originalItem->product->product_category_id);
                                } else {
                                    $q->where('product_type_id', $originalItem->product->product_type_id);
                                }
                            })
                            ->value('id');
                    }
                }
            }

            // 2. Calculate remaining target if target is known
            $remainingTarget = 0;
            $hasTarget = false;
            
            if ($rentalDetailId) {
                $rentalDetail = \App\Models\RentalDetail::find($rentalDetailId);
                if ($rentalDetail && $rentalDetail->bom_rental_qty > 0) {
                    $hasTarget = true;
                    $targetBom = $rentalDetail->bom_rental_qty;
                    
                    // Sum up currently issued volume for this group
                    // We match by RentalDetailID in notes
                    $issuedVolume = \App\Models\MaterialIssueItem::where('material_issue_id', $materialIssue->id)
                        ->where(function($q) use ($rentalDetailId) {
                            $q->where('notes', 'like', "%RentalDetailID: {$rentalDetailId}%")
                              ->orWhere('notes', 'like', "%ComponentID: {$rentalDetailId}%");
                        })
                        ->get()
                        ->sum(function($item) {
                            return $this->calculateBomQuantity($item->product, $item->quantity ?? 0);
                        });
                    
                    $remainingTarget = max(0, $targetBom - $issuedVolume);
                }
            }

            // 3. Find candidate products (same variant but different package)
            $suggestedProduct = $originalItem->product;
            $suggestedQty = 0;
            
            if ($originalItem->product && $hasTarget && $remainingTarget > 0) {
                $variantName = $originalItem->product->variant_name;
                
                // Find all active products with same variant
                $candidates = \App\Models\MasterProduct::where('variant_name', $variantName)
                    ->where('is_active', true)
                    ->orderBy('bom_quantity', 'desc') // Start from largest package
                    ->get();
                
                foreach ($candidates as $candidate) {
                    $bomPerUnit = $candidate->bom_quantity ?? 0;
                    if ($bomPerUnit <= 0) continue;
                    
                    // Check stock for this candidate in same warehouse
                    $stock = \App\Models\WarehouseProduct::where('warehouse_id', $materialIssue->warehouse_id)
                        ->where('master_product_id', $candidate->id)
                        ->value('quantity') ?? 0;
                    
                    if ($stock <= 0) continue;

                    // Can we suggest at least 1 unit?
                    if ($bomPerUnit <= $remainingTarget) {
                        $suggestedProduct = $candidate;
                        $suggestedQty = floor($remainingTarget / $bomPerUnit);
                        $suggestedQty = min($suggestedQty, $stock); // Constrain by stock
                        break; 
                    } else {
                        // If all packages are too big, maybe suggest 1 unit of the smallest available?
                        // Or just let user choose. Let's pick the smallest if we haven't found anything yet.
                        $suggestedProduct = $candidate; // This will be the smallest due to descending order if we go to the end
                    }
                }
            }

            // 4. Replicate and set values
            $copiedItem = $originalItem->replicate();
            $copiedItem->product_id = $suggestedProduct->id;
            $copiedItem->quantity = $suggestedQty; 
            $copiedItem->is_copied = true;
            $copiedItem->notes = $originalItem->notes ? $originalItem->notes . ' [Copied]' : '[Copied]';
            $copiedItem->unit_price = $suggestedProduct->last_unit_price ?? 0;
            $copiedItem->total_price = $copiedItem->quantity * $copiedItem->unit_price;
            $copiedItem->created_by = auth()->id();
            $copiedItem->updated_by = auth()->id();
            $copiedItem->save();
            
            \Log::info("Material item copied with automation: Original ID {$originalItem->id} → Copied ID {$copiedItem->id}, Product: {$suggestedProduct->name}, Qty: {$suggestedQty}");
            
            return response()->json([
                'status' => 'success',
                'message' => 'Material copied and automated. Suggesting ' . $suggestedProduct->name . ' x' . $suggestedQty,
                'data' => $copiedItem
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to copy material item: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to copy material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Copied Material Item
     * Only copied items can be deleted
     */
    public function deleteCopiedMaterial($itemId)
    {
        try {
            $item = \App\Models\MaterialIssueItem::findOrFail($itemId);
            
            // Only allow deletion of copied items
            if (!$item->is_copied) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can only delete copied materials. Original materials cannot be deleted.'
                ], 403);
            }
            
            $item->delete();
            
            \Log::info("Copied material item deleted: ID {$itemId}");
            
            return response()->json([
                'status' => 'success',
                'message' => 'Copied material deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to delete copied material: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to calculate BOM quantity robustly
     */
    private function calculateBomQuantity($product, $quantity)
    {
        if (!$product || $quantity <= 0) return 0;

        // Priority 1: Try to parse from Packaging Size (e.g., "250ml" -> 250)
        // This fixes the issue where database bom_quantity might be 1 but it should be 250
        if ($product->packagingSize) {
            preg_match('/(\d+)\s*ml/i', $product->packagingSize->name, $matches);
            if (isset($matches[1])) {
                $ml = (float)$matches[1];
                if ($ml > 0) {
                    return $quantity * $ml;
                }
            }
        }

        // Priority 2: Fallback to database bom_quantity
        if ($product->bom_quantity) {
            return $quantity * $product->bom_quantity;
        }

        return 0; // Default if nothing found
    }

    /**
     * Update quantity for material issue item with autosave
     */
    public function updateQtyIssue(Request $request, $itemId)
    {
        try {
            $request->validate([
                'quantity' => 'required|numeric|min:0'
            ]);

            $item = \App\Models\MaterialIssueItem::findOrFail($itemId);
            $item->quantity = $request->quantity;
            $item->updated_by = auth()->id();
            $item->save();

            // Calculate BOM quantity
            // Load product with packaging size for robust calculation
            $item->load('product.packagingSize');
            $bomQty = $this->calculateBomQuantity($item->product, $request->quantity);
            $materialIssue = $item->materialIssue()->first();
            $warehouseId = $materialIssue?->warehouse_id;
            $stock = 0;

            if ($warehouseId && $item->product_id) {
                $stock = \App\Models\WarehouseProduct::where('warehouse_id', $warehouseId)
                    ->where('master_product_id', $item->product_id)
                    ->value('quantity') ?? 0;
            }

            $status = $materialIssue?->status ?? 'pending';
            $displayStatus = ucfirst(str_replace('_', ' ', $status));
            $statusClass = 'status-pending';

            if ($status === 'approved') {
                $statusClass = 'status-approved';
            } elseif ($status === 'issued') {
                $statusClass = 'status-issued';
            } elseif ($status === 'rejected') {
                $statusClass = 'status-rejected';
            } elseif ($status === 'received') {
                $statusClass = 'status-received';
            } elseif ($status === 'returned') {
                $statusClass = 'status-returned';
            } elseif ($status === 'lost') {
                $statusClass = 'status-lost';
            }

            if (in_array($status, ['pending', 'draft', 'out_of_stock', 'out of stock'], true)) {
                if ((float) $request->quantity > 0 && (float) $stock < (float) $request->quantity) {
                    $statusClass = 'status-rejected';
                    $displayStatus = 'OUT OF STOCK';
                } else {
                    $statusClass = 'status-pending';
                    $displayStatus = 'Pending';
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Quantity updated successfully',
                'bom_qty' => $bomQty,
                'stock' => $stock,
                'material_issue_status' => $status,
                'display_status' => $displayStatus,
                'status_class' => $statusClass,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating qty issue: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update quantity'
            ], 500);
        }
    }

    /**
     * Update material/product for copied material issue item
     */
    public function updateMaterial(Request $request, $itemId)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:master_products,id'
            ]);

            $item = \App\Models\MaterialIssueItem::with('product')->findOrFail($itemId);
            $newProduct = MasterProduct::findOrFail($request->product_id);

            if ((int) $request->product_id !== (int) $item->product_id && !$this->productsHaveSameBrandLine($item->product, $newProduct)) {
                return response()->json([
                    'status' => 'error',
                    'message' => sprintf(
                        'Brand Line tidak boleh berubah dari %s ke %s.',
                        $item->product?->brand_line ?: '-',
                        $newProduct->brand_line ?: '-'
                    )
                ], 422);
            }

            $rentalDetailId = $this->extractSavedItemComponentId($item->notes);
            if ($rentalDetailId && (int) $request->product_id !== (int) $item->product_id) {
                $isAllowedProduct = DB::table('rental_detail_materials')
                    ->where('rental_detail_id', $rentalDetailId)
                    ->where('master_product_id', $request->product_id)
                    ->where('is_selected', true)
                    ->exists();

                if (!$isAllowedProduct) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Material ini tidak termasuk daftar material yang dipilih untuk detail rental tersebut.'
                    ], 422);
                }
            }
            
            // Allow changing material if:
            // 1. Item is a copied item (always allowed)
            // 2. OR Material Issue status is 'pending', 'approved', or 'out_of_stock' (MOM11: Allow editing approved/out_of_stock items)
            // Strictly block 'issued', 'received', 'sent'
            $materialIssue = $item->materialIssue;
            $canEdit = $item->is_copied || ($materialIssue && !in_array($materialIssue->status, ['issued', 'received', 'sent']));
            
            if (!$canEdit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot change material for items that are already Issued or Received.'
                ], 403);
            }

            $item->product_id = $request->product_id;
            $item->updated_by = auth()->id();
            $item->save();

            // MOM11: If material issue was approved or out_of_stock, revert to 'pending' because material changed
            if ($materialIssue && in_array($materialIssue->status, ['approved', 'out_of_stock'])) {
                $oldStatus = $materialIssue->status;
                $materialIssue->update([
                    'status' => 'pending',
                    'updated_by' => auth()->id()
                ]);
                \Log::info("Material Issue {$materialIssue->issue_number} status reverted from '{$oldStatus}' to 'pending' due to material change.");
            }

            // Get new product details for display
            $product = \App\Models\MasterProduct::with('productType', 'packagingSize')->find($request->product_id);
            
            // Get stock for new product in the same warehouse
            $warehouseId = $item->materialIssue->warehouse_id ?? null;
            $stock = 0;
            if ($warehouseId) {
                $stock = \App\Models\WarehouseProduct::where('warehouse_id', $warehouseId)
                    ->where('master_product_id', $request->product_id)
                    ->value('quantity') ?? 0;
            }

            // Calculate BOM quantity with new product using robust helper
            $bomQty = $this->calculateBomQuantity($product, $item->quantity);
            
            // Calculate bom_per_unit using same logic for consistency
            $bomPerUnit = $this->calculateBomQuantity($product, 1);
            $freshMaterialIssue = $item->materialIssue()->first();
            $status = $freshMaterialIssue?->status ?? 'pending';
            $displayStatus = ucfirst(str_replace('_', ' ', $status));
            $statusClass = 'status-pending';

            if ($status === 'approved') {
                $statusClass = 'status-approved';
            } elseif ($status === 'issued') {
                $statusClass = 'status-issued';
            } elseif ($status === 'rejected') {
                $statusClass = 'status-rejected';
            } elseif ($status === 'received') {
                $statusClass = 'status-received';
            } elseif ($status === 'returned') {
                $statusClass = 'status-returned';
            } elseif ($status === 'lost') {
                $statusClass = 'status-lost';
            } elseif (in_array($status, ['out_of_stock', 'out of stock'], true)) {
                $statusClass = 'status-rejected';
                $displayStatus = 'OUT OF STOCK';
            }

            if (in_array($status, ['pending', 'draft', 'out_of_stock', 'out of stock'], true)) {
                if ((float) $item->quantity > 0 && (float) $stock < (float) $item->quantity) {
                    $statusClass = 'status-rejected';
                    $displayStatus = 'OUT OF STOCK';
                } elseif (in_array($status, ['out_of_stock', 'out of stock'], true)) {
                    $statusClass = 'status-pending';
                    $displayStatus = 'Pending';
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Material updated successfully' . ($freshMaterialIssue && $freshMaterialIssue->status === 'pending' ? ' (Status reverted to Pending)' : ''),
                'product_name' => $product ? $product->name : '',
                'product_type' => $product && $product->productType ? $product->productType->name : '',
                'bom_qty' => $bomQty,
                'bom_per_unit' => $bomPerUnit,
                'stock' => $stock,
                'material_issue_status' => $status,
                'display_status' => $displayStatus,
                'status_class' => $statusClass,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating material: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update material: ' . $e->getMessage()
            ], 500);
        }
    }
}

