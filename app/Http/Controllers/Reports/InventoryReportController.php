<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\MasterRental;
use App\Models\MasterRentalCategory;
use App\Models\MasterRentalBrand;
use App\Models\MasterRentalSupplier;
use App\Models\JobAssignMaterialIssue;
use App\Models\JobSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statistics = [
            'total_items' => MasterRental::count(),
            'active_items' => MasterRental::where('status', 'active')->count(),
            'inactive_items' => MasterRental::where('status', 'inactive')->count(),
            'low_stock_items' => MasterRental::where('stock_quantity', '<=', DB::raw('minimum_stock'))->count(),
            'out_of_stock_items' => MasterRental::where('stock_quantity', 0)->count(),
            'total_categories' => MasterRentalCategory::count(),
            'total_brands' => MasterRentalBrand::count(),
            'total_suppliers' => MasterRentalSupplier::count(),
            'total_issues' => JobAssignMaterialIssue::count(),
            'issued_materials' => JobAssignMaterialIssue::where('issued', true)->count(),
        ];

        return view('reports.inventory.index', compact('statistics'));
    }

    /**
     * Stock Report.
     */
    public function stockReport(Request $request)
    {
        $query = MasterRental::with(['category', 'brand', 'supplier']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by stock level
        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'low':
                    $query->where('stock_quantity', '<=', DB::raw('minimum_stock'));
                    break;
                case 'out':
                    $query->where('stock_quantity', 0);
                    break;
                case 'normal':
                    $query->where('stock_quantity', '>', DB::raw('minimum_stock'));
                    break;
            }
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('item_name')->paginate(15);
        $categories = MasterRentalCategory::orderBy('category_name')->get();
        $brands = MasterRentalBrand::orderBy('brand_name')->get();
        $suppliers = MasterRentalSupplier::orderBy('supplier_name')->get();

        $statistics = [
            'total' => MasterRental::count(),
            'active' => MasterRental::where('status', 'active')->count(),
            'inactive' => MasterRental::where('status', 'inactive')->count(),
            'low_stock' => MasterRental::where('stock_quantity', '<=', DB::raw('minimum_stock'))->count(),
            'out_of_stock' => MasterRental::where('stock_quantity', 0)->count(),
            'total_value' => MasterRental::sum(DB::raw('stock_quantity * unit_price')),
            'average_price' => MasterRental::avg('unit_price'),
        ];

        return view('reports.inventory.stock', compact('items', 'categories', 'brands', 'suppliers', 'statistics'));
    }

    /**
     * Category Report.
     */
    public function categoryReport(Request $request)
    {
        $query = MasterRentalCategory::withCount(['items' => function ($q) {
            $q->where('status', 'active');
        }]);

        // Filter by search
        if ($request->filled('search')) {
            $query->where('category_name', 'like', "%{$request->search}%");
        }

        $categories = $query->orderBy('category_name')->get();

        // Calculate category statistics
        $categoryStats = $categories->map(function ($category) {
            $items = $category->items;
            $totalStock = $items->sum('stock_quantity');
            $totalValue = $items->sum(DB::raw('stock_quantity * unit_price'));
            $lowStockItems = $items->where('stock_quantity', '<=', DB::raw('minimum_stock'))->count();
            $outOfStockItems = $items->where('stock_quantity', 0)->count();

            return [
                'category' => $category,
                'total_items' => $items->count(),
                'total_stock' => $totalStock,
                'total_value' => $totalValue,
                'low_stock_items' => $lowStockItems,
                'out_of_stock_items' => $outOfStockItems,
                'average_price' => $items->count() > 0 ? $items->avg('unit_price') : 0,
            ];
        });

        $statistics = [
            'total_categories' => $categories->count(),
            'total_items' => $categories->sum('items_count'),
            'total_stock_value' => $categoryStats->sum('total_value'),
            'categories_with_low_stock' => $categoryStats->where('low_stock_items', '>', 0)->count(),
        ];

        return view('reports.inventory.category', compact('categoryStats', 'statistics'));
    }

    /**
     * Brand Report.
     */
    public function brandReport(Request $request)
    {
        $query = MasterRentalBrand::withCount(['items' => function ($q) {
            $q->where('status', 'active');
        }]);

        // Filter by search
        if ($request->filled('search')) {
            $query->where('brand_name', 'like', "%{$request->search}%");
        }

        $brands = $query->orderBy('brand_name')->get();

        // Calculate brand statistics
        $brandStats = $brands->map(function ($brand) {
            $items = $brand->items;
            $totalStock = $items->sum('stock_quantity');
            $totalValue = $items->sum(DB::raw('stock_quantity * unit_price'));
            $lowStockItems = $items->where('stock_quantity', '<=', DB::raw('minimum_stock'))->count();
            $outOfStockItems = $items->where('stock_quantity', 0)->count();

            return [
                'brand' => $brand,
                'total_items' => $items->count(),
                'total_stock' => $totalStock,
                'total_value' => $totalValue,
                'low_stock_items' => $lowStockItems,
                'out_of_stock_items' => $outOfStockItems,
                'average_price' => $items->count() > 0 ? $items->avg('unit_price') : 0,
            ];
        });

        $statistics = [
            'total_brands' => $brands->count(),
            'total_items' => $brands->sum('items_count'),
            'total_stock_value' => $brandStats->sum('total_value'),
            'brands_with_low_stock' => $brandStats->where('low_stock_items', '>', 0)->count(),
        ];

        return view('reports.inventory.brand', compact('brandStats', 'statistics'));
    }

    /**
     * Supplier Report.
     */
    public function supplierReport(Request $request)
    {
        $query = MasterRentalSupplier::withCount(['items' => function ($q) {
            $q->where('status', 'active');
        }]);

        // Filter by search
        if ($request->filled('search')) {
            $query->where('supplier_name', 'like', "%{$request->search}%");
        }

        $suppliers = $query->orderBy('supplier_name')->get();

        // Calculate supplier statistics
        $supplierStats = $suppliers->map(function ($supplier) {
            $items = $supplier->items;
            $totalStock = $items->sum('stock_quantity');
            $totalValue = $items->sum(DB::raw('stock_quantity * unit_price'));
            $lowStockItems = $items->where('stock_quantity', '<=', DB::raw('minimum_stock'))->count();
            $outOfStockItems = $items->where('stock_quantity', 0)->count();

            return [
                'supplier' => $supplier,
                'total_items' => $items->count(),
                'total_stock' => $totalStock,
                'total_value' => $totalValue,
                'low_stock_items' => $lowStockItems,
                'out_of_stock_items' => $outOfStockItems,
                'average_price' => $items->count() > 0 ? $items->avg('unit_price') : 0,
            ];
        });

        $statistics = [
            'total_suppliers' => $suppliers->count(),
            'total_items' => $suppliers->sum('items_count'),
            'total_stock_value' => $supplierStats->sum('total_value'),
            'suppliers_with_low_stock' => $supplierStats->where('low_stock_items', '>', 0)->count(),
        ];

        return view('reports.inventory.supplier', compact('supplierStats', 'statistics'));
    }

    /**
     * Material Issue Report.
     */
    public function materialIssueReport(Request $request)
    {
        $query = JobAssignMaterialIssue::with(['jobSchedule', 'customer', 'building', 'team', 'masterRental']);

        // Filter by issued status
        if ($request->filled('issued')) {
            $query->where('issued', $request->issued);
        }

        // Filter by team
        if ($request->filled('team_name')) {
            $query->where('team_name', 'like', "%{$request->team_name}%");
        }

        // Filter by item
        if ($request->filled('item_name')) {
            $query->where('item_name', 'like', "%{$request->item_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('job_date', [$request->start_date, $request->end_date]);
        }

        $issues = $query->orderBy('job_date', 'desc')->paginate(15);

        $statistics = [
            'total' => JobAssignMaterialIssue::count(),
            'issued' => JobAssignMaterialIssue::where('issued', true)->count(),
            'not_issued' => JobAssignMaterialIssue::where('issued', false)->count(),
            'total_quantity' => JobAssignMaterialIssue::sum('quantity'),
            'issued_quantity' => JobAssignMaterialIssue::where('issued', true)->sum('quantity'),
            'today' => JobAssignMaterialIssue::whereDate('job_date', today())->count(),
            'this_week' => JobAssignMaterialIssue::whereBetween('job_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('reports.inventory.material-issue', compact('issues', 'statistics'));
    }

    /**
     * Stock Movement Report.
     */
    public function stockMovementReport(Request $request)
    {
        // This would typically involve a stock_movements table
        // For now, we'll use material issues as a proxy for stock movements
        
        $query = JobAssignMaterialIssue::with(['masterRental'])
            ->where('issued', true);

        // Filter by item
        if ($request->filled('item_name')) {
            $query->where('item_name', 'like', "%{$request->item_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('job_date', [$request->start_date, $request->end_date]);
        }

        $movements = $query->orderBy('job_date', 'desc')->paginate(15);

        // Group by item for summary
        $itemMovements = $query->get()->groupBy('item_name')->map(function ($itemIssues, $itemName) {
            return [
                'item_name' => $itemName,
                'total_quantity' => $itemIssues->sum('quantity'),
                'total_issues' => $itemIssues->count(),
                'average_quantity' => $itemIssues->avg('quantity'),
                'last_issue_date' => $itemIssues->max('job_date'),
            ];
        });

        $statistics = [
            'total_movements' => $movements->total(),
            'total_quantity' => $movements->getCollection()->sum('quantity'),
            'unique_items' => $itemMovements->count(),
            'today' => JobAssignMaterialIssue::where('issued', true)->whereDate('job_date', today())->count(),
            'this_week' => JobAssignMaterialIssue::where('issued', true)->whereBetween('job_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('reports.inventory.stock-movement', compact('movements', 'itemMovements', 'statistics'));
    }

    /**
     * Low Stock Alert Report.
     */
    public function lowStockAlertReport(Request $request)
    {
        $query = MasterRental::with(['category', 'brand', 'supplier'])
            ->where('stock_quantity', '<=', DB::raw('minimum_stock'));

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $lowStockItems = $query->orderBy('stock_quantity')->paginate(15);
        $categories = MasterRentalCategory::orderBy('category_name')->get();
        $brands = MasterRentalBrand::orderBy('brand_name')->get();
        $suppliers = MasterRentalSupplier::orderBy('supplier_name')->get();

        $statistics = [
            'total_low_stock' => MasterRental::where('stock_quantity', '<=', DB::raw('minimum_stock'))->count(),
            'out_of_stock' => MasterRental::where('stock_quantity', 0)->count(),
            'critical_stock' => MasterRental::where('stock_quantity', '<=', DB::raw('minimum_stock * 0.5'))->count(),
            'total_value_at_risk' => MasterRental::where('stock_quantity', '<=', DB::raw('minimum_stock'))
                ->sum(DB::raw('(minimum_stock - stock_quantity) * unit_price')),
        ];

        return view('reports.inventory.low-stock-alert', compact('lowStockItems', 'categories', 'brands', 'suppliers', 'statistics'));
    }

    /**
     * Export Stock Report.
     */
    public function exportStockReport(Request $request)
    {
        $query = MasterRental::with(['category', 'brand', 'supplier']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'low':
                    $query->where('stock_quantity', '<=', DB::raw('minimum_stock'));
                    break;
                case 'out':
                    $query->where('stock_quantity', 0);
                    break;
                case 'normal':
                    $query->where('stock_quantity', '>', DB::raw('minimum_stock'));
                    break;
            }
        }

        $items = $query->orderBy('item_name')->get();

        $fileName = 'stock_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $items,
        ]);
    }

    /**
     * Export Material Issue Report.
     */
    public function exportMaterialIssueReport(Request $request)
    {
        $query = JobAssignMaterialIssue::with(['jobSchedule', 'customer', 'building', 'team', 'masterRental']);

        // Apply filters
        if ($request->filled('issued')) {
            $query->where('issued', $request->issued);
        }

        if ($request->filled('team_name')) {
            $query->where('team_name', 'like', "%{$request->team_name}%");
        }

        if ($request->filled('item_name')) {
            $query->where('item_name', 'like', "%{$request->item_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('job_date', [$request->start_date, $request->end_date]);
        }

        $issues = $query->orderBy('job_date', 'desc')->get();

        $fileName = 'material_issue_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $issues,
        ]);
    }

    /**
     * Get inventory statistics for API.
     */
    public function getInventoryStatistics()
    {
        $statistics = [
            'stock' => [
                'total_items' => MasterRental::count(),
                'active_items' => MasterRental::where('status', 'active')->count(),
                'inactive_items' => MasterRental::where('status', 'inactive')->count(),
                'low_stock_items' => MasterRental::where('stock_quantity', '<=', DB::raw('minimum_stock'))->count(),
                'out_of_stock_items' => MasterRental::where('stock_quantity', 0)->count(),
                'total_stock_value' => MasterRental::sum(DB::raw('stock_quantity * unit_price')),
            ],
            'categories' => [
                'total' => MasterRentalCategory::count(),
                'with_items' => MasterRentalCategory::has('items')->count(),
            ],
            'brands' => [
                'total' => MasterRentalBrand::count(),
                'with_items' => MasterRentalBrand::has('items')->count(),
            ],
            'suppliers' => [
                'total' => MasterRentalSupplier::count(),
                'with_items' => MasterRentalSupplier::has('items')->count(),
            ],
            'material_issues' => [
                'total' => JobAssignMaterialIssue::count(),
                'issued' => JobAssignMaterialIssue::where('issued', true)->count(),
                'not_issued' => JobAssignMaterialIssue::where('issued', false)->count(),
                'total_quantity' => JobAssignMaterialIssue::sum('quantity'),
                'issued_quantity' => JobAssignMaterialIssue::where('issued', true)->sum('quantity'),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }
}
