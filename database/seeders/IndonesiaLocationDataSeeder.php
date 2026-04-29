<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class IndonesiaLocationDataSeeder extends Seeder
{
    /**
     * Mapping dari old ID ke new ID
     */
    protected array $provinceMap = [];
    protected array $cityMap = [];
    protected array $districtMap = [];
    protected array $subdistrictMap = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Indonesia Location Data Import from SQL files...');
        $this->command->newLine();

        // Path to SQL files
        $sqlPath = base_path('SQL/PostgreeSql');

        try {
            DB::beginTransaction();

            // Step 1: Import Provinces
            $this->command->info('📍 Step 1: Importing Provinces...');
            $this->importProvinces($sqlPath . '/province.sql');

            // Step 2: Import Cities
            $this->command->info('🏙️  Step 2: Importing Cities/Kabupaten...');
            $this->importCities($sqlPath . '/city.sql');

            // Step 3: Import Districts
            $this->command->info('🏘️  Step 3: Importing Districts/Kecamatan...');
            $this->importDistricts($sqlPath . '/district.sql');

            // Step 4: Import Subdistricts
            $this->command->info('🏠 Step 4: Importing Subdistricts/Kelurahan...');
            $this->importSubdistricts($sqlPath . '/subdistrict.sql');

            // Step 5: Update Postal Codes
            $this->command->info('📮 Step 5: Updating Postal Codes...');
            $this->updatePostalCodes($sqlPath . '/postal_code.sql');

            DB::commit();

            $this->command->newLine();
            $this->command->info('✅ All data imported successfully!');
            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error importing data: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Import provinces from SQL file
     */
    private function importProvinces(string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $content = File::get($filePath);
        
        // Parse line by line for more reliable parsing
        $lines = explode("\n", $content);
        $matches = [];
        $inValuesSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check if we're in VALUES section
            if (stripos($line, 'VALUES') !== false) {
                $inValuesSection = true;
                continue;
            }
            
            // Skip empty lines and CREATE/INSERT statements (before VALUES)
            if (!$inValuesSection) {
                continue;
            }
            
            // Stop at semicolon (end of INSERT statement)
            if (strpos($line, ';') !== false) {
                // Process last line before semicolon
                $line = rtrim($line, ';');
            }
            
            // Only process lines that contain parentheses with data
            if (strpos($line, '(') !== false && strpos($line, ')') !== false) {
                // Match pattern: ('id', 'name') or ('id', 'name'),
                // Be careful with regex - match complete parenthetical groups
                if (preg_match("/\(\s*'(\d+)'\s*,\s*'([^']+)'\s*\)/", $line, $match)) {
                    if (count($match) >= 3) {
                        $matches[] = $match;
                    }
                }
            }
            
            // Stop processing after semicolon
            if (strpos($line, ';') !== false) {
                break;
            }
        }
        
        $total = count($matches);
        $imported = 0;
        $provinceMap = []; // Map old prov_id to new province id
        $processed = 0;

        foreach ($matches as $match) {
            // match[1] = ID, match[2] = Name
            if (count($match) >= 3) {
                $oldId = (int)$match[1];
                $name = trim($match[2], "'\"");
                
                // Skip invalid names (contains only brackets, commas, or whitespace)
                if (empty($name) || preg_match('/^[),(\s]+$/', $name) || strlen($name) < 3) {
                    $this->command->warn("  ⚠️  Skipping invalid province name: [{$name}]");
                    $processed++;
                    continue;
                }
                
                // Determine code from name or use old_id as code
                $code = $this->generateProvinceCode($name, $oldId);
                
                $province = Province::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'country' => 'Indonesia',
                        'description' => null,
                    ]
                );

                $provinceMap[$oldId] = $province->id;
                
                if ($province->wasRecentlyCreated) {
                    $imported++;
                }
            }
            
            $processed++;
            // Show progress every 10 items
            if ($processed % 10 == 0 || $processed == $total) {
                $this->command->info("  Processing: {$processed}/{$total}...");
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Imported {$imported} provinces. Total provinces: " . count($provinceMap));
        
        // Store map in memory for later use
        $this->provinceMap = $provinceMap;
    }

    /**
     * Import cities from SQL file
     */
    private function importCities(string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $content = File::get($filePath);
        
        // Extract all VALUES rows: ('1', 'PIDIE JAYA', '1'), ...
        // More flexible pattern: ('id', 'name', 'prov_id')
        preg_match_all('/\(\s*\'(\d+)\'\s*,\s*\'([^\']+)\'\s*,\s*\'(\d+)\'\s*\)/i', $content, $matches, PREG_SET_ORDER);
        
        // Create progress bar using Symfony OutputInterface
        $progressBar = null;
        if ($this->command && method_exists($this->command, 'getOutput')) {
            try {
                $progressBar = $this->command->getOutput()->createProgressBar(count($matches));
                $progressBar->start();
            } catch (\Exception $e) {
                // Fallback if progress bar not available
                $progressBar = null;
            }
        }

        $imported = 0;
        $cityMap = []; // Map old city_id to new city id

        foreach ($matches as $match) {
            // match[1] = city_id, match[2] = city_name, match[3] = prov_id
            if (count($match) >= 4) {
                $oldId = (int)$match[1];
                $name = trim($match[2], "'\"");
                $oldProvId = (int)$match[3];
                
                // Get province ID from map
                if (!isset($this->provinceMap[$oldProvId])) {
                    if ($progressBar) {
                $progressBar->advance();
            }
                    continue;
                }

                $provinceId = $this->provinceMap[$oldProvId];
                
                // Determine type (Kota or Kabupaten)
                $type = $this->determineCityType($name);
                
                $city = City::firstOrCreate(
                    [
                        'province_id' => $provinceId,
                        'name' => $name,
                    ],
                    ['type' => $type]
                );

                $cityMap[$oldId] = $city->id;
                
                if ($city->wasRecentlyCreated) {
                    $imported++;
                }
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
        }
        $this->command->newLine();
        $this->command->info("✅ Imported {$imported} cities. Total cities: " . count($cityMap));
        
        // Store map in memory for later use
        $this->cityMap = $cityMap;
    }

    /**
     * Import districts from SQL file
     */
    private function importDistricts(string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $content = File::get($filePath);
        
        // Extract all VALUES rows: ('1', 'BANDAR BARU', '1'), ...
        // More flexible pattern: ('id', 'name', 'city_id')
        preg_match_all('/\(\s*\'(\d+)\'\s*,\s*\'([^\']+)\'\s*,\s*\'(\d+)\'\s*\)/i', $content, $matches, PREG_SET_ORDER);
        
        // Create progress bar using Symfony OutputInterface
        $progressBar = null;
        if ($this->command && method_exists($this->command, 'getOutput')) {
            try {
                $progressBar = $this->command->getOutput()->createProgressBar(count($matches));
                $progressBar->start();
            } catch (\Exception $e) {
                // Fallback if progress bar not available
                $progressBar = null;
            }
        }

        $imported = 0;
        $districtMap = []; // Map old dis_id to new district id

        foreach ($matches as $match) {
            // match[1] = dis_id, match[2] = dis_name, match[3] = city_id
            if (count($match) >= 4) {
                $oldId = (int)$match[1];
                $name = trim($match[2], "'\"");
                $oldCityId = (int)$match[3];
                
                // Get city ID from map
                if (!isset($this->cityMap[$oldCityId])) {
                    if ($progressBar) {
                $progressBar->advance();
            }
                    continue;
                }

                $cityId = $this->cityMap[$oldCityId];
                
                $district = District::firstOrCreate(
                    [
                        'city_id' => $cityId,
                        'name' => $name,
                    ]
                );

                $districtMap[$oldId] = $district->id;
                
                if ($district->wasRecentlyCreated) {
                    $imported++;
                }
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
        }
        $this->command->newLine();
        $this->command->info("✅ Imported {$imported} districts. Total districts: " . count($districtMap));
        
        // Store map in memory for later use
        $this->districtMap = $districtMap;
    }

    /**
     * Import subdistricts from SQL file
     */
    private function importSubdistricts(string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $content = File::get($filePath);
        
        // Extract all VALUES rows: ('1', 'ABAH LUENG', '1'), ...
        // More flexible pattern: ('id', 'name', 'dis_id')
        preg_match_all('/\(\s*\'(\d+)\'\s*,\s*\'([^\']+)\'\s*,\s*\'(\d+)\'\s*\)/i', $content, $matches, PREG_SET_ORDER);
        
        // Create progress bar using Symfony OutputInterface
        $progressBar = null;
        if ($this->command && method_exists($this->command, 'getOutput')) {
            try {
                $progressBar = $this->command->getOutput()->createProgressBar(count($matches));
                $progressBar->start();
            } catch (\Exception $e) {
                // Fallback if progress bar not available
                $progressBar = null;
            }
        }

        $imported = 0;

        foreach ($matches as $match) {
            // match[1] = subdis_id, match[2] = subdis_name, match[3] = dis_id
            if (count($match) >= 4) {
                $oldId = (int)$match[1];
                $name = trim($match[2], "'\"");
                $oldDisId = (int)$match[3];
                
                // Get district ID from map
                if (!isset($this->districtMap[$oldDisId])) {
                    if ($progressBar) {
                $progressBar->advance();
            }
                    continue;
                }

                $districtId = $this->districtMap[$oldDisId];
                
                $subdistrict = Subdistrict::firstOrCreate(
                    [
                        'district_id' => $districtId,
                        'name' => $name,
                    ],
                    ['postal_code' => null] // Will be updated later
                );
                
                // Store mapping for postal code update
                $this->subdistrictMap[$oldId] = $subdistrict->id;
                
                if ($subdistrict->wasRecentlyCreated) {
                    $imported++;
                }
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
        }
        $this->command->newLine();
        $this->command->info("✅ Imported {$imported} subdistricts. Total subdistricts: " . count($this->subdistrictMap ?? []));
    }

    /**
     * Update postal codes from SQL file
     */
    private function updatePostalCodes(string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $content = File::get($filePath);
        
        // Extract all VALUES rows: ('1', '1', '1', '1', '1', '24184'), ...
        // Format: (postal_id, subdis_id, dis_id, city_id, prov_id, postal_code)
        preg_match_all('/\(\s*\'(\d+)\'\s*,\s*\'(\d+)\'\s*,\s*\'(\d+)\'\s*,\s*\'(\d+)\'\s*,\s*\'(\d+)\'\s*,\s*\'(\d+)\'\s*\)/i', $content, $matches, PREG_SET_ORDER);
        
        // Create progress bar using Symfony OutputInterface
        $progressBar = null;
        if ($this->command && method_exists($this->command, 'getOutput')) {
            try {
                $progressBar = $this->command->getOutput()->createProgressBar(count($matches));
                $progressBar->start();
            } catch (\Exception $e) {
                // Fallback if progress bar not available
                $progressBar = null;
            }
        }

        $updated = 0;

        foreach ($matches as $match) {
            // match[1] = postal_id, match[2] = subdis_id, match[3] = dis_id, match[4] = city_id, match[5] = prov_id, match[6] = postal_code
            if (count($match) >= 7) {
                $oldSubdisId = (int)$match[2]; // subdis_id is second column
                $postalCode = (int)$match[6]; // postal_code is last column
                
                // Get subdistrict ID from map
                if (!isset($this->subdistrictMap[$oldSubdisId])) {
                    if ($progressBar) {
                $progressBar->advance();
            }
                    continue;
                }

                $subdistrictId = $this->subdistrictMap[$oldSubdisId];
                
                // Update postal code
                Subdistrict::where('id', $subdistrictId)
                    ->update(['postal_code' => (string)$postalCode]);
                
                $updated++;
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
        }
        $this->command->newLine();
        $this->command->info("✅ Updated postal codes for {$updated} subdistricts.");
    }

    /**
     * Parse SQL VALUES string into array
     */
    private function parseValues(string $row): array
    {
        // Remove leading/trailing whitespace and parentheses
        $row = trim($row, " \t\n\r\0\x0B()");
        
        // Split by comma, but handle quoted strings
        $values = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        
        for ($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];
            
            if (!$inQuotes && ($char === "'" || $char === '"')) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                // Check if it's escaped quote
                if ($i + 1 < strlen($row) && $row[$i + 1] === $quoteChar) {
                    $current .= $char . $row[$i + 1];
                    $i++;
                } else {
                    $inQuotes = false;
                    $quoteChar = null;
                    $current .= $char;
                }
            } elseif (!$inQuotes && $char === ',') {
                $values[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $values[] = trim($current);
        }
        
        return $values;
    }

    /**
     * Generate province code from name or old ID
     */
    private function generateProvinceCode(string $name, int $oldId): string
    {
        // Try to map common province names to their official codes
        $codeMap = [
            'ACEH' => '11',
            'SUMATERA UTARA' => '12',
            'SUMATERA BARAT' => '13',
            'RIAU' => '14',
            'JAMBI' => '15',
            'SUMATERA SELATAN' => '16',
            'BENGKULU' => '17',
            'LAMPUNG' => '18',
            'KEPULAUAN BANGKA BELITUNG' => '19',
            'KEPULAUAN RIAU' => '21',
            'DAERAH KHUSUS IBUKOTA JAKARTA' => '31',
            'JAWA BARAT' => '32',
            'JAWA TENGAH' => '33',
            'DAERAH ISTIMEWA YOGYAKARTA' => '34',
            'JAWA TIMUR' => '35',
            'BANTEN' => '36',
            'BALI' => '51',
            'NUSA TENGGARA BARAT' => '52',
            'NUSA TENGGARA TIMUR' => '53',
            'KALIMANTAN BARAT' => '61',
            'KALIMANTAN TENGAH' => '62',
            'KALIMANTAN SELATAN' => '63',
            'KALIMANTAN TIMUR' => '64',
            'KALIMANTAN UTARA' => '65',
            'SULAWESI UTARA' => '71',
            'SULAWESI TENGAH' => '72',
            'SULAWESI SELATAN' => '73',
            'SULAWESI TENGGARA' => '74',
            'GORONTALO' => '75',
            'SULAWESI BARAT' => '76',
            'MALUKU' => '81',
            'MALUKU UTARA' => '82',
            'PAPUA' => '91',
            'PAPUA BARAT' => '92',
        ];
        
        $upperName = strtoupper($name);
        if (isset($codeMap[$upperName])) {
            return $codeMap[$upperName];
        }
        
        // Fallback: use old ID padded to 2 digits
        return str_pad((string)$oldId, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Determine city type (Kota or Kabupaten)
     */
    private function determineCityType(string $name): string
    {
        $upperName = strtoupper($name);
        
        // Check if name starts with "KOTA"
        if (strpos($upperName, 'KOTA ') === 0 || strpos($upperName, 'KABUPATEN ') === 0) {
            return strpos($upperName, 'KOTA ') === 0 ? 'Kota' : 'Kabupaten';
        }
        
        // Check if it's a known city (without prefix)
        // Common cities in Indonesia that don't have prefix
        $knownCities = [
            'BANDA ACEH', 'SABANG', 'LANGSA', 'LHOKSEUMAWE', 'SUBULUSSALAM',
            'MEDAN', 'BINJAI', 'PEMATANG SIANTAR', 'TANJUNG BALAI', 'TEBING TINGGI',
            // Add more as needed
        ];
        
        if (in_array($upperName, $knownCities)) {
            return 'Kota';
        }
        
        // Default to Kabupaten
        return 'Kabupaten';
    }
}