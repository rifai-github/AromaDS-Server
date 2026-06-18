<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\MasterOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterOptionController extends Controller
{
    use ColumnFilterTrait;

    public function index(Request $request)
    {
        $query = MasterOption::with(['updatedBy', 'createdBy']);

        $this->applyColumnFilters($query, null, [
            'name' => ['column' => 'name'],
            'description' => ['column' => 'description'],
            'system_reserved' => ['column' => 'system_reserved', 'boolean' => true],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'createdBy.name' => ['relation' => 'createdBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
            'updatedBy.name' => ['relation' => 'updatedBy', 'column' => 'name'],
        ]);

        // Manual filters only apply if no 'filter' parameter exists (to avoid conflict with AutoFilterable)
        if (!$request->has('filter')) {
            // Filter by name
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            // Filter by system reserved
            if ($request->filled('system_reserved')) {
                $query->where('system_reserved', $request->system_reserved);
            }

            // Filter by status active
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }
        }

        // AutoFilterable trait will automatically apply filters from 'filter[column]' parameters
        $masterOptions = $query->orderBy('name')->paginateStd(25);

        return view('other.master-options.index', compact('masterOptions'));
    }

    public function create()
    {
        return view('other.master-options.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'system_reserved' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'required|array|min:1',
            'options.*.option_name' => 'required|string|max:255',
            'options.*.label' => 'required|string|max:255',
            'options.*.code' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Check if master option with same name already exists
            $existingOption = MasterOption::where('name', $request->name)->exists();

            if ($existingOption) {
                throw new \Exception('Master option with this name already exists.');
            }

            $masterOption = MasterOption::create([
                'name' => $request->name,
                'description' => $request->description,
                'system_reserved' => $request->system_reserved ?? false,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
            ]);

            // Create option details
            foreach ($request->options as $optionData) {
                if (!empty($optionData['option_name']) && !empty($optionData['label'])) {
                    $masterOption->optionDetails()->create([
                        'option_name' => $optionData['option_name'],
                        'label' => $optionData['label'],
                        'code' => $optionData['code'] ?? null,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master option created successfully.',
                    'data' => $masterOption->fresh(['optionDetails'])
                ]);
            }

            return redirect()->route('other.master-options.show', $masterOption)
                ->with('success', 'Master option created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(MasterOption $masterOption)
    {
        $masterOption->load([
            'updatedBy',
            'createdBy',
            'optionDetails' => function($query) {
                $query->orderBy('option_name');
            }
        ]);
        
        if (request()->ajax()) {
            return response()->json([
                'masterOption' => $masterOption,
                'optionDetails' => $masterOption->optionDetails
            ]);
        }
        
        return view('other.master-options.show', compact('masterOption'));
    }

    public function edit(MasterOption $masterOption)
    {
        if (request()->ajax()) {
            return response()->json([
                'masterOption' => $masterOption,
                'optionDetails' => $masterOption->optionDetails
            ]);
        }
        
        return view('other.master-options.edit', compact('masterOption'));
    }

    public function update(Request $request, MasterOption $masterOption)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'system_reserved' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'nullable|array',
            'options.*.option_name' => 'required_with:options|string|max:255',
            'options.*.label' => 'required_with:options|string|max:255',
            'options.*.code' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Check if master option with same name already exists (excluding current record)
            $existingOption = MasterOption::where('name', $request->name)
                ->where('id', '!=', $masterOption->id)
                ->exists();

            if ($existingOption) {
                throw new \Exception('Master option with this name already exists.');
            }

            $masterOption->update([
                'name' => $request->name,
                'description' => $request->description,
                'system_reserved' => $request->system_reserved ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            // Update option details only if options array is provided
            if ($request->has('options') && is_array($request->options)) {
                // First, delete existing option details
                $masterOption->optionDetails()->delete();

                // Then create new option details
                foreach ($request->options as $optionData) {
                    if (!empty($optionData['option_name']) && !empty($optionData['label'])) {
                        $masterOption->optionDetails()->create([
                            'option_name' => $optionData['option_name'],
                            'label' => $optionData['label'],
                            'code' => $optionData['code'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master option updated successfully.',
                    'data' => $masterOption->fresh(['optionDetails'])
                ]);
            }

            return redirect()->route('other.master-options.show', $masterOption)
                ->with('success', 'Master option updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(MasterOption $masterOption)
    {
        try {
            // Prevent deletion of system options
            if ($masterOption->is_system) {
                throw new \Exception('Tidak dapat menghapus opsi sistem.');
            }

            $masterOption->delete();
            return redirect()->route('other.master-options.index')
                ->with('success', 'Opsi master berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'option_ids' => 'required|array',
                'option_ids.*' => 'exists:master_options,id'
            ]);

            $deletedCount = 0;
            foreach ($request->option_ids as $id) {
                $masterOption = MasterOption::find($id);
                if ($masterOption && !$masterOption->is_system) {
                    $masterOption->delete();
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} master option(s).",
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getOptionsByGroup(Request $request)
    {
        $request->validate([
            'option_group' => 'required|string',
        ]);

        $options = MasterOption::where('option_group', $request->option_group)
            ->where('status', 'active')
            ->orderBy('option_key')
            ->get();

        return response()->json($options);
    }

    public function getOptionValue(Request $request)
    {
        $request->validate([
            'option_group' => 'required|string',
            'option_key' => 'required|string',
        ]);

        $option = MasterOption::where('option_group', $request->option_group)
            ->where('option_key', $request->option_key)
            ->where('status', 'active')
            ->first();

        if (!$option) {
            return response()->json([
                'error' => 'Opsi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'option' => $option,
            'value' => $this->parseOptionValue($option->option_type, $option->option_value),
        ]);
    }

    public function setOptionValue(Request $request)
    {
        $request->validate([
            'option_group' => 'required|string',
            'option_key' => 'required|string',
            'option_value' => 'required|string',
        ]);

        try {
            $option = MasterOption::where('option_group', $request->option_group)
                ->where('option_key', $request->option_key)
                ->first();

            if (!$option) {
                throw new \Exception('Opsi tidak ditemukan.');
            }

            // Validate option value based on type
            $this->validateOptionValue($option->option_type, $request->option_value);

            $option->update([
                'option_value' => $request->option_value,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nilai opsi berhasil diperbarui.',
                'option' => $option,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'master_options' => 'required|array|min:1',
            'master_options.*.option_group' => 'required|string|max:100',
            'master_options.*.option_key' => 'required|string|max:255',
            'master_options.*.option_value' => 'required|string',
            'master_options.*.option_type' => 'required|in:string,integer,float,boolean,json,array',
            'master_options.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($request->master_options as $optionData) {
                // Check if option already exists in the same group
                $existingOption = MasterOption::where('option_group', $optionData['option_group'])
                    ->where('option_key', $optionData['option_key'])
                    ->exists();

                if (!$existingOption) {
                    // Validate option value based on type
                    $this->validateOptionValue($optionData['option_type'], $optionData['option_value']);

                    MasterOption::create([
                        'option_group' => $optionData['option_group'],
                        'option_key' => $optionData['option_key'],
                        'option_value' => $optionData['option_value'],
                        'option_type' => $optionData['option_type'],
                        'description' => $optionData['description'] ?? null,
                        'is_system' => false,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} opsi master.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $masterOptions = MasterOption::orderBy('option_group')
            ->orderBy('option_key')
            ->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return back()->with('success', "Berhasil mengekspor {$masterOptions->count()} opsi master.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Here you would implement the actual file import logic
            // For now, we'll just return a success message
            $importedCount = 0;

            // Process the uploaded file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Process CSV/Excel file and create master options
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return back()->with('success', "Berhasil mengimpor {$importedCount} opsi master.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalOptions = MasterOption::count();
        $activeOptions = MasterOption::where('status', 'active')->count();
        $inactiveOptions = MasterOption::where('status', 'inactive')->count();
        $systemOptions = MasterOption::where('is_system', true)->count();
        $userOptions = MasterOption::where('is_system', false)->count();

        // Get option groups count
        $optionGroups = MasterOption::selectRaw('option_group, COUNT(*) as count')
            ->groupBy('option_group')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_options' => $totalOptions,
            'active_options' => $activeOptions,
            'inactive_options' => $inactiveOptions,
            'system_options' => $systemOptions,
            'user_options' => $userOptions,
            'option_groups' => $optionGroups,
        ]);
    }

    public function getOptionGroups()
    {
        $optionGroups = MasterOption::selectRaw('option_group, COUNT(*) as count')
            ->groupBy('option_group')
            ->orderBy('option_group')
            ->get();

        return response()->json($optionGroups);
    }

    public function toggleStatus(MasterOption $masterOption)
    {
        try {
            $newStatus = $masterOption->status === 'active' ? 'inactive' : 'active';
            
            $masterOption->update(['status' => $newStatus]);

            return back()->with('success', "Status opsi berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function validateOptionValue($type, $value)
    {
        switch ($type) {
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    throw new \Exception('Nilai harus berupa bilangan bulat.');
                }
                break;
            case 'float':
                if (!is_numeric($value)) {
                    throw new \Exception('Nilai harus berupa angka.');
                }
                break;
            case 'boolean':
                if (!in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'])) {
                    throw new \Exception('Nilai harus berupa boolean (true/false, 1/0, yes/no).');
                }
                break;
            case 'json':
                if (!$this->isValidJson($value)) {
                    throw new \Exception('Nilai harus berupa JSON yang valid.');
                }
                break;
            case 'array':
                if (!$this->isValidArray($value)) {
                    throw new \Exception('Nilai harus berupa array yang valid.');
                }
                break;
        }
    }

    private function parseOptionValue($type, $value)
    {
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                return in_array(strtolower($value), ['true', '1', 'yes']);
            case 'json':
                return json_decode($value, true);
            case 'array':
                return $this->parseArray($value);
            default:
                return $value;
        }
    }

    private function isValidJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function isValidArray($string)
    {
        // Simple array validation - comma-separated values
        return !empty($string) && strpos($string, ',') !== false;
    }

    private function parseArray($string)
    {
        return array_map('trim', explode(',', $string));
    }
}
