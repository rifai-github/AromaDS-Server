<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\CustomerContactController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobAdvicePicVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->text('roles')->nullable();
            $table->string('data_restriction')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('access_type')->nullable();
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_customer_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('customer_contact_id')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('salutation')->nullable();
            $table->string('position')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token')->nullable();
            $table->timestamp('email_verification_sent_at')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customer_customer_contact');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('user_access_levels');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_pic_dropdown_shows_contact_created_by_another_user_even_with_strict_none_access(): void
    {
        // Bagus is a Marketing Staff handling the Moonton contract, but the customer's
        // only PIC contact was created by a different staff member (Jenniffer). Bagus's
        // access level is the strict "none" (own-data-only) type, which would normally
        // hide records created by other users.
        DB::table('users')->insert([
            ['id' => 111, 'name' => 'Bagus', 'email' => 'bagus@example.test', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 112, 'name' => 'Jenniffer', 'email' => 'jenniffer@example.test', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('user_access_levels')->insert([
            'user_id' => 111,
            'access_type' => 'none',
            'access_config' => json_encode([]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 7,
            'name' => 'Moonton',
            'created_by' => 112,
            'updated_by' => 112,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_contacts')->insert([
            'id' => 8,
            'customer_id' => 7,
            'name' => 'Lee Chan',
            'position' => 'CEO',
            'email' => 'ceo@moonton.abc',
            'phone' => '021-88889998888',
            'is_active' => true,
            'created_by' => 112,
            'updated_by' => 112,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(111));

        $controller = app(CustomerContactController::class);
        $response = $controller->getByCustomerId(7);

        $data = $response->getData(true);

        $this->assertCount(1, $data);
        $this->assertSame('Lee Chan', $data[0]['name']);
    }

    public function test_pic_dropdown_excludes_inactive_or_other_customers_contacts(): void
    {
        DB::table('users')->insert([
            'id' => 111, 'name' => 'Bagus', 'email' => 'bagus@example.test', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            ['id' => 7, 'name' => 'Moonton', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'Other Customer', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('customer_contacts')->insert([
            [
                'id' => 8,
                'customer_id' => 7,
                'name' => 'Lee Chan',
                'is_active' => true,
                'created_by' => 111,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'customer_id' => 7,
                'name' => 'Inactive Contact',
                'is_active' => false,
                'created_by' => 111,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'customer_id' => 9,
                'name' => 'Unrelated Customer Contact',
                'is_active' => true,
                'created_by' => 111,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Auth::login(User::findOrFail(111));

        $controller = app(CustomerContactController::class);
        $response = $controller->getByCustomerId(7);

        $data = $response->getData(true);

        $this->assertCount(1, $data);
        $this->assertSame('Lee Chan', $data[0]['name']);
    }
}
