<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class ImportIndonesiaLocationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'location:import-indonesia 
                            {--source=file : Source of data (file, api, json-url)}
                            {--file= : Path to JSON file containing location data}
                            {--url= : URL to JSON file containing location data}
                            {--api= : API endpoint to fetch data from}
                            {--clear : Clear existing data before importing}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import complete Indonesia location data (Provinces, Cities/Kabupaten, Districts/Kecamatan, Subdistricts/Kelurahan, and Postal Codes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Indonesia Location Data Import...');
        $this->newLine();

        // Determine data source
        $source = $this->option('source');
        $data = null;

        switch ($source) {
            case 'file':
                $data = $this->loadFromFile();
                break;
            case 'json-url':
                $data = $this->loadFromUrl();
                break;
            case 'api':
                $data = $this->loadFromApi();
                break;
            default:
                $this->error('Invalid source. Use: file, json-url, or api');
                return 1;
        }

        if (!$data) {
            $this->error('Failed to load data. Please check your source.');
            return 1;
        }

        // Clear existing data if requested
        if ($this->option('clear')) {
            if (!$this->confirm('⚠️  This will delete ALL existing location data. Are you sure?')) {
                $this->info('Import cancelled.');
                return 0;
            }
            $this->clearExistingData();
        }

        // Dry run check
        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE - No data will be imported');
            $this->analyzeData($data);
            return 0;
        }

        // Start import
        $this->info('📦 Importing data...');
        $this->newLine();

        try {
            DB::beginTransaction();

            $stats = [
                'provinces' => 0,
                'cities' => 0,
                'districts' => 0,
                'subdistricts' => 0,
            ];

            // Import provinces
            if (isset($data['provinces'])) {
                $stats['provinces'] = $this->importProvinces($data['provinces']);
            }

            // Import cities
            if (isset($data['cities'])) {
                $stats['cities'] = $this->importCities($data['cities']);
            }

            // Import districts
            if (isset($data['districts'])) {
                $stats['districts'] = $this->importDistricts($data['districts']);
            }

            // Import subdistricts
            if (isset($data['subdistricts'])) {
                $stats['subdistricts'] = $this->importSubdistricts($data['subdistricts']);
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ Import completed successfully!');
            $this->newLine();
            $this->table(
                ['Type', 'Imported'],
                [
                    ['Provinces', $stats['provinces']],
                    ['Cities/Kabupaten', $stats['cities']],
                    ['Districts/Kecamatan', $stats['districts']],
                    ['Subdistricts/Kelurahan', $stats['subdistricts']],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Import failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Load data from JSON file
     */
    private function loadFromFile()
    {
        $filePath = $this->option('file');
        
        if (!$filePath) {
            $this->error('Please provide --file option with path to JSON file');
            return null;
        }

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return null;
        }

        $this->info("📂 Loading data from file: {$filePath}");
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Load data from URL
     */
    private function loadFromUrl()
    {
        $url = $this->option('url');
        
        if (!$url) {
            $this->error('Please provide --url option with JSON URL');
            return null;
        }

        $this->info("🌐 Loading data from URL: {$url}");
        
        try {
            $response = Http::timeout(60)->get($url);
            
            if (!$response->successful()) {
                $this->error('Failed to fetch data from URL. Status: ' . $response->status());
                return null;
            }

            $data = $response->json();
            return $data;
        } catch (\Exception $e) {
            $this->error('Error fetching data from URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Load data from API
     */
    private function loadFromApi()
    {
        $apiUrl = $this->option('api');
        
        if (!$apiUrl) {
            // Use default API if available
            $apiUrl = 'https://raw.githubusercontent.com/cahyadsn/wilayah/master/data/indonesia.json';
        }

        return $this->loadFromUrl(); // Reuse URL loading
    }

    /**
     * Import provinces
     */
    private function importProvinces(array $provinces)
    {
        $this->info('📍 Importing provinces...');
        $bar = $this->output->createProgressBar(count($provinces));
        $bar->start();

        $imported = 0;
        foreach ($provinces as $provinceData) {
            $province = Province::firstOrCreate(
                ['code' => $provinceData['code'] ?? $provinceData['id'] ?? null],
                [
                    'name' => $provinceData['name'],
                    'code' => $provinceData['code'] ?? $provinceData['id'] ?? null,
                    'country' => $provinceData['country'] ?? 'Indonesia',
                    'description' => $provinceData['description'] ?? null,
                ]
            );

            if ($province->wasRecentlyCreated) {
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        return $imported;
    }

    /**
     * Import cities
     */
    private function importCities(array $cities)
    {
        $this->info('🏙️  Importing cities/kabupaten...');
        $bar = $this->output->createProgressBar(count($cities));
        $bar->start();

        $imported = 0;
        foreach ($cities as $cityData) {
            // Find province by code or name
            $province = Province::where('code', $cityData['province_code'] ?? $cityData['province_id'] ?? null)
                ->orWhere('name', $cityData['province_name'] ?? null)
                ->first();

            if (!$province) {
                $bar->advance();
                continue;
            }

            $city = City::firstOrCreate(
                [
                    'province_id' => $province->id,
                    'name' => $cityData['name'],
                ],
                [
                    'type' => $cityData['type'] ?? ($cityData['name'] ? (strpos(strtolower($cityData['name']), 'kota') !== false ? 'Kota' : 'Kabupaten') : 'Kota'),
                ]
            );

            if ($city->wasRecentlyCreated) {
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        return $imported;
    }

    /**
     * Import districts
     */
    private function importDistricts(array $districts)
    {
        $this->info('🏘️  Importing districts/kecamatan...');
        $bar = $this->output->createProgressBar(count($districts));
        $bar->start();

        $imported = 0;
        foreach ($districts as $districtData) {
            // Find city by code or name with province
            $city = City::where('name', $districtData['city_name'] ?? null)
                ->when(isset($districtData['province_name']), function ($query) use ($districtData) {
                    $query->whereHas('province', function ($q) use ($districtData) {
                        $q->where('name', $districtData['province_name']);
                    });
                })
                ->first();

            if (!$city) {
                $bar->advance();
                continue;
            }

            $district = District::firstOrCreate(
                [
                    'city_id' => $city->id,
                    'name' => $districtData['name'],
                ]
            );

            if ($district->wasRecentlyCreated) {
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        return $imported;
    }

    /**
     * Import subdistricts
     */
    private function importSubdistricts(array $subdistricts)
    {
        $this->info('🏠 Importing subdistricts/kelurahan...');
        $bar = $this->output->createProgressBar(count($subdistricts));
        $bar->start();

        $imported = 0;
        foreach ($subdistricts as $subdistrictData) {
            // Find district by name with city and province
            $district = District::where('name', $subdistrictData['district_name'] ?? null)
                ->when(isset($subdistrictData['city_name']), function ($query) use ($subdistrictData) {
                    $query->whereHas('city', function ($q) use ($subdistrictData) {
                        $q->where('name', $subdistrictData['city_name']);
                        
                        if (isset($subdistrictData['province_name'])) {
                            $q->whereHas('province', function ($p) use ($subdistrictData) {
                                $p->where('name', $subdistrictData['province_name']);
                            });
                        }
                    });
                })
                ->first();

            if (!$district) {
                $bar->advance();
                continue;
            }

            $subdistrict = Subdistrict::firstOrCreate(
                [
                    'district_id' => $district->id,
                    'name' => $subdistrictData['name'],
                ],
                [
                    'postal_code' => $subdistrictData['postal_code'] ?? $subdistrictData['postcode'] ?? null,
                ]
            );

            if ($subdistrict->wasRecentlyCreated) {
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        return $imported;
    }

    /**
     * Clear existing data
     */
    private function clearExistingData()
    {
        $this->info('🗑️  Clearing existing data...');
        
        Subdistrict::query()->delete();
        District::query()->delete();
        City::query()->delete();
        Province::query()->delete();
        
        $this->info('✅ Existing data cleared.');
    }

    /**
     * Analyze data structure
     */
    private function analyzeData(array $data)
    {
        $this->newLine();
        $this->info('📊 Data Analysis:');
        $this->newLine();
        
        $analysis = [];
        
        if (isset($data['provinces'])) {
            $analysis[] = ['Provinces', count($data['provinces'])];
        }
        
        if (isset($data['cities'])) {
            $analysis[] = ['Cities/Kabupaten', count($data['cities'])];
        }
        
        if (isset($data['districts'])) {
            $analysis[] = ['Districts/Kecamatan', count($data['districts'])];
        }
        
        if (isset($data['subdistricts'])) {
            $analysis[] = ['Subdistricts/Kelurahan', count($data['subdistricts'])];
        }
        
        if (!empty($analysis)) {
            $this->table(['Type', 'Count'], $analysis);
        } else {
            $this->warn('⚠️  Unknown data structure. Please check your JSON format.');
            $this->info('Expected format: { "provinces": [...], "cities": [...], "districts": [...], "subdistricts": [...] }');
        }
    }
}