<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalystImportedUserPasswordTest extends TestCase
{
    public function test_it_generates_a_secure_unknown_password_for_new_imported_users(): void
    {
        $importer = new class extends CatalystMasterDataImporter
        {
            public function makePassword(): string
            {
                return $this->newImportedUserPassword();
            }
        };

        $first = $importer->makePassword();
        $second = $importer->makePassword();

        $this->assertSame('bcrypt', Hash::info($first)['algoName']);
        $this->assertFalse(Hash::needsRehash($first));
        $this->assertNotSame($first, $second);

        foreach (['password123', 'admin', 'catalyst'] as $knownPassword) {
            $this->assertFalse(Hash::check($knownPassword, $first));
            $this->assertFalse(Hash::check($knownPassword, $second));
        }
    }
}
