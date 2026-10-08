<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AchievementStatusBackfillTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('achievements');
        parent::tearDown();
    }

    public function test_backfill_only_fixes_pending_automatic_rows(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('achievement_type');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('achieved_amount', 15, 2);
            $table->string('status');
        });

        DB::table('achievements')->insert([
            ['id' => 1, 'achievement_type' => 'new', 'target_amount' => 3_000_000, 'achieved_amount' => 5_172_000, 'status' => 'pending'],
            ['id' => 2, 'achievement_type' => 'renewal', 'target_amount' => 3_000_000, 'achieved_amount' => 3_000_000, 'status' => 'pending'],
            ['id' => 3, 'achievement_type' => 'new', 'target_amount' => 3_000_000, 'achieved_amount' => 2_400_000, 'status' => 'pending'],
            // Baris manual QA tidak disentuh.
            ['id' => 4, 'achievement_type' => 'sales', 'target_amount' => 3_000_000, 'achieved_amount' => 9_000_000, 'status' => 'pending'],
        ]);

        $migration = require database_path('migrations/2026_10_08_000001_recompute_automatic_achievement_status.php');
        $migration->up();

        $this->assertSame(
            [1 => 'exceeded', 2 => 'achieved', 3 => 'pending', 4 => 'pending'],
            DB::table('achievements')->orderBy('id')->pluck('status', 'id')->all()
        );
    }
}
