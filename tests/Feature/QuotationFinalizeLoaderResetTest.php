<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuotationFinalizeLoaderResetTest extends TestCase
{
    public function test_renewal_without_survey_skips_the_validation_loader(): void
    {
        $view = file_get_contents(resource_path('views/marketing/quotations/show.blade.php'));

        $functionStart = strpos($view, 'async function finalizeQuotation()');
        $noSurveyGuard = strpos($view, 'if (!surveyId)', $functionStart);
        $confirmationCall = strpos($view, 'proceedWithFinalize();', $noSurveyGuard);
        $validationDialog = strpos($view, "title: 'Memvalidasi...'", $functionStart);

        $this->assertNotFalse($functionStart);
        $this->assertNotFalse($noSurveyGuard);
        $this->assertNotFalse($confirmationCall);
        $this->assertNotFalse($validationDialog);
        $this->assertLessThan($validationDialog, $confirmationCall);
    }
}
