<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaterialAssignServiceWarehouseTest extends TestCase
{
    public function test_material_assign_creation_uses_operational_area_service_branch(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('use App\Services\OperationalAreaService;', $controller);
        $this->assertStringContainsString('OperationalAreaService::resolveServiceBranchForBuilding($building)', $controller);
        $this->assertStringContainsString('OperationalAreaService::resolveWarehouseForBranch($branch)', $controller);
        $this->assertStringContainsString('No service branch found for building, trying team branch', $controller);
    }

    public function test_material_assign_list_stock_fallback_uses_service_area_branch(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobAssignMaterialIssueController.php'));
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        $this->assertStringContainsString('use App\Services\OperationalAreaService;', $controller);
        $this->assertStringContainsString('OperationalAreaService::resolveServiceBranchForBuilding($building)', $controller);
        $this->assertStringContainsString('OperationalAreaService::resolveWarehouseForBranch($branchLookup->get($branchId))', $controller);
        $this->assertStringContainsString('\App\Services\OperationalAreaService::resolveServiceBranchForBuilding($building)', $view);
    }

    public function test_service_area_warehouse_resolver_prefers_primary_branch_mapping(): void
    {
        $service = file_get_contents(app_path('Services/OperationalAreaService.php'));

        $this->assertStringContainsString('BranchWarehouse::where(\'branch_id\', $branch->id)', $service);
        $this->assertStringContainsString('->orderByDesc(\'is_primary\')', $service);
        $this->assertStringContainsString('resolveServiceWarehouseForBuilding', $service);
    }

    public function test_material_issue_service_warehouse_repair_command_exists(): void
    {
        $command = file_get_contents(app_path('Console/Commands/RepairMaterialIssueServiceWarehouse.php'));

        $this->assertStringContainsString('operational:repair-material-issue-service-warehouse', $command);
        $this->assertStringContainsString('OperationalAreaService::resolveWarehouseForBranch($targetBranch)', $command);
        $this->assertStringContainsString('DRY RUN mode', $command);
        $this->assertStringContainsString('move to service area warehouse', $command);
    }
}
