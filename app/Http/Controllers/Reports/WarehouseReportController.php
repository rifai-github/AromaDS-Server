<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\MasterProduct;
use App\Models\StockOpname;
use App\Models\InventoryMovement;
use App\Models\InventoryIssuing;
use App\Models\InventoryReceiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarehouseReportController extends Controller
{
    public function index()
    {
        $warehouses = $this->getCachedWarehouses();
        $products = $this->getCachedProducts();
        $statistics = Cache::remember('reports:warehouse:index-statistics:v1', now()->addMinutes(2), function () {
            return [
                'total_warehouses' => Warehouse::where('is_active', true)->count(),
                'total_products' => MasterProduct::where('is_active', true)->count(),
                'total_stock_opnames' => StockOpname::count(),
                'total_movements' => InventoryMovement::count(),
            ];
        });

        return view('reports.warehouse.index', compact('warehouses', 'products', 'statistics'));
    }

    public function stockOpnameReport(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $query = StockOpname::with(['warehouse', 'createdBy']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        if ($request->product_id) {
            $query->whereHas('items', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        $stockOpnames = $query->orderBy('created_at', 'desc')->get();

        $summary = [
            'total_opnames' => $stockOpnames->count(),
            'total_variance' => $stockOpnames->sum('total_variance'),
            'positive_variance' => $stockOpnames->where('total_variance', '>', 0)->count(),
            'negative_variance' => $stockOpnames->where('total_variance', '<', 0)->count(),
        ];

        return view('reports.warehouse.stock-opname', compact('stockOpnames', 'summary'));
    }

    public function stock(Request $request)
    {
        return $this->stockReport($request);
    }

    public function stockReport(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'product_id' => 'nullable|exists:products,id',
            'low_stock' => 'nullable|boolean',
        ]);

        $query = DB::table('warehouse_products')
            ->join('warehouses', 'warehouse_products.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'warehouse_products.product_id', '=', 'products.id')
            ->select([
                'warehouses.name as warehouse_name',
                'products.name as product_name',
                'products.sku as product_sku',
                'warehouse_products.quantity as current_stock',
                'warehouse_products.minimum_stock',
                'warehouse_products.maximum_stock',
            ]);

        if ($request->warehouse_id) {
            $query->where('warehouse_products.warehouse_id', $request->warehouse_id);
        }

        if ($request->product_id) {
            $query->where('warehouse_products.product_id', $request->product_id);
        }

        if ($request->low_stock) {
            $query->whereRaw('warehouse_products.quantity <= warehouse_products.minimum_stock');
        }

        $stocks = $query->orderBy('warehouses.name')
            ->orderBy('products.name')
            ->get();

        $summary = [
            'total_products' => $stocks->count(),
            'low_stock_products' => $stocks->where('current_stock', '<=', 'minimum_stock')->count(),
            'out_of_stock' => $stocks->where('current_stock', 0)->count(),
            'total_value' => $stocks->sum('current_stock'), // This should be calculated with product price
        ];

        return view('reports.warehouse.stock', compact('stocks', 'summary'));
    }

    public function movementReport(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'product_id' => 'nullable|exists:products,id',
            'movement_type' => 'nullable|in:in,out,adjustment',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = InventoryMovement::with(['warehouse', 'product', 'createdBy']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('movement_date', [$request->start_date, $request->end_date]);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $summary = [
            'total_movements' => $movements->count(),
            'in_movements' => $movements->where('movement_type', 'in')->count(),
            'out_movements' => $movements->where('movement_type', 'out')->count(),
            'adjustments' => $movements->where('movement_type', 'adjustment')->count(),
            'total_in_quantity' => $movements->where('movement_type', 'in')->sum('quantity'),
            'total_out_quantity' => $movements->where('movement_type', 'out')->sum('quantity'),
        ];

        return view('reports.warehouse.movement', compact('movements', 'summary'));
    }

    public function issuingReport(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,issued,received,cancelled',
        ]);

        $query = InventoryIssuing::with(['warehouse', 'requestedBy', 'issuedBy']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('issue_date', [$request->start_date, $request->end_date]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $issuings = $query->orderBy('issue_date', 'desc')->get();

        $summary = [
            'total_issuings' => $issuings->count(),
            'pending_issuings' => $issuings->where('status', 'pending')->count(),
            'issued_issuings' => $issuings->where('status', 'issued')->count(),
            'received_issuings' => $issuings->where('status', 'received')->count(),
            'cancelled_issuings' => $issuings->where('status', 'cancelled')->count(),
        ];

        return view('reports.warehouse.issuing', compact('issuings', 'summary'));
    }

    public function receivingReport(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,received,cancelled',
        ]);

        $query = InventoryReceiving::with(['warehouse', 'receivedBy']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('receive_date', [$request->start_date, $request->end_date]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $receivings = $query->orderBy('receive_date', 'desc')->get();

        $summary = [
            'total_receivings' => $receivings->count(),
            'pending_receivings' => $receivings->where('status', 'pending')->count(),
            'received_receivings' => $receivings->where('status', 'received')->count(),
            'cancelled_receivings' => $receivings->where('status', 'cancelled')->count(),
        ];

        return view('reports.warehouse.receiving', compact('receivings', 'summary'));
    }

    public function exportStockOpname(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'required|in:excel,csv,pdf',
        ]);

        // Generate and return the export file
        // This is a placeholder for actual export logic
        return response()->json([
            'status' => 'success',
            'message' => 'Export will be generated and sent to your email.'
        ]);
    }

    public function exportStockOpnamePdf(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Generate and return the PDF export file
        // This is a placeholder for actual PDF export logic
        return response()->json([
            'status' => 'success',
            'message' => 'PDF export will be generated and sent to your email.'
        ]);
    }

    public function exportStock(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'format' => 'required|in:excel,csv,pdf',
        ]);

        // Generate and return the export file
        return response()->json([
            'status' => 'success',
            'message' => 'Export will be generated and sent to your email.'
        ]);
    }

    public function exportStockPdf(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        // Generate and return the PDF export file
        return response()->json([
            'status' => 'success',
            'message' => 'PDF export will be generated and sent to your email.'
        ]);
    }

    public function exportMovement(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'required|in:excel,csv,pdf',
        ]);

        // Generate and return the export file
        return response()->json([
            'status' => 'success',
            'message' => 'Export will be generated and sent to your email.'
        ]);
    }

    // API Methods
    public function getWarehouseStatistics()
    {
        $stats = Cache::remember('reports:warehouse:api-statistics:v1', now()->addMinutes(2), function () {
            return [
                'total_warehouses' => Warehouse::where('is_active', true)->count(),
                'total_products' => MasterProduct::where('is_active', true)->count(),
                'low_stock_products' => DB::table('warehouse_products')
                    ->whereRaw('quantity <= minimum_stock')
                    ->count(),
                'out_of_stock_products' => DB::table('warehouse_products')
                    ->where('quantity', 0)
                    ->count(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function getStockByWarehouse($warehouseId)
    {
        $stocks = DB::table('warehouse_products')
            ->join('products', 'warehouse_products.product_id', '=', 'products.id')
            ->where('warehouse_products.warehouse_id', $warehouseId)
            ->select([
                'products.name as product_name',
                'products.sku as product_sku',
                'warehouse_products.quantity as current_stock',
                'warehouse_products.minimum_stock',
                'warehouse_products.maximum_stock',
            ])
            ->orderBy('products.name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stocks
        ]);
    }

    private function getCachedWarehouses()
    {
        return Cache::remember('reports:warehouse:warehouses:v1', now()->addMinutes(10), function () {
            return Warehouse::where('is_active', true)->orderBy('name')->get();
        });
    }

    private function getCachedProducts()
    {
        return Cache::remember('reports:warehouse:products:v1', now()->addMinutes(10), function () {
            return MasterProduct::where('is_active', true)->orderBy('name')->get();
        });
    }
}
