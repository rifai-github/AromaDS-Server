<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuotationWizardRenewalSummaryFallbackTest extends TestCase
{
    public function test_renewal_summary_falls_back_to_contract_customer_when_survey_is_empty(): void
    {
        $view = file_get_contents(resource_path('views/marketing/quotations/wizard/create.blade.php'));

        $this->assertStringContainsString(
            "quotation_type === 'renewal' && window.renewalContractData",
            $view
        );
        $this->assertStringContainsString(
            'var renewalCustomer = window.renewalContractData.customer || {};',
            $view
        );
        $this->assertStringContainsString(
            "customerName = renewalCustomer.name || '-';",
            $view
        );
        $this->assertStringContainsString(
            'String(renewalCustomer.company_type).toUpperCase()',
            $view
        );
    }

    public function test_renewal_summary_falls_back_to_source_survey_numbers(): void
    {
        $view = file_get_contents(resource_path('views/marketing/quotations/wizard/create.blade.php'));

        $this->assertStringContainsString(
            'var renewalSurveys = Array.isArray(window.renewalContractData.surveys)',
            $view
        );
        $this->assertStringContainsString(
            'renewalSurveyNumbers.push(window.renewalContractData.survey_number);',
            $view
        );
        $this->assertStringContainsString(
            "surveyListHtml = '<li>-</li>';",
            $view
        );
    }
}
