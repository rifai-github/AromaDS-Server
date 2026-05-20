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

    private function extractComponentKeyBlock(string $view): string
    {
        $start = strpos($view, 'const componentKey = [');
        $this->assertNotFalse($start);

        $end = strpos($view, '].join', $start);
        $this->assertNotFalse($end);

        return substr($view, $start, $end - $start);
    }
}
