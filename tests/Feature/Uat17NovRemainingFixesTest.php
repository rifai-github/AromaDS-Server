<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * UAT 17 Nov 2026 sesi siang: sisa bug (layout, menu, role, profile, branch, gedung).
 */
class Uat17NovRemainingFixesTest extends TestCase
{
    private function viewSource(string $view): string
    {
        return file_get_contents(View::getFinder()->find($view));
    }

    public function test_double_click_guard_cancels_its_delayed_disable_when_the_button_is_released(): void
    {
        $layout = $this->viewSource('layouts.app');

        $this->assertStringContainsString('button._disableTimeout = setTimeout(() => {', $layout);
        $this->assertMatchesRegularExpression('/function enableButton\(button\) \{.*?clearTimeout\(button\._disableTimeout\)/s', $layout);
        $this->assertStringNotContainsString("setTimeout(() => {\n                button.disabled = true;\n            }, 100);", str_replace("\r\n", "\n", $layout));
    }

    public function test_jenis_ruangan_and_inventory_transfer_menus_are_hidden(): void
    {
        $layout = str_replace("\r\n", "\n", $this->viewSource('layouts.app'));

        $this->assertMatchesRegularExpression('/\{\{-- UAT 17 Nov: menu Jenis Ruangan.*?Jenis Ruangan<\/li>\s*--\}\}/s', $layout);
        $this->assertMatchesRegularExpression('/\{\{-- UAT 17 Nov: Inventory Transfer.*?Inventory Transfer<\/li>\s*@endif\s*--\}\}/s', $layout);
    }

    public function test_role_edit_sends_existing_permission_ids_and_index_selects_updated_at(): void
    {
        $view = $this->viewSource('system.roles.edit');
        $controller = file_get_contents(app_path('Http/Controllers/System/RoleController.php'));

        $this->assertSame(7, substr_count($view, 'data-permission-id="{{ $menuData[\'permissions\'][\'ids\']'));
        $this->assertStringContainsString('has_existing_id: !!checkbox.dataset.permissionId', $view);
        $this->assertMatchesRegularExpression("/Role::select\(\[.*?'updated_at',/s", $controller);
    }

    public function test_profile_uses_master_options_for_personal_data(): void
    {
        $view = $this->viewSource('profile.index');
        $controller = file_get_contents(app_path('Http/Controllers/ProfileController.php'));

        foreach (['salutation', 'gender', 'marital_status', 'religion', 'blood_type', 'rhesus', 'identity_type'] as $field) {
            $this->assertStringContainsString("@foreach(\$profileOptions['{$field}'] ?? [] as \$optionName)", $view, $field);
        }
        $this->assertStringNotContainsString('<input type="text" class="form-control" id="religion"', $view);
        $this->assertStringContainsString("'religion' => 'Religion'", $controller);
        $this->assertStringNotContainsString("value=\"{{ \$user->roles ?? 'N/A' }}\"", $view);
    }

    public function test_operational_area_completes_a_preselected_province_section(): void
    {
        $view = $this->viewSource('company.branches.operational-areas');

        $this->assertStringContainsString('function completeProvinceSection(section, provinceId)', $view);
        $this->assertStringContainsString("if (existingSection.dataset.complete !== '1')", $view);
    }

    public function test_va_edit_posts_formdata_with_method_spoofing_instead_of_put(): void
    {
        $view = str_replace("\r\n", "\n", $this->viewSource('company.company-virtual-accounts.index'));

        $this->assertMatchesRegularExpression("/method = 'POST';\s*formData\.append\('_method', 'PUT'\);/", $view);
        $this->assertDoesNotMatchRegularExpression("/method = 'PUT';\s*formData\.append\('_method', 'PUT'\);/", $view);
    }

    public function test_inventory_receiving_scan_keeps_serial_number_case(): void
    {
        $view = $this->viewSource('warehouse.inventory-receivings.show');

        $this->assertStringNotContainsString('onkeyup="this.value = this.value.toUpperCase()"', $view);
        $this->assertStringNotContainsString("getElementById('scanSNSerial').value.trim().toUpperCase()", $view);
        $this->assertStringNotContainsString('decodedText.trim().toUpperCase()', $view);
    }

    public function test_building_form_drops_english_address_fields_and_backfills_legacy_columns(): void
    {
        $view = $this->viewSource('operational.buildings.index');
        $controller = file_get_contents(app_path('Http/Controllers/Operational/BuildingController.php'));

        $this->assertStringNotContainsString('Address (English)', $view);
        $this->assertStringNotContainsString('Postal Code (English)', $view);
        $this->assertStringContainsString("'address' => \$request->address ?: \$request->alamat_1,", $controller);
        $this->assertStringContainsString("'postal_code' => \$request->postal_code ?: \$request->kode_pos,", $controller);
    }
}
