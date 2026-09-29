<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * QA "Revisi 1 - 29 sep 26": menu komisi di Finance.
 *
 * - Create Commission Level / Marketing Level gagal "Route [commission-levels.index] not defined":
 *   semua route Finance terdaftar dengan prefix nama `finance.`, controller-nya redirect tanpa prefix.
 * - Tombol +New di Commission / Achievement Period / Commission Payment memanggil
 *   openCreateModal() yang tidak pernah didefinisikan, dan view create/edit-nya sebagian tidak ada.
 * - Created At tampil "29/Sep/2026am30": format('d/M/Y<br>at H.i') membaca a/t/r sebagai kode format.
 * - Dropdown Marketing Target hanya berisi user ber-role "Marketing%"; staf import yang cuma
 *   punya posisi "Sales" (atau hanya tercatat sebagai marketing di kontrak) tidak muncul.
 */
class FinanceCommissionPagesTest extends TestCase
{
    /** Controllers whose web routes were removed (moved to Contract Management) - unreachable. */
    private const UNROUTED_CONTROLLERS = ['BillingGroupController.php', 'VirtualAccountController.php'];

    protected function tearDown(): void
    {
        foreach (['contracts', 'user_roles', 'roles', 'departments', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_finance_controllers_only_redirect_to_defined_routes(): void
    {
        $missing = [];

        foreach (glob(app_path('Http/Controllers/Finance/*.php')) as $file) {
            if (in_array(basename($file), self::UNROUTED_CONTROLLERS, true)) {
                continue;
            }

            $missing = array_merge($missing, $this->undefinedRouteNames($file));
        }

        $this->assertSame([], $missing);
    }

    public function test_finance_views_only_link_to_defined_routes(): void
    {
        $missing = [];

        foreach (glob(resource_path('views/finance/*/*.blade.php')) as $file) {
            $missing = array_merge($missing, $this->undefinedRouteNames($file));
        }

        $this->assertSame([], $missing);
    }

    /**
     * @testWith ["finance.commissions.index", "finance.commissions.create"]
     *           ["finance.achievement-periods.index", "finance.achievement-periods.create"]
     *           ["finance.commission-payments.index", "finance.commission-payments.create"]
     */
    public function test_new_button_links_to_the_create_page(string $indexView, string $createRoute): void
    {
        $source = file_get_contents(View::getFinder()->find($indexView));

        $this->assertStringNotContainsString('openCreateModal(', $source);
        $this->assertStringNotContainsString('openEditModal(', $source);
        $this->assertStringContainsString("route('{$createRoute}')", $source);
    }

    /**
     * @testWith ["finance.commissions.create"]
     *           ["finance.achievement-periods.create"]
     *           ["finance.achievement-periods.edit"]
     *           ["finance.commission-payments.create"]
     *           ["finance.commission-payments.edit"]
     */
    public function test_create_and_edit_views_exist(string $view): void
    {
        $this->assertTrue(View::exists($view), "View [{$view}] is missing");
    }

    public function test_no_view_puts_literal_text_inside_a_date_format_string(): void
    {
        // "<br>at" inside format() becomes "<b" + RFC-2822 date + ">" + am/pm + days-in-month.
        $this->assertSame(
            '29/Sep/2026<br>at 11.41',
            Carbon::parse('2026-09-29 11:41')->format('d/M/Y').'<br>at '.Carbon::parse('2026-09-29 11:41')->format('H.i')
        );

        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($files as $file) {
            if (! str_ends_with($file, '.blade.php')) {
                continue;
            }

            if (preg_match_all("/->format\('[^']*<br>[^']*'\)/", file_get_contents($file), $matches)) {
                $offenders[] = basename(dirname($file)).'/'.basename($file).': '.implode(', ', $matches[0]);
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_commission_eligible_users_include_sales_position_and_contract_marketing(): void
    {
        $this->createUserSchema();

        $this->insertUser(1, 'Role Marketing');
        $this->insertUser(2, 'Gabriella Krissta M', ['position_name' => 'Sales']);
        $this->insertUser(3, 'Fahrul Hidayat'); // no role/position/department, only marketing on a contract
        $this->insertUser(4, 'Achiever Only', ['is_commission_achiever' => true]);
        $this->insertUser(5, 'Recipient Only');
        $this->insertUser(6, 'Sales Dept', ['department_id' => 1]);
        $this->insertUser(7, 'Teknisi Biasa', ['position_name' => 'Teknisi']);
        $this->insertUser(8, 'Inactive Sales', ['position_name' => 'Sales', 'is_active' => false]);

        DB::table('departments')->insert(['id' => 1, 'name' => 'Sales & Marketing']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'Marketing Staff']);
        DB::table('user_roles')->insert(['user_id' => 1, 'role_id' => 1]);
        DB::table('contracts')->insert([
            ['id' => 1, 'marketing_id' => 3, 'commission_recipient_id' => null],
            ['id' => 2, 'marketing_id' => null, 'commission_recipient_id' => 5],
        ]);

        $this->assertEqualsCanonicalizing(
            [1, 2, 3, 4, 5, 6],
            User::commissionEligible()->pluck('id')->all()
        );

        // Editing a target keeps its current user selectable even when no longer eligible.
        $this->assertContains(7, User::commissionEligible(7)->pluck('id')->all());
    }

    private function undefinedRouteNames(string $file): array
    {
        $source = file_get_contents($file);
        $missing = [];

        if (preg_match_all('/route\(\s*[\'"]([\w.\-]+)[\'"]/', $source, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as [$name, $offset]) {
                if (! Route::has($name)) {
                    $line = substr_count(substr($source, 0, $offset), "\n") + 1;
                    $missing[] = basename($file).":{$line} {$name}";
                }
            }
        }

        return $missing;
    }

    private function createUserSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_commission_achiever')->default(false);
            $table->foreignId('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->string('position_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_id')->nullable();
            $table->foreignId('commission_recipient_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function insertUser(int $id, string $name, array $attributes = []): void
    {
        DB::table('users')->insert(array_merge(['id' => $id, 'name' => $name], $attributes));
    }
}
