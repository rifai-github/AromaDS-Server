<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->string('approval_status', 32)->default('not_required')->after('status')->index();
            $table->unsignedBigInteger('submitted_for_approval_by')->nullable()->after('central_approval_notes');
            $table->timestamp('submitted_for_approval_at')->nullable()->after('submitted_for_approval_by');
            $table->unsignedBigInteger('central_rejected_by')->nullable()->after('submitted_for_approval_at');
            $table->timestamp('central_rejected_at')->nullable()->after('central_rejected_by');
            $table->text('central_rejection_reason')->nullable()->after('central_rejected_at');
        });

        DB::table('inventory_transfers')
            ->where('is_direct_branch_transfer', true)
            ->whereNotNull('central_approved_by')
            ->update(['approval_status' => 'approved']);

        DB::table('inventory_transfers')
            ->where('is_direct_branch_transfer', true)
            ->whereNull('central_approved_by')
            ->update(['approval_status' => 'draft']);

        Schema::create('inventory_transfer_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->index();
            $table->string('action', 32)->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('permissions')) {
            foreach ([
                'warehouse.inventory-transfers' => 'Access Inventory Transfers',
                'warehouse.inventory-transfers.view' => 'View Inventory Transfers',
                'warehouse.inventory-transfers.create' => 'Create Inventory Transfers',
                'warehouse.inventory-transfers.update' => 'Update Inventory Transfers',
                'warehouse.inventory-transfers.delete' => 'Delete Draft Inventory Transfers',
                'warehouse.inventory-transfers.submit' => 'Submit Inventory Transfers for Approval',
                'warehouse.inventory-transfers.approve' => 'Approve Inventory Transfers at Central Office',
                'warehouse.inventory-transfers.reject' => 'Reject Inventory Transfers at Central Office',
                'warehouse.inventory-transfers.transfer' => 'Mark Inventory Transfers as Transferred',
                'warehouse.inventory-transfers.receive' => 'Mark Inventory Transfers as Received',
            ] as $name => $description) {
                $existing = DB::table('permissions')->where('name', $name)->exists();
                if ($existing) {
                    DB::table('permissions')->where('name', $name)->update([
                        'description' => $description,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('permissions')->insert([
                        'name' => $name,
                        'description' => $description,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_approval_histories');

        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropColumn([
                'approval_status',
                'submitted_for_approval_by',
                'submitted_for_approval_at',
                'central_rejected_by',
                'central_rejected_at',
                'central_rejection_reason',
            ]);
        });
    }
};
