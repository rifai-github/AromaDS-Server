<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CsrPrintReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name')->nullable();
            $table->timestamps();
        });

        // UAT 24 Nov 2026: printCsr memuat TTD PIC, anggota team, SQ dan rental alias.
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('joined_date')->nullable();
            $table->date('left_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('signature_file')->nullable();
            $table->text('signature_data')->nullable();
            $table->timestamp('signature_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('price_basis')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_alias')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->string('rental_alias')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('assigned_technician_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('building_name')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->integer('period')->nullable();
            $table->date('schedule_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('name')->nullable();
            $table->string('rental_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rental_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'quotation_details',
            'contract_rentals',
            'quotations',
            'job_reports',
            'team_members',
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_advice_rooms',
            'rental_details',
            'master_products',
            'product_types',
            'product_categories',
            'master_rentals',
            'master_rooms',
            'job_assign_schedules',
            'job_schedules',
            'teams',
            'users',
            'buildings',
            'job_advices',
            'contracts',
            'customers',
            'companies',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_csr_pdf_uses_product_item_qty_and_type_columns(): void
    {
        $job = (object) [
            'id' => 1,
            'type' => 'service_first',
            'period' => 1,
            'schedule_date' => '2026-06-04',
            'completed_at' => null,
            'company_name' => 'Maju Sejahtera Indonesia',
            'building_name' => 'Spektrum Biologi I',
            'jobAdvice' => (object) [
                'customer' => (object) ['name' => 'Maju Sejahtera Indonesia'],
                'contract' => (object) ['contract_number' => 'BDG-CA/26-05/0001'],
            ],
            'building' => (object) ['address' => 'Jalan Bandung Raya X'],
            'assignedTechnician' => null,
            'jobAssignSchedules' => collect([]),
            'jobScheduleRooms' => collect([
                (object) [
                    'id' => 1,
                    'display_rental_name' => 'Category Fallback',
                    'room' => (object) ['room_name' => 'Office Room'],
                    'jobAdviceRoom' => (object) [
                        'quantity' => 2,
                        'rental_name' => 'Alias Fallback',
                        'rentalProduct' => (object) [
                            'rental_name' => 'C100 100 ml',
                        ],
                    ],
                ],
            ]),
        ];

        $html = view('operational.job-schedules.pdf-csr', [
            'groupedJobs' => collect(['BDG-CSR/26-05/0001' => collect([$job])]),
            'selectedRoomIds' => null,
        ])->render();

        $this->assertStringContainsString('>Qty<', $html);
        $this->assertStringContainsString('>Type<', $html);
        $this->assertStringNotContainsString('<th>Job No</th>', $html);
        $this->assertStringContainsString('C100 100 ml', $html);
        $this->assertStringContainsString('Service/Refill', $html);
        $this->assertStringContainsString('>2<', $html);
    }

    public function test_csr_pdf_filters_unit_only_rental_from_same_room(): void
    {
        $unitOnlyRoom = (object) [
            'id' => 1001,
            'quantity' => 1,
            'install_job_schedule_id' => 1628,
            'service_job_schedule_id' => null,
            'remove_job_schedule_id' => null,
            'rentalProduct' => (object) [
                'rental_name' => 'ADS XL Unit Only',
                'rental_type' => 'unit_only',
                'rentalDetails' => collect([]),
            ],
        ];
        $refillOnlyRoom = (object) [
            'id' => 1002,
            'quantity' => 1,
            'install_job_schedule_id' => null,
            'service_job_schedule_id' => 1629,
            'remove_job_schedule_id' => null,
            'rentalProduct' => (object) [
                'rental_name' => 'Rental-5',
                'rental_type' => 'refill_only',
                'rentalDetails' => collect([]),
            ],
        ];
        $job = (object) [
            'id' => 1629,
            'type' => 'service_first',
            'period' => 1,
            'schedule_date' => '2026-06-04',
            'completed_at' => null,
            'company_name' => 'Test 260218 PT',
            'building_name' => 'Gedung Test260218',
            'jobAdvice' => (object) [
                'customer' => (object) ['name' => 'Test 260218 PT'],
                'contract' => (object) ['contract_number' => 'JKT-CA/26-06/0002'],
            ],
            'building' => null,
            'assignedTechnician' => null,
            'jobAssignSchedules' => collect([]),
            'jobScheduleRooms' => collect([
                (object) [
                    'id' => 1,
                    'room_name' => 'Ruang Delima',
                    'room' => (object) ['room_name' => 'Ruang Delima'],
                    'jobAdviceRoom' => $unitOnlyRoom,
                    'rentals' => collect([
                        (object) ['jobAdviceRoom' => $unitOnlyRoom],
                        (object) ['jobAdviceRoom' => $refillOnlyRoom],
                    ]),
                ],
            ]),
        ];

        $html = view('operational.job-schedules.pdf-csr', [
            'groupedJobs' => collect(['JKT-CSR/26-06/0004' => collect([$job])]),
            'selectedRoomIds' => null,
        ])->render();

        $this->assertStringContainsString('Rental-5', $html);
        $this->assertStringNotContainsString('ADS XL Unit Only', $html);
        $this->assertStringContainsString('Service/Refill', $html);
    }

    public function test_csr_pdf_prints_schedule_room_rental_when_service_link_points_to_future_job(): void
    {
        $currentServiceRoom = (object) [
            'id' => 2001,
            'quantity' => 1,
            'install_job_schedule_id' => null,
            'service_job_schedule_id' => 2900,
            'remove_job_schedule_id' => null,
            'rentalProduct' => (object) [
                'rental_name' => 'ADS W300 500 ml',
                'rental_type' => 'refill_only',
                'rentalDetails' => collect([]),
            ],
        ];

        $job = (object) [
            'id' => 7,
            'type' => 'service_first',
            'period' => 1,
            'schedule_date' => '2026-06-11',
            'completed_at' => null,
            'company_name' => 'Abadi Company',
            'building_name' => 'Hotel Melton Surabaya',
            'jobAdvice' => (object) [
                'customer' => (object) ['name' => 'Abadi Company'],
                'contract' => (object) ['contract_number' => 'SBY-CA/26-06/0001'],
            ],
            'building' => null,
            'assignedTechnician' => null,
            'jobAssignSchedules' => collect([]),
            'jobScheduleRooms' => collect([
                (object) [
                    'id' => 70,
                    'room_name' => 'Lobby',
                    'room' => (object) ['room_name' => 'Lobby'],
                    'jobAdviceRoom' => null,
                    'rentals' => collect([
                        (object) ['jobAdviceRoom' => $currentServiceRoom],
                    ]),
                ],
            ]),
        ];

        $html = view('operational.job-schedules.pdf-csr', [
            'groupedJobs' => collect(['SBY-CSR/26-06/0003' => collect([$job])]),
            'selectedRoomIds' => null,
        ])->render();

        $this->assertStringContainsString('ADS W300 500 ml', $html);
        $this->assertStringContainsString('Lobby', $html);
        $this->assertStringContainsString('SBY-CSR/26-06/0003', $html);
    }

    public function test_print_csr_allows_new_job_without_job_number(): void
    {
        DB::table('customers')->insert(['id' => 1, 'name' => 'Siloam']);
        DB::table('contracts')->insert(['id' => 1, 'contract_number' => 'SBY-CA/26-05/0001']);
        DB::table('job_advices')->insert(['id' => 1, 'customer_id' => 1, 'contract_id' => 1]);
        DB::table('buildings')->insert(['id' => 1, 'address' => 'Surabaya']);
        DB::table('master_rooms')->insert(['id' => 1, 'room_name' => 'Lobby Utama']);
        DB::table('master_rentals')->insert(['id' => 1, 'rental_name' => 'C100 100 ml']);
        DB::table('job_advice_rooms')->insert(['id' => 1, 'rental_product_id' => 1, 'quantity' => 1]);
        DB::table('job_schedules')->insert([
            'id' => 1465,
            'job_number' => null,
            'job_advice_id' => 1,
            'building_id' => 1,
            'company_name' => 'Siloam',
            'type' => 'service_first',
            'status' => 'scheduled',
            'period' => 1,
            'schedule_date' => '2026-06-05',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 1,
            'job_schedule_id' => 1465,
            'room_id' => 1,
            'job_advice_room_id' => 1,
            'room_name' => 'Lobby Utama',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = app(JobScheduleController::class)->printCsr(Request::create(
            '/operational/job-schedules/print-csr?ids=1465&view_mode=job',
            'GET',
            ['ids' => '1465', 'view_mode' => 'job']
        ));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    /**
     * UAT 24 Nov 2026 (printout): Install Free -> judul & tipe IF, header SQ No, Item = rental
     * alias SQ, QTY per room bila berbasis room, TTD customer = PIC aplikasi, TTD teknisi =
     * nama petugas (bukan nama team).
     */
    public function test_install_free_report_uses_sq_alias_room_qty_and_signers(): void
    {
        $job = (object) [
            'id' => 7,
            'type' => 'install',
            'quotation_number' => 'JKT-SQ/26-06/0005',
            'period' => null,
            'schedule_date' => '2026-06-15',
            'completed_at' => null,
            'company_name' => 'Cahaya Mandiri Utama',
            'building_name' => 'Cafe',
            'jobAdvice' => (object) [
                'type' => 'install_free',
                'customer' => (object) ['name' => 'Cahaya Mandiri Utama'],
                'contract' => null,
                'quotation' => (object) ['quotation_number' => 'JKT-SQ/26-06/0005', 'price_basis' => 'room'],
            ],
            'building' => (object) ['address' => 'Jalan Raya'],
            'assignedTechnician' => null,
            'jobReports' => collect([(object) ['pic_name' => 'Budi PIC', 'signature_file' => null, 'signature_data' => null, 'signature_at' => '2026-06-15 10:00:00', 'updated_at' => null]]),
            'jobAssignSchedules' => collect([(object) ['team' => (object) [
                'team_name' => 'Tim Service Jakarta Timur',
                'users' => collect([(object) ['name' => 'Andi Teknisi']]),
            ]]]),
            'jobScheduleRooms' => collect([
                (object) [
                    'id' => 1,
                    'room' => (object) ['room_name' => 'Lobby'],
                    'jobAdviceRoom' => (object) [
                        'quantity' => 2,
                        'rental_name' => 'Rental Diffuser',
                        'contractRental' => null,
                        'quotationDetail' => (object) ['rental_alias' => 'Diffuser Signature 300ml'],
                        'rentalProduct' => (object) ['rental_name' => 'Diffuser W300'],
                    ],
                ],
            ]),
        ];

        $html = view('operational.job-schedules.pdf-csr', [
            'groupedJobs' => collect(['JKT-IF/26-06/0099' => collect([$job])]),
            'selectedRoomIds' => null,
        ])->render();

        $this->assertStringContainsString('INSTALL FREE REPORT', $html);
        $this->assertStringContainsString('SQ No', $html);
        $this->assertStringContainsString('JKT-SQ/26-06/0005', $html);
        $this->assertStringNotContainsString('Contract No', $html);
        $this->assertStringContainsString('Diffuser Signature 300ml', $html);
        $this->assertStringContainsString('Install Free', $html);
        $this->assertMatchesRegularExpression('/Lobby<\/td>\s*<td[^>]*>\s*1\s*</', $html);
        $this->assertStringContainsString('<p>Budi PIC</p>', $html);
        $this->assertStringContainsString('<p>Andi Teknisi</p>', $html);
        $this->assertStringNotContainsString('<p>Cahaya Mandiri Utama</p>', $html);
    }

    public function test_service_first_report_is_titled_customer_service_report(): void
    {
        $job = (object) [
            'id' => 8, 'type' => 'service_first', 'period' => 1, 'schedule_date' => '2026-06-15', 'completed_at' => null,
            'company_name' => 'X', 'building_name' => 'Y',
            'jobAdvice' => (object) ['customer' => (object) ['name' => 'X'], 'contract' => (object) ['contract_number' => 'JKT-CA/26-06/0002']],
            'building' => null, 'room' => null, 'assignedTechnician' => null, 'jobAssignSchedules' => collect([]), 'jobScheduleRooms' => collect([]),
        ];

        $html = view('operational.job-schedules.pdf-csr', [
            'groupedJobs' => collect(['JKT-CSR/26-06/0002' => collect([$job])]),
            'selectedRoomIds' => null,
        ])->render();

        $this->assertStringContainsString('CUSTOMER SERVICE REPORT', $html);
        $this->assertStringNotContainsString('SERVICE_FIRST REPORT', $html);
        $this->assertStringContainsString('Contract No', $html);
    }
}
