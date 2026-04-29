<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

trait DataTableTrait
{
    /**
     * Process DataTables server-side request
     *
     * @param Builder $query
     * @param array $searchableColumns
     * @param array $columnMapping (optional) - Maps column index to database column name
     * @return \Illuminate\Http\JsonResponse
     */
    public function dataTableResponse(Builder $query, array $searchableColumns, array $columnMapping = [])
    {
        $request = request();
        
        // Get DataTables parameters
        $draw = $request->get('draw');
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');
        $order = $request->get('order', []);
        $columns = $request->get('columns', []);
        
        // Clone query untuk count (prevent query corruption)
        $totalRecords = (clone $query)->count();
        
        // Global search
        if (!empty($search['value'])) {
            $query->where(function($q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search['value'] . '%');
                }
            });
        }
        
        // Individual column search (filter per kolom)
        foreach ($columns as $index => $column) {
            if (!empty($column['search']['value'])) {
                $searchValue = $column['search']['value'];
                
                // Get the actual column name from mapping or use column data
                $columnName = $columnMapping[$index] ?? $column['data'];
                
                if (!empty($columnName) && in_array($columnName, $searchableColumns)) {
                    $query->where($columnName, 'LIKE', '%' . $searchValue . '%');
                }
            }
        }
        
        // Clone query untuk filtered count (prevent query corruption)
        $totalFiltered = (clone $query)->count();
        
        // Ordering
        if (!empty($order)) {
            foreach ($order as $orderItem) {
                $columnIndex = $orderItem['column'];
                $columnName = $columnMapping[$columnIndex] ?? $columns[$columnIndex]['data'];
                $direction = $orderItem['dir'];
                
                if (!empty($columnName) && $columnName !== 'branch' && $columnName !== 'warehouse_type') {
                    $query->orderBy($columnName, $direction);
                }
            }
        } else {
            // Default ordering
            $query->orderBy('created_at', 'desc');
        }
        
        // Pagination
        $data = $query->skip($start)
                     ->take($length)
                     ->get();
        
        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }
}

