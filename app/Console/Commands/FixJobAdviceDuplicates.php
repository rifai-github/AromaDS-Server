<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobAdviceRoom;
use App\Models\JobAdvice;
use App\Models\QuotationRoom;
use Illuminate\Support\Facades\DB;

class FixJobAdviceDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketing:fix-ja-duplicates {--dry-run : Only show what would be done without modifying data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate Job Advice rooms and restore missing quotation links';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info("Running in DRY RUN mode. No changes will be made.");
        }

        $this->fixDuplicates($isDryRun);
        $this->restoreQuotationLinks($isDryRun);

        return 0;
    }

    private function fixDuplicates($isDryRun)
    {
        $this->info("Checking for duplicate Job Advice Rooms...");

        // Find duplicates grouping by job_advice_id, rental_product_id, and room_name
        // This catches cases where quotation_room_id might be null in one and populated in another
        $duplicates = JobAdviceRoom::select('job_advice_id', 'rental_product_id', 'room_name', DB::raw('COUNT(*) as count'))
            ->groupBy('job_advice_id', 'rental_product_id', 'room_name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info("No duplicate rooms found (checked by room_name and rental).");
            return;
        }

        $this->info("Found " . $duplicates->count() . " groups of duplicates.");

        $deletedCount = 0;

        foreach ($duplicates as $group) {
            // Get all records for this group
            $rooms = JobAdviceRoom::where('job_advice_id', $group->job_advice_id)
                ->where('rental_product_id', $group->rental_product_id)
                ->where('room_name', $group->room_name)
                ->orderBy('id')
                ->get();
            
            // Prefer keeping the one with the most information (e.g. has quotation_room_id)
            $keep = $rooms->sortByDesc(function ($room) {
                return ($room->contract_room_id ? 10 : 0) + 
                       ($room->quotation_room_id ? 10 : 0) +
                       ($room->id); // Tie-breaker: keep higher ID (newer) or lower? Usually keep older (lower ID) is safer, but here we want the "best" one.
                       // Let's actually sort by: Has IDs (desc), then ID (asc) -> Keep the first one.
            })->first();

            // Wait, logic above:
            // We want to KEEP the record that has data.
            // Sort: 
            // 1. Has contract/quotation ID (Primary)
            // 2. ID (Secondary) - keep older? or newer?
            // Let's keep the one that looks "most complete".
            
            $sorted = $rooms->sortByDesc(function ($room) {
                return count(array_filter([$room->contract_room_id, $room->quotation_room_id]));
            })->values();

            $keep = $sorted->first();
            $roomsToDelete = $sorted->slice(1);
            
            $this->info("Group: JA #{$group->job_advice_id}, Room '{$group->room_name}'. Keeping ID: {$keep->id} (Has IDs: C=" . ($keep->contract_room_id??'N') . ", Q=" . ($keep->quotation_room_id??'N') . "). Deleting " . $roomsToDelete->count() . " duplicates.");

            foreach ($roomsToDelete as $duplicate) {
                if (!$isDryRun) {
                    $duplicate->delete();
                }
                $deletedCount++;
            }
        }

        $this->info("Total duplicates " . ($isDryRun ? "found" : "deleted") . ": $deletedCount");
    }

    private function restoreQuotationLinks($isDryRun)
    {
        $this->info("\nChecking for Job Advice Rooms with missing quotation links...");

        // Find JA rooms that belong to a JA from Quotation, but have null quotation_room_id
        $rooms = JobAdviceRoom::whereNull('quotation_room_id')
            ->whereHas('jobAdvice', function($q) {
                $q->whereNotNull('quotation_id');
            })
            ->with(['jobAdvice.quotation'])
            ->get();

        if ($rooms->isEmpty()) {
            $this->info("No rooms with missing quotation links found.");
            return;
        }

        $restoredCount = 0;

        foreach ($rooms as $room) {
            $quotation = $room->jobAdvice->quotation;
            if (!$quotation) continue;

            // Try to match with QuotationRoom by room name and rental
            $match = QuotationRoom::where('quotation_id', $quotation->id)
                ->where('rental_product_id', $room->rental_product_id)
                // We use LIKE because sometimes names might have slight variations or be trimmed
                ->where(function($q) use ($room) {
                    $q->where('room_name', $room->room_name)
                      ->orWhere('room_name', 'LIKE', $room->room_name);
                })
                ->first();

            if ($match) {
                $this->info("Match found for JA Room #{$room->id} ({$room->room_name}) -> Quotation Room #{$match->id}");
                
                if (!$isDryRun) {
                    $room->quotation_room_id = $match->id;
                    $room->save();
                }
                $restoredCount++;
            } else {
                $this->warn("No match found for JA Room #{$room->id} ({$room->room_name}) in Quotation #{$quotation->id}");
            }
        }

        $this->info("Total links " . ($isDryRun ? "identifed" : "restored") . ": $restoredCount");
    }
}
