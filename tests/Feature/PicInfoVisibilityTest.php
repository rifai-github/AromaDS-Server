<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
use App\Http\Controllers\Marketing\SurveyController;
use App\Models\Quotation;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jawaban klien 26 Nov 2026: role "PIC Info" berlaku di Survey, SQ, dan Kontrak (web + PDF).
 * Tanpa izin marketing.contract-pic-info.view (dan bukan Admin/Management) email & telepon PIC
 * disembunyikan, nama PIC tetap tampil, dan simpan form tidak menimpa nilai yang tersembunyi.
 * API APK teknisi (Api/Mobile) tidak terpengaruh.
 */
class PicInfoVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Marketing'],
            ['id' => 3, 'name' => 'PIC Info Holder'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['companies', 'customer_customer_contact', 'customer_contacts', 'customers', 'survey_details', 'surveys', 'prospects', 'buildings', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /** User dengan role/izin yang sudah di-resolve (tanpa tabel roles/permissions). */
    private function userWith(int $id, array $roles, array $permissions = []): User
    {
        $user = User::findOrFail($id);
        (function () use ($roles, $permissions) {
            $this->resolvedRoleNamesCache = $roles;
            $this->resolvedPermissionLookupCache = array_fill_keys($permissions, true);
        })->call($user);

        return $user;
    }

    public function test_helper_allows_admin_management_and_permission_holder_only(): void
    {
        $this->assertTrue($this->userWith(1, ['Admin'])->canSeePicInfo());
        $this->assertTrue($this->userWith(1, ['super_admin'])->canSeePicInfo());
        $this->assertTrue($this->userWith(1, ['Management Director'])->canSeePicInfo());
        $this->assertTrue($this->userWith(3, ['Marketing Staff', 'PIC Info'], [User::PIC_INFO_PERMISSION])->canSeePicInfo());
        $this->assertFalse($this->userWith(2, ['Marketing Staff'], ['marketing.contracts.view'])->canSeePicInfo());

        $this->assertFalse(User::currentCanSeePicInfo(), 'Tanpa login: disembunyikan');
        $this->actingAs($this->userWith(2, ['Marketing Staff']));
        $this->assertFalse(User::currentCanSeePicInfo());
        $this->actingAs($this->userWith(3, ['PIC Info'], [User::PIC_INFO_PERMISSION]));
        $this->assertTrue(User::currentCanSeePicInfo());
    }

    private function createSurveyTables(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('prospects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('assigned_to')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('buildings', function (Blueprint $t) {
            $t->id();
            $t->string('building_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('surveys', function (Blueprint $t) {
            $t->id();
            $t->string('survey_number')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->unsignedBigInteger('prospect_id')->nullable();
            $t->unsignedBigInteger('surveyor_id')->nullable();
            $t->unsignedBigInteger('marketing_id')->nullable();
            $t->date('survey_date')->nullable();
            $t->string('survey_location')->nullable();
            $t->decimal('temperature', 5, 2)->nullable();
            $t->string('latitude')->nullable();
            $t->string('longitude')->nullable();
            $t->string('company_name')->nullable();
            $t->string('customer_type')->nullable();
            $t->string('contact_person')->nullable();
            $t->string('email')->nullable();
            $t->string('phone_1')->nullable();
            $t->string('phone_2')->nullable();
            $t->string('position')->nullable();
            $t->string('building_name')->nullable();
            $t->text('address_1')->nullable();
            $t->text('address_2')->nullable();
            $t->string('province')->nullable();
            $t->string('city')->nullable();
            $t->string('district')->nullable();
            $t->string('village')->nullable();
            $t->string('postal_code')->nullable();
            $t->text('survey_result')->nullable();
            $t->text('recommendations')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('survey_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('survey_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('customers')->insert(['id' => 7, 'name' => 'PT Wangi', 'email' => 'office@wangi.test', 'phone' => '0215550000']);
        DB::table('surveys')->insert([
            'id' => 10,
            'survey_number' => 'JKT-SV/26-11/0001',
            'customer_id' => 7,
            'surveyor_id' => 1,
            'survey_date' => '2026-11-20',
            'survey_location' => 'Lobby',
            'company_name' => 'PT Wangi',
            'contact_person' => 'Budi',
            'email' => 'budi@wangi.test',
            'phone_1' => '0811111111',
            'phone_2' => '0822222222',
            'address_1' => 'Jl. Melati 1',
            'status' => 'draft',
        ]);
    }

    private function surveyUpdatePayload(array $overrides = []): array
    {
        return array_merge([
            'survey_number' => 'JKT-SV/26-11/0001',
            'status' => 'draft',
            'survey_date' => '2026-11-21',
            'surveyor_id' => 1,
            'survey_location' => 'Lobby Baru',
            'company_name' => 'PT Wangi',
            'contact_person' => 'Budi Santoso',
            'address_1' => 'Jl. Melati 1',
        ], $overrides);
    }

    public function test_survey_update_without_permission_keeps_hidden_email_and_phone(): void
    {
        $this->createSurveyTables();
        $this->actingAs($this->userWith(2, ['Marketing Staff']));

        // Form tanpa izin tidak mengirim email/telepon sama sekali (field disembunyikan).
        $request = Request::create('/marketing/surveys/10', 'PUT', $this->surveyUpdatePayload());
        $response = app(SurveyController::class)->update($request, Survey::findOrFail(10));

        $this->assertSame(200, $response->getStatusCode(), json_encode($response->getData(true)));
        $row = DB::table('surveys')->where('id', 10)->first();
        $this->assertSame('Budi Santoso', $row->contact_person, 'Nama PIC tetap bisa diubah');
        $this->assertSame('Lobby Baru', $row->survey_location);
        $this->assertSame('budi@wangi.test', $row->email);
        $this->assertSame('0811111111', $row->phone_1);
        $this->assertSame('0822222222', $row->phone_2);

        $data = $response->getData(true)['data'];
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('phone_1', $data);

        // Nilai palsu yang dikirim paksa juga diabaikan.
        $request = Request::create('/marketing/surveys/10', 'PUT', $this->surveyUpdatePayload(['email' => 'x@y.test', 'phone_1' => '']));
        app(SurveyController::class)->update($request, Survey::findOrFail(10));
        $this->assertSame('budi@wangi.test', DB::table('surveys')->where('id', 10)->value('email'));
    }

    public function test_survey_update_with_permission_still_requires_and_saves_email_and_phone(): void
    {
        $this->createSurveyTables();
        $this->actingAs($this->userWith(3, ['PIC Info'], [User::PIC_INFO_PERMISSION]));

        $request = Request::create('/marketing/surveys/10', 'PUT', $this->surveyUpdatePayload());
        $response = app(SurveyController::class)->update($request, Survey::findOrFail(10));
        $this->assertSame(422, $response->getStatusCode(), 'Email & Phone 1 tetap wajib untuk user yang melihatnya');

        $request = Request::create('/marketing/surveys/10', 'PUT', $this->surveyUpdatePayload([
            'email' => 'budi.baru@wangi.test',
            'phone_1' => '0899999999',
        ]));
        $response = app(SurveyController::class)->update($request, Survey::findOrFail(10));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('budi.baru@wangi.test', DB::table('surveys')->where('id', 10)->value('email'));
        $this->assertSame('0899999999', $response->getData(true)['data']['phone_1']);
    }

    public function test_survey_edit_json_masks_pic_contact_without_permission(): void
    {
        $this->createSurveyTables();

        $this->actingAs($this->userWith(2, ['Marketing Staff']));
        $survey = Survey::with('customer')->findOrFail(10);
        $masked = (fn () => $this->maskPicContactForCurrentUser($survey))->call(app(SurveyController::class))->toArray();

        $this->assertSame('Budi', $masked['contact_person']);
        $this->assertArrayNotHasKey('email', $masked);
        $this->assertArrayNotHasKey('phone_1', $masked);
        $this->assertArrayNotHasKey('phone_2', $masked);
        $this->assertArrayNotHasKey('email', $masked['customer']);
        $this->assertArrayNotHasKey('phone', $masked['customer']);

        $this->actingAs($this->userWith(1, ['Admin']));
        $survey = Survey::with('customer')->findOrFail(10);
        $visible = (fn () => $this->maskPicContactForCurrentUser($survey))->call(app(SurveyController::class))->toArray();
        $this->assertSame('budi@wangi.test', $visible['email']);
        $this->assertSame('office@wangi.test', $visible['customer']['email']);
    }

    private function createContactTables(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('surveys', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('name');
            $t->string('position')->nullable();
            $t->string('email')->nullable();
            $t->string('email_verification_token')->nullable();
            $t->string('phone')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customer_customer_contact', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->unsignedBigInteger('customer_contact_id');
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
        });
        DB::table('customers')->insert(['id' => 7, 'name' => 'PT Wangi']);
        DB::table('surveys')->insert(['id' => 40, 'customer_id' => 7]);
        DB::table('customer_contacts')->insert([
            ['id' => 30, 'customer_id' => 7, 'name' => 'Budi', 'position' => 'GA', 'email' => 'budi@wangi.test', 'email_verification_token' => 'tok', 'phone' => '0811111111', 'is_active' => true],
            ['id' => 31, 'customer_id' => null, 'name' => 'Sari', 'position' => 'FM', 'email' => 'sari@wangi.test', 'email_verification_token' => null, 'phone' => '0833333333', 'is_active' => true],
        ]);
        // Sari = PIC multi-customer (pivot), bukan lewat customer_id lama.
        DB::table('customer_customer_contact')->insert(['customer_id' => 7, 'customer_contact_id' => 31]);
    }

    private function picContactsPayload(): array
    {
        $this->app->instance('request', Request::create('/marketing/quotations/wizard/get-pic-contacts', 'GET', ['survey_ids' => [40]]));

        return app(QuotationWizardController::class)->getPicContacts()->getData(true);
    }

    public function test_quotation_wizard_pic_contacts_hide_email_and_phone_without_permission(): void
    {
        $this->createContactTables();

        $this->actingAs($this->userWith(2, ['Marketing Staff']));
        $contacts = collect($this->picContactsPayload()['customer_contacts'])->keyBy('name');
        $this->assertSame(['Budi', 'Sari'], $contacts->keys()->sort()->values()->all());
        $this->assertSame('GA', $contacts['Budi']['position']);
        $this->assertArrayNotHasKey('email', $contacts['Budi']);
        $this->assertArrayNotHasKey('phone', $contacts['Budi']);
        $this->assertArrayNotHasKey('email_verification_token', $contacts['Budi']);
        $this->assertArrayNotHasKey('phone', $contacts['Sari']);

        $this->actingAs($this->userWith(1, ['Admin']));
        $contacts = collect($this->picContactsPayload()['customer_contacts'])->keyBy('name');
        $this->assertSame('budi@wangi.test', $contacts['Budi']['email']);
        $this->assertSame('0833333333', $contacts['Sari']['phone']);
    }

    private function picForSave(array $input, $customerId, ?Quotation $existing = null): array
    {
        $request = Request::create('/marketing/quotations/wizard', 'POST', $input);

        return (fn () => $this->picContactForSave($request, $customerId, $existing))->call(app(QuotationWizardController::class));
    }

    public function test_quotation_wizard_save_never_blanks_hidden_pic_contact(): void
    {
        $this->createContactTables();
        $existing = new Quotation(['pic_name' => 'Budi', 'pic_email' => 'lama@wangi.test', 'pic_phone' => '0800000000']);

        $this->actingAs($this->userWith(2, ['Marketing Staff']));
        // PIC sama -> nilai lama dipertahankan walau field tersembunyi datang kosong.
        $this->assertSame(
            ['pic_email' => 'lama@wangi.test', 'pic_phone' => '0800000000'],
            $this->picForSave(['pic_quotation' => 'Budi', 'pic_email' => '', 'pic_phone' => ''], 7, $existing)
        );
        // PIC berganti -> diambil dari kontak customer (termasuk PIC multi-customer via pivot).
        $this->assertSame(
            ['pic_email' => 'sari@wangi.test', 'pic_phone' => '0833333333'],
            $this->picForSave(['pic_quotation' => 'Sari', 'pic_email' => '', 'pic_phone' => ''], 7, $existing)
        );
        $this->assertSame(
            ['pic_email' => 'budi@wangi.test', 'pic_phone' => '0811111111'],
            $this->picForSave(['pic_quotation' => 'Budi', 'pic_email' => 'palsu@x.test'], 7)
        );

        // User berizin: nilai dari form dipakai apa adanya (perilaku lama).
        $this->actingAs($this->userWith(1, ['Admin']));
        $this->assertSame(
            ['pic_email' => 'baru@wangi.test', 'pic_phone' => '0812'],
            $this->picForSave(['pic_quotation' => 'Budi', 'pic_email' => 'baru@wangi.test', 'pic_phone' => '0812'], 7, $existing)
        );
    }

    public function test_views_gate_pic_email_and_phone_with_the_shared_helper(): void
    {
        $read = fn (string $view) => str_replace("\r\n", "\n", file_get_contents(resource_path("views/{$view}.blade.php")));
        $helper = '$canSeePicInfo = \App\Models\User::currentCanSeePicInfo();';

        foreach (['marketing/surveys/show', 'marketing/surveys/index', 'marketing/surveys/pdf', 'marketing/quotations/show', 'marketing/contracts/show'] as $view) {
            $this->assertStringContainsString($helper, $read($view), $view);
        }

        $surveyShow = $read('marketing/surveys/show');
        $this->assertStringContainsString('{{ $canSeePicInfo ? $survey->display_email : \App\Models\User::PIC_INFO_HIDDEN_LABEL }}', $surveyShow);
        $this->assertStringContainsString('{{ $canSeePicInfo ? $survey->display_phone_one : \App\Models\User::PIC_INFO_HIDDEN_LABEL }}', $surveyShow);
        // Payload AJAX simpan/finalisasi tidak membawa email/telepon untuk user tanpa izin.
        $this->assertSame(2, preg_match_all('/@if\(\$canSeePicInfo\)\n\s*email: @json/', $surveyShow));

        $surveyIndex = $read('marketing/surveys/index');
        // Modal detail + 2 modal edit (modal edit kedua punya 2 blok).
        $this->assertSame(4, substr_count($surveyIndex, '@if($canSeePicInfo)'));
        $this->assertMatchesRegularExpression('/@if\(\$canSeePicInfo\)\n(?:.*\n){1,2}.*name="email" class="form-input" value="\$\{data\.email/', $surveyIndex);

        $surveyPdf = $read('marketing/surveys/pdf');
        $this->assertStringNotContainsString('{{ $survey->display_email }}', $surveyPdf);
        $this->assertStringNotContainsString('{{ $survey->display_phone_one }}', $surveyPdf);
        $this->assertStringContainsString('{{ $survey->display_contact_person }}', $surveyPdf, 'Nama PIC tetap tampil');

        $quotationShow = $read('marketing/quotations/show');
        $this->assertStringContainsString('{{ $canSeePicInfo ? ($quotation->pic_email', $quotationShow);
        $this->assertStringContainsString('{{ $canSeePicInfo ? ($quotation->pic_phone', $quotationShow);
        $this->assertStringContainsString('{{ $quotation->pic_name ?? \'-\' }}', $quotationShow);

        $quotationPdf = $read('marketing/quotations/pdf');
        $this->assertMatchesRegularExpression('/\$picPhone = \\\\App\\\\Models\\\\User::currentCanSeePicInfo\(\)\n\s*\? trim/', $quotationPdf);
    }

    public function test_survey_and_quotation_pdfs_hide_pic_contact_but_keep_name(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $survey = new Survey([
            'survey_number' => 'JKT-SV/26-11/0001',
            'survey_date' => '2026-11-20',
            'company_name' => 'PT Wangi',
            'contact_person' => 'Budi Survey',
            'email' => 'budi@wangi.test',
            'phone_1' => '0811111111',
            'phone_2' => '0822222222',
        ]);
        $survey->setRelation('building', null);
        $survey->setRelation('customer', null);
        $survey->setRelation('surveyDetails', collect());

        $quotation = new Quotation([
            'quotation_number' => 'JKT-SQ/26-11/0001',
            'pic_name' => 'Budi Quotation',
            'pic_email' => 'budi@wangi.test',
            'pic_phone' => '0811111111',
            'grand_total' => 0,
        ]);
        $quotation->setRelation('prospect', new \App\Models\Prospect(['company_name' => 'PT Wangi', 'contact_phone' => '0844444444']));
        $quotation->setRelation('customer', null);
        $quotation->setRelation('marketing', null);
        $quotation->setRelation('quotationDetails', collect());

        $this->actingAs($this->userWith(2, ['Marketing Staff']));
        $surveyHtml = view('marketing.surveys.pdf', compact('survey'))->render();
        $quotationHtml = view('marketing.quotations.pdf', compact('quotation'))->render();

        $this->assertStringContainsString('Budi Survey', $surveyHtml);
        $this->assertStringNotContainsString('budi@wangi.test', $surveyHtml);
        $this->assertStringNotContainsString('0811111111', $surveyHtml);
        $this->assertStringNotContainsString('0822222222', $surveyHtml);
        $this->assertStringContainsString('Budi Quotation', $quotationHtml);
        $this->assertStringNotContainsString('0844444444', $quotationHtml);

        $this->actingAs($this->userWith(3, ['PIC Info'], [User::PIC_INFO_PERMISSION]));
        $surveyHtml = view('marketing.surveys.pdf', compact('survey'))->render();
        $quotationHtml = view('marketing.quotations.pdf', compact('quotation'))->render();
        $this->assertStringContainsString('budi@wangi.test', $surveyHtml);
        $this->assertStringContainsString('0811111111', $surveyHtml);
        $this->assertStringContainsString('0844444444', $quotationHtml);

    }

    public function test_mobile_api_does_not_use_pic_info_rule(): void
    {
        foreach (glob(app_path('Http/Controllers/Api/Mobile/*.php')) as $file) {
            $source = file_get_contents($file);
            $this->assertStringNotContainsString('canSeePicInfo', $source, basename($file));
            $this->assertStringNotContainsString('contract-pic-info', $source, basename($file));
        }
    }
}
