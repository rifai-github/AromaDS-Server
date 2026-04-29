<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use App\Models\ReportTemplateField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ReportTemplate::with(['creator', 'fields']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('template_type')) {
            $query->where('template_type', $request->template_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('reports.template.index', compact('templates'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('reports.template.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255|unique:report_templates,template_name',
            'template_type' => 'required|in:pdf,excel,csv,html',
            'template_config' => 'nullable|array',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:text,number,date,boolean',
            'fields.*.field_config' => 'nullable|array',
            'fields.*.is_required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $template = ReportTemplate::create([
                'template_name' => $request->template_name,
                'template_type' => $request->template_type,
                'template_config' => $request->template_config ?? [],
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Create fields
            if ($request->fields) {
                foreach ($request->fields as $field) {
                    ReportTemplateField::create([
                        'template_id' => $template->id,
                        'field_name' => $field['field_name'],
                        'field_type' => $field['field_type'],
                        'field_config' => $field['field_config'] ?? [],
                        'is_required' => $field['is_required'] ?? false
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template created successfully',
                    'data' => $template->load('fields')
                ]);
            }

            return redirect()->route('reports.template.index')
                           ->with('success', 'Report template created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating report template: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error creating report template: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function show($id)
    {
        $template = ReportTemplate::with(['creator', 'fields'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($template);
        }

        return view('reports.template.show', compact('template'));
    }

    public function edit($id)
    {
        $template = ReportTemplate::with(['fields'])->findOrFail($id);
        $users = User::where('is_active', true)->get();

        if (request()->ajax()) {
            return response()->json($template);
        }

        return view('reports.template.edit', compact('template', 'users'));
    }

    public function update(Request $request, $id)
    {
        $template = ReportTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255|unique:report_templates,template_name,' . $id,
            'template_type' => 'required|in:pdf,excel,csv,html',
            'template_config' => 'nullable|array',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:text,number,date,boolean',
            'fields.*.field_config' => 'nullable|array',
            'fields.*.is_required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $template->update([
                'template_name' => $request->template_name,
                'template_type' => $request->template_type,
                'template_config' => $request->template_config ?? $template->template_config,
                'is_active' => $request->is_active ?? $template->is_active,
                'updated_by' => Auth::id()
            ]);

            // Update fields
            if ($request->fields) {
                $template->fields()->delete();
                foreach ($request->fields as $field) {
                    ReportTemplateField::create([
                        'template_id' => $template->id,
                        'field_name' => $field['field_name'],
                        'field_type' => $field['field_type'],
                        'field_config' => $field['field_config'] ?? [],
                        'is_required' => $field['is_required'] ?? false
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template updated successfully',
                    'data' => $template->load('fields')
                ]);
            }

            return redirect()->route('reports.template.index')
                           ->with('success', 'Report template updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating report template: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error updating report template: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $template = ReportTemplate::findOrFail($id);
            $template->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template deleted successfully'
                ]);
            }

            return redirect()->route('reports.template.index')
                           ->with('success', 'Report template deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting report template: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting report template: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:report_templates,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = ReportTemplate::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} report template(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting report templates: ' . $e->getMessage()
            ], 500);
        }
    }

    public function duplicate($id)
    {
        try {
            $originalTemplate = ReportTemplate::with('fields')->findOrFail($id);
            
            DB::beginTransaction();

            $newTemplate = ReportTemplate::create([
                'template_name' => $originalTemplate->template_name . ' (Copy)',
                'template_type' => $originalTemplate->template_type,
                'template_config' => $originalTemplate->template_config,
                'is_active' => false, // Set as inactive by default
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Duplicate fields
            foreach ($originalTemplate->fields as $field) {
                ReportTemplateField::create([
                    'template_id' => $newTemplate->id,
                    'field_name' => $field->field_name,
                    'field_type' => $field->field_type,
                    'field_config' => $field->field_config,
                    'is_required' => $field->is_required
                ]);
            }

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template duplicated successfully',
                    'data' => $newTemplate->load('fields')
                ]);
            }

            return redirect()->route('reports.template.index')
                           ->with('success', 'Report template duplicated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error duplicating report template: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error duplicating report template: ' . $e->getMessage());
        }
    }
}
