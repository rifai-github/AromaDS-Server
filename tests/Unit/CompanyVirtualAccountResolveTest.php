<?php

namespace Tests\Unit;

use App\Models\CompanyVirtualAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyVirtualAccountResolveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // migrations/ is empty in this checkout, so build the table under test.
        Schema::create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('bank_payment_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('company_virtual_accounts');

        parent::tearDown();
    }

    private function makeVa(string $number, array $overrides = []): CompanyVirtualAccount
    {
        return CompanyVirtualAccount::create(array_merge([
            'account_number' => $number,
            'account_name' => 'Test '.$number,
            'customer_id' => random_int(1000, 999999),
            'is_active' => true,
        ], $overrides));
    }

    public function test_matches_a_legacy_six_digit_number_exactly_as_stored(): void
    {
        $va = $this->makeVa('999998');

        $this->assertSame($va->id, CompanyVirtualAccount::resolveByAccountNumber('999998')?->id);
    }

    public function test_matches_a_generated_eleven_digit_number(): void
    {
        $va = $this->makeVa('50000000001');

        $this->assertSame($va->id, CompanyVirtualAccount::resolveByAccountNumber('50000000001')?->id);
    }

    public function test_tolerates_separators_and_surrounding_whitespace(): void
    {
        $va = $this->makeVa('50000000001');

        $this->assertSame($va->id, CompanyVirtualAccount::resolveByAccountNumber(' 5000-0000 001 ')?->id);
    }

    public function test_matches_when_only_leading_zero_padding_differs(): void
    {
        $va = $this->makeVa('999998');

        // Bank sends a zero-padded 16-wide number for the same account.
        $this->assertSame($va->id, CompanyVirtualAccount::resolveByAccountNumber('0000000000999998')?->id);
    }

    public function test_exact_match_wins_over_a_padding_variant(): void
    {
        // Mirrors the real QA pair: '000007' and '7' are different accounts.
        $padded = $this->makeVa('000007', ['customer_id' => 5233]);
        $bare = $this->makeVa('7', ['customer_id' => 410]);

        $this->assertSame($padded->id, CompanyVirtualAccount::resolveByAccountNumber('000007')?->id);
        $this->assertSame($bare->id, CompanyVirtualAccount::resolveByAccountNumber('7')?->id);
    }

    public function test_refuses_to_guess_when_padding_match_is_ambiguous(): void
    {
        // Both active and both normalize to '7' — crediting either could pay
        // the wrong customer, so no account may be returned.
        $this->makeVa('000007', ['customer_id' => 5233]);
        $this->makeVa('7', ['customer_id' => 410]);

        $this->assertNull(CompanyVirtualAccount::resolveByAccountNumber('00000000000000007'));
    }

    public function test_prefers_an_active_account_over_an_inactive_one(): void
    {
        $this->makeVa('000007', ['customer_id' => 5233, 'is_active' => false]);
        $active = $this->makeVa('7', ['customer_id' => 410, 'is_active' => true]);

        // Ambiguous across all rows, but unambiguous among active ones.
        $this->assertSame($active->id, CompanyVirtualAccount::resolveByAccountNumber('0000007')?->id);
    }

    public function test_still_resolves_an_inactive_account_when_it_is_the_only_match(): void
    {
        $va = $this->makeVa('123456', ['is_active' => false]);

        $this->assertSame($va->id, CompanyVirtualAccount::resolveByAccountNumber('123456')?->id);
    }

    public function test_returns_null_for_unknown_blank_or_non_numeric_input(): void
    {
        $this->makeVa('999998');

        $this->assertNull(CompanyVirtualAccount::resolveByAccountNumber('123123123'));
        $this->assertNull(CompanyVirtualAccount::resolveByAccountNumber(''));
        $this->assertNull(CompanyVirtualAccount::resolveByAccountNumber('   '));
        $this->assertNull(CompanyVirtualAccount::resolveByAccountNumber(null));
    }

    public function test_does_not_treat_a_different_number_as_a_padding_variant(): void
    {
        $this->makeVa('999998');

        // Same digits plus a trailing zero is a different account, not padding.
        $this->assertNull(CompanyVirtualAccount::resolveByAccountNumber('9999980'));
    }
}
