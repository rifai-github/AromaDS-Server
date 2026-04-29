<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\DataExport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DataExportController extends Controller
{
    public function index(Request $request)
    {
        $query = DataExport::with(['creator']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('export_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('export_type')) {
            $query->where('export_type', $request->export_type);
        }

        if ($request->filled('file_format')) {
            $query->where('file_format', $request->file_format);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $exports = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('reports.export.index', compact('exports'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('reports.export.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'export_name' => 'required|string|max:255',
            'export_type' => 'required|in:standard,custom',
            'query' => 'required|string',
            'parameters' => 'nullable|array',
            'file_format' => 'required|in:csv,excel,pdf,json',
            'status' => 'in:pending,processing,completed,failed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $export = DataExport::create([
                'export_name' => $request->export_name,
                'export_type' => $request->export_type,
                'query' => $request->query,
                'parameters' => $request->parameters ?? [],
                'file_format' => $request->file_format,
                'status' => $request->status ?? 'pending',
                'created_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data export created successfully',
                    'data' => $export
                ]);
            }

            return redirect()->route('reports.export.index')
                           ->with('success', 'Data export created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating data export: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error creating data export: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function show($id)
    {
        $export = DataExport::with(['creator'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($export);
        }

        return view('reports.export.show', compact('export'));
    }

    public function edit($id)
    {
        $export = DataExport::findOrFail($id);
        $users = User::where('is_active', true)->get();

        if (request()->ajax()) {
            return response()->json($export);
        }

        return view('reports.export.edit', compact('export', 'users'));
    }

    public function update(Request $request, $id)
    {
        $export = DataExport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'export_name' => 'required|string|max:255',
            'export_type' => 'required|in:standard,custom',
            'query' => 'required|string',
            'parameters' => 'nullable|array',
            'file_format' => 'required|in:csv,excel,pdf,json',
            'status' => 'in:pending,processing,completed,failed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $export->update([
                'export_name' => $request->export_name,
                'export_type' => $request->export_type,
                'query' => $request->query,
                'parameters' => $request->parameters ?? $export->parameters,
                'file_format' => $request->file_format,
                'status' => $request->status ?? $export->status
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data export updated successfully',
                    'data' => $export
                ]);
            }

            return redirect()->route('reports.export.index')
                           ->with('success', 'Data export updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating data export: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error updating data export: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $export = DataExport::findOrFail($id);
            
            // Delete file if exists
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }
            
            $export->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data export deleted successfully'
                ]);
            }

            return redirect()->route('reports.export.index')
                           ->with('success', 'Data export deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting data export: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting data export: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:data_exports,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exports = DataExport::whereIn('id', $request->ids)->get();
            $count = 0;
            
            foreach ($exports as $export) {
                // Delete file if exists
                if ($export->file_path && Storage::exists($export->file_path)) {
                    Storage::delete($export->file_path);
                }
                $export->delete();
                $count++;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} data export(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting data exports: ' . $e->getMessage()
            ], 500);
        }
    }

    public function execute($id)
    {
        try {
            $export = DataExport::findOrFail($id);
            
            // Update status to processing
            $export->update(['status' => 'processing']);
            
            // Here you would implement the actual export logic
            // For now, we'll simulate the process
            
            // Simulate processing time
            sleep(2);
            
            // Generate a dummy file path
            $fileName = 'export_' . $export->id . '_' . time() . '.' . $export->file_format;
            $filePath = 'exports/' . $fileName;
            
            // Create dummy file content based on format
            $content = $this->generateExportContent($export);
            
            // Store the file
            Storage::put($filePath, $content);
            
            // Update export with file path and completed status
            $export->update([
                'status' => 'completed',
                'file_path' => $filePath
            ]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data export executed successfully',
                    'data' => $export
                ]);
            }

            return redirect()->route('reports.export.index')
                           ->with('success', 'Data export executed successfully');

        } catch (\Exception $e) {
            // Update status to failed
            $export->update(['status' => 'failed']);
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error executing data export: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error executing data export: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            $export = DataExport::findOrFail($id);
            
            if (!$export->file_path || !Storage::exists($export->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export file not found'
                ], 404);
            }

            return Storage::download($export->file_path, $export->export_name . '.' . $export->file_format);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading export file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateExportContent($export)
    {
        switch ($export->file_format) {
            case 'csv':
                return "Name,Email,Phone\nJohn Doe,john@example.com,1234567890\nJane Smith,jane@example.com,0987654321";
            case 'json':
                return json_encode([
                    ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '1234567890'],
                    ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '0987654321']
                ], JSON_PRETTY_PRINT);
            case 'excel':
                // For Excel, you would use a library like PhpSpreadsheet
                return "Excel content would be generated here";
            case 'pdf':
                // For PDF, you would use a library like TCPDF or DomPDF
                return "PDF content would be generated here";
            default:
                return "Export content";
        }
    }
}
