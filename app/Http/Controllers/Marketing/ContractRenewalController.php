<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContractRenewal;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Survey;
use App\Models\UnitOnWall;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContractRenewalController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractRenewal::with([
            'contract.customer',
            'customer',
            'newContract',
            'initiatedBy',
            'customerApprovedBy',
            'internalApprovedBy'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('renewal_number', 'like', "%{$search}%")
                    ->orWhereHas('contract', function ($q2) use ($search) {
                        $q2->where('contract_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('contract_status', $request->status);
        }

        if ($request->filled('auto_renewal')) {
            $query->where('auto_renewal', $request->auto_renewal);
        }

        if ($request->filled('expiring_in_days')) {
            $query->expiringIn($request->expiring_in_days);
        }

        $renewals = $query->orderBy('created_at', 'desc')->paginateStd(25);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'data' => $renewals]);
        }

        return view('marketing.contract-renewals.index', compact('renewals'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'renewal_duration_months' => 'required|integer|min:1|max:60',
            'proposed_start_date' => 'required|date',
            'same_terms' => 'boolean',
            'price_adjustment' => 'boolean',
            'price_adjustment_percentage' => 'nullable|numeric|min:-100|max:100',
            'price_adjustment_reason' => 'nullable|string|max:1000',
            'renewal_notes' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $contract = Contract::findOrFail($request->contract_id);
            $blockReason = $contract->getRenewalBlockReason();

            if ($blockReason) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => $blockReason,
                ], 422);
            }

            $proposedEndDate = \Carbon\Carbon::parse($request->proposed_start_date)
                                ->addMonths($request->renewal_duration_months);

            $renewal = ContractRenewal::create([
                'renewal_number' => ContractRenewal::generateRenewalNumber(),
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id,
                'current_end_date' => $contract->end_date,
                'proposed_start_date' => $request->proposed_start_date,
                'proposed_end_date' => $proposedEndDate,
                'renewal_duration_months' => $request->renewal_duration_months,
                'same_terms' => $request->same_terms ?? true,
                'previous_total_value' => $contract->total_value,
                'price_adjustment' => $request->price_adjustment ?? false,
                'price_adjustment_percentage' => $request->price_adjustment_percentage,
                'price_adjustment_reason' => $request->price_adjustment_reason,
                'renewal_notes' => $request->renewal_notes,
                'status' => ContractRenewal::STATUS_DRAFT,
                'initiated_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            // Calculate new total value if price adjustment
            if ($renewal->price_adjustment) {
                $newValue = $renewal->calculateNewTotalValue();
                $renewal->update(['new_total_value' => $newValue]);
            }

            DB::commit();

            Log::info("Contract Renewal created: {$renewal->renewal_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Contract renewal created successfully',
                'data' => $renewal->load(['contract.customer', 'customer', 'initiatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error creating contract renewal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create contract renewal: ' . $e->getMessage()], 500);
        }
    }

    public function show(ContractRenewal $contractRenewal)
    {
        $contractRenewal->load([
            'contract.customer',
            'customer',
            'newContract',
            'initiatedBy',
            'customerApprovedBy',
            'internalApprovedBy'
        ]);

        return response()->json(['status' => 'success', 'data' => $contractRenewal]);
    }

    public function submitToCustomer(ContractRenewal $contractRenewal)
    {
        if (!$contractRenewal->isDraft) {
            return response()->json(['status' => 'error', 'message' => 'Renewal must be in draft status'], 403);
        }

        try {
            $contractRenewal->submitToCustomer();
            Log::info("Contract Renewal submitted to customer: {$contractRenewal->renewal_number}");
            return response()->json(['status' => 'success', 'message' => 'Renewal submitted to customer']);
        } catch (\Exception $e) {
            Log::error("Error submitting renewal to customer: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to submit: ' . $e->getMessage()], 500);
        }
    }

    public function customerApprove(ContractRenewal $contractRenewal)
    {
        if (!$contractRenewal->isPendingCustomer) {
            return response()->json(['status' => 'error', 'message' => 'Renewal must be pending customer approval'], 403);
        }

        try {
            $contractRenewal->customerApprove(Auth::id());
            Log::info("Contract Renewal customer approved: {$contractRenewal->renewal_number}");
            return response()->json(['status' => 'success', 'message' => 'Renewal approved by customer']);
        } catch (\Exception $e) {
            Log::error("Error customer approving renewal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to approve: ' . $e->getMessage()], 500);
        }
    }

    public function internalApprove(Request $request, ContractRenewal $contractRenewal)
    {
        if (!$contractRenewal->isPendingInternal && !$contractRenewal->isCustomerApproved) {
            return response()->json(['status' => 'error', 'message' => 'Invalid status for internal approval'], 403);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $contractRenewal->internalApprove(Auth::id(), $request->approval_notes);
            Log::info("Contract Renewal internal approved: {$contractRenewal->renewal_number}");
            return response()->json(['status' => 'success', 'message' => 'Renewal approved internally']);
        } catch (\Exception $e) {
            Log::error("Error internal approving renewal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to approve: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, ContractRenewal $contractRenewal)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $contractRenewal->reject(Auth::id(), $request->rejection_reason);
            Log::info("Contract Renewal rejected: {$contractRenewal->renewal_number}");
            return response()->json(['status' => 'success', 'message' => 'Renewal rejected']);
        } catch (\Exception $e) {
            Log::error("Error rejecting renewal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to reject: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(ContractRenewal $contractRenewal)
    {
        if ($contractRenewal->isCompleted) {
            return response()->json(['status' => 'error', 'message' => 'Cannot cancel completed renewal'], 403);
        }

        try {
            $contractRenewal->cancel();
            Log::info("Contract Renewal cancelled: {$contractRenewal->renewal_number}");
            return response()->json(['status' => 'success', 'message' => 'Renewal cancelled']);
        } catch (\Exception $e) {
            Log::error("Error cancelling renewal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to cancel: ' . $e->getMessage()], 500);
        }
    }

    public function sendReminder(ContractRenewal $contractRenewal)
    {
        try {
            $contractRenewal->sendReminder();
            Log::info("Reminder sent for Contract Renewal: {$contractRenewal->renewal_number}");
            return response()->json(['status' => 'success', 'message' => 'Reminder sent successfully']);
        } catch (\Exception $e) {
            Log::error("Error sending reminder: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to send reminder: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Auto-create renewals for expiring contracts (can be called via schedule/command)
     */
    public function autoCreate(Request $request)
    {
        try {
            $renewals = ContractRenewal::autoCreateForExpiringContracts();
            Log::info("Auto-created " . count($renewals) . " contract renewals");

            return response()->json([
                'status' => 'success',
                'message' => count($renewals) . ' renewals created',
                'data' => $renewals
            ]);
        } catch (\Exception $e) {
            Log::error("Error auto-creating renewals: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to auto-create renewals: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get active contracts eligible for renewal (for quotation wizard)
     */
    /**
     * Get active contracts eligible for renewal (for quotation wizard)
     */
    public function getEligibleContracts(Request $request)
    {
        try {
            $query = Contract::query()
                ->orderBy('end_date', 'asc') // Sort by expiry date (soonest first)
                ->with(['customer', 'contractRooms.room', 'contractRooms.billingGroup'])
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')
                        ->orWhereRaw('LOWER(TRIM(status)) != ?', ['terminated']);
                })
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('contract_status')
                        ->orWhereRaw('LOWER(TRIM(contract_status)) != ?', ['terminated']);
                });

            // Optional: Filter by customer
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('branch_id')) {
                $branchId = $request->integer('branch_id');
                $query->whereHas('quotation', fn ($quotationQuery) => $quotationQuery->where('branch_id', $branchId));
            }

            // Include specific contract ID if provided (for edit mode)
            $includeId = $request->input('include_id');

            $contracts = $query->get()
                ->values()
                ->filter(function ($contract) use ($includeId) {
                    if ($includeId && $contract->id == $includeId) {
                        return true;
                    }

                    return $contract->getRenewalAlreadyInProgressBlockReason() === null;
                })
                ->values()
                ->map(function ($contract) use ($includeId) {
                    $blockReason = $contract->getRenewalBlockReason();
                // Calculate remaining duration string
                $endDate = \Carbon\Carbon::parse($contract->end_date);
                $now = now();
                
                // Calculate difference
                $diff = $now->diff($endDate);
                
                $parts = [];
                if ($diff->y > 0) $parts[] = $diff->y . ' tahun';
                if ($diff->m > 0) $parts[] = $diff->m . ' bulan';
                if ($diff->d > 0) $parts[] = $diff->d . ' hari';
                
                // If expire today/tomorrow
                if (empty($parts)) {
                    $remainingDuration = 'hari ini';
                } else {
                    $remainingDuration = implode(' ', $parts);
                }

                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_id' => $contract->customer_id,
                    'customer_name' => $contract->customer->name ?? '',
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'contract_value' => $contract->contract_value,
                    // Pass formatted string for frontend
                    'remaining_duration' => 'sisa masa kontrak ' . $remainingDuration,
                    'contract_rooms_count' => $contract->contractRooms->count(),
                    'contract_rooms' => $contract->contractRooms,
                    'eligible' => $blockReason === null,
                    'block_reason' => $blockReason,
                    'is_current' => ($includeId && $contract->id == $includeId)
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $contracts
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching eligible contracts: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch contracts: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get contract details for renewal (copy data to quotation)
     */
    public function getContractForRenewal($contractId)
    {
        try {
            $contract = Contract::with([
                'customer',
                'contractRooms.room',
                'contractRooms.billingGroup',
                'contractRentals.masterRental',
                'contractRentals.room',
                'contractSurveys.survey.building',
                'quotation.survey.surveyDetails',
                'quotation.survey.building',
                'quotation.quotationSurveys.survey.surveyDetails',
                'quotation.quotationSurveys.survey.building',
                'quotation.quotationRooms',
                'quotation.primaryPic',
                'quotation.primaryPic.customerContact',
                'quotation.quotationDetails',
                'quotation.quotationDetails.masterRoom'
            ])->findOrFail($contractId);

            $blockReason = $contract->getRenewalBlockReason();

            // Explicitly calculate days until expiry for frontend display
            // Use actual_end_date (BA date based) - if null, contract hasn't started yet
            $actualStartDate = $contract->actual_start_date;
            $actualEndDate = $contract->actual_end_date;
            $daysUntilExpiry = null;
            $renewalWindowDays = null;
            
            if ($actualEndDate) {
                $daysUntilExpiry = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($actualEndDate), false);
            }

            if ($actualStartDate && $actualEndDate) {
                $renewalWindowDays = ContractRenewal::calculateRenewalWindowDays($actualStartDate, $actualEndDate);
            }

            $eligibility = [
                'eligible' => $blockReason === null,
                'reason' => $blockReason,
                'days_until_expiry' => $daysUntilExpiry !== null ? (int) $daysUntilExpiry : null,
                'renewal_window_days' => $renewalWindowDays,
            ];

            $activeUnitOnWalls = $this->getActiveUnitOnWallsForRenewal($contract);
            $renewalSurveys = $this->getRenewalSurveys($contract);
            
            // Prepare data for quotation wizard
            // Use actual_start_date and actual_end_date (BA date based)
            $renewalData = [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer' => $contract->customer,
                'start_date' => $contract->actual_start_date,
                'end_date' => $contract->actual_end_date,
                'contract_value' => $contract->contract_value,
                'payment_terms' => $contract->payment_terms,
                'contract_terms' => $contract->contract_terms,
                'marketing_id' => $contract->marketing_id,
                'branch_id' => $contract->quotation->branch_id ?? null,
                'survey_id' => $renewalSurveys->first()?->id ?? $contract->quotation->survey_id ?? null,
                'survey_ids' => $renewalSurveys->pluck('id')->values(),
                'survey_number' => $renewalSurveys->first()?->survey_number ?? $contract->quotation->survey->survey_number ?? null,
 // Add Survey ID
                'rental_period' => $contract->quotation->rental_period ?? 12,
                'rental_unit' => $contract->quotation->rental_unit ?? 'bulan',
                'payment_method' => $contract->quotation->payment_method ?? 'After Service',
                'term_of_payment' => $contract->quotation->terms_of_payment ?? $contract->payment_terms ?? 'Tahunan',
                
                'notes_operation' => $contract->notes_operation,
                'notes_finance' => $contract->notes_finance,
                'notes_sales' => $contract->notes_sales, // Will trigger pop-up in renewal wizard
                'rooms' => $this->buildRenewalRooms($contract, $activeUnitOnWalls),
                'rentals' => $contract->contractRentals->map(function ($rental) use ($contract, $activeUnitOnWalls) {
                    
                    // Fallback search for Room info
                    $resolvedRoomId = $rental->room_id;
                    $resolvedRoomName = $rental->room->room_name ?? '';
                    $resolvedRoomType = $rental->room->room_type ?? '';
                    $resolvedSurveyId = $contract->quotation->survey_id; // Default to main survey

                    if (!$resolvedRoomId) {
                        // Try to find ANY quotation detail with same rental (first match)
                        // Ideally we should match by some other unique prop, but for renewal likely 1:1 or good enough
                        $quotDetail = $contract->quotation->quotationDetails
                            ->where('master_rental_id', $rental->master_rental_id)
                            ->first();

                        if ($quotDetail) {
                            if ($quotDetail->masterRoom) {
                                 $resolvedRoomId = $quotDetail->room_id;
                                 $resolvedRoomName = $quotDetail->masterRoom->room_name;
                                 $resolvedRoomType = $quotDetail->masterRoom->room_type;
                                 \Log::info("Renewal: Recovered room {$resolvedRoomId} for rental {$rental->id} via MasterRoom");
                            } elseif ($quotDetail->room_name) {
                                 // Legacy fallback: Use stored room_name string if ID is missing
                                 $resolvedRoomName = $quotDetail->room_name;
                                 \Log::info("Renewal: Recovered room name '{$resolvedRoomName}' for rental {$rental->id} via string fallback");
                            } else {
                                \Log::info("Renewal: QuotDetail found for rental {$rental->id} but NO MasterRoom and NO room_name string. Dump:", $quotDetail->toArray());
                            }
                        } else {
                             \Log::info("Renewal: No QuotDetail found for rental {$rental->id} (Master Rental: {$rental->master_rental_id})");
                        }
                    }

                    // Resolve Survey ID for this Room
                    if ($resolvedRoomId) {
                        // Check if the main survey has this room
                        $mainSurveyHasRoom = $contract->quotation->survey 
                            && $contract->quotation->survey->surveyDetails
                            && $contract->quotation->survey->surveyDetails->contains('room_id', $resolvedRoomId);
                        
                        if (!$mainSurveyHasRoom && $contract->quotation->quotationSurveys->isNotEmpty()) {
                            // Check additional surveys
                            foreach ($contract->quotation->quotationSurveys as $quotSurvey) {
                                if ($quotSurvey->survey->surveyDetails->contains('room_id', $resolvedRoomId)) {
                                    $resolvedSurveyId = $quotSurvey->survey_id;
                                    break;
                                }
                            }
                        }
                    }

                    $resolvedSurveyDetailId = null;
                    if ($resolvedSurveyId && $resolvedRoomId) {
                        $surveyForRental = Survey::with('surveyDetails')->find($resolvedSurveyId);
                        $resolvedSurveyDetailId = $surveyForRental?->surveyDetails
                            ->where('room_id', $resolvedRoomId)
                            ->first()?->id;
                    }

                    if (!$resolvedSurveyDetailId) {
                        $resolvedSurveyId = null;
                    }

                    $unitOnWall = $this->findUnitOnWallForRental(
                        $activeUnitOnWalls,
                        $rental,
                        $resolvedRoomId,
                        $resolvedRoomName
                    );

                    $resolvedRentalId = $rental->master_rental_id ?: $unitOnWall?->rental_id;
                    $resolvedRentalName = ($rental->masterRental->rental_name ?? '')
                        ?: $this->unitOnWallRentalName($unitOnWall);
                    $resolvedRoomId = $unitOnWall?->room_id ?: $resolvedRoomId;
                    $resolvedRoomName = $this->unitOnWallRoomName($unitOnWall) ?: $resolvedRoomName;
                    $resolvedRoomType = $unitOnWall?->room?->room_type ?: $resolvedRoomType;
                    $contractRoom = $resolvedRoomId
                        ? $contract->contractRooms->firstWhere('room_id', $resolvedRoomId)
                        : null;

                    if (!$contractRoom && trim((string) $resolvedRoomName) !== '') {
                        $roomName = $this->normalizeRenewalText($resolvedRoomName);
                        $contractRoom = $contract->contractRooms
                            ->first(fn ($room) => $this->normalizeRenewalText($room->room?->room_name) === $roomName);
                    }

                    $resolvedSpecifications = $resolvedSurveyDetailId
                        ? \App\Models\SurveyDetail::find($resolvedSurveyDetailId)?->specifications
                        : null;

                    return [
                        'rental_id' => $resolvedRentalId,
                        'room_id' => $resolvedSurveyDetailId ?? $resolvedRoomId,
                        'survey_detail_id' => $resolvedSurveyDetailId,
                        'master_room_id' => $resolvedRoomId,
                        'contract_room_id' => $contractRoom?->id,
                        'room_name' => $resolvedRoomName,
                        'room_type' => $resolvedRoomType,
                        'specifications' => $resolvedSpecifications ?: $this->masterRoomSpecifications($contractRoom?->room ?: \App\Models\MasterRoom::find($resolvedRoomId)),
                        'survey_id' => $resolvedSurveyId, // Add resolved survey ID
                        'contract_rental_id' => $rental->id,
                        'unit_on_wall_id' => $unitOnWall?->id,
                        'unit_on_wall_status' => $unitOnWall?->status,
                        'source' => $unitOnWall ? 'unit_on_wall' : 'contract',
                        'serial_number' => $unitOnWall?->serial_number,
                        'rental_code' => $rental->masterRental->rental_code ?? '',
                        'rental_name' => $resolvedRentalName,
                        'rental_price' => $rental->unit_price ?? $rental->masterRental->rental_price ?? 0,
                        'quantity' => $rental->quantity ?? 1,
                        'qty_free' => $rental->qty_free ?? 0,
                        'unit' => $rental->masterRental->unit ?? 'unit',
                        'notes' => $rental->notes, 
                        'rental_alias' => $rental->rental_alias,
                    ];
                }),
                'survey' => $renewalSurveys->first() ? $this->formatRenewalSurvey($renewalSurveys->first(), $contract) : null,
                'surveys' => $renewalSurveys
                    ->map(fn (Survey $survey) => $this->formatRenewalSurvey($survey, $contract))
                    ->values(),
                'remark_internal' => $contract->internal_remark ?? ($contract->quotation->internal_notes ?? ''),
                'remark_external' => $contract->external_remark ?? ($contract->quotation->additional_notes ?? ''),
                'pic_id' => $contract->quotation->primaryPic->customer_contact_id ?? null,
                'pic_name' => $contract->quotation->primaryPic->customerContact->name ?? $contract->quotation->pic_name ?? null,
                'price_basis' => $contract->quotation->price_basis ?? 'rental',
                'eligibility' => $eligibility
            ];

            return response()->json([
                'status' => 'success',
                'data' => $renewalData
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching contract for renewal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch contract details: ' . $e->getMessage()], 500);
        }
    }

    private function getRenewalSurveys(Contract $contract)
    {
        return collect()
            ->push($contract->quotation?->survey)
            ->merge($contract->quotation?->quotationSurveys?->pluck('survey') ?? collect())
            ->merge($contract->contractSurveys?->pluck('survey') ?? collect())
            ->filter()
            ->unique('id')
            ->values();
    }

    private function formatRenewalSurvey(Survey $survey, Contract $contract): array
    {
        return [
            'id' => $survey->id,
            'survey_number' => $survey->survey_number,
            'customer_name' => $survey->customer->name ?? $contract->customer->name ?? '',
            'building_name' => $survey->building->name ?? $survey->building->nama_gedung ?? '',
            'marketing_id' => $survey->marketing_id,
        ];
    }

    private function buildRenewalRooms(Contract $contract, $activeUnitOnWalls)
    {
        $rooms = $contract->contractRooms->map(function ($room) use ($contract, $activeUnitOnWalls) {
                    $unitOnWall = $this->findUnitOnWallForRoom($activeUnitOnWalls, $room);
                    $quotRoom = $contract->quotation->quotationRooms->where('room_id', $room->room_id)->first();
                    
                    // Fallback to name match if room_id match fails
                    if (!$quotRoom && $room->room) {
                        $roomName = $room->room->room_name;
                        $quotRoom = $contract->quotation->quotationRooms
                            ->filter(function($qr) use ($roomName) {
                                return strtolower(trim($qr->room_name)) === strtolower(trim($roomName));
                            })->first();
                    }

                    $surveyDetail = null;
                    if ($contract->quotation->survey && $room->room_id) {
                        $surveyDetail = $contract->quotation->survey->surveyDetails
                            ->where('room_id', $room->room_id)
                            ->first();
                    }

                    if (!$surveyDetail && $contract->quotation->survey && $room->room) {
                        $roomName = strtolower(trim((string) $room->room->room_name));
                        $surveyDetail = $contract->quotation->survey->surveyDetails
                            ->filter(function ($detail) use ($roomName) {
                                return strtolower(trim((string) $detail->room_name)) === $roomName;
                            })
                            ->first();
                    }

                    return [
                        'room_id' => $surveyDetail->id ?? $room->room_id,
                        'survey_detail_id' => $surveyDetail->id,
                        'survey_id' => $surveyDetail->survey_id ?? null,
                        'master_room_id' => $room->room_id,
                        'contract_room_id' => $room->id,
                        'unit_on_wall_id' => $unitOnWall?->id,
                        'unit_on_wall_status' => $unitOnWall?->status,
                        'source' => $unitOnWall ? 'unit_on_wall' : 'contract',
                        'serial_number' => $unitOnWall?->serial_number,
                        'room_name' => $this->unitOnWallRoomName($unitOnWall) ?: ($room->room->room_name ?? ''),
                        'room_type' => $surveyDetail->room_type ?? $unitOnWall?->room?->room_type ?? $room->room->room_type ?? null,
                        'specifications' => $surveyDetail?->specifications ?: $this->masterRoomSpecifications($room->room),
                        'billing_group_id' => $room->billing_group_id,
                        'aroma_product_id' => $quotRoom->aroma_product_id ?? $unitOnWall?->product_id ?? null,
                        'aroma_variant' => $quotRoom->aroma_variant ?? $unitOnWall?->product?->variant ?? null,
                    ];
                });

        if ($rooms->isNotEmpty()) {
            return $rooms->values();
        }

        return $contract->contractRentals
            ->map(function ($rental) use ($contract, $activeUnitOnWalls) {
                $resolvedRoomId = $rental->room_id;
                $resolvedRoomName = $rental->room->room_name ?? '';
                $resolvedRoomType = $rental->room->room_type ?? '';

                if (!$resolvedRoomId) {
                    $quotDetail = $contract->quotation->quotationDetails
                        ->where('master_rental_id', $rental->master_rental_id)
                        ->first();

                    if ($quotDetail) {
                        $resolvedRoomId = $quotDetail->room_id;
                        $resolvedRoomName = $quotDetail->room_name ?: $resolvedRoomName;
                        $resolvedRoomType = $quotDetail->masterRoom->room_type ?? $resolvedRoomType;
                    }
                }

                $unitOnWall = $this->findUnitOnWallForRental(
                    $activeUnitOnWalls,
                    $rental,
                    $resolvedRoomId,
                    $resolvedRoomName
                );

                $resolvedRoomId = $unitOnWall?->room_id ?: $resolvedRoomId;
                $resolvedRoomName = $this->unitOnWallRoomName($unitOnWall) ?: $resolvedRoomName;
                $resolvedRoomType = $unitOnWall?->room?->room_type ?: $resolvedRoomType;

                if (!$resolvedRoomId && trim((string) $resolvedRoomName) === '') {
                    return null;
                }

                $surveyDetail = null;
                if ($contract->quotation->survey && $resolvedRoomId) {
                    $surveyDetail = $contract->quotation->survey->surveyDetails
                        ->where('room_id', $resolvedRoomId)
                        ->first();
                }

                $quotRoom = null;
                if ($resolvedRoomId) {
                    $quotRoom = $contract->quotation->quotationRooms->where('room_id', $resolvedRoomId)->first();
                }

                if (!$quotRoom && trim((string) $resolvedRoomName) !== '') {
                    $roomName = strtolower(trim((string) $resolvedRoomName));
                    $quotRoom = $contract->quotation->quotationRooms
                        ->filter(fn ($qr) => strtolower(trim((string) $qr->room_name)) === $roomName)
                        ->first();
                }

                return [
                    'room_id' => $surveyDetail->id ?? $resolvedRoomId,
                    'survey_detail_id' => $surveyDetail?->id,
                    'survey_id' => $surveyDetail?->survey_id,
                    'master_room_id' => $resolvedRoomId,
                    'contract_room_id' => null,
                    'unit_on_wall_id' => $unitOnWall?->id,
                    'unit_on_wall_status' => $unitOnWall?->status,
                    'source' => $unitOnWall ? 'unit_on_wall' : 'contract_rental',
                    'serial_number' => $unitOnWall?->serial_number,
                    'room_name' => $resolvedRoomName,
                    'room_type' => $surveyDetail?->room_type ?? $resolvedRoomType,
                    'specifications' => $surveyDetail?->specifications ?: $this->masterRoomSpecifications(
                        \App\Models\MasterRoom::find($resolvedRoomId)
                    ),
                    'billing_group_id' => null,
                    'aroma_product_id' => $quotRoom?->aroma_product_id ?? $unitOnWall?->product_id ?? null,
                    'aroma_variant' => $quotRoom?->aroma_variant ?? $unitOnWall?->product?->variant ?? null,
                ];
            })
            ->filter()
            ->unique(fn ($room) => ($room['master_room_id'] ?: 'name') . '|' . strtolower(trim((string) $room['room_name'])))
            ->values();
    }

    private function masterRoomSpecifications(?\App\Models\MasterRoom $room): ?string
    {
        if (!$room) {
            return null;
        }

        return json_encode([
            'floor' => $room->room_floor,
            'intensity' => $room->room_intensity,
            'installation_type' => $room->room_installation_type,
            'qty' => $room->room_qty,
            'length' => $room->room_length,
            'width' => $room->room_width,
            'height' => $room->room_height,
            'temperature' => $room->room_temperature,
            'remark' => $room->room_remark,
        ]);
    }

    private function getActiveUnitOnWallsForRenewal(Contract $contract)
    {
        $contractRoomIds = $contract->contractRooms->pluck('room_id')->filter()->unique()->values();
        $contractRentalIds = $contract->contractRentals->pluck('master_rental_id')->filter()->unique()->values();
        $contractRoomNames = $contract->contractRooms
            ->map(fn ($room) => $this->normalizeRenewalText($room->room->room_name ?? null))
            ->filter()
            ->unique()
            ->values();
        $contractRentalNames = $contract->contractRentals
            ->map(fn ($rental) => $this->normalizeRenewalText($rental->masterRental->rental_name ?? null))
            ->filter()
            ->unique()
            ->values();

        return UnitOnWall::with(['room', 'rental', 'product'])
            ->where('customer_id', $contract->customer_id)
            ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
            ->where(function ($query) use ($contractRoomIds, $contractRentalIds, $contractRoomNames, $contractRentalNames) {
                $hasCondition = false;

                if ($contractRoomIds->isNotEmpty()) {
                    $query->whereIn('room_id', $contractRoomIds->all());
                    $hasCondition = true;
                }

                if ($contractRentalIds->isNotEmpty()) {
                    $hasCondition
                        ? $query->orWhereIn('rental_id', $contractRentalIds->all())
                        : $query->whereIn('rental_id', $contractRentalIds->all());
                    $hasCondition = true;
                }

                if ($contractRoomNames->isNotEmpty()) {
                    $hasCondition
                        ? $query->orWhereIn(DB::raw('LOWER(TRIM(room_name))'), $contractRoomNames->all())
                        : $query->whereIn(DB::raw('LOWER(TRIM(room_name))'), $contractRoomNames->all());
                    $hasCondition = true;
                }

                if ($contractRentalNames->isNotEmpty()) {
                    $hasCondition
                        ? $query->orWhereIn(DB::raw('LOWER(TRIM(rental_name))'), $contractRentalNames->all())
                        : $query->whereIn(DB::raw('LOWER(TRIM(rental_name))'), $contractRentalNames->all());
                    $hasCondition = true;
                }

                if (!$hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->get();
    }

    private function findUnitOnWallForRoom($unitOnWalls, $contractRoom)
    {
        $roomId = $contractRoom->room_id;
        $roomName = $this->normalizeRenewalText($contractRoom->room->room_name ?? null);

        return $unitOnWalls->first(fn ($unit) => $roomId && (int) $unit->room_id === (int) $roomId)
            ?? $unitOnWalls->first(fn ($unit) => $roomName && $this->normalizeRenewalText($this->unitOnWallRoomName($unit)) === $roomName);
    }

    private function findUnitOnWallForRental($unitOnWalls, $contractRental, $roomId = null, $roomName = null)
    {
        $rentalId = $contractRental->master_rental_id;
        $normalizedRoomName = $this->normalizeRenewalText($roomName);
        $rentalName = $this->normalizeRenewalText($contractRental->masterRental->rental_name ?? null);

        return $unitOnWalls->first(fn ($unit) => $roomId && $rentalId
            && (int) $unit->room_id === (int) $roomId
            && (int) $unit->rental_id === (int) $rentalId)
            ?? $unitOnWalls->first(fn ($unit) => $roomId && (int) $unit->room_id === (int) $roomId)
            ?? $unitOnWalls->first(fn ($unit) => $rentalId && (int) $unit->rental_id === (int) $rentalId)
            ?? $unitOnWalls->first(fn ($unit) => $normalizedRoomName
                && $this->normalizeRenewalText($this->unitOnWallRoomName($unit)) === $normalizedRoomName)
            ?? $unitOnWalls->first(fn ($unit) => $rentalName
                && $this->normalizeRenewalText($this->unitOnWallRentalName($unit)) === $rentalName);
    }

    private function unitOnWallRoomName($unitOnWall): ?string
    {
        if (!$unitOnWall) {
            return null;
        }

        return $unitOnWall->getRawOriginal('room_name') ?: ($unitOnWall->room->room_name ?? null);
    }

    private function unitOnWallRentalName($unitOnWall): ?string
    {
        if (!$unitOnWall) {
            return null;
        }

        return $unitOnWall->getRawOriginal('rental_name') ?: ($unitOnWall->rental->rental_name ?? null);
    }

    private function normalizeRenewalText(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
}

