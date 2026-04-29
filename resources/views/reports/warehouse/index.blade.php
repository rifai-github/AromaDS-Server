@extends('layouts.app')

@section('title', 'Warehouse Report')
@section('breadcrumb', 'Home / Report / Warehouse Report')

@section('content')
<style>
    .report-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    
    .report-section {
        margin-bottom: 30px;
    }
    
    .report-section h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .filter-section {
        background-color: #f9fafb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        font-weight: 500;
        color: #374151;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .filter-input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }
    
    .filter-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        transition: border-color 0.2s ease;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .btn-primary {
        background-color: #214589;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .btn-primary:hover {
        background-color: #1e3a8a;
    }
    
    .btn-secondary {
        background-color: #6b7280;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    
    .btn-success {
        background-color: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-success:hover {
        background-color: #059669;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #214589;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }
    
    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .report-table th {
        background-color: #214589;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 500;
        font-size: 14px;
    }
    
    .report-table td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
    }
    
    .report-table tr:hover {
        background-color: #f9fafb;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
</style>

<div class="flex flex-col   w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Warehouse Report</p>
            </div>
            
            <div class="flex flex-row justify-end items-center w-auto gap-2">
                <a href="{{ route('reports.index') }}" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Reports
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Warehouse Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_warehouses'] ?? 0 }}</div>
                        <div class="stat-label">Total Warehouses</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_products'] ?? 0 }}</div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_stock_opnames'] ?? 0 }}</div>
                        <div class="stat-label">Stock Opnames</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $statistics['total_movements'] ?? 0 }}</div>
                        <div class="stat-label">Stock Movements</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Opname Report -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Stock Opname Report</h3>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="{{ route('reports.warehouse-reports.stock-opname') }}" method="GET" id="stockOpnameForm">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label class="filter-label">Warehouse</label>
                                <select name="warehouse_id" class="filter-select">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Product</label>
                                <select name="product_id" class="filter-select">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Start Date</label>
                                <input type="date" name="start_date" class="filter-input" value="{{ request('start_date') }}">
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">End Date</label>
                                <input type="date" name="end_date" class="filter-input" value="{{ request('end_date') }}">
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-search mr-2"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Report Table -->
                @if(isset($stockOpnames) && $stockOpnames->count() > 0)
                <div class="overflow-x-auto">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Stock Opname No</th>
                                <th>Warehouse</th>
                                <th>Product</th>
                                <th>Qty Warehouse</th>
                                <th>Qty Opname</th>
                                <th>Qty Selisih</th>
                                <th>Created By</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockOpnames as $opname)
                            <tr>
                                <td>{{ $opname->stock_opname_number ?? 'N/A' }}</td>
                                <td>{{ $opname->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ $opname->product->name ?? 'N/A' }}</td>
                                <td>{{ $opname->qty_warehouse ?? 0 }}</td>
                                <td>{{ $opname->qty_opname ?? 0 }}</td>
                                <td class="{{ ($opname->qty_selisih ?? 0) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $opname->qty_selisih ?? 0 }}
                                </td>
                                <td>{{ $opname->createdBy->name ?? 'N/A' }}</td>
                                <td>{{ $opname->created_at ? $opname->created_at->format('d M Y H:i') : 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Export Buttons -->
                <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200 mt-6">
                    <a href="{{ route('reports.warehouse-reports.export.stock-opname') }}?{{ http_build_query(request()->all()) }}" class="btn-success">
                        <i class="fas fa-download mr-2"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('reports.warehouse-reports.export.stock-opname.pdf') }}?{{ http_build_query(request()->all()) }}" class="btn-secondary">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500">No stock opname data found for the selected filters.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Stock Report -->
        <div class="report-container w-full">
            <div class="report-section">
                <h3>Stock Report</h3>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="{{ route('reports.warehouse-reports.stock') }}" method="GET" id="stockForm">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label class="filter-label">Warehouse</label>
                                <select name="warehouse_id" class="filter-select">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Product</label>
                                <select name="product_id" class="filter-select">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-search mr-2"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Report Table -->
                @if(isset($stocks) && $stocks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Product</th>
                                <th>Product Category</th>
                                <th>Unit</th>
                                <th>Qty Awal</th>
                                <th>Qty Penerimaan</th>
                                <th>Qty Pengeluaran</th>
                                <th>Qty Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                            <tr>
                                <td>{{ $stock->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ $stock->product->name ?? 'N/A' }}</td>
                                <td>{{ $stock->product->productCategory->name ?? 'N/A' }}</td>
                                <td>{{ $stock->product->unit ?? 'N/A' }}</td>
                                <td>{{ $stock->qty_awal ?? 0 }}</td>
                                <td>{{ $stock->qty_penerimaan ?? 0 }}</td>
                                <td>{{ $stock->qty_pengeluaran ?? 0 }}</td>
                                <td class="{{ ($stock->qty_sisa ?? 0) < 10 ? 'text-red-600 font-bold' : '' }}">
                                    {{ $stock->qty_sisa ?? 0 }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Export Buttons -->
                <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200 mt-6">
                    <a href="{{ route('reports.warehouse-reports.export.stock') }}?{{ http_build_query(request()->all()) }}" class="btn-success">
                        <i class="fas fa-download mr-2"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('reports.warehouse-reports.export.stock.pdf') }}?{{ http_build_query(request()->all()) }}" class="btn-secondary">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500">No stock data found for the selected filters.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for date ranges
    const forms = ['stockOpnameForm', 'stockForm'];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                const startDate = form.querySelector('input[name="start_date"]');
                const endDate = form.querySelector('input[name="end_date"]');
                
                if (startDate && endDate && startDate.value && endDate.value) {
                    if (new Date(startDate.value) > new Date(endDate.value)) {
                        e.preventDefault();
                        alert('Start date must be before or equal to end date.');
                    }
                }
            });
        }
    });
});
</script>
@endsection
