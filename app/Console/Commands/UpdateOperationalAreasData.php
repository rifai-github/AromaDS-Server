<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperationalArea;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;

class UpdateOperationalAreasData extends Command
{
    protected $signature = 'operational-areas:update-data';
    protected $description = 'Update operational areas data to include district_id and subdistrict_id';

    public function handle()
    {
        $this->info('Updating operational areas data...');
        
        $areas = OperationalArea::all();
        $this->info('Found ' . $areas->count() . ' operational areas');
        
        foreach ($areas as $area) {
            $this->info("Processing area: {$area->name}");
            
            // Find province by name
            $province = Province::where('name', $area->province)->first();
            if ($province) {
                $area->province_id = $province->id;
                $this->info("  - Found province: {$province->name} (ID: {$province->id})");
            }
            
            // Find city by name
            $city = City::where('name', $area->city)->first();
            if ($city) {
                $area->city_id = $city->id;
                $this->info("  - Found city: {$city->name} (ID: {$city->id})");
            }
            
            // Find district by name (if district field exists)
            if ($area->district) {
                $district = District::where('name', $area->district)->first();
                if ($district) {
                    $area->district_id = $district->id;
                    $this->info("  - Found district: {$district->name} (ID: {$district->id})");
                }
            } else {
                // If no district name, try to find first district for the city
                $district = District::where('city_id', $area->city_id)->first();
                if ($district) {
                    $area->district_id = $district->id;
                    $area->district = $district->name;
                    $this->info("  - Assigned first district: {$district->name} (ID: {$district->id})");
                }
            }
            
            // Find subdistrict by name (if subdistrict field exists)
            if ($area->subdistrict) {
                $subdistrict = Subdistrict::where('name', $area->subdistrict)->first();
                if ($subdistrict) {
                    $area->subdistrict_id = $subdistrict->id;
                    $this->info("  - Found subdistrict: {$subdistrict->name} (ID: {$subdistrict->id})");
                }
            } else {
                // If no subdistrict name, try to find first subdistrict for the district
                if ($area->district_id) {
                    $subdistrict = Subdistrict::where('district_id', $area->district_id)->first();
                    if ($subdistrict) {
                        $area->subdistrict_id = $subdistrict->id;
                        $area->subdistrict = $subdistrict->name;
                        $this->info("  - Assigned first subdistrict: {$subdistrict->name} (ID: {$subdistrict->id})");
                    }
                }
            }
            
            $area->save();
            $this->info("  - Updated area: {$area->name}");
        }
        
        $this->info('Update completed!');
    }
}