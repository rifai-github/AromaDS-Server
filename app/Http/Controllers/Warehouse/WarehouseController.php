<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Branch;
use App\Models\User;
use App\Models\MasterProduct;
use App\Models\WarehouseType;
use App\Services\Warehouse\BranchWarehouseResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    private const BRANCH_WAREHOUSE_TYPE_CODE = 'BRANCH';
    private const CENTER_WAREHOUSE_TYPE_CODE = 'CENTER';

    use ColumnFilterTrait, \App\Http\Traits\AccessControlFilterTrait;

    public function index(Request $request)
    {
        // Optimized JSON response for Select Options / Dropdowns
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Warehouse::select('id', 'name', 'warehouse_code', 'branch_id')
                ->where('is_active', true)
                ->orderBy('name');

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // Apply Access Control for dropdowns
            $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'branch_id', null, 'id');

            return response()->json([
                'status' => 'success',
                'data' => $query->get()
            ]);
        }

        $query = Warehouse::with(['branch', 'warehouseType', 'managerUser', 'updatedBy', 'createdBy']);

        // Apply column filters
        $this->applyColumnFilters($query, 'warehousesTable', [
            'warehouse_code' => ['column' => 'warehouse_code'],
            'name' => ['column' => 'name'],
            'branch__name' => ['relation' => 'branch', 'column' => 'name'],
            'warehouseType__name' => ['relation' => 'warehouseType', 'column' => 'name'],
            'address' => ['column' => 'address'],
            'manager' => ['column' => 'manager'], // String column
            'is_center' => ['column' => 'is_center', 'boolean' => true],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
            'createdBy__name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updatedBy__name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ]);

        // Apply Access Control
        // Uses 'branch_id' for branch separation and 'id' (itself) for warehouse manager matching
        $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'branch_id', null, 'id');

        // Filtering
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // [Global Soft Delete] Default Filter: Active Only
        // If no is_active/status filter is present, default to active
        $isActiveKey = null;
        if ($request->has('is_active')) $isActiveKey = 'is_active';
        elseif ($request->has('status')) $isActiveKey = 'status';

        if (!$isActiveKey) {
             // Default to Active
             $request->merge(['is_active' => '1']);
             $query->where('is_active', true);
        } elseif ($request->input($isActiveKey) === 'all') {
            // Show all (do nothing to query)
        } else {
            // Handle specific status (active/inactive)
            $val = $request->input($isActiveKey);
            $boolVal = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            // Handle 'active'/'inactive' strings if passed
            if ($val === 'active') $boolVal = true;
            if ($val === 'inactive') $boolVal = false;
            
            $query->where('is_active', $boolVal);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('warehouse_code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%')
                  ->orWhereHas('branch', function ($branchQuery) use ($search) {
                      $branchQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $warehouses = $query->orderBy('created_at', 'desc')->paginate(15);

        $branches = Branch::all();
        $users = User::all();

        return view('warehouse.warehouses.index', compact('warehouses', 'branches', 'users'));
    }

    public function create()
    {
        $branches = Branch::all();
        $users = User::all();
        $warehouseTypes = WarehouseType::active()->get();

        // Return JSON for API requests
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'branches' => $branches,
                    'users' => $users,
                    'warehouse_types' => $warehouseTypes,
                    'has_center_warehouse' => Warehouse::hasCenterWarehouse()
                ]
            ]);
        }

        return view('warehouse.warehouses.create', compact('branches', 'users', 'warehouseTypes'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'warehouse_type_id' => $request->input('warehouse_type_id')
                ?: $this->resolveDefaultWarehouseTypeId($request),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'warehouse_type_id' => 'required|exists:warehouse_types,id',
            'warehouse_code' => 'nullable|string|max:50|unique:warehouses,warehouse_code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'manager' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
            'is_center' => 'required|boolean',
        ]);

        // Custom validation: Only one center warehouse allowed
        if ($request->is_center && Warehouse::hasCenterWarehouse()) {
            $validator->after(function ($validator) {
                $validator->errors()->add('is_center', 'A center warehouse already exists. Only one center warehouse is allowed.');
            });
        }

        $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        if ($isActive && app(BranchWarehouseResolver::class)->activeCountForBranch($request->branch_id) > 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('branch_id', 'Branch ini sudah memiliki warehouse aktif. Nonaktifkan warehouse lama sebelum membuat warehouse aktif baru.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Use provided warehouse code or auto-generate
        $warehouseCode = $request->warehouse_code ?: $this->generateWarehouseCode();

        $warehouse = Warehouse::create([
            'warehouse_code' => $warehouseCode,
            'name' => $request->name,
            'branch_id' => $request->branch_id,
            'warehouse_type_id' => $request->warehouse_type_id,
            'address' => $request->address,
            'phone' => $request->phone,
            'manager' => $request->manager,
            'is_active' => $request->is_active,
            'is_center' => $request->is_center,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Sync admins if provided
        if ($request->has('admins')) {
            $warehouse->admins()->sync($request->admins);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse created successfully',
            'data' => $warehouse->load(['branch', 'warehouseType', 'createdBy', 'updatedBy'])
        ]);
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load([
            'branch', 
            'warehouseType', 
            'updatedBy', 
            'createdBy',
            'managerUser',
            'admins',
            'warehouseProducts.masterProduct.productCategory',
            'warehouseProducts.masterProduct.packagingSize'
        ]);
        
        // Return JSON for API requests
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $warehouse
            ]);
        }
        
        // Return view for web requests
        $warehouse->load([
            'inventoryMovements' => function($query) {
                $query->with('masterProduct.productCategory')
                      ->orderBy('created_at', 'desc')
                      ->limit(100); // Limit to recent 100 movements for performance
            },
            'stockOpnames',
            'stockAdjustments',
            'serialNumbers.masterProduct'
        ]);
        
        // Get warehouse statistics from warehouseProducts
        $totalProducts = $warehouse->warehouseProducts()->count();
        $totalStock = $warehouse->warehouseProducts()->sum('quantity');
        $lowStockProducts = $warehouse->warehouseProducts()
            ->whereColumn('quantity', '<=', 'minimum_stock')
            ->where('quantity', '>', 0)
            ->count();
        $outOfStockProducts = $warehouse->warehouseProducts()
            ->where('quantity', '<=', 0)
            ->count();

        return view('warehouse.warehouses.show', compact('warehouse', 'totalProducts', 'totalStock', 'lowStockProducts', 'outOfStockProducts'));
    }

    public function edit(Warehouse $warehouse)
    {
        $branches = Branch::all();
        $users = User::all();
        $warehouseTypes = WarehouseType::active()->get();

        // Return JSON for API requests
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'warehouse' => $warehouse->load(['branch', 'warehouseType', 'updatedBy', 'createdBy', 'managerUser', 'admins']),
                    'branches' => $branches,
                    'users' => $users,
                    'warehouse_types' => $warehouseTypes,
                    'has_center_warehouse' => Warehouse::hasCenterWarehouse()
                ]
            ]);
        }

        return view('warehouse.warehouses.edit', compact('warehouse', 'branches', 'users', 'warehouseTypes'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->merge([
            'warehouse_type_id' => $request->input('warehouse_type_id')
                ?: $this->resolveDefaultWarehouseTypeId($request),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'warehouse_type_id' => 'required|exists:warehouse_types,id',
            'warehouse_code' => 'nullable|string|max:50|unique:warehouses,warehouse_code,' . $warehouse->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'manager' => 'nullable|string|max:100',
            'is_active' => 'required|in:0,1,true,false',
            'is_center' => 'required|in:0,1,true,false',
        ]);

        // Custom validation: Only one center warehouse allowed
        $isCenter = filter_var($request->is_center, FILTER_VALIDATE_BOOLEAN);
        if ($isCenter && Warehouse::where('is_center', true)->where('id', '!=', $warehouse->id)->exists()) {
            $validator->after(function ($validator) {
                $validator->errors()->add('is_center', 'A center warehouse already exists. Only one center warehouse is allowed.');
            });
        }

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Use provided warehouse code or keep existing
        $warehouseCode = $request->warehouse_code ?: $warehouse->warehouse_code;

        // Convert boolean fields
        $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        $isCenter = filter_var($request->is_center, FILTER_VALIDATE_BOOLEAN);

        if ($isActive && app(BranchWarehouseResolver::class)->activeCountForBranch($request->branch_id, $warehouse->id) > 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('branch_id', 'Branch ini sudah memiliki warehouse aktif. Nonaktifkan warehouse lama sebelum mengaktifkan warehouse ini.');
            });
        }

        if ($warehouse->is_active && ! $isActive && ! $this->canDeactivateWarehouse($warehouse)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('is_active', 'Warehouse tidak bisa dinonaktifkan karena masih memiliki stok, SN aktif, atau dokumen inventory terbuka.');
            });
        }

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $warehouse->update([
            'warehouse_code' => $warehouseCode,
            'name' => $request->name,
            'branch_id' => $request->branch_id,
            'warehouse_type_id' => $request->warehouse_type_id,
            'address' => $request->address,
            'phone' => $request->phone,
            'manager' => $request->manager,
            'is_active' => $isActive,
            'is_center' => $isCenter,
            'updated_by' => Auth::id(),
        ]);

        // Sync admins if provided
        if ($request->has('admins')) {
            $warehouse->admins()->sync($request->admins);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Warehouse updated successfully',
                'data' => $warehouse->load(['branch', 'warehouseType', 'createdBy', 'updatedBy'])
            ]);
        }

        return redirect()->route('warehouse.warehouses.show', $warehouse->id)
            ->with('success', 'Warehouse updated successfully');
    }



    public function dashboard()
    {
        $totalWarehouses = Warehouse::count();
        $activeWarehouses = Warehouse::where('status', 'Active')->count();
        $inactiveWarehouses = Warehouse::where('status', 'Inactive')->count();

        $totalProducts = \App\Models\WarehouseProduct::sum('quantity');
        $totalValue = \App\Models\WarehouseProduct::join('master_products', 'warehouse_products.master_product_id', '=', 'master_products.id')
            ->selectRaw('SUM(warehouse_products.quantity * master_products.price) as total_value')
            ->first()->total_value ?? 0;

        $lowStockProducts = \App\Models\WarehouseProduct::where('quantity', '<=', 10)->count();
        $outOfStockProducts = \App\Models\WarehouseProduct::where('quantity', 0)->count();

        $recentWarehouses = Warehouse::with(['branch', 'pic'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $warehouseStats = Warehouse::with(['products'])
            ->get()
            ->map(function ($warehouse) {
                return [
                    'warehouse_name' => $warehouse->warehouse_name,
                    'total_products' => $warehouse->products->count(),
                    'total_stock' => $warehouse->products->sum('quantity'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_warehouses' => $totalWarehouses,
                'active_warehouses' => $activeWarehouses,
                'inactive_warehouses' => $inactiveWarehouses,
                'total_products' => $totalProducts,
                'total_value' => $totalValue,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'recent_warehouses' => $recentWarehouses,
                'warehouse_stats' => $warehouseStats
            ]
        ]);
    }

    public function getWarehouses(Request $request)
    {
        $query = Warehouse::where('is_active', true)
            ->with(['branch'])
            ->orderBy('name');

        // Apply Access Control
        $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'branch_id', null, 'id');

        $warehouses = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $warehouses
        ]);
    }

    private function generateWarehouseCode()
    {
        $prefix = 'WH';
        $year = date('Y');
        $month = date('m');
        
        $lastWarehouse = Warehouse::where('warehouse_code', 'like', $prefix . $year . $month . '%')
            ->orderBy('warehouse_code', 'desc')
            ->first();

        if ($lastWarehouse) {
            $lastNumber = intval(substr($lastWarehouse->warehouse_code, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Show detail stock for a specific product in warehouse
     */
    public function detailStock(Warehouse $warehouse, $productId)
    {
        $warehouse->load('admins');
        // Find the product with packaging size relationship
        $product = MasterProduct::with('packagingSize')->findOrFail($productId);
        
        // Get warehouse product
        $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
            ->where('master_product_id', $productId)
            ->first();
        
        if (!$warehouseProduct) {
            return back()->with('error', 'Product not found in this warehouse.');
        }
        
        // Load inventory movements for this product in this warehouse
        $movements = \App\Models\InventoryMovement::where('warehouse_id', $warehouse->id)
            ->where('master_product_id', $productId)
            ->with(['creator', 'updater', 'masterProduct'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        
        // Load serial numbers for this product in this warehouse
        // Eager load unitOnWalls with status filter for better performance
        $serialNumbers = \App\Models\SerialNumber::where('warehouse_id', $warehouse->id)
            ->where('master_product_id', $productId)
            ->with([
                'masterProduct', 
                'inventoryReceiving', 
                'unitOnWalls' => function($q) {
                    $q->where('status', 'active'); // Only load active unit on walls
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Format movements with description including SN info
        $formattedMovements = $movements->map(function($movement) use ($productId) {
            $description = $movement->notes ?? '';
            $userName = $movement->creator->name ?? ($movement->updater->name ?? 'System');
            
            // Try to get SN info from reference
            if ($movement->reference_type === 'inventory_receiving' && $movement->reference_no) {
                $receiving = \App\Models\InventoryReceiving::where('receiving_number', $movement->reference_no)->first();
                if ($receiving) {
                    // Get SNs yang diinput di receiving ini untuk product ini
                    $sns = \App\Models\SerialNumber::where('inventory_receiving_id', $receiving->id)
                        ->where('master_product_id', $productId)
                        ->pluck('serial_number')
                        ->toArray();
                    
                    if (!empty($sns)) {
                        $snList = implode(', ', $sns);
                        $description = "{$movement->reference_no} - Produk baru dengan nomor SN {$snList}, ditambah dari {$userName}.";
                    } else {
                        $description = "{$movement->reference_no} - Produk baru, ditambah dari {$userName}.";
                    }
                } else {
                    // Fallback jika receiving tidak ditemukan
                    $description = "{$movement->reference_no} - Produk baru, ditambah dari {$userName}.";
                }
            } elseif ($movement->reference_type === 'inventory_issuing' && $movement->reference_no) {
                $issuing = \App\Models\InventoryIssuing::where('issuing_number', $movement->reference_no)->first();
                if ($issuing) {
                    // Get SNs yang di-scan di issuing ini untuk product ini
                    $issuingItems = \App\Models\InventoryIssuingItem::where('inventory_issuing_id', $issuing->id)
                        ->where('product_id', $productId)
                        ->whereNotNull('serial_number_id')
                        ->with('serialNumber')
                        ->get();
                    
                    $sns = $issuingItems->map(function($item) {
                        return $item->serialNumber->serial_number ?? null;
                    })->filter()->unique()->values()->toArray();
                    
                    if (!empty($sns)) {
                        $snList = implode(', ', $sns);
                        $description = "{$movement->reference_no} - Produk dengan nomor SN {$snList}, diambil oleh {$userName}.";
                    } else {
                        $description = "{$movement->reference_no} - Produk, diambil oleh {$userName}.";
                    }
                } else {
                    // Fallback jika issuing tidak ditemukan
                    $description = "{$movement->reference_no} - Produk, diambil oleh {$userName}.";
                }
            } else {
                // Untuk movement type lain, gunakan notes yang ada atau buat description default
                if (empty($description)) {
                    $typeText = $movement->movement_type === 'in' ? 'masuk' : 'keluar';
                    $referenceNo = $movement->reference_no ?? 'N/A';
                    $description = "Stock {$typeText} - {$referenceNo}, oleh {$userName}.";
                }
            }
            
            return [
                'id' => $movement->id,
                'date' => $movement->created_at,
                'adjustment' => $movement->quantity,
                'description' => $description,
                'updated_by' => $movement->updater->name ?? $movement->creator->name ?? 'System',
                'updated_at' => $movement->updated_at ?? $movement->created_at,
            ];
        });
        
        // Format serial numbers with status
        $formattedSerialNumbers = $serialNumbers->map(function($sn) {
            // Transfer status: unit on wall atau warehouse
            // Cek apakah SN sudah terpasang di unit on wall
            // Check if relationship is already loaded first (to avoid N+1 queries)
            $unitOnWall = null;
            if ($sn->relationLoaded('unitOnWalls')) {
                $unitOnWall = $sn->unitOnWalls->first(function($uow) {
                    return $uow->status === 'active';
                });
            } else {
                $unitOnWall = $sn->unitOnWalls()->where('status', 'active')->first();
            }
            
            $transferStatus = 'in warehouse';
            if ($unitOnWall) {
                $transferStatus = 'unit on wall';
            } elseif ($sn->location_type === 'technician') {
                $transferStatus = 'with technician';
            }
            
            // Unit status: ready, broken, on service, in_use, on hand
            // Active Unit On Wall is the source of truth: installed units must remain "in use"
            // even if the SN status was temporarily changed to on_hand/on_hand_remove by an RF flow.
            $snStatus = strtolower($sn->status ?? 'ready');
            
            if ($unitOnWall) {
                $unitStatus = 'in use';
            } elseif ($snStatus === 'on_hand' || $snStatus === 'on_hand_remove') {
                $unitStatus = 'on hand';
            } else {
                $unitStatus = match (true) {
                    in_array($snStatus, ['broken', 'damaged'], true) => 'broken',
                    in_array($snStatus, ['on_service', 'maintenance', 'in_repair'], true) => 'on service',
                    $snStatus === 'in_use' => 'in use',
                    in_array($snStatus, ['ready', 'available', 'in_stock'], true) => 'ready',
                    default => $snStatus ?: 'unknown',
                };
            }
            
            return [
                'id' => $sn->id,
                'serial_number' => $sn->serial_number,
                'unit_status' => $unitStatus,
                'transfer_status' => $transferStatus,
                'status' => $sn->status,
                'created_at' => $sn->created_at,
            ];
        });
        
        return view('warehouse.warehouses.detail-stock', compact(
            'warehouse',
            'product',
            'warehouseProduct',
            'formattedMovements',
            'formattedSerialNumbers'
        ));
    }

    /**
     * Export stock to Excel (.xlsx) using PhpSpreadsheet
     * Format same as Stock Opname export for consistency
     */
    public function exportStock(Warehouse $warehouse)
    {
        $warehouse->load(['branch', 'managerUser']);
        
        // Get ONLY products that exist in this warehouse
        $warehouseProducts = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
            ->with(['masterProduct' => function($q) {
                $q->where('is_active', true);
            }])
            ->whereHas('masterProduct', function($q) {
                $q->where('is_active', true);
            })
            ->get()
            ->sortBy(function($wp) {
                return $wp->masterProduct->name ?? '';
            });
        
        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Opname');
        
        // Header info
        $sheet->setCellValue('A1', 'FORM STOCK OPNAME');
        $sheet->setCellValue('A2', 'Warehouse Name');
        $sheet->setCellValue('B2', ': ' . ($warehouse->name ?? '-'));
        $sheet->setCellValue('A3', 'Warehouse Code');
        $sheet->setCellValue('B3', ': ' . ($warehouse->warehouse_code ?? '-'));
        $sheet->setCellValue('A4', 'Branch');
        $sheet->setCellValue('B4', ': ' . ($warehouse->branch->name ?? '-'));
        $sheet->setCellValue('A5', 'Manager');
        $sheet->setCellValue('B5', ': ' . ($warehouse->managerUser->name ?? '-'));
        $sheet->setCellValue('A6', 'Date Export');
        $sheet->setCellValue('B6', ': ' . date('d M Y H:i'));
        $sheet->setCellValue('A7', 'Total Products');
        $sheet->setCellValue('B7', ': ' . $warehouseProducts->count() . ' produk');
        
        // Bold title
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Data header row at row 9
        $headerRow = 9;
        $sheet->setCellValue('A' . $headerRow, 'No');
        $sheet->setCellValue('B' . $headerRow, 'Product Code');
        $sheet->setCellValue('C' . $headerRow, 'Product Name');
        $sheet->setCellValue('D' . $headerRow, 'Stock Fisik');
        
        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A' . $headerRow . ':D' . $headerRow)->applyFromArray($headerStyle);
        
        // Data rows
        $row = $headerRow + 1;
        $index = 1;
        foreach ($warehouseProducts as $wp) {
            $product = $wp->masterProduct;
            if (!$product) continue;
            
            $sheet->setCellValue('A' . $row, $index);
            $sheet->setCellValue('B' . $row, $product->sku ?? ($product->sku_code ?? '-'));
            $sheet->setCellValue('C' . $row, $product->name ?? '-');
            $sheet->setCellValue('D' . $row, ''); // Empty for user to fill
            
            // Add borders to data rows
            $sheet->getStyle('A' . $row . ':D' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $row++;
            $index++;
        }
        
        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Center the No column
        $sheet->getStyle('A' . $headerRow . ':A' . ($row - 1))->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Generate filename
        $filename = 'Export_Stock_' . str_replace([' ', '/', '\\'], '_', $warehouse->name) . '_' . date('Ymd_His') . '.xlsx';
        
        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    public function destroy(Warehouse $warehouse)
    {
        try {
            // Checks:
            // 1. Is it a center warehouse?
            if ($warehouse->is_center) {
                $errorMessage = 'Cannot deactivate center warehouse.';
                 if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errors' => [$errorMessage]
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }
            
            if (! $this->canDeactivateWarehouse($warehouse)) {
                $errorMessage = 'Cannot deactivate warehouse with remaining stock, active serial numbers, or open inventory documents.';
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errors' => [$errorMessage]
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }

            // Perform Deactivation
            $warehouse->update(['is_active' => false]);
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Warehouse successfully deactivated.'
                ]);
            }

            return back()->with('success', 'Warehouse successfully deactivated.');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage(),
                    'errors' => ['Error: ' . $e->getMessage()]
                ], 500);
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:warehouses,id'
        ]);

        try {
            DB::beginTransaction();

            $ids = $request->ids;
            $errors = [];
            $processedIds = [];
            
            // Filter invalid deletions
            foreach ($ids as $id) {
                $warehouse = Warehouse::find($id);
                if (!$warehouse) continue;

                if ($warehouse->is_center) {
                    $errors[] = "Warehouse '{$warehouse->name}': Cannot deactivate center warehouse.";
                    continue;
                }
                
                if (! $this->canDeactivateWarehouse($warehouse)) {
                    $errors[] = "Warehouse '{$warehouse->name}': Cannot deactivate warehouse with remaining stock, active serial numbers, or open inventory documents.";
                    continue;
                }
                
                $processedIds[] = $id;
            }

            if (empty($processedIds) && !empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }

            // Perform deactivation on valid IDs
            Warehouse::whereIn('id', $processedIds)->update(['is_active' => false]);
            
            DB::commit();

            $count = count($processedIds);
            $message = "Successfully deactivated {$count} warehouses.";
            $success = true;
            $statusCode = 200;

            if ($count === 0 && !empty($errors)) {
                $message = "Failed to deactivate warehouses.";
                $success = false;
                $statusCode = 422;
            } elseif (!empty($errors)) {
                 $message = "Deactivated {$count} warehouses. Some failed.";
            }

            return response()->json([
                'status' => $success ? 'success' : 'error',
                'success' => $success,
                'message' => $message,
                'count' => $count,
                'errors' => $errors
            ], $statusCode);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'errors' => ['Error: ' . $e->getMessage()]
            ], 500);
        }
    }

    private function canDeactivateWarehouse(Warehouse $warehouse): bool
    {
        if ($warehouse->warehouseProducts()->where('quantity', '>', 0)->exists()) {
            return false;
        }

        if ($warehouse->serialNumbers()
            ->whereNotIn('status', ['retired'])
            ->where(function ($query) {
                $query->whereNull('location_type')
                    ->orWhereIn('location_type', ['warehouse', 'technician', 'customer']);
            })
            ->exists()) {
            return false;
        }

        if (\App\Models\InventoryIssuing::where('warehouse_id', $warehouse->id)
            ->whereNotIn('status', ['received', 'cancelled'])
            ->exists()) {
            return false;
        }

        if (\App\Models\InventoryReceiving::whereNotIn('status', ['received', 'cancelled'])
            ->where(function ($query) use ($warehouse) {
                $query->whereHas('issuing', function ($issuingQuery) use ($warehouse) {
                    $issuingQuery->where('warehouse_id', $warehouse->id);
                });

                if ($warehouse->branch_id) {
                    $query->orWhere(function ($branchQuery) use ($warehouse) {
                        $branchQuery->where('branch_id', $warehouse->branch_id)
                            ->whereNull('issuing_id');
                    });
                }
            })
            ->exists()) {
            return false;
        }

        if (\App\Models\InventoryRequest::where('warehouse_id', $warehouse->id)
            ->whereNotIn('status', ['issued', 'rejected', 'cancelled'])
            ->exists()) {
            return false;
        }

        return true;
    }

    private function resolveDefaultWarehouseTypeId(Request $request): int
    {
        $isCenter = filter_var($request->input('is_center', false), FILTER_VALIDATE_BOOLEAN);
        $code = $isCenter ? self::CENTER_WAREHOUSE_TYPE_CODE : self::BRANCH_WAREHOUSE_TYPE_CODE;
        $name = $isCenter ? 'Central Warehouse' : 'Branch Warehouse';

        $type = WarehouseType::where('code', $code)->first()
            ?: WarehouseType::where('name', $name)->first();

        if (! $type) {
            $type = WarehouseType::create([
                'code' => $code,
                'name' => $name,
                'description' => $isCenter
                    ? 'Default type for central warehouse locations.'
                    : 'Default type for single warehouse per branch flow.',
                'is_active' => true,
            ]);
        } elseif (! $type->is_active || $type->code !== $code || $type->name !== $name) {
            $type->update([
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
        }

        return $type->id;
    }
}
