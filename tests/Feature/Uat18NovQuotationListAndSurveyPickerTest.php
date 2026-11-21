<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * UAT 18 Nov 2026: picker survey Step 2 quotation wizard, urutan kolom index
 * quotation, dan label revisi di detail quotation.
 */
class Uat18NovQuotationListAndSurveyPickerTest extends TestCase
{
    private function viewSource(string $view): string
    {
        return file_get_contents(View::getFinder()->find($view));
    }

    public function test_wizard_survey_picker_uses_server_side_select2_search(): void
    {
        $source = $this->viewSource('marketing.quotations.wizard.create');

        $this->assertStringContainsString('route("marketing.quotations.wizard.search-surveys")', $source);
        $this->assertStringContainsString('selected_customer_id: getSelectedSurveyCustomerId()', $source);
        // Preload semua survey + penghapusan option berdasarkan NAMA customer sudah tidak ada.
        $this->assertStringNotContainsString('route("marketing.quotations.wizard.get-surveys-by-customer")', $source);
        $this->assertStringNotContainsString("\$(document).on('select2:open', '#survey_tags'", $source);
        $this->assertStringNotContainsString("loaded-marketing-id", $source);
    }

    public function test_wizard_survey_restore_reloads_selected_surveys_by_id(): void
    {
        $source = $this->viewSource('marketing.quotations.wizard.create');

        $this->assertMatchesRegularExpression('/function restoreSurveySelections\(withinContext = false\) \{.*?ids: ids,.*?ensureSurveySelectOptions\(results\);/s', $source);
        $this->assertStringContainsString("new Option(survey.text || ('Survey ID: ' + survey.id), survey.id, false, false)", $source);
        // Pilihan user sendiri tidak boleh terlewat walau flag restore masih aktif.
        $this->assertMatchesRegularExpression("/on\('select2:select select2:unselect', function\(\) \{\s*syncSurveyOptionMetaFromCache\(\);\s*saveSurveySelections\(true\);/", $source);
    }

    public function test_quotation_index_shows_revisi_and_aksi_right_after_checkbox(): void
    {
        $source = $this->viewSource('marketing.quotations.index');

        $thead = substr($source, strpos($source, '<thead>'), strpos($source, '</thead>') - strpos($source, '<thead>'));
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $thead, $matches);
        $headers = array_map(fn ($label) => trim(strip_tags($label)), $matches[1]);

        $this->assertSame(['', 'Revisi', 'Aksi', 'Quotation Number'], array_slice($headers, 0, 4));
        $this->assertCount(24, $headers);

        $tbody = substr($source, strpos($source, '@forelse($quotations'));
        $revisionCell = strpos($tbody, '{{ $quotation->revision_number ?? 0 }}');
        $copyButton = strpos($tbody, 'copyQuotation({{ $quotation->id }})');
        $quotationNumberCell = strpos($tbody, "{{ \$quotation->quotation_number ?? 'N/A' }}");
        $this->assertTrue($revisionCell < $copyButton && $copyButton < $quotationNumberCell);

        // Lebar kolom ikut digeser: 24 kolom -> nth-child sampai 19 (dulu 17).
        $this->assertStringContainsString('.responsive-table th:nth-child(19)', $source);
    }

    public function test_quotation_detail_always_shows_revision_number(): void
    {
        $source = $this->viewSource('marketing.quotations.show');

        $this->assertMatchesRegularExpression('/@if\(\(int\) \$quotation->revision_number > 0\).*?@endif\s*<\/span>\s*<\/div>\s*@endif/s', $source);
        preg_match('/@if\(\(int\) \$quotation->revision_number > 0\).*?@endif\s*<\/span>\s*<\/div>\s*@endif/s', $source, $block);

        $render = fn ($quotation) => preg_replace('/\s+/', ' ', trim(Blade::render($block[0], ['quotation' => $quotation])));

        $this->assertStringContainsString('Revisi ke-2 (terbaru)', $render((object) ['revision_number' => 2, 'is_latest_revision' => true]));

        $old = $render((object) ['revision_number' => 1, 'is_latest_revision' => false]);
        $this->assertStringContainsString('Revisi ke-1', $old);
        $this->assertStringNotContainsString('terbaru', $old);

        $this->assertSame('', $render((object) ['revision_number' => 0, 'is_latest_revision' => true]));

        // Peringatan revisi lama tetap ada.
        $this->assertStringContainsString('Revisi Lama (Revisi {{ $quotation->revision_number }})', $source);
    }
}
