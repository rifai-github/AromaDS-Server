<?php

namespace Tests\Unit;

use App\Models\Quotation;
use App\Models\User;
use Tests\TestCase;

class QuotationApprovalDisplayTest extends TestCase
{
    public function test_auto_approved_quotation_displays_marketing_name(): void
    {
        $quotation = new Quotation([
            'status' => 'approved',
            'approved_by' => null,
            'date_approved' => now(),
        ]);
        $quotation->setRelation('marketing', new User(['name' => 'Rizky Marketing']));

        $this->assertTrue($quotation->is_auto_approved);
        $this->assertSame('Rizky Marketing (Auto Approve)', $quotation->approved_by_display_name);
    }

    public function test_manually_approved_quotation_displays_approver_name(): void
    {
        $quotation = new Quotation([
            'status' => 'approved',
            'approved_by' => 7,
            'date_approved' => now(),
        ]);
        $quotation->setRelation('approver', new User(['name' => 'Manager Approval']));
        $quotation->setRelation('marketing', new User(['name' => 'Rizky Marketing']));

        $this->assertFalse($quotation->is_auto_approved);
        $this->assertSame('Manager Approval', $quotation->approved_by_display_name);
    }
}
