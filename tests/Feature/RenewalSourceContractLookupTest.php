<?php

namespace Tests\Feature;

use Tests\TestCase;

class RenewalSourceContractLookupTest extends TestCase
{
    public function test_renewal_source_contract_lookups_include_archived_contracts(): void
    {
        $quotationModel = file_get_contents(app_path('Models/Quotation.php'));
        $contractWizardController = file_get_contents(app_path('Http/Controllers/Marketing/ContractWizardController.php'));
        $quotationWizardController = file_get_contents(app_path('Http/Controllers/Marketing/QuotationWizardController.php'));
        $quotationController = file_get_contents(app_path('Http/Controllers/Marketing/QuotationController.php'));
        $contractController = file_get_contents(app_path('Http/Controllers/Marketing/ContractController.php'));

        $this->assertStringContainsString(
            "belongsTo(Contract::class, 'existing_contract_id')",
            $quotationModel
        );
        $this->assertStringContainsString('->withoutGlobalScopes()', $quotationModel);

        $this->assertStringContainsString(
            'Contract::withoutGlobalScopes()->find($quotation->existing_contract_id)',
            $contractWizardController
        );

        $this->assertStringContainsString(
            "Contract::withoutGlobalScopes()->with('customer')->find(",
            $quotationWizardController
        );

        $this->assertStringContainsString(
            'Contract::withoutGlobalScopes()->find($existingContractId)',
            $quotationWizardController
        );

        $this->assertStringContainsString(
            'Contract::withoutGlobalScopes()->find($existingContractId)',
            $quotationController
        );

        $this->assertStringContainsString(
            'Contract::withoutGlobalScopes()->find($quotation->existing_contract_id)',
            $contractController
        );
    }
}
