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
            'create stock opname' => [
                'warehouse.stock-opnames.store',
                'warehouse.stock-opnames.create',
            ],
            'update stock opname' => [
                'warehouse.stock-opnames.update',
                'warehouse.stock-opnames.update',
            ],
            'delete stock opname' => [
                'warehouse.stock-opnames.destroy',
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
                'stock-opnames.create',
            ],
            'api update stock opname' => [
                'api.warehouse.stock-opnames.update',
                'stock-opnames.edit',
            ],
            'api delete stock opname' => [
                'api.warehouse.stock-opnames.destroy',
                'stock-opnames.delete',
            ],
            'api approve stock opname' => [
                'api.warehouse.stock-opnames.approve',
                'stock-opnames.edit',
            ],
        ];
    }
}
