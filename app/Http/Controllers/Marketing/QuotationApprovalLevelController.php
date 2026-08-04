<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationApprovalLevel;
use App\Models\Role;
use App\Services\Marketing\QuotationBottomPriceEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Master screen for the quotation approval ladder.
 *
 * Levels are ordinary data: percentages can be retuned and rungs added or
 * removed at any time without a deploy.
 */
class QuotationApprovalLevelController extends Controller
{
    public function index()
    {
        $levels = QuotationApprovalLevel::query()->ordered()->get();

        $roles = Role::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Read role membership straight from role_permissions so that assignments
        // made in the Role & Permission screen show up here too.
        $levelRoleIds = [];
        foreach ($levels as $level) {
            $levelRoleIds[$level->id] = $level->roleIds();
        }

        $highest = $levels->where('is_active', true)->max('max_discount_percentage');

        // Prepared here rather than inline in Blade so the view keeps a plain @json call.
        $levelsPayload = $levels->map(fn (QuotationApprovalLevel $level) => [
            'id' => $level->id,
            'level_code' => $level->level_code,
            'level_name' => $level->level_name,
            'max_discount_percentage' => (float) $level->max_discount_percentage,
            'sort_order' => (int) $level->sort_order,
            'description' => $level->description,
            'is_active' => (bool) $level->is_active,
            'role_ids' => $levelRoleIds[$level->id] ?? [],
        ])->values();

        return view('marketing.quotation-approval-levels.index', [
            'levels' => $levels,
            'roles' => $roles,
            'levelRoleIds' => $levelRoleIds,
            'levelsPayload' => $levelsPayload,
            // Without a rung reaching 100% the deepest discounts cannot be approved by anyone.
            'hasFullCoverage' => $highest !== null && (float) $highest >= 100,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('quotation_approval_levels', 'level_code')->whereNull('deleted_at')],
            'level_name' => ['required', 'string', 'max:100'],
            'max_discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer'],
        ]);

        $level = DB::transaction(function () use ($validated, $request) {
            $level = QuotationApprovalLevel::create([
                'level_code' => strtolower($validated['level_code']),
                'level_name' => $validated['level_name'],
                'max_discount_percentage' => $validated['max_discount_percentage'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $level->syncRoles($validated['role_ids'] ?? []);

            return $level;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Level approval berhasil dibuat.',
            'data' => $level->fresh(),
        ]);
    }

    public function update(Request $request, QuotationApprovalLevel $quotationApprovalLevel)
    {
        $validated = $request->validate([
            // level_code is deliberately not editable: it backs the permission name.
            'level_name' => ['required', 'string', 'max:100'],
            'max_discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($validated, $request, $quotationApprovalLevel) {
            $quotationApprovalLevel->update([
                'level_name' => $validated['level_name'],
                'max_discount_percentage' => $validated['max_discount_percentage'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => Auth::id(),
            ]);

            $quotationApprovalLevel->syncRoles($validated['role_ids'] ?? []);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Level approval berhasil diperbarui.',
        ]);
    }

    public function destroy(QuotationApprovalLevel $quotationApprovalLevel)
    {
        $remainingActive = QuotationApprovalLevel::query()
            ->active()
            ->where('id', '!=', $quotationApprovalLevel->id)
            ->count();

        // With no active level left nothing could ever be approved again.
        if ($remainingActive === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak bisa menghapus level aktif terakhir. Buat level pengganti terlebih dahulu.',
            ], 422);
        }

        // Soft delete keeps the role assignments so a restore is lossless.
        $quotationApprovalLevel->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Level approval berhasil dihapus.',
        ]);
    }

    /**
     * How many quotations currently waiting for approval sit on this level.
     * Used to warn before the percentage is lowered, which would escalate them.
     */
    public function impact(QuotationApprovalLevel $quotationApprovalLevel)
    {
        $evaluator = app(QuotationBottomPriceEvaluator::class);

        $affected = Quotation::query()
            ->where('status', 'waiting_for_approval')
            ->get()
            ->filter(function (Quotation $quotation) use ($evaluator, $quotationApprovalLevel) {
                $evaluation = $evaluator->evaluate($quotation);

                return ($evaluation['required_level']['id'] ?? null) === $quotationApprovalLevel->id;
            })
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => ['pending_quotations' => $affected],
        ]);
    }
}
