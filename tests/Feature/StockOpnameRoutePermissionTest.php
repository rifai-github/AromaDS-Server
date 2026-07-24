<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StockOpnameRoutePermissionTest extends TestCase
{
    #[DataProvider('routePermissionProvider')]
    public function test_web_stock_opname_routes_require_their_granular_permission(
        string $routeName,
        string $permission
    ): void {
        $route = app('router')->getRoutes()->getByName($routeName);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertContains(
            "permission:{$permission}",
            $route->gatherMiddleware(),
            "Route {$routeName} does not require {$permission}."
        );
    }

    public static function routePermissionProvider(): array
    {
        return [
            'list stock opnames' => [
                'warehouse.stock-opnames.index',
                'warehouse.stock-opnames.view',
            ],
            'show stock opname' => [
                'warehouse.stock-opnames.show',
                'warehouse.stock-opnames.view',
            ],
            'stock opname dashboard' => [
                'warehouse.stock-opnames.dashboard',
                'warehouse.stock-opnames.view',
            ],
            'export stock opname stock' => [
                'warehouse.stock-opnames.export-stock',
                'warehouse.stock-opnames.view',
            ],
            'create stock opname' => [
                'warehouse.stock-opnames.store',
                'warehouse.stock-opnames.create',
            ],
            'start stock opname' => [
                'warehouse.stock-opnames.start',
                'warehouse.stock-opnames.update',
            ],
            'complete stock opname' => [
                'warehouse.stock-opnames.complete',
                'warehouse.stock-opnames.update',
            ],
            'submit stock opname' => [
                'warehouse.stock-opnames.submit',
                'warehouse.stock-opnames.update',
            ],
            'update stock opname' => [
                'warehouse.stock-opnames.update',
                'warehouse.stock-opnames.update',
            ],
            'delete stock opname' => [
                'warehouse.stock-opnames.destroy',
                'warehouse.stock-opnames.delete',
            ],
            'bulk delete stock opname' => [
                'warehouse.stock-opnames.bulk-delete',
                'warehouse.stock-opnames.delete',
            ],
            'approve stock opname' => [
                'warehouse.stock-opnames.approve',
                'warehouse.stock-opnames.approve',
            ],
            'unpost stock opname' => [
                'warehouse.stock-opnames.unpost',
                'warehouse.stock-opnames.approve',
            ],
            'import stock opname stock' => [
                'warehouse.stock-opnames.import-stock',
                'warehouse.stock-opnames.update',
            ],
            'create stock opname adjustment' => [
                'warehouse.stock-opnames.create-adjustment',
                'warehouse.stock-opnames.update',
            ],
            'update stock opname detail' => [
                'warehouse.stock-opnames.details.update',
                'warehouse.stock-opnames.update',
            ],
            'legacy list stock opname' => [
                'warehouse.stocks.index',
                'warehouse.stock-opnames.view',
            ],
            'legacy show stock opname' => [
                'warehouse.stocks.show',
                'warehouse.stock-opnames.view',
            ],
            'legacy create stock opname' => [
                'warehouse.stocks.store',
                'warehouse.stock-opnames.create',
            ],
            'legacy update stock opname' => [
                'warehouse.stocks.update',
                'warehouse.stock-opnames.update',
            ],
            'legacy delete stock opname' => [
                'warehouse.stocks.destroy',
                'warehouse.stock-opnames.delete',
            ],
            'api create stock opname' => [
                'api.warehouse.stock-opnames.store',
                'warehouse.stock-opnames.create',
            ],
            'api update stock opname' => [
                'api.warehouse.stock-opnames.update',
                'warehouse.stock-opnames.update',
            ],
            'api delete stock opname' => [
                'api.warehouse.stock-opnames.destroy',
                'warehouse.stock-opnames.delete',
            ],
            'api approve stock opname' => [
                'api.warehouse.stock-opnames.approve',
                'warehouse.stock-opnames.approve',
            ],
        ];
    }
}
