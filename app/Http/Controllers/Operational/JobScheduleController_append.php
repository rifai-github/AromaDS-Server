
    /**
     * Return units to warehouse for Remove Jobs
     *
     * @param JobSchedule $jobSchedule
     * @return void
     */
    private function returnUnitsToWarehouse(JobSchedule $jobSchedule)
    {
        try {
            \Log::info("🔄 Processing Return Units to Warehouse for Job {$jobSchedule->job_number} (Type: {$jobSchedule->type})");

            $jobAdvice = $jobSchedule->jobAdvice;
            if (!$jobAdvice) {
                \Log::warning("⚠️ No Job Advice found for Job {$jobSchedule->job_number}. Cannot return units.");
                return;
            }

            // Determine Warehouse (from Building -> Branch default or first active)
            $building = $jobSchedule->building;
            $warehouse = null;
            if ($building && $building->branch_id) {
                $warehouse = Warehouse::where('branch_id', $building->branch_id)->where('is_active', true)->first();
            }
            if (!$warehouse) {
                $warehouse = Warehouse::where('is_active', true)->first();
            }

            if (!$warehouse) {
                \Log::error("❌ No active warehouse found to return units to.");
                return;
            }

            $rooms = $jobAdvice->rooms;
            $totalReturned = 0;
            $customerId = $jobAdvice->customer_id ?? $jobAdvice->quotation?->customer_id ?? $jobAdvice->contract?->customer_id;

            if (!$customerId && $building) {
                // Try getting customer from building relationship if not in advice
                 // Usually building->customer or building->customers via pivot
                 if ($building->customer_id) {
                     $customerId = $building->customer_id;
                 }
            }

            if (!$customerId) {
                \Log::warning("⚠️ Could not determine Customer ID for Unit Return.");
                return;
            }

            foreach ($rooms as $jaRoom) {
                // Determine target Room ID (Master Room)
                $masterRoomId = $jaRoom->contractRoom?->room_id ?? $jaRoom->quotationRoom?->room_id;
                
                if (!$masterRoomId) {
                    \Log::warning("⚠️ JA Room {$jaRoom->id} has no linked Room ID. Skipping.");
                    continue;
                }

                // Check for specific units (UnitOnWall) installed in this room for this customer
                $unitsOnWall = UnitOnWall::where('customer_id', $customerId)
                    ->where('room_id', $masterRoomId)
                    ->where('status', 'active')
                    ->get();

                foreach ($unitsOnWall as $unit) {
                    // 1. Update Warehouse Product Stock
                    $warehouseProduct = WarehouseProduct::firstOrCreate(
                        ['warehouse_id' => $warehouse->id, 'master_product_id' => $unit->product_id],
                        ['quantity' => 0, 'created_by' => auth()->id()]
                    );
                    
                    $warehouseProduct->increment('quantity', 1);

                    // 2. Create Inventory Movement
                    InventoryMovement::create([
                        'movement_no' => 'MV-' . now()->format('ymdHis') . '-' . mt_rand(1000, 9999),
                        'movement_type' => 'return',
                        'warehouse_id' => $warehouse->id,
                        'master_product_id' => $unit->product_id,
                        'quantity' => 1,
                        'unit_price' => 0,
                        'total_value' => 0,
                        'movement_date' => now(),
                        'reference_no' => $jobSchedule->job_number,
                        'reference_type' => 'job_schedule',
                        'notes' => "Returned from Customer: {$unit->customer_name}, Room: {$unit->room_name} (Serial: {$unit->serial_number}). Ex-installed.",
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]);

                    // 3. Update UnitOnWall Status
                    $unit->update([
                        'status' => 'removed',
                        'notes' => ($unit->notes ? $unit->notes . "\n" : "") . "Removed via Job {$jobSchedule->job_number} returned to {$warehouse->name} on " . now()->format('Y-m-d'),
                        'updated_by' => auth()->id()
                    ]);

                    // 4. Create History
                    UnitOnWallHistory::create([
                        'unit_on_wall_id' => $unit->id,
                        'action' => 'remove',
                        'action_date' => now(),
                        'job_schedule_id' => $jobSchedule->id,
                        'technician_id' => $jobSchedule->assigned_technician_id,
                        'notes' => "Unit removed and returned to warehouse {$warehouse->name}. Stock incremented.",
                        'created_by' => auth()->id()
                    ]);

                    $totalReturned++;
                }
            }

            \Log::info("✅ Successfully returned {$totalReturned} units to warehouse {$warehouse->name} for Job {$jobSchedule->job_number}");

        } catch (\Exception $e) {
            \Log::error("❌ Failed to return units to warehouse: " . $e->getMessage());
        }
    }
