<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\InvoiceController;
use App\Models\Finance\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceIndexPrintStatusFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('invoices')->insert([
            [
                'id' => 1,
                'invoice_number' => 'INV/PRINTED/001',
                'is_printed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'invoice_number' => 'INV/UNPRINTED/001',
                'is_printed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoices');

        parent::tearDown();
    }

    public function test_invoice_index_default_print_status_shows_printed_and_unprinted_invoices(): void
    {
        $query = Invoice::query();

        $this->applyPrintStatusFilter($query, Request::create('/finance/invoices', 'GET'));

        $this->assertSame(2, $query->count());
    }

    public function test_invoice_index_print_status_filters_only_when_explicitly_selected(): void
    {
        $printedQuery = Invoice::query();
        $this->applyPrintStatusFilter($printedQuery, Request::create('/finance/invoices', 'GET', [
            'print_status' => 'sudah',
        ]));

        $unprintedQuery = Invoice::query();
        $this->applyPrintStatusFilter($unprintedQuery, Request::create('/finance/invoices', 'GET', [
            'print_status' => 'belum',
        ]));

        $this->assertSame(['INV/PRINTED/001'], $printedQuery->pluck('invoice_number')->all());
        $this->assertSame(['INV/UNPRINTED/001'], $unprintedQuery->pluck('invoice_number')->all());
    }

    private function applyPrintStatusFilter($query, Request $request): void
    {
        $controller = new InvoiceController();
        $method = new \ReflectionMethod($controller, 'applyPrintStatusFilter');
        $method->setAccessible(true);
        $method->invoke($controller, $query, $request);
    }
}
