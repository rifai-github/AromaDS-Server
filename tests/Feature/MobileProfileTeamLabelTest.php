<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 25 Nov 2026: profil APK menampilkan "Tim: logistik" (departemen), seharusnya tim dari
 * Master Team ("Tim Service Ops").
 */
class MobileProfileTeamLabelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['teams', 'team_members'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('teams', function (Blueprint $t) {
            $t->id();
            $t->string('team_name')->nullable();
            $t->boolean('active_status')->default(true);
            $t->unsignedBigInteger('team_head_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('team_members', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('user_id');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    private function label(User $user): string
    {
        $method = new \ReflectionMethod(AuthController::class, 'mobileTeamLabel');
        $method->setAccessible(true);

        return $method->invoke(app(AuthController::class), $user);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 978;
        $user->department_name = 'Logistik';

        return $user;
    }

    public function test_active_master_teams_are_shown_instead_of_the_department(): void
    {
        DB::table('teams')->insert([
            ['id' => 14, 'team_name' => 'Tim Service Ops', 'active_status' => true],
            ['id' => 15, 'team_name' => 'Tim Teknisi Ops Service', 'active_status' => true],
            ['id' => 16, 'team_name' => 'Tim Lama', 'active_status' => false],
        ]);
        DB::table('team_members')->insert([
            ['team_id' => 14, 'user_id' => 978, 'is_active' => true],
            ['team_id' => 15, 'user_id' => 978, 'is_active' => true],
            ['team_id' => 16, 'user_id' => 978, 'is_active' => true],
        ]);

        $this->assertSame('Tim Service Ops, Tim Teknisi Ops Service', $this->label($this->user()));
    }

    public function test_team_head_counts_and_department_is_only_a_fallback(): void
    {
        $this->assertSame('Logistik', $this->label($this->user()));

        DB::table('teams')->insert(['id' => 20, 'team_name' => 'Tim Head', 'active_status' => true, 'team_head_id' => 978]);
        $this->assertSame('Tim Head', $this->label($this->user()));
    }
}
