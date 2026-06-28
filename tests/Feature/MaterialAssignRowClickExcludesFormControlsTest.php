<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Bug #18 follow-up (QA, 2026-06-27, notes: "masih tidak keluar dropdown nya"):
 * the row-level click handler that opens the "View Material Issue" modal
 * (MOM10) only excluded clicks on .row-checkbox/button/a. The Material
 * dropdown's underlying <select> carries onclick="event.stopPropagation()",
 * but select2 hides that real <select> and renders a sibling
 * .select2-selection span in its place, so clicks on the visible span never
 * reached the <select> and stopPropagation() never fired - the click fell
 * through to the row handler and popped the View modal open on top of (and
 * blocking) the dropdown the user was trying to use.
 */
class MaterialAssignRowClickExcludesFormControlsTest extends TestCase
{
    public function test_row_click_handler_excludes_select_and_select2_elements(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        $this->assertStringContainsString("e.target.closest('select')", $view);
        $this->assertStringContainsString("e.target.closest('input')", $view);
        $this->assertStringContainsString("e.target.closest('.select2-container')", $view);
        $this->assertStringContainsString('showMaterialIssue(id)', $view);
    }
}
