<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobAdviceModalScriptTest extends TestCase
{
    public function test_job_advice_edit_dates_do_not_use_utc_iso_conversion(): void
    {
        $indexView = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));
        $showView = file_get_contents(resource_path('views/marketing/job-advices/show.blade.php'));

        $this->assertStringNotContainsString("toISOString().split('T')[0]", $indexView);
        $this->assertStringNotContainsString("toISOString().split('T')[0]", $showView);
        $this->assertStringContainsString('function dateValueForInput(dateInput)', $indexView);
        $this->assertStringContainsString("dateString.match(/^(\\d{4}-\\d{2}-\\d{2})/)", $showView);
    }

    public function test_install_job_advice_create_flow_keeps_rental_rooms_hidden(): void
    {
        $view = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));

        $this->assertStringContainsString("return ['install', 'install_free'].includes(normalizeJobAdviceType(type));", $view);
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($view, 'shouldSelectRoomsAfterCreate(getCreateModalJobAdviceType())')
        );
        $this->assertStringContainsString('function addRoomRow() {', $view);
        $this->assertStringContainsString('resetCreateRoomSelection();', $view);
    }
}
