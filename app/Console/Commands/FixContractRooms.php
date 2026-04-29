<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixContractRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:fix-rooms 
                            {--contract= : Contract ID or Contract Number to fix}
                            {--all : Fix all contracts}
                            {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix contract rooms to match quotation rooms';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $contractId = $this->option('contract');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($contractId) {
            // Fix specific contract
            $this->fixContract($contractId, $dryRun);
        } elseif ($all) {
            // Fix all contracts
            $this->fixAllContracts($dryRun);
        } else {
            $this->error('Please specify --contract=ID or --all');
            return 1;
        }

        return 0;
    }

    /**
     * Fix specific contract
     */
    private function fixContract($contractId, $dryRun = false)
    {
        // Try to find by ID first, then by contract number
        $contract = Contract::where('id', $contractId)
            ->orWhere('contract_number', $contractId)
            ->with(['quotation.quotationRooms.room', 'contractRooms.room'])
            ->first();

        if (!$contract) {
            $this->error("Contract not found: {$contractId}");
            return;
        }

        if (!$contract->quotation) {
            $this->warn("Contract {$contract->contract_number} has no quotation");
            return;
        }

        $this->info("=== Fixing Contract: {$contract->contract_number} (ID: {$contract->id}) ===");
        $this->newLine();

        $this->processContract($contract, $dryRun);
    }

    /**
     * Fix all contracts
     */
    private function fixAllContracts($dryRun = false)
    {
        $contracts = Contract::with(['quotation.quotationRooms.room', 'contractRooms.room'])
            ->whereHas('quotation')
            ->get();

        $this->info("Found {$contracts->count()} contracts with quotations");
        $this->newLine();

        $fixed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($contracts as $contract) {
            try {
                $result = $this->processContract($contract, $dryRun, false);
                if ($result['fixed']) {
                    $fixed++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error("Error fixing contract {$contract->contract_number}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Fixed: {$fixed}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: {$errors}");
    }

    /**
     * Process contract to fix rooms
     */
    private function processContract(Contract $contract, $dryRun = false, $verbose = true)
    {
        $quotation = $contract->quotation;
        
        if (!$quotation) {
            if ($verbose) {
                $this->warn("Contract {$contract->contract_number} has no quotation - skipping");
            }
            return ['fixed' => false, 'message' => 'No quotation'];
        }

        // Get active quotation rooms (not soft deleted)
        $quotationRooms = $quotation->quotationRooms->filter(function($qr) {
            return $qr->room_id !== null;
        });

        if ($quotationRooms->isEmpty()) {
            if ($verbose) {
                $this->warn("Quotation {$quotation->quotation_number} has no rooms - skipping");
            }
            return ['fixed' => false, 'message' => 'No quotation rooms'];
        }

        // Get current contract rooms
        $currentContractRooms = $contract->contractRooms;
        $currentRoomIds = $currentContractRooms->pluck('room_id')->toArray();
        
        // Get expected room IDs from quotation
        $expectedRoomIds = $quotationRooms->pluck('room_id')->unique()->toArray();

        // Find rooms to add (in quotation but not in contract)
        $roomsToAdd = array_diff($expectedRoomIds, $currentRoomIds);
        
        // Find rooms to remove (in contract but not in quotation)
        $roomsToRemove = array_diff($currentRoomIds, $expectedRoomIds);

        if (empty($roomsToAdd) && empty($roomsToRemove)) {
            if ($verbose) {
                $this->info("✅ Contract rooms already match quotation rooms - no changes needed");
            }
            return ['fixed' => false, 'message' => 'Already correct'];
        }

        if ($verbose) {
            $this->info("Current contract rooms: " . count($currentRoomIds));
            $this->info("Expected quotation rooms: " . count($expectedRoomIds));
            $this->newLine();
            
            if (!empty($roomsToAdd)) {
                $this->info("Rooms to ADD (" . count($roomsToAdd) . "):");
                foreach ($roomsToAdd as $roomId) {
                    $quotationRoom = $quotationRooms->firstWhere('room_id', $roomId);
                    $roomName = $quotationRoom ? $quotationRoom->room_name : "Room ID: {$roomId}";
                    $this->line("  + {$roomName} (ID: {$roomId})");
                }
                $this->newLine();
            }
            
            if (!empty($roomsToRemove)) {
                $this->warn("Rooms to REMOVE (" . count($roomsToRemove) . "):");
                foreach ($roomsToRemove as $roomId) {
                    $contractRoom = $currentContractRooms->firstWhere('room_id', $roomId);
                    $roomName = $contractRoom && $contractRoom->room ? $contractRoom->room->room_name : "Room ID: {$roomId}";
                    $this->line("  - {$roomName} (ID: {$roomId})");
                }
                $this->newLine();
            }
        }

        if ($dryRun) {
            if ($verbose) {
                $this->info("🔍 DRY RUN - Would make changes above");
            }
            return ['fixed' => true, 'message' => 'Dry run'];
        }

        // Apply changes
        try {
            DB::beginTransaction();

            // Add missing rooms
            foreach ($roomsToAdd as $roomId) {
                $quotationRoom = $quotationRooms->firstWhere('room_id', $roomId);
                
                if ($quotationRoom) {
                    ContractRoom::create([
                        'contract_id' => $contract->id,
                        'room_id' => $roomId,
                        'created_by' => 1, // System
                        'updated_by' => 1, // System
                    ]);
                    
                    if ($verbose) {
                        $this->info("✅ Added room: {$quotationRoom->room_name} (ID: {$roomId})");
                    }
                    
                    Log::info("Fixed contract room: Added room {$roomId} to contract {$contract->id}", [
                        'contract_id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'room_id' => $roomId,
                        'room_name' => $quotationRoom->room_name,
                    ]);
                }
            }

            // Remove extra rooms
            foreach ($roomsToRemove as $roomId) {
                $contractRoom = $currentContractRooms->firstWhere('room_id', $roomId);
                
                if ($contractRoom) {
                    $roomName = $contractRoom->room ? $contractRoom->room->room_name : "Room ID: {$roomId}";
                    $contractRoom->delete();
                    
                    if ($verbose) {
                        $this->warn("❌ Removed room: {$roomName} (ID: {$roomId})");
                    }
                    
                    Log::info("Fixed contract room: Removed room {$roomId} from contract {$contract->id}", [
                        'contract_id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'room_id' => $roomId,
                        'room_name' => $roomName,
                    ]);
                }
            }

            DB::commit();
            
            if ($verbose) {
                $this->info("✅ Contract rooms fixed successfully!");
            }
            
            return ['fixed' => true, 'message' => 'Fixed'];
            
        } catch (\Exception $e) {
            DB::rollBack();
            if ($verbose) {
                $this->error("❌ Error fixing contract: {$e->getMessage()}");
            }
            throw $e;
        }
    }
}

