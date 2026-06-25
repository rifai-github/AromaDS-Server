@extends('layouts.app')

@section('title', 'Stock')
@section('breadcrumb', 'Home / Marketing / Stock')

@section('content')
<style>
    html, body { overflow-x: hidden; max-width: 100vw; }
    *, *::before, *::after { box-sizing: border-box; }

    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }

    .responsive-table {
        width: 100%;
        border-collapse: collapse;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        font-size: 14px;
        white-space: nowrap;
    }

    .responsive-table thead th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #d1d5db;
        background-color: #f3f4f6;
        color: #374151;
    }

    .stock-low { color: #dc2626; font-weight: 600; }
</style>

<div class="w-full">
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
        <h1 class="text-xl font-semibold text-[#214589]">Stock Gudang</h1>
    </div>

    <form method="GET" action="{{ route('marketing.stock-view.index') }}" class="flex flex-row flex-wrap gap-3 items-center w-full p-4 bg-white">
        <select name="warehouse_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">Semua Gudang</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" {{ (string) request('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                </option>
            @endforeach
        </select>

        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/kode barang..." class="border border-gray-300 rounded-md px-3 py-2 text-sm">

        <button type="submit" class="btn">
            <i class="fas fa-search"></i> Cari
        </button>

        @if(request('warehouse_id') || request('search'))
            <a href="{{ route('marketing.stock-view.index') }}" class="btn">Reset</a>
        @endif
    </form>

    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Kode (SKU)</th>
                    <th>Gudang</th>
                    <th>Qty Stock</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr>
                        <td>{{ $stock->masterProduct->name ?? '-' }}</td>
                        <td>{{ $stock->masterProduct->sku ?? '-' }}</td>
                        <td>{{ $stock->warehouse->name ?? '-' }}</td>
                        <td class="{{ $stock->quantity <= ($stock->minimum_stock ?? 0) ? 'stock-low' : '' }}">
                            {{ number_format($stock->quantity, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Tidak ada data stock.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 bg-white rounded-b-[10px]">
        {{ $stocks->links() }}
    </div>
</div>
@endsection
