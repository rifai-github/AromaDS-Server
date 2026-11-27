<?php

namespace Tests\Feature;

use App\Models\CustomerContact;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (Customer Testing Indo, wizard kontrak):
 *  - step 4 menampilkan gedung customer yang tidak ada di SQ ("x Gedung Test 3 x");
 *  - PIC baru dari Billing Group 1 tak muncul di Billing Group 2;
 *  - edit Billing Group: ganti PIC lewat dropdown tak tersimpan (select2 vs addEventListener).
 */
class ContractWizardBuildingAndPicDropdownTest extends TestCase
{
    public function test_contact_cache_for_the_customer_api_is_cleared_with_the_survey_cache(): void
    {
        Cache::put('customer:15230:api-contacts:v3', ['stale'], 300);
        Cache::put('survey-wizard:contacts:v2:15230', ['stale'], 300);

        CustomerContact::forgetSurveyWizardContactsCache(15230);

        $this->assertNull(Cache::get('customer:15230:api-contacts:v3'));
        $this->assertNull(Cache::get('survey-wizard:contacts:v2:15230'));
    }

    public function test_wizard_limits_buildings_to_the_quotation_and_refreshes_contacts(): void
    {
        $wizard = file_get_contents(resource_path('views/marketing/contracts/wizard/create.blade.php'));

        $this->assertStringContainsString('merged.filter(building => quotationBuildingIds.has(building.id))', $wizard);
        $this->assertStringContainsString('customerLookupCache.contacts.delete(customerId)', $wizard);
        $this->assertStringContainsString("option.setAttribute('data-contact-email', contact.email || '');", $wizard);
    }

    public function test_edit_billing_group_listens_to_select2_change(): void
    {
        $view = file_get_contents(resource_path('views/finance/billing-groups/editbg.blade.php'));

        $this->assertStringContainsString("window.jQuery(picSelect).on('change select2:select', applyPicSelection);", $view);
    }
}
