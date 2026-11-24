<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractSignatureRequest;
use App\Models\User;
use App\Services\Marketing\ContractSignatureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * MOM 19 Nov 2026 p4 no 8 + jawaban klien 23 Nov 2026: Digital Sign kontrak lewat link publik.
 * Penandatangan = pilihan di kontrak; tanda tangan digoreskan di layar; sudah ditandatangani =
 * final (snapshot dokumen, link tak bisa dibuat ulang).
 */
class ContractDigitalSignatureTest extends TestCase
{
    private const MARKED_HTML = '<html><body><p>Kontrak uji</p>'
        .'<!--SIG:internal--><div class="signature-line"></div><!--/SIG:internal-->'
        .'<!--SIG:customer--><div class="signature-line"></div><!--/SIG:customer-->'
        .'</body></html>';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-23 10:00:00');
        Storage::fake('local');

        foreach (['contract_signature_requests', 'customer_contacts', 'contracts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->string('contract_status')->nullable();
            $t->string('signatory_name')->nullable();
            $t->string('signatory_position')->nullable();
            $t->unsignedBigInteger('internal_signing_id')->nullable();
            $t->unsignedBigInteger('customer_signing_1_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('position')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_signature_requests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->index();
            $t->string('batch', 36)->index();
            $t->string('signer_role', 20);
            $t->string('signer_name');
            $t->string('signer_position')->nullable();
            $t->unsignedBigInteger('signer_user_id')->nullable();
            $t->unsignedBigInteger('signer_contact_id')->nullable();
            $t->string('token', 64)->unique();
            $t->string('html_path');
            $t->string('document_path');
            $t->string('document_hash', 64);
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('signed_at')->nullable();
            $t->string('signed_name')->nullable();
            $t->string('signature_path')->nullable();
            $t->string('signature_method', 10)->nullable();
            $t->string('signer_ip', 45)->nullable();
            $t->text('signer_user_agent')->nullable();
            $t->string('final_document_path')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        DB::table('customer_contacts')->insert(['id' => 5, 'name' => 'Anika Camar', 'position' => 'Manager']);
        DB::table('contracts')->insert([
            ['id' => 1, 'contract_number' => 'SBY-CA/26-11/0001', 'contract_status' => 'active', 'signatory_name' => 'Furton', 'signatory_position' => 'Director', 'customer_signing_1_id' => 5],
            ['id' => 2, 'contract_number' => 'SBY-CA/26-11/0002', 'contract_status' => 'draft', 'signatory_name' => 'Furton', 'signatory_position' => 'Director', 'customer_signing_1_id' => 5],
            ['id' => 3, 'contract_number' => 'SBY-CA/26-11/0003', 'contract_status' => 'active', 'signatory_name' => 'Furton', 'signatory_position' => 'Director', 'customer_signing_1_id' => null],
        ]);

        // Render PDF kontrak asli butuh puluhan tabel; di sini cukup HTML bertanda SIG:* dan
        // "PDF" = HTML-nya sendiri supaya hasil akhir bisa diperiksa sebagai teks.
        $this->app->instance(ContractSignatureService::class, new class extends ContractSignatureService
        {
            protected function renderContractHtml(Contract $contract): string
            {
                return str_replace('Kontrak uji', 'Kontrak uji '.$contract->contract_number, ContractDigitalSignatureTest::markedHtml());
            }

            protected function renderPdf(string $html): string
            {
                return $html;
            }
        });
    }

    protected function tearDown(): void
    {
        foreach (['contract_signature_requests', 'customer_contacts', 'contracts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Carbon::setTestNow();

        parent::tearDown();
    }

    public static function markedHtml(): string
    {
        return self::MARKED_HTML;
    }

    /** User belum disimpan: cukup untuk Auth::check()/Auth::id() di halaman tanda tangan. */
    private function pinkUser(int $id = 77, string $name = 'Furton'): User
    {
        $user = new User(['name' => $name]);
        $user->id = $id;
        // Middleware global membaca role/akun user; tabel-tabelnya tidak ada di test ini.
        $user->setRelation('roles', collect());
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckFrozenAccount::class,
            \App\Http\Middleware\CheckMultiLogin::class,
            \App\Http\Middleware\CheckLoginRestriction::class,
        ]);

        return $user;
    }

    private function service(): ContractSignatureService
    {
        return $this->app->make(ContractSignatureService::class);
    }

    private function signatureDataUrl(): string
    {
        $image = imagecreatetruecolor(40, 20);
        imageline($image, 0, 10, 39, 10, imagecolorallocate($image, 10, 30, 90));
        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    private function signPayload(array $overrides = []): array
    {
        return array_merge([
            'signature' => $this->signatureDataUrl(),
            'signed_name' => 'Nama Diketik',
            'agree' => 1,
        ], $overrides);
    }

    public function test_both_parties_sign_through_public_links_and_the_final_document_is_built(): void
    {
        $rows = $this->service()->createBatch(Contract::find(1), 28);

        $this->assertSame(['internal', 'customer'], $rows->pluck('signer_role')->all());
        $this->assertSame(['Furton', 'Anika Camar'], $rows->pluck('signer_name')->all());
        Storage::disk('local')->assertExists($rows->first()->document_path);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($rows->first()->document_path)), $rows->first()->document_hash);

        [$internal, $customer] = [$rows[0], $rows[1]];

        // Link Pihak Pink wajib login; link customer publik.
        $this->get(route('contract-signatures.public.show', $internal->token))
            ->assertRedirect(route('login'))
            ->assertSessionHas('contract_signature.intended', route('contract-signatures.public.show', $internal->token));
        $this->get(route('contract-signatures.public.document', $internal->token))->assertForbidden();
        $this->postJson(route('contract-signatures.public.sign', $internal->token), $this->signPayload())->assertStatus(401);
        $this->assertNull($internal->fresh()->signed_at);
        $this->get(route('contract-signatures.public.show', $customer->token))->assertOk()->assertSee('Tanda Tangani Kontrak');
        $this->get(route('contract-signatures.public.document', $customer->token))->assertOk();

        $this->actingAs($this->pinkUser());

        $this->get(route('contract-signatures.public.show', $internal->token))
            ->assertOk()
            ->assertSee('SBY-CA/26-11/0001')
            ->assertSee('Tanda Tangani Kontrak');
        $this->get(route('contract-signatures.public.document', $internal->token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->postJson(route('contract-signatures.public.sign', $internal->token), $this->signPayload(['signed_name' => 'Furton S.']))
            ->assertOk()->assertJson(['status' => 'success']);
        $this->assertNull($internal->fresh()->final_document_path, 'Final baru dibuat setelah semua pihak tanda tangan.');

        $this->postJson(route('contract-signatures.public.sign', $customer->token), $this->signPayload(['signed_name' => 'Anika']))
            ->assertOk();

        $final = $customer->fresh()->final_document_path;
        $this->assertNotNull($final);
        $this->assertSame($final, $internal->fresh()->final_document_path);

        $html = Storage::disk('local')->get($final);
        $this->assertSame(2, substr_count($html, 'data:image/png;base64,'));
        $this->assertStringNotContainsString('<!--SIG:', $html);
        $this->assertStringContainsString('Lembar Pengesahan Tanda Tangan Elektronik', $html);
        $this->assertStringContainsString('Furton S.', $html);
        $this->assertStringContainsString($internal->document_hash, $html);
        $this->assertLessThan(strpos($html, '</body>'), strpos($html, 'Lembar Pengesahan'));

        // Link yang sudah dipakai tidak aktif lagi: hanya status, tanpa dokumen, tak bisa tanda tangan.
        $this->get(route('contract-signatures.public.show', $internal->token))
            ->assertOk()->assertSee('sudah Anda tandatangani')->assertSee('Link ini sudah tidak aktif')
            ->assertDontSee('Tanda Tangani Kontrak')->assertDontSee('Dokumen Kontrak');
        $this->get(route('contract-signatures.public.document', $internal->token))->assertNotFound();
        $this->get(route('contract-signatures.public.document', $customer->token))->assertNotFound();
        $this->postJson(route('contract-signatures.public.sign', $internal->token), $this->signPayload())
            ->assertStatus(422);
    }

    public function test_uploaded_signature_is_recorded_and_named_on_the_audit_page(): void
    {
        $this->actingAs($this->pinkUser());
        $rows = $this->service()->createBatch(Contract::find(1));
        [$internal, $customer] = [$rows[0], $rows[1]];

        $this->get(route('contract-signatures.public.show', $customer->token))
            ->assertOk()->assertSee('Unggah gambar')->assertSee('Gores di layar');

        $this->postJson(route('contract-signatures.public.sign', $internal->token), $this->signPayload())
            ->assertOk();
        $this->postJson(route('contract-signatures.public.sign', $customer->token), $this->signPayload(['signature_method' => 'upload']))
            ->assertOk()->assertJson(['status' => 'success']);

        $this->assertSame('draw', $internal->fresh()->signature_method, 'Tanpa signature_method = digores (perilaku lama).');
        $this->assertSame('upload', $customer->fresh()->signature_method);

        $html = Storage::disk('local')->get($customer->fresh()->final_document_path);
        $this->assertStringContainsString('Unggah gambar', $html);
        $this->assertStringContainsString('Digores di layar', $html);

        $this->postJson(route('contract-signatures.public.sign', $rows[0]->token), $this->signPayload(['signature_method' => 'fax']))
            ->assertStatus(422)->assertJsonValidationErrors('signature_method');
    }

    public function test_pink_link_only_works_for_the_internal_signer_chosen_on_the_contract(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('position_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 77, 'name' => 'Furton']);
        DB::table('contracts')->where('id', 1)->update(['internal_signing_id' => 77]);
        $internal = $this->service()->createBatch(Contract::find(1))[0];
        $this->assertSame(77, (int) $internal->signer_user_id);

        $this->actingAs($this->pinkUser(78, 'Budi'));
        $this->get(route('contract-signatures.public.show', $internal->token))
            ->assertOk()->assertSee('Link ini khusus untuk')->assertSee('Budi')
            ->assertDontSee('Tanda Tangani Kontrak')->assertDontSee('Dokumen Kontrak');
        $this->get(route('contract-signatures.public.document', $internal->token))->assertForbidden();
        $this->postJson(route('contract-signatures.public.sign', $internal->token), $this->signPayload())->assertForbidden();
        $this->assertNull($internal->fresh()->signed_at);

        $this->actingAs($this->pinkUser(77));
        $this->get(route('contract-signatures.public.show', $internal->token))->assertOk()->assertSee('Tanda Tangani Kontrak');
        $this->postJson(route('contract-signatures.public.sign', $internal->token), $this->signPayload())->assertOk();
        $this->assertNotNull($internal->fresh()->signed_at);
    }

    public function test_signed_contract_is_final_and_links_cannot_be_recreated(): void
    {
        $rows = $this->service()->createBatch(Contract::find(1));
        $this->service()->sign($rows[1], $this->signatureDataUrl(), 'Anika', '10.0.0.1', 'UA');

        $this->assertTrue($this->service()->hasAnySignature(Contract::find(1)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah ditandatangani');
        $this->service()->createBatch(Contract::find(1));
    }

    public function test_recreating_links_before_anyone_signs_revokes_the_old_links(): void
    {
        $this->actingAs($this->pinkUser());
        $old = $this->service()->createBatch(Contract::find(1));
        $new = $this->service()->createBatch(Contract::find(1));

        $this->assertNotSame($old[0]->batch, $new[0]->batch);
        $this->assertNotNull($old[0]->fresh()->revoked_at);
        $this->assertSame($new[0]->batch, $this->service()->activeBatch(Contract::find(1))->first()->batch);

        $this->get(route('contract-signatures.public.show', $old[0]->token))
            ->assertOk()->assertSee('sudah tidak berlaku')->assertDontSee('Tanda Tangani Kontrak');
        $this->get(route('contract-signatures.public.document', $old[0]->token))->assertNotFound();
        $this->postJson(route('contract-signatures.public.sign', $old[0]->token), $this->signPayload())
            ->assertStatus(422);
    }

    public function test_expired_link_cannot_be_signed(): void
    {
        $this->actingAs($this->pinkUser());
        $rows = $this->service()->createBatch(Contract::find(1));

        Carbon::setTestNow(now()->addDays((int) config('contract.signature_link_days', 14) + 1));

        $this->get(route('contract-signatures.public.show', $rows[0]->token))->assertOk()->assertSee('kedaluwarsa');
        $this->postJson(route('contract-signatures.public.sign', $rows[0]->token), $this->signPayload())
            ->assertStatus(422)->assertJson(['status' => 'error']);
        $this->assertNull($rows[0]->fresh()->signed_at);
    }

    public function test_invalid_signature_or_missing_consent_is_rejected(): void
    {
        $this->actingAs($this->pinkUser());
        $rows = $this->service()->createBatch(Contract::find(1));
        $url = route('contract-signatures.public.sign', $rows[0]->token);

        $this->postJson($url, $this->signPayload(['signature' => 'data:image/png;base64,'.base64_encode('bukan png')]))
            ->assertStatus(422)->assertJsonFragment(['message' => 'Format tanda tangan tidak valid. Silakan ulangi tanda tangan.']);
        $this->postJson($url, $this->signPayload(['agree' => 0]))->assertStatus(422)->assertJsonValidationErrors('agree');
        $this->postJson($url, $this->signPayload(['signed_name' => '']))->assertStatus(422)->assertJsonValidationErrors('signed_name');

        $this->assertNull($rows[0]->fresh()->signed_at);
        $this->get(route('contract-signatures.public.show', 'token-tidak-ada'))->assertNotFound();
    }

    public function test_links_require_a_posted_contract_with_both_signers(): void
    {
        try {
            $this->service()->createBatch(Contract::find(2));
            $this->fail('Kontrak draft tidak boleh dapat link.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sudah di-post', $e->getMessage());
        }

        try {
            $this->service()->createBatch(Contract::find(3));
            $this->fail('Kontrak tanpa penandatangan customer tidak boleh dapat link.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Customer Signing 1', $e->getMessage());
        }

        $this->assertSame(0, ContractSignatureRequest::count());
    }
}
