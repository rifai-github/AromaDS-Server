<?php

namespace Tests\Unit;

use App\Http\Controllers\Warehouse\InventoryRequestImportController;
use Tests\TestCase;

class InventoryRequestImportDateParsingTest extends TestCase
{
    private function parse(?string $value): ?\Carbon\Carbon
    {
        $controller = new InventoryRequestImportController();
        $method = new \ReflectionMethod($controller, 'parseRequiredDate');
        $method->setAccessible(true);

        return $method->invoke($controller, $value);
    }

    public function test_accepts_standard_dashboard_format(): void
    {
        $date = $this->parse('29 Jun 2026');

        $this->assertNotNull($date);
        $this->assertSame('2026-06-29', $date->format('Y-m-d'));
    }

    public function test_accepts_dash_separated_format_for_backward_compatibility(): void
    {
        $date = $this->parse('29-Jun-2026');

        $this->assertNotNull($date);
        $this->assertSame('2026-06-29', $date->format('Y-m-d'));
    }

    public function test_accepts_iso_format_for_backward_compatibility(): void
    {
        $date = $this->parse('2026-06-29');

        $this->assertNotNull($date);
        $this->assertSame('2026-06-29', $date->format('Y-m-d'));
    }

    public function test_accepts_excel_numeric_serial_date(): void
    {
        $date = $this->parse('46202');

        $this->assertNotNull($date);
        $this->assertSame('2026-06-29', $date->format('Y-m-d'));
    }

    public function test_returns_null_for_empty_value(): void
    {
        $this->assertNull($this->parse(''));
        $this->assertNull($this->parse(null));
    }

    public function test_returns_null_for_garbage_value(): void
    {
        $this->assertNull($this->parse('not-a-date'));
    }
}
