<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaterialAssignBuildingFilterScriptTest extends TestCase
{
    public function test_material_assign_top_filter_uses_building_name_instead_of_team_name(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobAssignMaterialIssueController.php'));

        $this->assertStringContainsString('Filter Nama Gedung:', $view);
        $this->assertStringContainsString('id="filterBuildingName"', $view);
        $this->assertStringContainsString('name="building_name"', $view);
        $this->assertStringContainsString('Semua Gedung', $view);
        $this->assertStringContainsString("params.set('building_name', buildingName)", $view);
        $this->assertStringNotContainsString('id="filterTeamCode"', $view);
        $this->assertStringNotContainsString('Filter Team Name:', $view);

        $this->assertStringContainsString("'buildings'", $controller);
        $this->assertStringContainsString('job-assign-material-issues:index:buildings', $controller);
    }
}
