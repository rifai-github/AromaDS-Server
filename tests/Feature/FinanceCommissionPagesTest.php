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
        foreach (['commission_payments', 'commission_calculations', 'marketing_targets', 'contracts', 'user_roles', 'roles', 'departments', 'users'] as $table) {
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

    public function test_marketing_target_can_be_created_without_is_locked_in_the_request(): void
    {
        // QA 30 Sep: create form has no lock checkbox -> 'Undefined array key "is_locked"'.
        Schema::create('marketing_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('achievement_period_id');
            $table->string('target_type');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('achieved_amount', 15, 2)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->date('lock_date')->nullable();
            $table->foreignId('locked_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $result = app(\App\Services\Finance\MarketingTargetService::class)->createOrUpdateTarget([
            'user_id' => 1,
            'achievement_period_id' => 1,
            'target_type' => 'new',
            'target_amount' => 3000000,
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertDatabaseHas('marketing_targets', ['user_id' => 1, 'target_type' => 'new', 'is_locked' => false]);

        $locked = app(\App\Services\Finance\MarketingTargetService::class)->createOrUpdateTarget([
            'user_id' => 2, 'achievement_period_id' => 1, 'target_type' => 'new',
            'target_amount' => 1, 'is_locked' => true, 'locked_by' => 9,
        ]);
        $this->assertTrue($locked['success']);
        $this->assertDatabaseHas('marketing_targets', ['user_id' => 2, 'is_locked' => true, 'locked_by' => 9]);
    }

    public function test_achievement_optional_fields_are_not_required_and_new_button_opens_create_page(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Finance/AchievementController.php'));
        foreach (['achieved_amount', 'commission_rate', 'achievement_date'] as $field) {
            $this->assertStringNotContainsString("'{$field}' => 'required", $controller);
        }

        $index = file_get_contents(View::getFinder()->find('finance.achievements.index'));
        $this->assertStringContainsString("route('finance.achievements.create')", $index);
        $this->assertStringNotContainsString('openCreateModal();', $index); // no script re-hijacking the link
    }

    public function test_commission_payment_list_filters_by_user_status_method_and_date_range(): void
    {
        // QA 1 Okt: filter user dan Start/End Date di Commission Payment tak berpengaruh -
        // form mengirim parameter datar, trait filter hanya membaca filter[kolom].
        $this->createUserSchema();
        Schema::create('commission_calculations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_calculation_id')->nullable();
            $table->foreignId('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('payment_reference')->nullable();
            $table->date('payment_date');
            $table->string('status')->default('pending');
            $table->foreignId('processed_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->insertUser(1, 'Wahyu');
        $this->insertUser(2, 'Yan');
        foreach ([
            [1, 1, 'bank_transfer', '2026-10-01', 'pending'],
            [2, 1, 'cash', '2026-10-01', 'pending'],
            [3, 2, 'bank_transfer', '2026-09-29', 'completed'],
        ] as [$id, $user, $method, $date, $status]) {
            DB::table('commission_payments')->insert([
                'id' => $id, 'user_id' => $user, 'amount' => 1000, 'payment_method' => $method,
                'payment_date' => $date, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $ids = function (array $query): array {
            $request = \Illuminate\Http\Request::create('/finance/commission-payments', 'GET', $query);
            app()->instance('request', $request);
            $view = app(\App\Http\Controllers\Finance\CommissionPaymentController::class)->index($request);

            return $view->getData()['payments']->pluck('id')->sort()->values()->all();
        };

        $this->assertSame([1, 2, 3], $ids([]));
        $this->assertSame([3], $ids(['user_id' => 2]));
        $this->assertSame([1, 2], $ids(['user_id' => 1]));
        $this->assertSame([3], $ids(['status' => 'completed']));
        $this->assertSame([2], $ids(['payment_method' => 'cash']));
        // Rentang 28-30 Sep hanya memuat pembayaran 29 Sep; yang 1 Okt harus keluar.
        $this->assertSame([3], $ids(['start_date' => '2026-09-28', 'end_date' => '2026-09-30']));
        $this->assertSame([1, 2], $ids(['start_date' => '2026-10-01']));
        $this->assertSame([], $ids(['user_id' => 2, 'start_date' => '2026-10-01']));
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
