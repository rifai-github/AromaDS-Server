<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\SurveyWizardController;
use App\Models\CustomerContact;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jawaban klien 26 Nov 2026 (Survey Wizard):
 *  - untuk semua user, pilihan gedung memuat SEMUA gedung tanpa kecuali; yang beda hanya lokasi
 *    detailnya, jadi gedung bernama sama tetap tampil dan dibedakan lewat alamat/kota;
 *  - PIC yang baru dibuat tersimpan tapi tidak ikut termuat, dan hilang setelah Next -> Previous.
 */
class SurveyWizardAllBuildingsAndNewPicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['provinces', 'cities', 'districts', 'subdistricts'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->string('name')->nullable();
                if ($table === 'cities') {
                    $t->string('type')->nullable();
                }
                if ($table === 'subdistricts') {
                    $t->string('postal_code')->nullable();
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }

        Schema::create('buildings', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('nama_gedung')->nullable();
            $t->string('address')->nullable();
            $t->string('alamat_1')->nullable();
            $t->string('alamat_2')->nullable();
            $t->string('postal_code')->nullable();
            $t->string('kode_pos')->nullable();
            $t->string('phone_1')->nullable();
            $t->string('phone_2')->nullable();
            $t->string('fax')->nullable();
            $t->unsignedBigInteger('province_id')->nullable();
            $t->unsignedBigInteger('city_id')->nullable();
            $t->unsignedBigInteger('district_id')->nullable();
            $t->unsignedBigInteger('subdistrict_id')->nullable();
            $t->boolean('status_update')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('salutation')->nullable();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('position')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('provinces')->insert(['id' => 1, 'name' => 'DKI Jakarta']);
        DB::table('cities')->insert([
            ['id' => 1, 'name' => 'Jakarta Selatan', 'type' => 'Kota'],
            ['id' => 2, 'name' => 'Jakarta Pusat', 'type' => 'Kota'],
        ]);

        $now = now()->subDay();
        DB::table('buildings')->insert([
            // Dua gedung bernama & beralamat sama di kota sama: dulu salah satunya dibuang de-duplikasi.
            ['id' => 1, 'nama_gedung' => 'Menara Satu', 'alamat_1' => 'Jl. Sudirman 1', 'province_id' => 1, 'city_id' => 1, 'status_update' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nama_gedung' => 'Menara Satu', 'alamat_1' => 'Jl. Sudirman 1', 'province_id' => 1, 'city_id' => 1, 'status_update' => true, 'created_at' => $now, 'updated_at' => $now],
            // Nama sama, lokasi beda.
            ['id' => 3, 'nama_gedung' => 'Menara Satu', 'alamat_1' => 'Jl. Thamrin 9', 'province_id' => 1, 'city_id' => 2, 'status_update' => true, 'created_at' => $now, 'updated_at' => $now],
            // Nonaktif - dulu disaring.
            ['id' => 4, 'nama_gedung' => 'Gedung Lama', 'alamat_1' => 'Jl. Gatot 3', 'province_id' => 1, 'city_id' => 1, 'status_update' => false, 'created_at' => $now, 'updated_at' => $now],
            // Tanpa provinsi/kota - dulu disaring.
            ['id' => 5, 'nama_gedung' => 'Ruko Baru', 'alamat_1' => 'Jl. Kenanga 7', 'province_id' => null, 'city_id' => null, 'status_update' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['customer_contacts', 'buildings', 'subdistricts', 'districts', 'cities', 'provinces'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function buildings(): array
    {
        return app(SurveyWizardController::class)->getBuildings(Request::create('/get-buildings', 'GET'))->getData(true);
    }

    public function test_every_building_is_listed_without_filter_or_dedup(): void
    {
        $ids = collect($this->buildings())->pluck('id')->sort()->values()->all();

        $this->assertSame([1, 2, 3, 4, 5], $ids);
    }

    public function test_label_tells_same_name_buildings_apart(): void
    {
        $labels = collect($this->buildings())->keyBy('id')->map(fn ($b) => $b['label']);

        $this->assertSame('Menara Satu - Jl. Sudirman 1, Jakarta Selatan', $labels[1]);
        $this->assertSame('Menara Satu - Jl. Thamrin 9, Jakarta Pusat', $labels[3]);
        $this->assertStringEndsWith('(Nonaktif)', $labels[4]);
        $this->assertStringContainsString('provinsi/kota belum diisi', $labels[5]);
        // Nama murni tetap dikirim untuk ringkasan/form.
        $this->assertSame('Menara Satu', collect($this->buildings())->firstWhere('id', 3)['name']);
    }

    public function test_a_building_created_elsewhere_shows_up_without_waiting_for_the_cache(): void
    {
        $this->assertCount(5, $this->buildings());

        // Dibuat dari menu lain (Master Building) yang tidak membersihkan cache wizard.
        DB::table('buildings')->insert(['id' => 6, 'nama_gedung' => 'Gedung Anyar', 'alamat_1' => 'Jl. Melati', 'province_id' => 1, 'city_id' => 1, 'status_update' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertContains(6, collect($this->buildings())->pluck('id')->all());
    }

    public function test_saving_a_contact_clears_the_wizard_contacts_cache_of_its_customer(): void
    {
        Cache::put('survey-wizard:contacts:v2:7', ['stale'], now()->addMinutes(5));
        Cache::put('survey-wizard:contacts:v2:8', ['other'], now()->addMinutes(5));

        CustomerContact::create(['customer_id' => 7, 'name' => 'PIC Baru', 'email' => 'pic@example.test', 'phone' => '0812', 'position' => 'Manager']);

        $this->assertFalse(Cache::has('survey-wizard:contacts:v2:7'));
        $this->assertTrue(Cache::has('survey-wizard:contacts:v2:8'));
    }

    public function test_wizard_remembers_and_restores_a_new_pic_between_steps(): void
    {
        $view = preg_replace('/\s+/', ' ', file_get_contents(resource_path('views/marketing/surveys/wizard/create.blade.php')));

        $this->assertStringContainsString('if (currentStep === 3) { rememberWizardNewContact(); }', $view);
        $this->assertStringContainsString('initializeSelect2(); if (currentStep === 3) { restoreWizardNewContact(); }', $view);
        // Opsi gedung memakai label (nama + lokasi), nama murni di data-name untuk ringkasan.
        $this->assertStringContainsString("option.attr('data-name', buildingName);", $view);
        $this->assertStringContainsString("buildingName = surveyBuildingNameOf($('#building_id option:selected'));", $view);
    }

    public function test_survey_save_clears_contacts_cache_after_commit(): void
    {
        $controller = preg_replace('/\s+/', ' ', file_get_contents(app_path('Http/Controllers/Marketing/SurveyWizardController.php')));

        $this->assertStringContainsString(
            'DB::commit(); // PIC baru (Tambah Kontak baru) langsung ikut saat daftar kontak customer dimuat ulang. if ($addNewContact) { $this->forgetSurveyWizardContactCaches((int) $customerId); }',
            $controller
        );
    }
}
