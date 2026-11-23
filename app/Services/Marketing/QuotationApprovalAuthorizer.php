<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
use App\Models\QuotationApprovalLevel;
use App\Models\User;

/**
 * Answers "may THIS user approve THIS quotation?" for the tiered bottom-price
 * ladder. Sejak MOM 18 Nov 2026 wewenang = user yang ditunjuk per level
 * (quotation_approval_level_users), bukan role/permission level. Lives
 * outside the User model because it needs the evaluator, and the
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

        // QA 23 Nov: Admin dicek SEBELUM gate izin. Role Admin di bootstrap tak punya
        // izin *.quotations.approve, jadi dulu Admin pun harus dicentang di Master Price Slab.
        if ($this->bypassesLevelCheck($user)) {
            return true;
        }

        // The pre-existing base gate still applies to everyone else.
        if (! $user->canApprove('quotations')) {
            return false;
        }

        $evaluation ??= $this->evaluate($quotation);

        // Nothing breached the floor, so there is no level to satisfy.
        if (empty($evaluation['requires_approval'])) {
            return true;
        }

        $required = $this->requiredLevel($quotation, $evaluation);

        // MOM 18 Nov 2026 p4: tanpa price slab yang menutup diskon ini -> toleransi 0%.
        // Dulu flag legacy membuat pemegang izin approve lama tetap lolos (fail open).
        if (! $required) {
            return false;
        }

        // Wewenang = user yang DITUNJUK di level itu atau level yang lebih tinggi, bukan role.
        return $this->assignedLevelsQuery($user)
            ->where('max_discount_percentage', '>=', $required->max_discount_percentage)
            ->exists();
    }

    /** Highest level the user personally holds, for UI messaging. */
    public function highestLevelFor(?User $user): ?QuotationApprovalLevel
    {
        if (! $user) {
            return null;
        }

        return $this->assignedLevelsQuery($user)
            ->orderByDesc('max_discount_percentage')
            ->first();
    }

    private function assignedLevelsQuery(User $user)
    {
        return QuotationApprovalLevel::query()
            ->active()
            ->whereHas('users', fn ($query) => $query->where('users.id', $user->id));
    }

    /**
     * Admin sistem dan role Management* boleh meng-approve tanpa dicentang di Master Price Slab
     * (keputusan user 23 Nov 2026, membatalkan penghapusan bypass Management dari MOM 18 Nov).
     * Role lain tetap harus ditunjuk per level.
     */
    private function bypassesLevelCheck(User $user): bool
    {
        return $user->hasRole('Admin')
            || $user->hasRole('super_admin')
            || $user->hasRoleStartingWith('Management');
    }
}
