<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and branches
        $users = User::all();
        $branches = Branch::all();

        if ($users->isEmpty() || $branches->isEmpty()) {
            $this->command->error('No users or branches found. Please run other seeders first.');
            return;
        }

        // Assign users to branches
        foreach ($users as $user) {
            // Skip if user already has a branch assigned
            if ($user->branch_id) {
                continue;
            }

            // Assign random branch
            $randomBranch = $branches->random();
            $user->update(['branch_id' => $randomBranch->id]);
            
            $this->command->info("Assigned user {$user->name} to branch {$randomBranch->branch_name}");
        }

        $this->command->info('Branch assignments completed successfully!');
    }
}
