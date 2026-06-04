<?php

namespace Tests\Feature;

use Tests\TestCase;

class RenewalSourceContractLookupTest extends TestCase
{
    public function test_renewal_source_contract_lookups_include_archived_contracts(): void
    {
        $quotationModel = file_get_contents(app_path('Models/Quotation.php'));
        $contractModel = file_get_contents(app_path('Models/Contract.php'));
        $contractWizardController = file_get_contents(app_path('Http/Controllers/Marketing/ContractWizardController.php'));
        $quotationWizardController = file_get_contents(app_path('Http/Controllers/Marketing/QuotationWizardController.php'));
        $quotationController = file_get_contents(app_path('Http/Controllers/Marketing/QuotationController.php'));
        $contractController = file_get_contents(app_path('Http/Controllers/Marketing/ContractController.php'));

        $this->assertStringContainsString(
            "belongsTo(Contract::class, 'existing_contract_id')",
            $quotationModel
        );
        $this->assertStringContainsString('->withoutGlobalScopes()', $quotationModel);
        $this->assertStringContainsString('public static function findRenewalSource', $contractModel);
        $this->assertStringContainsString('DB::table((new static)->getTable())', $contractModel);

        $this->assertStringContainsString(
            'Contract::findRenewalSource($quotation->existing_contract_id)',
            $contractWizardController
        );
        $this->assertStringContainsString(
            "'Renewal source contract lookup failed during contract draft creation'",
            $contractWizardController
        );
        $this->assertStringNotContainsString(
            "\$oldContract?->getRenewalBlockReason() ?? 'Contract lama untuk renewal tidak ditemukan.'",
            $contractWizardController
        );

        $this->assertStringContainsString(
            "Contract::findRenewalSource(\$request->get('existing_contract_id'))?->load('customer')",
            $quotationWizardController
        );

        $this->assertStringContainsString(
            'Contract::findRenewalSource($existingContractId)',
            $quotationWizardController
        );

        $this->assertStringContainsString(
            'Contract::findRenewalSource($existingContractId)',
            $quotationController
        );

        $this->assertStringContainsString(
            'Contract::findRenewalSource($quotation->existing_contract_id)',
            $contractController
        );
    }
}
