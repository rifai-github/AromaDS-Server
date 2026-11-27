<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationController;
use App\Models\MasterRental;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Jawaban klien 26 Nov 2026: Basis Harga SQ (per ruangan / per rental) tampil di Basic Info,
 * bisa diubah selama SQ draft, dan PDF SQ mengikutinya. Total SQ tidak berubah.
 */
class QuotationPriceBasisTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['companies', 'quotations', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function createQuotationTables(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->string('status')->nullable();
            $t->string('price_basis', 20)->nullable();
            $t->integer('revision_number')->default(0);
            $t->boolean('is_latest_revision')->default(true);
            $t->decimal('grand_total', 15, 2)->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Marketing']);
        DB::table('quotations')->insert([
            ['id' => 1, 'quotation_number' => 'JKT-SQ/26-11/0001', 'status' => 'draft', 'price_basis' => 'room', 'is_latest_revision' => true, 'grand_total' => 900000],
            ['id' => 2, 'quotation_number' => 'JKT-SQ/26-11/0002', 'status' => 'approved', 'price_basis' => 'room', 'is_latest_revision' => true, 'grand_total' => 900000],
            ['id' => 3, 'quotation_number' => 'JKT-SQ/26-11/0003', 'status' => 'draft', 'price_basis' => 'room', 'is_latest_revision' => false, 'grand_total' => 900000],
        ]);
    }

    private function updatePriceBasis(int $quotationId, $value)
    {
        $request = Request::create("/marketing/quotations/{$quotationId}/update-price-basis", 'POST', ['price_basis' => $value]);

        return app(QuotationController::class)->updatePriceBasis($request, Quotation::findOrFail($quotationId));
    }

    public function test_price_basis_can_be_changed_while_draft_without_touching_totals(): void
    {
        $this->createQuotationTables();
        $this->actingAs(User::findOrFail(1));

        $response = $this->updatePriceBasis(1, 'rental');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertSame('Per Rental', $response->getData(true)['label']);
        $row = DB::table('quotations')->where('id', 1)->first();
        $this->assertSame('rental', $row->price_basis);
        $this->assertSame(1, (int) $row->updated_by);
        $this->assertEquals(900000, (float) $row->grand_total, 'Total SQ tidak dihitung ulang');
    }

    public function test_price_basis_change_is_rejected_when_not_draft_or_old_revision(): void
    {
        $this->createQuotationTables();
        $this->actingAs(User::findOrFail(1));

        foreach ([2, 3] as $id) {
            $response = $this->updatePriceBasis($id, 'rental');
            $this->assertSame(422, $response->getStatusCode(), "SQ {$id}");
            $this->assertSame('room', DB::table('quotations')->where('id', $id)->value('price_basis'));
        }

        $this->expectException(ValidationException::class);
        $this->updatePriceBasis(1, 'unit');
    }

    public function test_show_page_displays_and_edits_price_basis_in_basic_info(): void
    {
        $view = str_replace("\r\n", "\n", file_get_contents(resource_path('views/marketing/quotations/show.blade.php')));

        $this->assertStringContainsString('<div class="quotation-field-label">Basis Harga</div>', $view);
        $this->assertStringContainsString('id="quotationPriceBasis"', $view);
        $this->assertStringContainsString('route("marketing.quotations.update-price-basis", $quotation->id)', $view);
        $this->assertStringContainsString("@if(\$quotation->status === 'draft' && \$quotation->is_latest_revision)", $view);
        $this->assertStringContainsString('\App\Models\Quotation::priceBasisLabel($quotation->price_basis)', $view);
        $this->assertSame(1, substr_count($view, 'Basis Harga</div>'), 'Hanya di kartu Basic Info');

        $this->assertSame('Per Ruangan', Quotation::priceBasisLabel('room'));
        $this->assertSame('Per Rental', Quotation::priceBasisLabel('RENTAL'));
        $this->assertSame('-', Quotation::priceBasisLabel(null));

        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('marketing.quotations.update-price-basis'));
    }

    private function quotationForPdf(string $priceBasis): Quotation
    {
        $ads = new MasterRental(['rental_code' => 'A10812100', 'rental_name' => 'ADS 100 12 Bln']);
        $ads->id = 5;
        $diffuser = new MasterRental(['rental_code' => 'D200', 'rental_name' => 'Diffuser 200']);
        $diffuser->id = 6;

        $line = function (int $id, MasterRental $rental, string $room, int $qty, int $total) {
            $detail = new QuotationDetail([
                'master_rental_id' => $rental->id,
                'rental_alias' => $rental->rental_code,
                'room_name' => $room,
                'quantity' => $qty,
                'total_price' => $total,
            ]);
            $detail->id = $id;
            $detail->setRelation('masterRental', $rental);
            $detail->setRelation('survey', null);

            return $detail;
        };

        $quotation = new Quotation([
            'quotation_number' => 'JKT-SQ/26-11/0001',
            'price_basis' => $priceBasis,
            'pic_name' => 'Budi',
            'grand_total' => 1234567,
        ]);
        $quotation->setRelation('prospect', null);
        $quotation->setRelation('customer', null);
        $quotation->setRelation('marketing', null);
        $quotation->setRelation('quotationDetails', collect([
            $line(1, $ads, 'Lobby Utama', 1, 300000),
            $line(2, $ads, 'Ruang Rapat', 2, 600000),
            $line(3, $diffuser, 'Toilet Pria', 1, 334567),
        ]));

        return $quotation;
    }

    private function renderPdf(string $priceBasis): string
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }

        $quotation = $this->quotationForPdf($priceBasis);
        $html = view('marketing.quotations.pdf', compact('quotation'))->render();

        return preg_replace('/\s+/', ' ', $html);
    }

    public function test_pdf_room_basis_keeps_one_row_per_room(): void
    {
        $html = $this->renderPdf('room');

        $this->assertStringContainsString('<th style="width: 50%;">Room</th>', $html);
        $this->assertStringNotContainsString('>Qty</th>', $html);
        $this->assertStringContainsString('<td>ADS 100 12 Bln</td> <td>Lobby Utama</td> <td class="right">300,000</td>', $html);
        $this->assertStringContainsString('<td>ADS 100 12 Bln</td> <td>Ruang Rapat</td> <td class="right">600,000</td>', $html);
        $this->assertStringContainsString('<td>Diffuser 200</td> <td>Toilet Pria</td> <td class="right">334,567</td>', $html);
        $this->assertStringContainsString('1,234,567', $html);
    }

    public function test_pdf_rental_basis_groups_rows_by_rental_without_room_column(): void
    {
        $html = $this->renderPdf('rental');

        $this->assertStringNotContainsString('>Room</th>', $html);
        $this->assertStringContainsString('<th style="width: 15%;" class="center">Qty</th>', $html);
        $this->assertStringNotContainsString('Lobby Utama', $html);
        $this->assertStringNotContainsString('Ruang Rapat', $html);
        // Dua ruangan ADS digabung: qty 1 + 2, total 300.000 + 600.000.
        $this->assertStringContainsString('<td>ADS 100 12 Bln</td> <td class="center">3</td> <td class="right">900,000</td>', $html);
        $this->assertStringContainsString('<td>Diffuser 200</td> <td class="center">1</td> <td class="right">334,567</td>', $html);
        $this->assertSame(1, substr_count($html, '<td>ADS 100 12 Bln</td>'));
        // Total / TOP tetap grand_total SQ.
        $this->assertStringContainsString('1,234,567', $html);
    }
}
