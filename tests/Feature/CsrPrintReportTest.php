<?php

namespace Tests\Feature;

use Tests\TestCase;

class CsrPrintReportTest extends TestCase
{
    public function test_csr_pdf_uses_product_item_qty_and_type_columns(): void
    {
        $job = (object) [
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
        $this->assertStringNotContainsString('>Job No<', $html);
        $this->assertStringContainsString('C100 100 ml', $html);
        $this->assertStringContainsString('Service/Refill', $html);
        $this->assertStringContainsString('>2<', $html);
    }
}
