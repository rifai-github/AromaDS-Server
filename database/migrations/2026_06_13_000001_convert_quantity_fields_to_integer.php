<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $quantityColumns = [
        'contract_rentals' => ['quantity', 'qty_free'],
        'invoice_details' => ['quantity'],
        'invoice_form_details' => ['quantity'],
        'invoice_form_rentals' => ['quantity'],
        'invoice_rental_details' => ['quantity', 'qty_free'],
        'inventory_issuing_items' => ['quantity_requested', 'quantity_issued', 'quantity_received'],
        'inventory_receiving_items' => ['quantity_received'],
        'inventory_request_items' => ['approved_qty', 'issued_qty', 'received_qty', 'returned_qty'],
        'inventory_transfer_items' => ['quantity'],
        'job_advice_rooms' => ['qty_free'],
        'material_issue_items' => ['quantity'],
        'material_return_items' => ['quantity'],
        'quotation_details' => ['quantity', 'qty_free'],
        'quotation_rentals' => ['quantity', 'qty_free'],
        'rental_details' => ['quantity'],
    ];

    public function up(): void
    {
        foreach ($this->quantityColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->integer($column)->default(0)->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->quantityColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->decimal($column, 10, 2)->default(0)->change();
                    }
                }
            });
        }
    }
};
