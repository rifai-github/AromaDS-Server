<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FileCategory;

class FileCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Documents',
                'slug' => 'documents',
                'description' => 'General documents and files',
                'icon' => 'fas fa-file-alt',
                'color' => '#3b82f6',
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
                'max_file_size' => 10485760, // 10MB
                'is_active' => true,
            ],
            [
                'name' => 'Images',
                'slug' => 'images',
                'description' => 'Image files and photos',
                'icon' => 'fas fa-image',
                'color' => '#10b981',
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
                'max_file_size' => 20971520, // 20MB
                'is_active' => true,
            ],
            [
                'name' => 'Spreadsheets',
                'slug' => 'spreadsheets',
                'description' => 'Excel and CSV files',
                'icon' => 'fas fa-file-excel',
                'color' => '#059669',
                'allowed_extensions' => ['xls', 'xlsx', 'csv'],
                'max_file_size' => 10485760, // 10MB
                'is_active' => true,
            ],
            [
                'name' => 'Presentations',
                'slug' => 'presentations',
                'description' => 'PowerPoint presentations',
                'icon' => 'fas fa-file-powerpoint',
                'color' => '#ea580c',
                'allowed_extensions' => ['ppt', 'pptx'],
                'max_file_size' => 52428800, // 50MB
                'is_active' => true,
            ],
            [
                'name' => 'Archives',
                'slug' => 'archives',
                'description' => 'Compressed files and archives',
                'icon' => 'fas fa-file-archive',
                'color' => '#f59e0b',
                'allowed_extensions' => ['zip', 'rar', '7z', 'tar', 'gz'],
                'max_file_size' => 104857600, // 100MB
                'is_active' => true,
            ],
            [
                'name' => 'Videos',
                'slug' => 'videos',
                'description' => 'Video files',
                'icon' => 'fas fa-video',
                'color' => '#dc2626',
                'allowed_extensions' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
                'max_file_size' => 524288000, // 500MB
                'is_active' => true,
            ],
            [
                'name' => 'Audio',
                'slug' => 'audio',
                'description' => 'Audio files',
                'icon' => 'fas fa-music',
                'color' => '#7c3aed',
                'allowed_extensions' => ['mp3', 'wav', 'flac', 'aac', 'ogg'],
                'max_file_size' => 104857600, // 100MB
                'is_active' => true,
            ],
            [
                'name' => 'Product Photos',
                'slug' => 'product-photos',
                'description' => 'Product images and photos',
                'icon' => 'fas fa-camera',
                'color' => '#ec4899',
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'max_file_size' => 52428800, // 50MB
                'is_active' => true,
            ],
            [
                'name' => 'Contract Documents',
                'slug' => 'contract-documents',
                'description' => 'Contract and legal documents',
                'icon' => 'fas fa-file-contract',
                'color' => '#1f2937',
                'allowed_extensions' => ['pdf', 'doc', 'docx'],
                'max_file_size' => 10485760, // 10MB
                'is_active' => true,
            ],
            [
                'name' => 'Job Reports',
                'slug' => 'job-reports',
                'description' => 'Job completion reports and documentation',
                'icon' => 'fas fa-clipboard-list',
                'color' => '#374151',
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                'max_file_size' => 20971520, // 20MB
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            FileCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('File categories seeded successfully!');
    }
}