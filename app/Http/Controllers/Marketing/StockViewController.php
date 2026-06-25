<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;

class StockViewController extends Controller
{
    /**
     * Read-only stock listing (item name + warehouse qty) for Marketing.
     */
    public function index(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'warehouse_code']);

        $query = WarehouseProduct::with(['masterProduct:id,name,sku', 'warehouse:id,name'])
            ->whereHas('masterProduct', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('masterProduct', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('quantity', 'desc')
            ->paginateStd(25)
            ->withQueryString();

        return view('marketing.stock-view.index', compact('stocks', 'warehouses'));
    }
}
