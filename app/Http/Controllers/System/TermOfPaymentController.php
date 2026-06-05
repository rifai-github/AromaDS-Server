<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TermOfPaymentController extends Controller
{
    private const MASTER_OPTION_NAME = 'Term of Payment';

    public function index()
    {
        $masterOption = $this->termOfPaymentMasterOption();
        $terms = $masterOption->optionDetails()
            ->orderBy('id')
            ->get();

        return view('system.master-term-of-payments.index', compact('masterOption', 'terms'));
    }

    public function store(Request $request)
    {
        $data = $this->validateTerm($request);
        $masterOption = $this->termOfPaymentMasterOption();

        OptionDetail::create([
            'master_option_id' => $masterOption->id,
            'option_name' => $data['value'],
            'label' => $data['label'],
            'code' => $data['is_advance'] ? 'advance' : (string) $data['months'],
            'option_description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->forgetQuotationTermCache();

        return redirect()
            ->route('system.master-term-of-payments.index')
            ->with('success', 'Term of Payment berhasil ditambahkan.');
    }

    public function update(Request $request, OptionDetail $masterTermOfPayment)
    {
        abort_unless($masterTermOfPayment->masterOption?->name === self::MASTER_OPTION_NAME, 404);

        $data = $this->validateTerm($request, $masterTermOfPayment);

        $masterTermOfPayment->update([
            'option_name' => $data['value'],
            'label' => $data['label'],
            'code' => $data['is_advance'] ? 'advance' : (string) $data['months'],
            'option_description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ]);

        $this->forgetQuotationTermCache();

        return redirect()
            ->route('system.master-term-of-payments.index')
            ->with('success', 'Term of Payment berhasil diperbarui.');
    }

    public function destroy(OptionDetail $masterTermOfPayment)
    {
        abort_unless($masterTermOfPayment->masterOption?->name === self::MASTER_OPTION_NAME, 404);

        $masterTermOfPayment->delete();

        $this->forgetQuotationTermCache();

        return redirect()
            ->route('system.master-term-of-payments.index')
            ->with('success', 'Term of Payment berhasil dihapus.');
    }

    public function toggleStatus(OptionDetail $masterTermOfPayment)
    {
        abort_unless($masterTermOfPayment->masterOption?->name === self::MASTER_OPTION_NAME, 404);

        $masterTermOfPayment->update([
            'is_active' => ! $masterTermOfPayment->is_active,
            'updated_by' => Auth::id(),
        ]);

        $this->forgetQuotationTermCache();

        return redirect()
            ->route('system.master-term-of-payments.index')
            ->with('success', 'Status Term of Payment berhasil diperbarui.');
    }

    private function termOfPaymentMasterOption(): MasterOption
    {
        return MasterOption::firstOrCreate(
            ['name' => self::MASTER_OPTION_NAME],
            [
                'description' => 'Pilihan Terms of Payment untuk quotation dan kontrak',
                'system_reserved' => true,
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );
    }

    private function validateTerm(Request $request, ?OptionDetail $current = null): array
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'months' => 'nullable|integer|min:1|max:120',
            'is_advance' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_advance'] = $request->boolean('is_advance');

        if (! $data['is_advance'] && empty($data['months'])) {
            throw ValidationException::withMessages([
                'months' => 'Jumlah bulan wajib diisi jika bukan 1x Advance.',
            ]);
        }

        $masterOption = $this->termOfPaymentMasterOption();
        $exists = OptionDetail::where('master_option_id', $masterOption->id)
            ->where('option_name', $data['value'])
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'value' => 'Value Term of Payment sudah digunakan.',
            ]);
        }

        return $data;
    }

    private function forgetQuotationTermCache(): void
    {
        Cache::forget('quotation-wizard:term-of-payment-options');
    }
}
