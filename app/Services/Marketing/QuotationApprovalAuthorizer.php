<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
use App\Models\QuotationApprovalLevel;
use App\Models\User;

/**
 * Answers "may THIS user approve THIS quotation?" for the tiered bottom-price
 * ladder. Lives outside the User model because it needs the evaluator, and the
 * codebase has no policy layer.
 */
class QuotationApprovalAuthorizer
{
    public function __construct(private QuotationBottomPriceEvaluator $evaluator) {}

    public function evaluate(Quotation $quotation): array
    {
        return $this->evaluator->evaluate($quotation);
    }

    public function requiredLevel(Quotation $quotation, ?array $evaluation = null): ?QuotationApprovalLevel
    {
        $evaluation ??= $this->evaluate($quotation);

        $levelId = $evaluation['required_level']['id'] ?? null;

        return $levelId ? QuotationApprovalLevel::find($levelId) : null;
    }

    public function canApprove(?User $user, Quotation $quotation, ?array $evaluation = null): bool
    {
        if (! $user) {
            return false;
        }

        // The pre-existing base gate still applies to everyone.
        if (! $user->canApprove('quotations')) {
            return false;
        }

        if ($this->bypassesLevelCheck($user)) {
            return true;
        }

        $evaluation ??= $this->evaluate($quotation);

        // Nothing breached the floor, so there is no level to satisfy.
        if (empty($evaluation['requires_approval'])) {
            return true;
        }

        $required = $this->requiredLevel($quotation, $evaluation);

        if (! $required) {
            // No active level covers this discount - fail closed unless the
            // legacy fallback is still carrying un-migrated approvers.
            return $this->qualifiesAsLegacyApprover($user);
        }

        foreach ($this->permissionsSatisfying($required) as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        return $this->qualifiesAsLegacyApprover($user);
    }

    /** Highest level the user personally holds, for UI messaging. */
    public function highestLevelFor(?User $user): ?QuotationApprovalLevel
    {
        if (! $user) {
            return null;
        }

        return QuotationApprovalLevel::query()
            ->active()
            ->orderByDesc('max_discount_percentage')
            ->get()
            ->first(fn (QuotationApprovalLevel $level) => $user->hasPermission($level->permission_name));
    }

    /**
     * Every level senior enough to approve what $required demands.
     * Authority ordering comes from max_discount_percentage only.
     */
    private function permissionsSatisfying(QuotationApprovalLevel $required): array
    {
        return QuotationApprovalLevel::query()
            ->active()
            ->where('max_discount_percentage', '>=', $required->max_discount_percentage)
            ->pluck('permission_name')
            ->all();
    }

    /**
     * A holder of the legacy blanket permission who has not been given any
     * level yet keeps their old power while the config flag is on.
     */
    private function qualifiesAsLegacyApprover(User $user): bool
    {
        if (! config('quotation.legacy_approve_is_highest', true)) {
            return false;
        }

        $levelPermissions = QuotationApprovalLevel::query()->pluck('permission_name');

        foreach ($levelPermissions as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                // They have been migrated onto the ladder, so judge them by it.
                return false;
            }
        }

        return true;
    }

    /** Mirrors the Admin/Management short-circuit in CheckPermission. */
    private function bypassesLevelCheck(User $user): bool
    {
        return $user->hasRole('Admin')
            || $user->hasRole('super_admin')
            || $user->hasRoleStartingWith('Management');
    }
}
