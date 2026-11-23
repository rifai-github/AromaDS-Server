<?php

namespace Tests\Unit;

use App\Models\Survey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MOM 18 Nov 2026: Basic Info survey harus terhubung ke Master Building (alamat) dan Master
 * Contact (jabatan/posisi, email, phone PIC), bukan salinan saat survey dibuat.
 */
class SurveyLinkedMasterDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['surveys', 'buildings', 'customers', 'customer_contacts', 'customer_customer_contact'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('buildings', function (Blueprint $t) {
            $t->id();
            $t->string('building_name')->nullable();
            $t->string('alamat_1')->nullable();
            $t->string('alamat_2')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('name')->nullable();
            $t->string('position')->nullable();
            $t->string('email')->nullable();
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
        Schema::create('surveys', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->string('contact_person')->nullable();
            $t->string('position')->nullable();
            $t->string('email')->nullable();
            $t->string('phone_1')->nullable();
            $t->string('address_1')->nullable();
            $t->string('address_2')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('customers')->insert(['id' => 1, 'name' => 'PT Contoh', 'email' => 'cust@example.test', 'phone' => '0210000']);
        DB::table('buildings')->insert(['id' => 5, 'building_name' => 'Gedung A', 'alamat_1' => 'Jl. Baru No. 2', 'alamat_2' => 'Lt. 3']);
        DB::table('customer_contacts')->insert([
            'id' => 9, 'customer_id' => 1, 'name' => 'Andi Wijaya', 'position' => 'Facility Manager',
            'email' => 'andi.baru@example.test', 'phone' => '0812-baru',
        ]);
    }

    private function survey(array $overrides = []): Survey
    {
        DB::table('surveys')->insert(array_merge([
            'id' => 1, 'customer_id' => 1, 'building_id' => 5, 'contact_person' => 'Andi Wijaya',
            'position' => 'Staff', 'email' => 'andi.lama@example.test', 'phone_1' => '0812-lama',
            'address_1' => 'Jl. Lama No. 1', 'address_2' => null,
        ], $overrides));

        return Survey::findOrFail(1);
    }

    public function test_basic_info_follows_master_contact_and_master_building(): void
    {
        $survey = $this->survey();

        $this->assertSame('Facility Manager', $survey->display_position);
        $this->assertSame('andi.baru@example.test', $survey->display_email);
        $this->assertSame('0812-baru', $survey->display_phone_one);
        $this->assertSame('Jl. Baru No. 2', $survey->display_address_one);
        $this->assertSame('Lt. 3', $survey->display_address_two);
    }

    public function test_company_name_follows_the_renamed_master_customer(): void
    {
        // QA 23 Nov (survey 33446): nama customer di Basic Info/PDF ikut Master Customer.
        if (! Schema::hasColumn('surveys', 'company_name')) {
            Schema::table('surveys', fn (Blueprint $t) => $t->string('company_name')->nullable());
        }
        $survey = $this->survey(['company_name' => "PT Contoh 'Lama'"]);
        DB::table('customers')->where('id', 1)->update(['name' => "PT Contoh 'Baru'"]);

        $this->assertSame("PT Contoh 'Baru'", $survey->fresh()->display_company_name);
    }

    public function test_contact_renamed_in_master_is_still_found_by_email(): void
    {
        DB::table('customer_contacts')->where('id', 9)->update(['name' => 'Andi W.', 'email' => 'andi.lama@example.test']);

        $survey = $this->survey();

        $this->assertSame('Andi W.', $survey->display_contact_person);
        $this->assertSame('Facility Manager', $survey->display_position);
    }

    public function test_contact_linked_through_the_pivot_is_found(): void
    {
        DB::table('customer_contacts')->where('id', 9)->update(['customer_id' => null]);
        DB::table('customer_customer_contact')->insert(['customer_id' => 1, 'customer_contact_id' => 9]);

        $this->assertSame('Facility Manager', $this->survey()->display_position);
    }

    public function test_falls_back_to_the_survey_copy_when_nothing_matches(): void
    {
        $survey = $this->survey(['contact_person' => 'Orang Lain', 'email' => 'lain@example.test', 'building_id' => null]);

        $this->assertSame('Staff', $survey->display_position);
        $this->assertSame('lain@example.test', $survey->display_email);
        $this->assertSame('Jl. Lama No. 1', $survey->display_address_one);
    }

    public function test_identical_second_address_line_is_not_repeated(): void
    {
        DB::table('buildings')->where('id', 5)->update(['alamat_2' => 'jl. baru no. 2']);

        $this->assertNull($this->survey()->display_address_two);
    }
}
