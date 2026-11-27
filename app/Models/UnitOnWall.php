<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class UnitOnWall extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'customer_id',
        'contract_id',
        'contract_room_id',
        'install_job_schedule_id',
        'building_id',
        'room_id',
        'rental_id',
        'product_id',
        'serial_number_id',
        'serial_number',
        'install_date',
        'last_service_date',
        'status',
        'notes',
        'temperature',
        'last_seen_at',
        'warranty_expires_at',
        'company_name', // Add company_name to fillable
        'room_name', // Add room_name to fillable
        'rental_name', // Add rental_name to fillable
        'product_name', // Add product_name to fillable
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'install_date' => 'date',
        'last_service_date' => 'date',
        'last_seen_at' => 'datetime',
        'warranty_expires_at' => 'date',
        'temperature' => 'decimal:2'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Contract the unit was physically installed under.
     * Populated at install time - do not infer the contract from customer+room,
     * one room can hold units from several different contracts.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class, 'contract_room_id');
    }

    public function installJobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'install_job_schedule_id');
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function rental()
    {
        return $this->belongsTo(MasterRental::class, 'rental_id');
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'serial_number_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function roomRentalUnit()
    {
        return $this->belongsTo(RoomRentalUnit::class);
    }

    public function histories()
    {
        return $this->hasMany(UnitOnWallHistory::class)->orderBy('action_date', 'desc')->orderBy('created_at', 'desc');
    }

    public function installHistories()
    {
        return $this->histories()->whereIn('action', ['install', 'remove', 'swap']);
    }

    public function serviceHistories()
    {
        return $this->histories()->where('action', 'service');
    }

    public function repairHistories()
    {
        return $this->histories()->where('action', 'repair');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByRental($query, $rentalId)
    {
        return $query->where('rental_id', $rentalId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBySerialNumber($query, $serialNumber)
    {
        return $query->where('serial_number', 'like', "%{$serialNumber}%");
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByInstallDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('install_date', [$startDate, $endDate]);
    }

    public function scopeByLastServiceRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('last_service_date', [$startDate, $endDate]);
    }

    public function scopeInstalled($query)
    {
        return $query->whereNotNull('install_date');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTemperatureRange($query, $minTemp, $maxTemp)
    {
        return $query->whereBetween('temperature', [$minTemp, $maxTemp]);
    }

    /**
     * Narrow a Unit On Wall query to units that belong to the given contract(s).
     *
     * customer + building + room + rental is NOT enough to identify a contract's units: the
     * same customer can rent the same rental into the same room under several contracts at
     * once, and every one of those units matches all four filters. QA room 460 held 8 active
     * units across contracts SBY-CA/26-08/0007..0010, all on rental_id 4 - which is how a
     * Remove job for a 2-unit contract listed 8 serial numbers (fixed in 8ce6d11) and how a
     * Lost Unit Report can retire units belonging to someone else's contract.
     *
     * The probe is deliberate. Rows predating the unit_on_walls.contract_id backfill
     * (5fc9b84), and units whose room was moved to another contract by Contract Switching,
     * carry no matching contract_id. Tightening unconditionally would make those units
     * unreachable to the very processes that exist to take them off the wall, so when
     * nothing in the candidate set carries a matching contract the query stays open.
     *
     * "Open" no longer means "anything": a unit whose contract_id names ANOTHER live contract
     * (outside the renewal lineage and Contract Switching sources, see
     * Contract::unitOwnershipLineageIds()) or a trial unit of another SQ is proven to belong
     * elsewhere and is still dropped (scopeNotOwnedByOtherSource()). Contract Switching rooms
     * keep working because the switching source contract is part of that lineage.
     */
    public function scopeScopedToContracts($query, array $contractIds)
    {
        $contractIds = collect($contractIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($contractIds)) {
            return $query;
        }

        // Rantai renewal + sumber Contract Switching dihitung "kontrak yang sama": unit
        // kontrak sumber switching tetap membawa contract_id lama.
        $contractIds = Contract::unitOwnershipLineageIds($contractIds);

        if (! (clone $query)->whereIn('contract_id', $contractIds)->exists()) {
            return $query->notOwnedByOtherSource($contractIds);
        }

        return $query->whereIn('contract_id', $contractIds);
    }

    /**
     * Jalur fail-open scopedToContracts(): unit tanpa contract_id yang cocok tetap ikut
     * (data lama / ruangan hasil switching), KECUALI unit yang terbukti milik sumber lain:
     *
     * - QA 26 Nov 2026 (Ruang-6 BLP-CA/26-11/0004): unit trial dari SQ LAIN (DIS...0007
     *   dipasang trial BLP-SQ/26-11/0003) ikut tampil di kartu service kontrak dari
     *   BLP-SQ/26-11/0004.
     * - QA 27 Nov 2026 (Ruang-4 ADS-CSR/26-11/0001, kontrak BLP-CA/26-11/0006): unit
     *   DIS005S2611230016 ber-contract_id BLP-CA/26-11/0004 (kontrak lain, SQ lain) ikut
     *   tampil di samping DIS005S2611230015 milik kontrak ini.
     *
     * @param  int[]  $contractIds  sudah diperluas lewat Contract::unitOwnershipLineageIds()
     */
    public function scopeNotOwnedByOtherSource($query, array $contractIds)
    {
        $contractIds = collect($contractIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (empty($contractIds)) {
            return $query;
        }

        $quotationNumbers = Contract::withTrashed()
            ->with('quotation')
            ->whereIn('id', $contractIds)
            ->get()
            ->map(fn ($contract) => $contract->quotation?->quotation_number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $query
            ->excludingUnitsBoundToOtherContracts($contractIds)
            ->excludingOtherQuotationTrials($quotationNumbers);
    }

    /**
     * Buang unit yang contract_id-nya menunjuk kontrak HIDUP di luar daftar. Unit tanpa
     * contract_id, atau yang kontraknya sudah dihapus, tidak dianggap milik siapa pun.
     *
     * @param  int[]  $contractIds
     */
    public function scopeExcludingUnitsBoundToOtherContracts($query, array $contractIds)
    {
        $contractIds = array_values(array_filter(array_map('intval', $contractIds)));
        if (empty($contractIds)) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        return $query->whereNot(function ($unitQuery) use ($contractIds, $table) {
            $unitQuery->whereNotNull("{$table}.contract_id")
                ->whereNotIn("{$table}.contract_id", $contractIds)
                ->whereIn("{$table}.contract_id", static::liveContractIdsQuery());
        });
    }

    /**
     * id kontrak yang belum dihapus. Dibuat lewat query builder biasa (bukan scope SoftDeletes)
     * supaya tetap jalan di skema tanpa contracts.deleted_at (fixture test lama).
     */
    protected static function liveContractIdsQuery()
    {
        $query = \Illuminate\Support\Facades\DB::table('contracts')->select('id');

        if (\Illuminate\Support\Facades\Schema::hasColumn('contracts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    /**
     * @param  int[]  $contractIds  sudah diperluas lewat Contract::unitOwnershipLineageIds()
     */
    public function isBoundToOtherContract(array $contractIds): bool
    {
        if (! $this->contract_id || in_array((int) $this->contract_id, array_map('intval', $contractIds), true)) {
            return false;
        }

        return static::liveContractIdsQuery()->where('id', $this->contract_id)->exists();
    }

    /**
     * Buang unit tanpa kontrak yang dipasang oleh job Install Free dari SQ di luar daftar.
     * Unit tanpa job install / tanpa nomor SQ (data lama) tetap ikut.
     */
    public function scopeExcludingOtherQuotationTrials($query, array $quotationNumbers)
    {
        $quotationNumbers = array_values(array_filter($quotationNumbers));
        if (empty($quotationNumbers)) {
            return $query;
        }

        return $query->whereNot(function ($unitQuery) use ($quotationNumbers) {
            $unitQuery->whereNull('contract_id')
                ->whereHas('installJobSchedule', function ($jobQuery) use ($quotationNumbers) {
                    $jobQuery->whereIn('type', ['install_free', 'install free', 'if'])
                        ->whereNotNull('quotation_number')
                        ->whereNotIn('quotation_number', $quotationNumbers);
                });
        });
    }

    public function belongsToOtherQuotationTrial(array $quotationNumbers): bool
    {
        $quotationNumbers = array_values(array_filter($quotationNumbers));
        if (empty($quotationNumbers) || $this->contract_id) {
            return false;
        }

        $installJob = $this->installJobSchedule;
        if (! $installJob || ! in_array(strtolower(trim((string) $installJob->type)), ['install_free', 'install free', 'if'], true)) {
            return false;
        }

        return $installJob->quotation_number && ! in_array($installJob->quotation_number, $quotationNumbers, true);
    }

    // Accessors
    public function getFormattedInstallDateAttribute()
    {
        return $this->install_date ? $this->install_date->format('d/M/Y') : '-';
    }

    public function getFormattedLastServiceDateAttribute()
    {
        return $this->last_service_date ? $this->last_service_date->format('d/M/Y') : '-';
    }

    public function getFormattedWarrantyExpiresAtAttribute()
    {
        return $this->warranty_expires_at ? $this->warranty_expires_at->format('d/M/Y') : '-';
    }

    public function getIsInstalledAttribute()
    {
        return !is_null($this->install_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            'removed' => 'Removed',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'active' => 'status-active',
            'inactive' => 'status-inactive',
            'maintenance' => 'status-warning',
            'removed' => 'status-danger',
            default => 'status-inactive'
        };
    }

    public function getFullLocationAttribute()
    {
        $location = [];
        if ($this->building) $location[] = $this->building->building_name;
        if ($this->room) $location[] = $this->room->room_name;
        return implode(' - ', $location) ?: '-';
    }

    public function getCustomerNameAttribute()
    {
        return $this->customer ? $this->customer->name : '-';
    }

    public function getProductNameAttribute()
    {
        return $this->product ? $this->product->name : '-';
    }

    public function getRentalNameAttribute()
    {
        return $this->rental ? $this->rental->rental_name : '-';
    }

    public function getRoomNameAttribute()
    {
        return $this->room ? $this->room->room_name : '-';
    }
}
