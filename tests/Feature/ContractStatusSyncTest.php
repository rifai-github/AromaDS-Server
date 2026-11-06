<?php

namespace Tests\Feature;

use App\Models\Contract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Contracts created in the app only ever had `contract_status` changed, so their
 * legacy `status` column stayed `draft` and they were invisible to every reader
 * filtering `where('status', 'active')` (Billing Group dropdown, invoice cron,
 * reports, ...). Contract::booted() now keeps `status` in step.
 */
class ContractStatusSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createContractsTable(withLegacyStatus: true);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');

        parent::tearDown();
    }

    private function createContractsTable(bool $withLegacyStatus): void
    {
        Schema::dropIfExists('contracts');
        Schema::create('contracts', function (Blueprint $table) use ($withLegacyStatus) {
            $table->id();
            $table->string('contract_number')->nullable();
            if ($withLegacyStatus) {
                $table->string('status')->default('draft');
            }
            $table->string('contract_status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function draftContract(string $number = 'MDO-CA/26-10/0002'): Contract
    {
        return Contract::create([
            'contract_number' => $number,
            'status' => 'draft',
            'contract_status' => 'waiting_for_approval',
        ]);
    }

    public function test_activating_an_app_contract_makes_it_visible_to_legacy_status_readers(): void
    {
        $contract = $this->draftContract();
        $this->assertSame('draft', $contract->fresh()->status);

        $contract->update(['contract_status' => 'active']);

        $this->assertSame('active', $contract->fresh()->status);
        $this->assertTrue($contract->fresh()->is_active);
        $this->assertSame(
            ['MDO-CA/26-10/0002'],
            Contract::where('status', 'active')->pluck('contract_number')->all()
        );
    }

    public function test_unpost_back_to_draft_hides_it_again(): void
    {
        $contract = $this->draftContract();
        $contract->update(['contract_status' => 'active']);

        $contract->update(['contract_status' => 'draft']);

        $this->assertSame('draft', $contract->fresh()->status);
        $this->assertSame(0, Contract::where('status', 'active')->count());
    }

    public function test_renewed_source_contract_and_terminated_contract_follow_contract_status(): void
    {
        // Shape of a Catalyst-imported contract: both columns already 'active'.
        $renewedSource = Contract::create(['contract_number' => 'SMG-AG/26-09/0001', 'status' => 'active', 'contract_status' => 'active']);
        $terminated = Contract::create(['contract_number' => 'SMG-AG/26-09/0002', 'status' => 'active', 'contract_status' => 'active']);

        $renewedSource->update(['contract_status' => 'completed']);
        $terminated->update(['contract_status' => 'terminated']);

        $this->assertSame('completed', $renewedSource->fresh()->status);
        $this->assertSame('terminated', $terminated->fresh()->status);
    }

    public function test_contract_created_already_active_gets_active_status(): void
    {
        // e.g. ContractController merge flow creates the contract straight as active.
        $contract = Contract::create(['contract_number' => 'MDO-CA/26-10/0099', 'contract_status' => 'active']);

        $this->assertSame('active', $contract->fresh()->status);
    }

    public function test_status_without_enum_equivalent_leaves_legacy_status_untouched(): void
    {
        $contract = Contract::create(['contract_number' => 'MDO-CA/26-10/0003', 'status' => 'active', 'contract_status' => 'active']);

        $contract->update(['contract_status' => 'cancelled']);

        $this->assertSame('active', $contract->fresh()->status);
    }

    public function test_saving_other_fields_does_not_rewrite_status(): void
    {
        $contract = Contract::create(['contract_number' => 'MDO-CA/26-10/0004', 'status' => 'completed', 'contract_status' => 'active']);
        // Creation syncs it once; force the historical mismatch back in to prove an
        // unrelated save leaves it alone.
        Contract::whereKey($contract->id)->update(['status' => 'completed']);

        $contract->fresh()->update(['notes' => 'edit catatan saja']);

        $this->assertSame('completed', $contract->fresh()->status);
    }

    public function test_repair_command_dry_runs_by_default_and_fixes_drifted_rows_with_apply(): void
    {
        $drifted = Contract::create(['contract_number' => 'MDO-CA/26-10/0002', 'contract_status' => 'active']);
        $completed = Contract::create(['contract_number' => 'MDO-CA/26-10/0003', 'contract_status' => 'completed']);
        $inSync = Contract::create(['contract_number' => 'SMG-AG/26-09/0011', 'contract_status' => 'active']);
        // Recreate the pre-fix production state (query builder bypasses the hook).
        Contract::whereKey([$drifted->id, $completed->id])->update(['status' => 'draft']);

        $this->artisan('contracts:sync-legacy-status')->assertSuccessful();
        $this->assertSame('draft', $drifted->fresh()->status);

        $this->artisan('contracts:sync-legacy-status', ['--apply' => true])
            ->expectsOutputToContain('Diperbarui: 2 kontrak.')
            ->assertSuccessful();

        $this->assertSame('active', $drifted->fresh()->status);
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertSame('active', $inSync->fresh()->status);
    }

    public function test_schema_without_legacy_status_column_still_saves(): void
    {
        $this->createContractsTable(withLegacyStatus: false);

        $contract = Contract::create(['contract_number' => 'TST-CA/26-06/0001', 'contract_status' => 'waiting_for_approval']);
        $contract->update(['contract_status' => 'active']);

        $this->assertSame('active', $contract->fresh()->contract_status);
    }
}
