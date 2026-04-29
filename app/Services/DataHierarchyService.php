<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\MasterRoom;
use App\Models\RoomRentalUnit;
use App\Models\MasterRental;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DataHierarchyService
{
    private const HIERARCHY_TTL_SECONDS = 120;
    private const SEARCH_TTL_SECONDS = 60;

    /**
     * Get complete data hierarchy for a customer
     * Hierarchy: Customer -> Contract -> Building -> Master Room -> Rental Unit
     */
    public function getCustomerHierarchy(int $customerId): array
    {
        try {
            $hierarchy = Cache::remember(
                "data-hierarchy:customer:{$customerId}",
                self::HIERARCHY_TTL_SECONDS,
                function () use ($customerId) {
                    $customer = Customer::query()
                        ->select([
                            'id',
                            'customer_code',
                            'name',
                            'status',
                            'customer_type',
                            'is_active',
                            'address',
                            'city',
                            'phone',
                            'email',
                            'member_since',
                        ])
                        ->with([
                            'contracts:id,customer_id,contract_number,contract_date,start_date,end_date,contract_value,status,contract_type,is_approved,is_posted',
                            'activeBuildings:id,name,nama_gedung,address,alamat_1,alamat_2,total_floors,total_area,phone_1,phone_2,status_update',
                            'activeBuildings.masterRooms:id,building_id,customer_id,room_name,room_type,room_floor,is_active',
                            'activeBuildings.roomRentalUnits:id,customer_id,building_id,master_room_id,master_rental_id',
                            'activeBuildings.roomRentalUnits.masterRoom:id,room_name,room_type',
                            'activeBuildings.roomRentalUnits.masterRental:id,rental_name,rental_type',
                        ])
                        ->findOrFail($customerId);

                    $buildings = $this->getCustomerBuildings($customer);

                    return [
                        'customer' => $this->formatCustomerData($customer),
                        'contracts' => $this->formatContractsData($customer->contracts),
                        'buildings' => $this->formatBuildingsData($buildings),
                        'summary' => $this->getHierarchySummary($customer),
                    ];
                }
            );

            return [
                'success' => true,
                'data' => $hierarchy,
                'message' => 'Customer hierarchy retrieved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Get customer hierarchy failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get customer hierarchy: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get hierarchy for a specific contract
     */
    public function getContractHierarchy(int $contractId): array
    {
        try {
            $hierarchy = Cache::remember(
                "data-hierarchy:contract:{$contractId}",
                self::HIERARCHY_TTL_SECONDS,
                function () use ($contractId) {
                    $contract = Contract::query()
                        ->select([
                            'id',
                            'customer_id',
                            'contract_number',
                            'contract_date',
                            'start_date',
                            'end_date',
                            'contract_value',
                            'status',
                            'contract_type',
                            'is_approved',
                            'is_posted',
                        ])
                        ->with([
                            'customer:id,customer_code,name,status,customer_type,is_active,address,city,phone,email,member_since',
                            'contractRooms:id,contract_id,room_id',
                            'contractRooms.room:id,building_id,room_name,room_type,room_floor,is_active',
                            'contractRentals:id,contract_id,room_id,master_rental_id',
                            'contractRentals.masterRental:id,rental_name,rental_type',
                        ])
                        ->findOrFail($contractId);

                    $rentalMap = $contract->contractRentals
                        ->filter(fn ($rental) => $rental->room_id)
                        ->keyBy('room_id');

                    return [
                        'contract' => $this->formatContractData($contract),
                        'customer' => $this->formatCustomerData($contract->customer),
                        'rooms' => $this->formatContractRoomsData($contract->contractRooms, $rentalMap),
                        'summary' => $this->getContractHierarchySummary($contract),
                    ];
                }
            );

            return [
                'success' => true,
                'data' => $hierarchy,
                'message' => 'Contract hierarchy retrieved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Get contract hierarchy failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get contract hierarchy: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get hierarchy for a specific building
     */
    public function getBuildingHierarchy(int $buildingId): array
    {
        try {
            $hierarchy = Cache::remember(
                "data-hierarchy:building:{$buildingId}",
                self::HIERARCHY_TTL_SECONDS,
                function () use ($buildingId) {
                    $building = Building::query()
                        ->select([
                            'id',
                            'name',
                            'nama_gedung',
                            'address',
                            'alamat_1',
                            'alamat_2',
                            'total_floors',
                            'total_area',
                            'phone_1',
                            'phone_2',
                            'status_update',
                        ])
                        ->with([
                            'activeCustomers:id,customer_code,name,status,customer_type,is_active,address,city,phone,email,member_since',
                            'masterRooms:id,building_id,customer_id,room_name,room_type,room_floor,is_active',
                            'masterRooms.contractRooms:id,contract_id,room_id',
                            'masterRooms.roomRentalUnits:id,customer_id,building_id,master_room_id,master_rental_id',
                            'masterRooms.roomRentalUnits.masterRental:id,rental_name,rental_type',
                            'roomRentalUnits:id,customer_id,building_id,master_room_id,master_rental_id',
                            'roomRentalUnits.masterRoom:id,room_name,room_type',
                            'roomRentalUnits.masterRental:id,rental_name,rental_type',
                        ])
                        ->findOrFail($buildingId);

                    $primaryCustomer = $building->activeCustomers->first() ?? $building->customers()->select([
                        'customers.id',
                        'customers.customer_code',
                        'customers.name',
                        'customers.status',
                        'customers.customer_type',
                        'customers.is_active',
                        'customers.address',
                        'customers.city',
                        'customers.phone',
                        'customers.email',
                        'customers.member_since',
                    ])->first();

                    return [
                        'building' => $this->formatBuildingData($building),
                        'customer' => $primaryCustomer ? $this->formatCustomerData($primaryCustomer) : null,
                        'rooms' => $this->formatMasterRoomsData($building->masterRooms),
                        'rental_units' => $this->formatRentalUnitsData($building->roomRentalUnits),
                        'summary' => $this->getBuildingHierarchySummary($building),
                    ];
                }
            );

            return [
                'success' => true,
                'data' => $hierarchy,
                'message' => 'Building hierarchy retrieved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Get building hierarchy failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get building hierarchy: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get hierarchy statistics
     */
    public function getHierarchyStatistics(): array
    {
        try {
            $stats = Cache::remember('data-hierarchy:statistics', self::HIERARCHY_TTL_SECONDS, function () {
                return [
                    'customers' => Customer::count(),
                    'contracts' => Contract::count(),
                    'buildings' => Building::count(),
                    'master_rooms' => MasterRoom::count(),
                    'rental_units' => RoomRentalUnit::count(),
                    'rentals' => MasterRental::count(),
                    'active_contracts' => Contract::where('is_posted', true)->count(),
                    'active_buildings' => Building::where('status_update', true)->count(),
                ];
            });

            return [
                'success' => true,
                'data' => $stats,
                'message' => 'Hierarchy statistics retrieved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Get hierarchy statistics failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get hierarchy statistics: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Search within hierarchy
     */
    public function searchHierarchy(string $searchTerm, array $filters = []): array
    {
        try {
            $cacheKey = 'data-hierarchy:search:' . md5($searchTerm . '|' . json_encode($filters));

            $results = Cache::remember($cacheKey, self::SEARCH_TTL_SECONDS, function () use ($searchTerm, $filters) {
                $results = [];

                if (!($filters['exclude_customers'] ?? false)) {
                    $customers = Customer::query()
                        ->select([
                            'id',
                            'customer_code',
                            'name',
                            'status',
                            'customer_type',
                            'is_active',
                            'address',
                            'city',
                            'phone',
                            'email',
                            'member_since',
                        ])
                        ->where(function ($query) use ($searchTerm) {
                            $query->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('customer_code', 'like', "%{$searchTerm}%");
                        })
                        ->with([
                            'contracts:id,customer_id,contract_number,is_posted',
                            'activeBuildings:id,name,nama_gedung',
                        ])
                        ->limit(25)
                        ->get();

                    $results['customers'] = $this->formatCustomersData($customers);
                }

                if (!($filters['exclude_buildings'] ?? false)) {
                    $buildings = Building::query()
                        ->select([
                            'id',
                            'name',
                            'nama_gedung',
                            'address',
                            'alamat_1',
                            'alamat_2',
                            'total_floors',
                            'total_area',
                            'phone_1',
                            'phone_2',
                            'status_update',
                        ])
                        ->where(function ($query) use ($searchTerm) {
                            $query->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('nama_gedung', 'like', "%{$searchTerm}%");
                        })
                        ->with([
                            'activeCustomers:id,customer_code,name,status,customer_type,is_active,address,city,phone,email,member_since',
                            'masterRooms:id,building_id,room_name,room_type',
                            'roomRentalUnits:id,building_id,master_rental_id',
                        ])
                        ->limit(25)
                        ->get();

                    $results['buildings'] = $this->formatBuildingsData($buildings);
                }

                if (!($filters['exclude_contracts'] ?? false)) {
                    $contracts = Contract::query()
                        ->select([
                            'id',
                            'customer_id',
                            'contract_number',
                            'contract_date',
                            'start_date',
                            'end_date',
                            'contract_value',
                            'status',
                            'contract_type',
                            'is_approved',
                            'is_posted',
                        ])
                        ->where('contract_number', 'like', "%{$searchTerm}%")
                        ->with([
                            'customer:id,customer_code,name,status,customer_type,is_active,address,city,phone,email,member_since',
                            'contractRooms:id,contract_id,room_id',
                        ])
                        ->limit(25)
                        ->get();

                    $results['contracts'] = $this->formatContractsData($contracts);
                }

                return $results;
            });

            return [
                'success' => true,
                'data' => $results,
                'search_term' => $searchTerm,
                'message' => 'Hierarchy search completed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Search hierarchy failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to search hierarchy: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get hierarchy tree structure
     */
    public function getHierarchyTree(int $customerId = null): array
    {
        try {
            $cacheKey = $customerId
                ? "data-hierarchy:tree:customer:{$customerId}"
                : 'data-hierarchy:tree:all-customers';

            $tree = Cache::remember($cacheKey, self::HIERARCHY_TTL_SECONDS, function () use ($customerId) {
                if ($customerId) {
                    $customer = Customer::query()
                        ->select('id', 'customer_code', 'name')
                        ->with([
                            'activeBuildings:id,name,nama_gedung',
                            'activeBuildings.masterRooms:id,building_id,room_name',
                            'activeBuildings.masterRooms.roomRentalUnits:id,master_room_id,master_rental_id',
                            'activeBuildings.masterRooms.roomRentalUnits.masterRental:id,rental_name',
                        ])
                        ->findOrFail($customerId);

                    return $this->buildCustomerTree($customer);
                }

                return Customer::query()
                    ->select('id', 'customer_code', 'name')
                    ->with([
                        'activeBuildings:id,name,nama_gedung',
                        'activeBuildings.masterRooms:id,building_id,room_name',
                        'activeBuildings.masterRooms.roomRentalUnits:id,master_room_id,master_rental_id',
                        'activeBuildings.masterRooms.roomRentalUnits.masterRental:id,rental_name',
                    ])
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($customer) => $this->buildCustomerTree($customer))
                    ->all();
            });

            return [
                'success' => true,
                'data' => $tree,
                'message' => 'Hierarchy tree retrieved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Get hierarchy tree failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get hierarchy tree: ' . $e->getMessage(),
            ];
        }
    }

    private function getCustomerBuildings(Customer $customer): Collection
    {
        if ($customer->relationLoaded('activeBuildings')) {
            return $customer->activeBuildings;
        }

        return $customer->activeBuildings()->get();
    }

    private function formatCustomerData($customer): ?array
    {
        if (!$customer) {
            return null;
        }

        $buildings = method_exists($customer, 'activeBuildings')
            ? $this->getCustomerBuildings($customer)
            : collect();
        $contracts = $customer->relationLoaded('contracts') ? $customer->contracts : collect();

        return [
            'id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'name' => $customer->name,
            'status' => $customer->status,
            'customer_type' => $customer->customer_type,
            'is_active' => $customer->is_active,
            'address' => $customer->address,
            'city' => $customer->city,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'member_since' => $customer->member_since?->format('Y-m-d'),
            'buildings_count' => $buildings->count(),
            'contracts_count' => $contracts->count(),
        ];
    }

    private function formatContractData($contract): array
    {
        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'contract_date' => $contract->contract_date?->format('Y-m-d'),
            'start_date' => $contract->start_date?->format('Y-m-d'),
            'end_date' => $contract->end_date?->format('Y-m-d'),
            'contract_value' => $contract->contract_value,
            'status' => $contract->status,
            'contract_type' => $contract->contract_type,
            'is_approved' => $contract->is_approved,
            'is_posted' => $contract->is_posted,
            'rooms_count' => $contract->relationLoaded('contractRooms') ? $contract->contractRooms->count() : 0,
        ];
    }

    private function formatBuildingData($building): array
    {
        $rooms = $building->relationLoaded('masterRooms') ? $building->masterRooms : collect();
        $rentalUnits = $building->relationLoaded('roomRentalUnits') ? $building->roomRentalUnits : collect();

        return [
            'id' => $building->id,
            'name' => $building->name,
            'nama_gedung' => $building->nama_gedung,
            'address' => $building->address,
            'alamat_1' => $building->alamat_1,
            'alamat_2' => $building->alamat_2,
            'total_floors' => $building->total_floors,
            'total_area' => $building->total_area,
            'phone_1' => $building->phone_1,
            'phone_2' => $building->phone_2,
            'status_update' => $building->status_update,
            'rooms_count' => $rooms->count(),
            'rental_units_count' => $rentalUnits->count(),
        ];
    }

    private function formatContractsData($contracts): array
    {
        return $contracts->map(fn ($contract) => $this->formatContractData($contract))->toArray();
    }

    private function formatBuildingsData($buildings): array
    {
        return $buildings->map(fn ($building) => $this->formatBuildingData($building))->toArray();
    }

    private function formatCustomersData($customers): array
    {
        return $customers->map(fn ($customer) => $this->formatCustomerData($customer))->toArray();
    }

    private function formatContractRoomsData($contractRooms, Collection $rentalMap): array
    {
        return $contractRooms->map(function ($contractRoom) use ($rentalMap) {
            $room = $contractRoom->room;
            $rental = $room ? ($rentalMap->get($room->id)?->masterRental) : null;

            return [
                'id' => $contractRoom->id,
                'room_rental_unit' => [
                    'id' => $room?->id,
                    'master_room' => $room ? [
                        'id' => $room->id,
                        'room_name' => $room->room_name,
                        'room_type' => $room->room_type,
                    ] : null,
                    'rental' => $rental ? [
                        'id' => $rental->id,
                        'rental_name' => $rental->rental_name,
                        'rental_type' => $rental->rental_type,
                    ] : null,
                ],
            ];
        })->toArray();
    }

    private function formatMasterRoomsData($masterRooms): array
    {
        return $masterRooms->map(function ($masterRoom) {
            return [
                'id' => $masterRoom->id,
                'room_name' => $masterRoom->room_name,
                'room_type' => $masterRoom->room_type,
                'floor' => $masterRoom->room_floor,
                'area' => null,
                'rental_units_count' => $masterRoom->relationLoaded('roomRentalUnits') ? $masterRoom->roomRentalUnits->count() : 0,
            ];
        })->toArray();
    }

    private function formatRentalUnitsData($rentalUnits): array
    {
        return $rentalUnits->map(function ($rentalUnit) {
            return [
                'id' => $rentalUnit->id,
                'master_room' => $rentalUnit->masterRoom ? [
                    'id' => $rentalUnit->masterRoom->id,
                    'room_name' => $rentalUnit->masterRoom->room_name,
                    'room_type' => $rentalUnit->masterRoom->room_type,
                ] : null,
                'rental' => $rentalUnit->masterRental ? [
                    'id' => $rentalUnit->masterRental->id,
                    'rental_name' => $rentalUnit->masterRental->rental_name,
                    'rental_type' => $rentalUnit->masterRental->rental_type,
                ] : null,
                'contracts_count' => 0,
            ];
        })->toArray();
    }

    private function getHierarchySummary(Customer $customer): array
    {
        $buildings = $this->getCustomerBuildings($customer);
        $contracts = $customer->relationLoaded('contracts') ? $customer->contracts : collect();

        return [
            'total_contracts' => $contracts->count(),
            'total_buildings' => $buildings->count(),
            'active_contracts' => $contracts->where('is_posted', true)->count(),
            'total_rooms' => $buildings->sum(fn ($building) => $building->relationLoaded('masterRooms') ? $building->masterRooms->count() : 0),
            'total_rental_units' => $buildings->sum(fn ($building) => $building->relationLoaded('roomRentalUnits') ? $building->roomRentalUnits->count() : 0),
        ];
    }

    private function getContractHierarchySummary(Contract $contract): array
    {
        $contractRooms = $contract->relationLoaded('contractRooms') ? $contract->contractRooms : collect();
        $roomIds = $contractRooms->pluck('room_id')->filter()->unique();
        $rentals = $contract->relationLoaded('contractRentals')
            ? $contract->contractRentals->whereIn('room_id', $roomIds)
            : collect();

        return [
            'total_rooms' => $contractRooms->count(),
            'total_rental_units' => $roomIds->count(),
            'total_rentals' => $rentals->count(),
        ];
    }

    private function getBuildingHierarchySummary(Building $building): array
    {
        $rooms = $building->relationLoaded('masterRooms') ? $building->masterRooms : collect();
        $rentalUnits = $building->relationLoaded('roomRentalUnits') ? $building->roomRentalUnits : collect();

        return [
            'total_rooms' => $rooms->count(),
            'total_rental_units' => $rentalUnits->count(),
            'total_rentals' => $rentalUnits->filter(fn ($rentalUnit) => !is_null($rentalUnit->masterRental))->count(),
            'total_contracts' => $rooms->sum(fn ($room) => $room->relationLoaded('contractRooms') ? $room->contractRooms->count() : 0),
        ];
    }

    private function buildCustomerTree(Customer $customer): array
    {
        $tree = [
            'id' => $customer->id,
            'type' => 'customer',
            'name' => $customer->name,
            'customer_code' => $customer->customer_code,
            'children' => [],
        ];

        foreach ($this->getCustomerBuildings($customer) as $building) {
            $buildingNode = [
                'id' => $building->id,
                'type' => 'building',
                'name' => $building->name ?: $building->nama_gedung,
                'children' => [],
            ];

            foreach ($building->masterRooms as $masterRoom) {
                $roomNode = [
                    'id' => $masterRoom->id,
                    'type' => 'master_room',
                    'name' => $masterRoom->room_name,
                    'children' => [],
                ];

                foreach ($masterRoom->roomRentalUnits as $rentalUnit) {
                    $rentalUnitNode = [
                        'id' => $rentalUnit->id,
                        'type' => 'rental_unit',
                        'name' => $rentalUnit->masterRental?->rental_name ?? 'Rental Unit',
                        'children' => [],
                    ];

                    if ($rentalUnit->masterRental) {
                        $rentalUnitNode['children'][] = [
                            'id' => $rentalUnit->masterRental->id,
                            'type' => 'rental',
                            'name' => $rentalUnit->masterRental->rental_name,
                            'children' => [],
                        ];
                    }

                    $roomNode['children'][] = $rentalUnitNode;
                }

                $buildingNode['children'][] = $roomNode;
            }

            $tree['children'][] = $buildingNode;
        }

        return $tree;
    }
}
