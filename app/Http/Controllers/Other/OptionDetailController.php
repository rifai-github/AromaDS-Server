<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\OptionDetail;
use App\Models\MasterOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionDetailController extends Controller
{
    public function index(MasterOption $masterOption)
    {
        $optionDetails = $masterOption->optionDetails()->orderBy('option_name')->get();
        
        return view('other.option-details.index', compact('masterOption', 'optionDetails'));
    }

    public function create(MasterOption $masterOption)
    {
        return view('other.option-details.create', compact('masterOption'));
    }

    public function createWithQuery(Request $request)
    {
        // Get ID from query parameter (bisa berupa ?41, ?masterOption=41, ?id=41)
        $masterOptionId = $request->query('masterOption') ?? $request->query('id') ?? $request->query('41') ?? $request->query('masterOption');
        
        // Jika tidak ada ID, coba ambil dari semua query parameters
        if (!$masterOptionId) {
            $queryParams = $request->query();
            $masterOptionId = collect($queryParams)->first(function($value, $key) {
                return is_numeric($value) && $value > 0;
            });
        }
        
        $masterOption = MasterOption::find($masterOptionId);
        
        if (!$masterOption) {
            abort(404, 'Master Option not found. ID: ' . $masterOptionId);
        }
        
        return view('other.option-details.create', compact('masterOption'));
    }

    public function store(Request $request, MasterOption $masterOption)
    {
        $request->validate([
            'option_name' => 'required|string|max:255',
            'option_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        try {
            $optionDetail = $masterOption->optionDetails()->create([
                'option_name' => $request->option_name,
                'option_description' => $request->option_description,
                'is_active' => $request->boolean('is_active', true)
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Option detail created successfully.',
                    'optionDetail' => $optionDetail
                ]);
            }

            return redirect()->route('other.master-options.details', $masterOption)
                ->with('success', 'Option detail created successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 400);
            }

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(OptionDetail $optionDetail)
    {
        if (request()->ajax()) {
            return response()->json([
                'optionDetail' => $optionDetail,
                'masterOption' => $optionDetail->masterOption
            ]);
        }

        return view('other.option-details.show', compact('optionDetail'));
    }

    public function edit(OptionDetail $optionDetail)
    {
        if (request()->ajax()) {
            return response()->json([
                'optionDetail' => $optionDetail,
                'masterOption' => $optionDetail->masterOption
            ]);
        }

        return view('other.option-details.edit', compact('optionDetail'));
    }

    public function update(Request $request, OptionDetail $optionDetail)
    {
        $request->validate([
            'option_name' => 'required|string|max:255',
            'option_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        try {
            $optionDetail->update([
                'option_name' => $request->option_name,
                'option_description' => $request->option_description,
                'is_active' => $request->boolean('is_active', true)
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Option detail updated successfully.',
                    'optionDetail' => $optionDetail
                ]);
            }

            return redirect()->route('other.master-options.details', $optionDetail->masterOption)
                ->with('success', 'Option detail updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 400);
            }

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(OptionDetail $optionDetail)
    {
        try {
            $masterOption = $optionDetail->masterOption;
            $optionDetail->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Option detail deleted successfully.'
                ]);
            }

            return redirect()->route('other.master-options.details', $masterOption)
                ->with('success', 'Option detail deleted successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 400);
            }

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
