<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addInventoryTransferApprovalColumns();

        DB::table('inventory_transfers')
            ->where('is_direct_branch_transfer', true)
            ->whereNotNull('central_approved_by')
            ->update(['approval_status' => 'approved']);

        DB::table('inventory_transfers')
            ->where('is_direct_branch_transfer', true)
            ->whereNull('central_approved_by')
            ->update(['approval_status' => 'draft']);

        $this->createOrRepairApprovalHistoryTable();

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

        if (Schema::hasTable('inventory_transfers')) {
            if (Schema::hasColumn('inventory_transfers', 'approval_status')
                && Schema::hasIndex('inventory_transfers', ['approval_status'])) {
                Schema::table('inventory_transfers', function (Blueprint $table) {
                    $table->dropIndex(['approval_status']);
                });
            }

            foreach ([
                'central_rejection_reason',
                'central_rejected_at',
                'central_rejected_by',
                'submitted_for_approval_at',
                'submitted_for_approval_by',
                'approval_status',
            ] as $column) {
                if (Schema::hasColumn('inventory_transfers', $column)) {
                    Schema::table('inventory_transfers', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }

    private function addInventoryTransferApprovalColumns(): void
    {
        $columns = [
            'approval_status',
            'submitted_for_approval_by',
            'submitted_for_approval_at',
            'central_rejected_by',
            'central_rejected_at',
            'central_rejection_reason',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('inventory_transfers', $column)) {
                continue;
            }

            Schema::table('inventory_transfers', function (Blueprint $table) use ($column) {
                match ($column) {
                    'approval_status' => $table->string('approval_status', 32)->default('not_required')->after('status'),
                    'submitted_for_approval_by' => $table->unsignedBigInteger('submitted_for_approval_by')->nullable()->after('central_approval_notes'),
                    'submitted_for_approval_at' => $table->timestamp('submitted_for_approval_at')->nullable()->after('submitted_for_approval_by'),
                    'central_rejected_by' => $table->unsignedBigInteger('central_rejected_by')->nullable()->after('submitted_for_approval_at'),
                    'central_rejected_at' => $table->timestamp('central_rejected_at')->nullable()->after('central_rejected_by'),
                    'central_rejection_reason' => $table->text('central_rejection_reason')->nullable()->after('central_rejected_at'),
                };
            });
        }

        if (! Schema::hasIndex('inventory_transfers', ['approval_status'])) {
            Schema::table('inventory_transfers', function (Blueprint $table) {
                $table->index('approval_status', 'it_approval_status_idx');
            });
        }
    }

    private function createOrRepairApprovalHistoryTable(): void
    {
        if (! Schema::hasTable('inventory_transfer_approval_histories')) {
            Schema::create('inventory_transfer_approval_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_transfer_id');
                $table->string('action', 32);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('notes')->nullable();
                $table->json('snapshot')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            ['inventory_transfer_id', 'ita_history_transfer_idx'],
            ['action', 'ita_history_action_idx'],
            ['actor_id', 'ita_history_actor_idx'],
        ] as [$column, $index]) {
            if (Schema::hasColumn('inventory_transfer_approval_histories', $column)
                && ! Schema::hasIndex('inventory_transfer_approval_histories', [$column])) {
                Schema::table('inventory_transfer_approval_histories', function (Blueprint $table) use ($column, $index) {
                    $table->index($column, $index);
                });
            }
        }
    }
};
