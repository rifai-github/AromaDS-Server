<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\CustomerContact;

class FixCustomerContactLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:customer-contact-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix customer contacts that are assigned to a customer but not linked back via customer_id';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to fix customer contact links...');
        
        $count = 0;
        $customers = Customer::whereNotNull('assigned_to')->get();
        $total = $customers->count();
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($customers as $customer) {
            $contact = CustomerContact::find($customer->assigned_to);
            
            if ($contact && $contact->customer_id !== $customer->id) {
                $contact->update(['customer_id' => $customer->id]);
                $count++;
                // $this->line(" Fixed: {$contact->name} -> {$customer->name}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("Done! Total contacts fixed: {$count}");
        
        return 0;
    }
}
