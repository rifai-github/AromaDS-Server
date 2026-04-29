<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Customer;
use App\Models\User;
use App\Models\Survey;
use App\Models\Room;
use App\Models\MasterProduct;
use App\Models\Building;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first customer and user
        $customer = Customer::first();
        $user = User::first();
        
        if (!$customer || !$user) {
            $this->command->info('No customer or user found. Please create them first.');
            return;
        }

        // Create required data if not exists
        $building = Building::first();
        if (!$building) {
            $building = Building::create([
                'building_name' => 'Sample Building',
                'address' => 'Sample Address',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12345',
                'contact_person' => 'Building Manager',
                'phone' => '021-12345678',
                'email' => 'building@sample.com',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
        }

        // Use existing room or skip if none
        $room = Room::first();

        // Create survey if not exists
        $survey = Survey::first();
        if (!$survey) {
            $survey = Survey::create([
                'survey_number' => 'SUR-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'building_id' => $building->id,
                'survey_date' => now()->subDays(7),
                'surveyor_name' => 'Sample Surveyor',
                'status' => 'completed',
                'notes' => 'Sample survey for testing',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
        }

        $product = MasterProduct::first();

        // Create comprehensive approved quotation
        $quotation = Quotation::create([
            'quotation_number' => 'QTN-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'prospect_id' => $customer->id,
            'survey_id' => $survey->id,
            'quotation_date' => now()->subDays(5),
            'valid_until' => now()->addDays(25),
            'company_name' => $customer->company_name ?? 'PT Sample Company',
            'pic_name' => 'John Doe - Manager',
            'billing_methods' => 'monthly',
            'status' => 'approved',
            'rental_period' => 12,
            'terms_of_payment' => 'net_30',
            'marketing_id' => $user->id,
            'approved_by' => $user->id,
            'date_approved' => now()->subDays(2),
            'internal_notes' => 'Quotation untuk customer premium dengan kebutuhan khusus',
            'additional_notes' => 'Customer memerlukan instalasi khusus dan training tim',
            'quotation_type' => 'new',
            'existing_contract_id' => null,
            'total_amount' => 5000000,
            'discount_amount' => 500000,
            'tax_amount' => 495000,
            'grand_total' => 4995000,
            'terms_conditions' => '1. Pembayaran dilakukan setiap bulan\n2. Garansi produk 1 tahun\n3. Training tim customer gratis\n4. Maintenance rutin setiap 3 bulan',
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);

        // Create quotation details only if we have the required data
        if ($survey && $product) {
            $quotationDetails = [
                [
                    'quotation_id' => $quotation->id,
                    'master_rental_id' => $product->id,
                    'room_name' => 'Meeting Room',
                    'quantity' => 2,
                    'unit_price' => 1500000,
                    'total_price' => 3000000,
                    'specifications' => 'Aroma diffuser premium untuk ruang meeting',
                    'created_by' => $user->id,
                    'updated_by' => $user->id
                ],
                [
                    'quotation_id' => $quotation->id,
                    'master_rental_id' => $product->id,
                    'room_name' => 'Lobby Utama',
                    'quantity' => 1,
                    'unit_price' => 2000000,
                    'total_price' => 2000000,
                    'specifications' => 'Aroma diffuser untuk lobby utama',
                    'created_by' => $user->id,
                    'updated_by' => $user->id
                ]
            ];

            foreach ($quotationDetails as $detail) {
                QuotationDetail::create($detail);
            }
        }

        $this->command->info('Comprehensive sample quotation created successfully!');
        $this->command->info('Quotation Number: ' . $quotation->quotation_number);
        $this->command->info('Customer: ' . $customer->company_name);
        $this->command->info('Total Amount: Rp ' . number_format($quotation->grand_total, 0, ',', '.'));
        $this->command->info('Status: ' . $quotation->status_text);
    }
}