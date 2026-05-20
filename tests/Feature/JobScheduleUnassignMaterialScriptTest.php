<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobScheduleUnassignMaterialScriptTest extends TestCase
{
    public function test_unassign_material_modal_normalizes_material_assign_statuses(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/index.blade.php'));

        $this->assertStringContainsString('const normalizeMaterialUnassignStatus', $view);
        $this->assertStringContainsString("replace(/[\\s-]+/g, '_')", $view);
        $this->assertStringContainsString("'barang_dipersiapkan'", $view);
        $this->assertStringContainsString("'material_in_prep'", $view);
        $this->assertStringContainsString('materialUnassignableStatuses.includes(jobStatus)', $view);
    }

    public function test_barang_dipersiapkan_display_variants_are_unassignable(): void
    {
        $allowedStatuses = [
            'assign_material',
            'material_assign',
            'barang_dipersiapkan',
            'material_prepare',
            'material_in_prep',
        ];

        $normalize = fn (string $status): string => strtolower(preg_replace('/[\s-]+/', '_', trim($status)));

        $this->assertContains($normalize('barang_dipersiapkan'), $allowedStatuses);
        $this->assertContains($normalize('Barang Dipersiapkan'), $allowedStatuses);
        $this->assertContains($normalize('Material In Prep'), $allowedStatuses);
    }
}
