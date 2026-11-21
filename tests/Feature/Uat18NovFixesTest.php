<?php

namespace Tests\Feature;

use App\Models\Survey;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * UAT 18 Nov 2026 (Survey -> Invoice): bug yang aturannya sudah jelas.
 */
class Uat18NovFixesTest extends TestCase
{
    private function viewSource(string $view): string
    {
        return file_get_contents(View::getFinder()->find($view));
    }

    public function test_material_assign_restores_approved_when_qty_returns_to_the_approved_value(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Operational/JobAssignMaterialIssueController.php'));

        $this->assertStringContainsString("\$metadata['qty_edit_approved_snapshot']", $source);
        $this->assertMatchesRegularExpression("/if \(\\\$backToApproved\) \{.*?'status' => 'approved'/s", $source);
    }

    public function test_material_assign_submit_confirmation_lists_buildings(): void
    {
        $source = $this->viewSource('operational.job-assign-material-issues.index');

        $this->assertSame(2, substr_count($source, 'data-building-name="{{ $jobSchedule && $jobSchedule->building'));
        $this->assertStringContainsString('Apakah Anda yakin material assign ini untuk ${formatSubmitBuildingList(selectedRows)}?', $source);
    }

    public function test_survey_index_sort_is_whitelisted_and_sorts_user_relations_by_subquery(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Marketing/SurveyController.php'));

        $this->assertStringNotContainsString('$query->orderBy($sort, $direction);', $source);
        $this->assertStringContainsString("'marketing.name' => 'surveys.marketing_id'", $source);
        $this->assertStringContainsString("'updater.name' => 'surveys.updated_by'", $source);
    }

    public function test_survey_detail_sends_values_to_js_as_json_not_html_escaped_strings(): void
    {
        $source = $this->viewSource('marketing.surveys.show');

        $this->assertStringNotContainsString("company_name: '{{ \$survey->customer->name ?? \$survey->company_name }}'", $source);
        $this->assertSame(2, substr_count($source, 'company_name: @json($survey->customer->name ?? $survey->company_name)'));
        $this->assertStringNotContainsString('addslashes($survey->survey_result)', $source);
        // Finalize mengirim email yang terlihat (kontak, lalu customer) -> tidak lagi 422.
        $this->assertSame(2, substr_count($source, 'email: @json($survey->email ?: $survey->customer?->email)'));
    }

    public function test_survey_decodes_previously_escaped_text(): void
    {
        $this->assertSame("l'arc en ciel", Survey::decodeStoredHtmlEntities('l&#039;arc en ciel'));
        $this->assertSame('l"arc', Survey::decodeStoredHtmlEntities('l&quot;arc'));
        $this->assertSame("l'arc", Survey::decodeStoredHtmlEntities('l&amp;#039;arc'));
        $this->assertSame('PT A & B', Survey::decodeStoredHtmlEntities('PT A &amp; B'));
        $this->assertSame('Biasa', Survey::decodeStoredHtmlEntities('Biasa'));
    }

    public function test_survey_pic_prefers_the_contact_over_the_customer(): void
    {
        $survey = new Survey(['email' => 'pic@kontak.test', 'phone_1' => '0811']);
        $survey->setRelation('customer', new \App\Models\Customer(['email' => 'office@customer.test', 'phone' => '021']));

        $this->assertSame('pic@kontak.test', $survey->display_email);
        $this->assertSame('0811', $survey->display_phone_one);

        $survey = new Survey(['email' => null, 'phone_1' => null]);
        $survey->setRelation('customer', new \App\Models\Customer(['email' => 'office@customer.test', 'phone' => '021']));
        $this->assertSame('office@customer.test', $survey->display_email);
    }

    public function test_survey_wizard_salutation_uses_option_name_and_location_detail_is_required(): void
    {
        $view = $this->viewSource('marketing.surveys.wizard.create');
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/SurveyWizardController.php'));

        $this->assertStringNotContainsString('$salutation->label ?? $salutation->option_name', $view);
        $this->assertStringNotContainsString("Cache::remember('survey-wizard:salutations'", $controller);
        $this->assertStringContainsString("showFieldError('#building_location_detail', 'Lokasi Detail harus diisi')", $view);
        $this->assertStringContainsString("\$option.is('[data-address1]')", $view);
    }

    public function test_quotation_wizard_keeps_other_rows_select2_and_rounds_prices(): void
    {
        $view = $this->viewSource('marketing.quotations.wizard.create');

        $this->assertStringNotContainsString("Removed orphaned Select2 container", $view);
        $this->assertStringContainsString('window.toWholePrice = function', $view);
        $this->assertStringContainsString('const summaryGroupsByRoom = false;', $view);
    }

    public function test_quotation_detail_labels_use_top(): void
    {
        $view = $this->viewSource('marketing.quotations.show');

        $this->assertStringContainsString('Harga Rental / TOP', $view);
        $this->assertStringContainsString('Sub Total / TOP', $view);
    }

    public function test_job_advice_has_back_to_draft_and_master_room_has_explicit_save(): void
    {
        $this->assertStringContainsString('onclick="saveToDraft()"', $this->viewSource('marketing.job-advices.show'));

        $room = $this->viewSource('operational.master-rooms.show');
        $this->assertStringContainsString('id="roomSaveBtn"', $room);
        $this->assertStringContainsString('function cancelRoomChanges()', $room);
        $this->assertStringNotContainsString("field.addEventListener('change', function() {\n                performAutoSave();", $room);
    }
}
