<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryTransferRoutePermissionTest extends TestCase
{
    #[DataProvider('routePermissionProvider')]
    public function test_transfer_routes_require_granular_permissions(string $routeName, string $permission): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertContains("permission:{$permission}", $route->gatherMiddleware());
    }

    public static function routePermissionProvider(): array
    {
        return [
            ['warehouse.inventory-transfers.index', 'warehouse.inventory-transfers.view'],
            ['warehouse.inventory-transfers.api.store', 'warehouse.inventory-transfers.create'],
            ['warehouse.inventory-transfers.api.update', 'warehouse.inventory-transfers.update'],
            ['warehouse.inventory-transfers.submit-approval', 'warehouse.inventory-transfers.submit,warehouse.inventory-transfers.submit.create,warehouse.inventory-transfers.submit.approve'],
            ['warehouse.inventory-transfers.approve', 'warehouse.inventory-transfers.approve'],
            ['warehouse.inventory-transfers.reject', 'warehouse.inventory-transfers.reject'],
            ['warehouse.inventory-transfers.mark-transferred', 'warehouse.inventory-transfers.transfer,warehouse.inventory-transfers.transfer.create,warehouse.inventory-transfers.transfer.approve'],
            ['warehouse.inventory-transfers.mark-received', 'warehouse.inventory-transfers.receive,warehouse.inventory-transfers.receive.create,warehouse.inventory-transfers.receive.approve'],
            ['warehouse.inventory-transfers.documents', 'warehouse.inventory-transfers.update'],
            ['warehouse.inventory-transfers.api.delete', 'warehouse.inventory-transfers.delete'],
        ];
    }
}
