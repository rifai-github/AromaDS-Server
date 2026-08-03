<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaterialAssignBomValidationScriptTest extends TestCase
{
    public function test_bom_validation_groups_split_packages_by_rental_detail_not_material_name(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        $this->assertStringContainsString('const componentKey = [', $view);
        $this->assertStringContainsString('rentalDetailId,', $view);
        $this->assertStringContainsString('targetBomQty,', $view);
        $this->assertStringContainsString('productType.toLowerCase()', $view);

        $componentKeyBlock = $this->extractComponentKeyBlock($view);

        $this->assertStringNotContainsString('materialName.toLowerCase()', $componentKeyBlock);
    }

    public function test_split_package_volume_example_matches_target_when_grouped_together(): void
    {
        $rows = [
            ['rental_detail_id' => 123, 'target' => 250, 'product_type' => 'Aroma', 'package_ml' => 100, 'qty' => 2],
            ['rental_detail_id' => 123, 'target' => 250, 'product_type' => 'Aroma', 'package_ml' => 50, 'qty' => 1],
        ];

        $groupedVolumes = [];
        foreach ($rows as $row) {
            $groupKey = implode('|', [
                $row['rental_detail_id'],
                $row['target'],
                strtolower($row['product_type']),
            ]);

            $groupedVolumes[$groupKey] = ($groupedVolumes[$groupKey] ?? 0) + ($row['package_ml'] * $row['qty']);
        }

        $this->assertCount(1, $groupedVolumes);
        $this->assertSame(250, array_values($groupedVolumes)[0]);
    }

    /**
     * WA report 2/8/2026: Install Free, Extra, and Complaint jobs don't need to cover the
     * whole rental BOM the way an Install/Service job does, so they're allowed to submit
     * under the BOM target. Over-issuing is still blocked for every job type.
     */
    public function test_bom_validation_allows_install_free_extra_and_complain_to_submit_below_target(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        $this->assertStringContainsString("const bomUnderIssueExemptTypes = ['install_free', 'extra', 'complain'];", $view);
        $this->assertStringContainsString('const diff = totalVolume - targetBomQty;', $view);
        $this->assertStringContainsString('if (diff < 0 && bomUnderIssueExemptTypes.includes(jobType)) return;', $view);
    }

    /**
     * The under-issue exemption must only ever apply to the whitelisted job types, and only
     * when the total is short of target - never when it exceeds it. This guards against the
     * fragile, previously-rejected approach of inferring job type from the job number string
     * (e.g. checking for a "-IF" suffix), which breaks if the numbering format ever changes.
     */
    public function test_bom_validation_still_blocks_over_issue_and_non_exempt_under_issue(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        $this->assertStringContainsString('if (Math.abs(diff) <= 0.01) return;', $view);
        $this->assertStringContainsString('bomValidationErrors.push(', $view);
        $this->assertStringNotContainsString("jobNumber.includes('-IF')", $view);
        $this->assertStringNotContainsString('total issued can be less than or equal to target', $view);
    }

    private function extractComponentKeyBlock(string $view): string
    {
        $start = strpos($view, 'const componentKey = [');
        $this->assertNotFalse($start);

        $end = strpos($view, '].join', $start);
        $this->assertNotFalse($end);

        return substr($view, $start, $end - $start);
    }
}
