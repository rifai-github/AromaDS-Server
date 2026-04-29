<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SurveyDetail;
use App\Models\MasterRoom;
use Illuminate\Support\Facades\DB;

class MigrateSurveyRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:survey-rooms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link survey_details to master_rooms via room_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of survey rooms...');

        $details = SurveyDetail::with('survey')->whereNull('room_id')->get();
        $count = $details->count();
        $this->info("Found {$count} survey details to process.");

        $updated = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($count);

        foreach ($details as $detail) {
            $survey = $detail->survey;
            if (!$survey || !$survey->building_id) {
                // Cannot link without building info
                $failed++;
                $bar->advance();
                continue;
            }

            // 1. Try Exact Match
            $room = MasterRoom::where('building_id', $survey->building_id)
                ->where('room_name', $detail->room_name)
                ->first();

            // 2. Try Fuzzy Match (StartsWith) - for renamed rooms like JKT-2 -> JKT-2x
            if (!$room) {
                $candidates = MasterRoom::where('building_id', $survey->building_id)
                    ->where('room_name', 'LIKE', $detail->room_name . '%')
                    ->get();
                
                if ($candidates->count() === 1) {
                    $room = $candidates->first();
                    $this->line("");
                    $this->comment("Fuzzy matched: '{$detail->room_name}' -> '{$room->room_name}'");
                }
            }

            if ($room) {
                $detail->room_id = $room->id;
                $detail->save();
                $updated++;
            } else {
                $failed++;
                // $this->line("");
                // $this->warn("Could not find room for: {$detail->room_name} (Building ID: {$survey->building_id})");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("");
        $this->info("Migration completed. Updated: {$updated}, Failed/Skipped: {$failed}");
    }
}
