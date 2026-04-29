<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SurveyDetail;
use App\Models\MasterRoom;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class LinkSurveyRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'survey:link-rooms 
                            {--dry-run : Show what would be linked without making changes}
                            {--survey= : Link only for specific survey ID}
                            {--create-missing : Auto-create MasterRoom for orphans with no match}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link orphaned SurveyDetails to their corresponding MasterRooms. Use --create-missing to auto-create missing MasterRooms.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $surveyId = $this->option('survey');
        $createMissing = $this->option('create-missing');
        $force = $this->option('force');
        
        $this->info('🔍 Finding orphaned SurveyDetails (where room_id IS NULL)...');
        $this->newLine();
        
        // Build query for orphaned survey details
        $query = SurveyDetail::whereNull('room_id')
            ->with(['survey.building']);
        
        if ($surveyId) {
            $query->where('survey_id', $surveyId);
            $this->info("📋 Filtering for Survey ID: {$surveyId}");
        }
        
        $orphanedDetails = $query->get();
        
        if ($orphanedDetails->isEmpty()) {
            $this->info('✅ No orphaned SurveyDetails found. All records are properly linked!');
            return Command::SUCCESS;
        }
        
        $this->info("📊 Found {$orphanedDetails->count()} orphaned SurveyDetail(s)");
        $this->newLine();
        
        $linked = 0;
        $created = 0;
        $notFound = 0;
        $noBuilding = 0;
        
        $tableData = [];
        
        foreach ($orphanedDetails as $detail) {
            $survey = $detail->survey;
            
            if (!$survey || !$survey->building_id) {
                $tableData[] = [
                    $detail->id,
                    $detail->getRawOriginal('room_name'),
                    $survey->survey_number ?? 'N/A',
                    '-',
                    '-',
                    '⚠️ No building'
                ];
                $noBuilding++;
                continue;
            }
            
            // Strategy 1: Find matching MasterRoom by EXACT room_name + building_id
            $masterRoom = MasterRoom::where('building_id', $survey->building_id)
                ->where('room_name', $detail->getRawOriginal('room_name'))
                ->first();
            
            if ($masterRoom) {
                $tableData[] = [
                    $detail->id,
                    $detail->getRawOriginal('room_name'),
                    $survey->survey_number,
                    $masterRoom->id,
                    $masterRoom->room_name,
                    '✅ Exact match'
                ];
                
                if (!$isDryRun) {
                    $detail->room_id = $masterRoom->id;
                    $detail->save();
                }
                
                $linked++;
                continue;
            }
            
            // Strategy 2: Find by SIMILAR room_name (case-insensitive, trim whitespace)
            $masterRoom = MasterRoom::where('building_id', $survey->building_id)
                ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim($detail->getRawOriginal('room_name')))])
                ->first();
            
            if ($masterRoom) {
                $tableData[] = [
                    $detail->id,
                    $detail->getRawOriginal('room_name'),
                    $survey->survey_number,
                    $masterRoom->id,
                    $masterRoom->room_name,
                    '✅ Similar match'
                ];
                
                if (!$isDryRun) {
                    $detail->room_id = $masterRoom->id;
                    $detail->save();
                }
                
                $linked++;
                continue;
            }
            
            // Strategy 3: Create missing MasterRoom if --create-missing flag is set
            if ($createMissing) {
                $specs = json_decode($detail->getRawOriginal('specifications') ?? '{}', true);
                
                if (!$isDryRun) {
                    $masterRoom = MasterRoom::create([
                        'building_id' => $survey->building_id,
                        'room_name' => $detail->getRawOriginal('room_name'),
                        'room_code' => 'RM-' . strtoupper(substr($detail->getRawOriginal('room_name'), 0, 3)) . '-' . time() . rand(100, 999),
                        'room_type' => $detail->getRawOriginal('room_type'),
                        'room_floor' => $specs['floor'] ?? null,
                        'room_qty' => $specs['qty'] ?? $detail->quantity_needed ?? 1,
                        'room_temperature' => $specs['temperature'] ?? 0,
                        'room_intensity' => $specs['intensity'] ?? null,
                        'room_installation_type' => $specs['installation_type'] ?? null,
                        'room_length' => $specs['length'] ?? null,
                        'room_width' => $specs['width'] ?? null,
                        'room_height' => $specs['height'] ?? null,
                        'room_remark' => $specs['remark'] ?? null,
                        'is_active' => true,
                        'created_by' => null, // System-generated
                        'updated_by' => null
                    ]);
                    
                    $detail->room_id = $masterRoom->id;
                    $detail->save();
                    
                    $tableData[] = [
                        $detail->id,
                        $detail->getRawOriginal('room_name'),
                        $survey->survey_number,
                        $masterRoom->id,
                        $masterRoom->room_name,
                        '🆕 Created'
                    ];
                } else {
                    $tableData[] = [
                        $detail->id,
                        $detail->getRawOriginal('room_name'),
                        $survey->survey_number,
                        'NEW',
                        $detail->getRawOriginal('room_name'),
                        '🆕 Would create'
                    ];
                }
                
                $created++;
                continue;
            }
            
            // No match found and not creating
            $tableData[] = [
                $detail->id,
                $detail->getRawOriginal('room_name'),
                $survey->survey_number,
                '-',
                '-',
                '❌ No match'
            ];
            $notFound++;
        }
        
        // Display results in table
        $this->table(
            ['Detail ID', 'Room Name (Snapshot)', 'Survey #', 'MasterRoom ID', 'MasterRoom Name', 'Status'],
            $tableData
        );
        
        $this->newLine();
        
        if ($isDryRun) {
            $this->info('🔄 DRY RUN - No changes were made');
            $this->info("   Would link (exact/similar): {$linked} record(s)");
            if ($createMissing) {
                $this->info("   Would create MasterRoom for: {$created} record(s)");
            }
            $this->info("   No match found: {$notFound} record(s)");
            $this->info("   No building_id: {$noBuilding} record(s)");
            $this->newLine();
            $this->info('Run without --dry-run to apply changes.');
            if (!$createMissing && $notFound > 0) {
                $this->warn('💡 TIP: Use --create-missing to auto-create MasterRooms for unmatched records.');
            }
        } else {
            $this->info('📝 Summary:');
            $this->info("   ✅ Linked (exact/similar): {$linked} record(s)");
            if ($createMissing) {
                $this->info("   🆕 Created MasterRooms: {$created} record(s)");
            }
            $this->info("   ❌ No match found: {$notFound} record(s)");
            $this->info("   ⚠️  No building_id: {$noBuilding} record(s)");
            
            if ($notFound > 0 && !$createMissing) {
                $this->newLine();
                $this->warn('💡 TIP: Run with --create-missing to auto-create MasterRooms for remaining records.');
            }
        }
        
        return Command::SUCCESS;
    }
}
