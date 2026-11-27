<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\BillingGroupController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pak Hengky 27 Nov 2026 (kontrak import Catalyst ADS-AG/26-07/0096): "Edit billing group,
 * PIC kosong jadi PIC finance kosong, email & phone juga. Konfigurasi pajak kosong harus
 * dipilih ulang. Bank payment kosong harus dipilih ulang. Bahaya."
 *
 * Akar masalah di produksi: billing group import menyimpan NITKU 6 digit ("000000") sedangkan
 * opsi dropdown pajak memakai NITKU 22 digit, sehingga tak ada opsi yang terpilih; PIC import
 * sering tak sama dengan nama/email kontak; pic_email import "-" ditolak type="email". Form
 * yang tampil kosong lalu disimpan menimpa nilai tersimpan dengan kosong.
 */
class BillingGroupEditPreservesStoredValuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('data_restriction')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('name')->nullable();
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
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('marketing_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('billing_groups', function (Blueprint $t) {
            $t->id();
            $t->string('billing_group_name')->nullable();
            $t->string('transaction_number')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('contract_id')->nullable();
            foreach (['pic_name', 'pic_phone', 'pic_email', 'pic_address', 'npwp_number', 'npwp_name', 'npwp_address',
                'npwp', 'nitku', 'nik', 'tax_type', 'tax_number', 'invoice_type', 'payment_method',
                'virtual_account_number', 'bank_name'] as $column) {
                $t->string($column)->nullable();
            }
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Finance PIC Info', 'data_restriction' => 'none'],
            ['id' => 2, 'name' => 'Finance tanpa PIC Info', 'data_restriction' => 'none'],
        ]);
        DB::table('customers')->insert(['id' => 11886, 'name' => 'Hairnerd Studio']);
        DB::table('customer_contacts')->insert([
            'id' => 20050, 'customer_id' => 11886, 'name' => 'Ole', 'email' => 'ole@example.test', 'phone' => '0811',
        ]);
        DB::table('contracts')->insert(['id' => 13258, 'contract_number' => 'ADS-AG/26-07/0096', 'customer_id' => 11886]);
        DB::table('billing_groups')->insert([
            'id' => 21013,
            'billing_group_name' => 'ADS-BIL-12/8138',
            'transaction_number' => 'ADS-BIL-12/8138',
            'customer_id' => 11886,
            'contract_id' => 13258,
            'pic_name' => 'Yasinta Sumiyanti',
            'pic_email' => '-',
            'pic_phone' => '021-5152299 / 0812-3456-78901',
            'pic_address' => 'RUKO FRANKFURT',
            'npwp' => '0430314344036000',
            'nitku' => '000000',
            'tax_type' => 'NPWP',
            'tax_number' => '0430314344036000',
            'npwp_address' => 'Jalan Tomang Raya',
            'invoice_type' => 'soft_copy',
            'bank_name' => 'Bank BCA',
            'virtual_account_number' => 'Bank BCA - PT Aroma Delivery Indonesia (1234567890123)',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['billing_groups', 'contracts', 'customer_customer_contact', 'customer_contacts', 'customers', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function actingWith(int $id, array $permissions): void
    {
        $user = User::findOrFail($id);
        (function () use ($permissions) {
            $this->resolvedRoleNamesCache = ['Finance Staff'];
            $this->resolvedPermissionLookupCache = array_fill_keys($permissions, true);
        })->call($user);
        $this->actingAs($user);
    }

    private function submit(array $input): void
    {
        $request = Request::create('/finance/billing-groups/21013', 'PUT', $input);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        $response = app(BillingGroupController::class)->update($request, 21013);
        $this->assertTrue($response->isRedirect());
        $this->assertFalse(session()->has('errors'), 'Validasi gagal: '.json_encode(session('errors')?->all()));
    }

    private function stored(): array
    {
        return (array) DB::table('billing_groups')->find(21013);
    }

    public function test_saving_the_form_as_rendered_keeps_every_stored_value(): void
    {
        $this->actingWith(1, [User::PIC_INFO_PERMISSION]);
        $before = $this->stored();

        // Persis nilai yang dirender form edit (hidden field = nilai tersimpan, select tak diubah).
        $this->submit([
            'contract_id' => 13258,
            'pic_contact_id' => '__stored__',
            'pic_contact_changed' => '0',
            'pic_name' => 'Yasinta Sumiyanti',
            'pic_email' => '-',
            'pic_phone' => '021-5152299 / 0812-3456-78901',
            'pic_address' => 'RUKO FRANKFURT',
            'npwp' => '0430314344036000',
            'nitku' => '000000',
            'nik' => '',
            'tax_type' => 'NPWP',
            'tax_number' => '0430314344036000',
            'npwp_address' => 'Jalan Tomang Raya',
            'invoice_type' => 'soft_copy',
            'bank_name' => 'Bank BCA',
            'payment_method' => '',
            'virtual_account_number' => 'Bank BCA - PT Aroma Delivery Indonesia (1234567890123)',
        ]);

        $after = $this->stored();
        foreach (['pic_name', 'pic_email', 'pic_phone', 'pic_address', 'npwp', 'nitku', 'nik', 'tax_type',
            'tax_number', 'npwp_address', 'invoice_type', 'bank_name', 'payment_method', 'virtual_account_number'] as $field) {
            $this->assertSame($before[$field], $after[$field], "{$field} berubah padahal user tidak mengubahnya");
        }
    }

    public function test_blank_tax_and_bank_selection_never_wipes_stored_values(): void
    {
        $this->actingWith(1, [User::PIC_INFO_PERMISSION]);

        $this->submit([
            'pic_name' => 'Yasinta Sumiyanti',
            'pic_email' => '-',
            'pic_phone' => '021-5152299 / 0812-3456-78901',
            'pic_address' => 'RUKO FRANKFURT',
            'npwp' => '', 'nitku' => '', 'nik' => '',
            'tax_type' => '', 'tax_number' => '', 'npwp_address' => '',
            'invoice_type' => '',
            'bank_name' => '', 'payment_method' => '', 'virtual_account_number' => '',
        ]);

        $after = $this->stored();
        $this->assertSame('0430314344036000', $after['npwp']);
        $this->assertSame('000000', $after['nitku']);
        $this->assertSame('0430314344036000', $after['tax_number']);
        $this->assertSame('Jalan Tomang Raya', $after['npwp_address']);
        $this->assertSame('soft_copy', $after['invoice_type']);
        $this->assertSame('Bank BCA', $after['bank_name']);
        $this->assertSame('Bank BCA - PT Aroma Delivery Indonesia (1234567890123)', $after['virtual_account_number']);
    }

    public function test_user_changes_are_still_saved(): void
    {
        $this->actingWith(1, [User::PIC_INFO_PERMISSION]);

        $this->submit([
            'pic_name' => 'Ole',
            'pic_email' => 'ole@example.test',
            'pic_phone' => '0811',
            'pic_address' => 'RUKO FRANKFURT',
            'npwp' => '0430314344036000', 'nitku' => '0430314344036000000001', 'nik' => '',
            'npwp_address' => 'Cabang Serpong',
            'invoice_type' => 'hard_copy',
            'bank_name' => 'Bank BII', 'payment_method' => 'transfer',
            'virtual_account_number' => 'Bank BII - PSI BII (123)',
        ]);

        $after = $this->stored();
        $this->assertSame('Ole', $after['pic_name']);
        $this->assertSame('ole@example.test', $after['pic_email']);
        $this->assertSame('0811', $after['pic_phone']);
        $this->assertSame('0430314344036000000001', $after['nitku']);
        $this->assertSame('NPWP, NITKU', $after['tax_type']);
        $this->assertSame('Cabang Serpong', $after['npwp_address']);
        $this->assertSame('hard_copy', $after['invoice_type']);
        $this->assertSame('Bank BII', $after['bank_name']);
        $this->assertSame('transfer', $after['payment_method']);
    }

    public function test_changed_pic_email_is_still_validated(): void
    {
        $this->actingWith(1, [User::PIC_INFO_PERMISSION]);

        $request = Request::create('/finance/billing-groups/21013', 'PUT', ['pic_email' => 'bukan-email']);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);
        app(BillingGroupController::class)->update($request, 21013);

        $this->assertTrue(session()->has('errors'));
        $this->assertSame('-', $this->stored()['pic_email']);
    }

    public function test_user_without_pic_info_permission_cannot_wipe_hidden_email_and_phone(): void
    {
        $this->actingWith(2, []);

        // Field email/telepon tidak dirender untuk user ini; request palsu yang mengirimnya
        // kosong pun diabaikan.
        $this->submit([
            'pic_contact_id' => '__stored__',
            'pic_contact_changed' => '0',
            'pic_name' => 'Yasinta Sumiyanti',
            'pic_email' => '',
            'pic_phone' => '',
        ]);

        $after = $this->stored();
        $this->assertSame('-', $after['pic_email']);
        $this->assertSame('021-5152299 / 0812-3456-78901', $after['pic_phone']);
    }

    public function test_user_without_pic_info_permission_picking_a_contact_takes_its_email_and_phone(): void
    {
        $this->actingWith(2, []);

        $this->submit([
            'pic_contact_id' => '20050',
            'pic_contact_changed' => '1',
            'pic_name' => 'Ole',
        ]);

        $after = $this->stored();
        $this->assertSame('Ole', $after['pic_name']);
        $this->assertSame('ole@example.test', $after['pic_email']);
        $this->assertSame('0811', $after['pic_phone']);
    }

    public function test_edit_form_preselects_stored_values_and_hides_pic_info(): void
    {
        $source = file_get_contents(resource_path('views/finance/billing-groups/editbg.blade.php'));

        // Opsi "tersimpan" untuk PIC, pajak, dan bank yang tidak cocok dengan daftar.
        $this->assertStringContainsString('$showStoredPicOption', $source);
        $this->assertStringContainsString("'source' => 'stored'", $source);
        $this->assertStringContainsString('<option value="__stored__" selected data-stored="1">', $source);
        // NITKU 6 digit dibandingkan dalam bentuk normal 22 digit.
        $this->assertStringContainsString('$normalizeNitku', $source);
        // Hidden NIK tidak lagi jatuh ke NIK induk.
        $this->assertStringNotContainsString('$storedNik ?? $indukNik', $source);
        // Placeholder mengembalikan nilai tersimpan, bukan mengosongkan.
        $this->assertStringContainsString('restoreStoredValues', $source);
        $this->assertStringNotContainsString("document.getElementById('bank_name').value = '';", $source);
        // Inisialisasi pajak saat load tidak merombak data tersimpan.
        $this->assertStringContainsString("getAttribute('data-has-stored-tax') !== '1'", $source);
        // Aturan PIC Info + binding select2 (7e79ef9) tetap ada.
        $this->assertStringContainsString('currentCanSeePicInfo()', $source);
        $this->assertStringContainsString("window.jQuery(picSelect).on('change select2:select', applyPicSelection)", $source);
        $this->assertStringNotContainsString('<input type="email"', $source);
    }
}
