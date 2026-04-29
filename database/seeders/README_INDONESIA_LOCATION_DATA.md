# Import Data Wilayah Indonesia

Dokumen ini menjelaskan cara mengisi database dengan data lengkap wilayah Indonesia (Provinsi, Kota/Kabupaten, Kecamatan, Kelurahan, dan Kode Pos).

## Struktur Tabel

Database menggunakan struktur hierarki:
- **provinces**: Provinsi (34 provinsi)
- **cities**: Kota/Kabupaten (514 kota/kabupaten)
- **districts**: Kecamatan (~7,000 kecamatan)
- **subdistricts**: Kelurahan/Desa (~80,000 kelurahan/desa) + Kode Pos

## Cara Import Data

### Metode 1: Menggunakan Artisan Command

Kami telah menyediakan Artisan Command untuk import data:

```bash
php artisan location:import-indonesia --source=file --file=path/to/indonesia-location-data.json
```

#### Opsi yang Tersedia:

- `--source`: Sumber data (`file`, `json-url`, `api`)
- `--file`: Path ke file JSON lokal
- `--url`: URL ke file JSON online
- `--api`: API endpoint (default: GitHub raw file)
- `--clear`: Hapus data existing sebelum import
- `--dry-run`: Preview data tanpa import

#### Contoh Penggunaan:

1. **Import dari file lokal:**
   ```bash
   php artisan location:import-indonesia --source=file --file=storage/data/indonesia-location.json
   ```

2. **Import dari URL:**
   ```bash
   php artisan location:import-indonesia --source=json-url --url=https://example.com/indonesia-location.json
   ```

3. **Import dengan clear existing data:**
   ```bash
   php artisan location:import-indonesia --source=file --file=storage/data/indonesia-location.json --clear
   ```

4. **Dry run (preview tanpa import):**
   ```bash
   php artisan location:import-indonesia --source=file --file=storage/data/indonesia-location.json --dry-run
   ```

### Metode 2: Download File JSON dan Import

1. **Download file data wilayah Indonesia:**
   
   Beberapa sumber data yang bisa digunakan:
   - [GitHub: cahyadsn/wilayah](https://github.com/cahyadsn/wilayah)
   - [GitHub: edwardsamuel/Wilayah-Administratif-Indonesia](https://github.com/edwardsamuel/Wilayah-Administratif-Indonesia)
   - [REST Countries API](https://restcountries.com/) (hanya provinsi)
   
   Atau buat file JSON sendiri dengan format:

```json
{
  "provinces": [
    {
      "code": "11",
      "name": "Aceh",
      "country": "Indonesia"
    },
    {
      "code": "12",
      "name": "Sumatera Utara",
      "country": "Indonesia"
    }
  ],
  "cities": [
    {
      "province_code": "11",
      "province_name": "Aceh",
      "name": "Kota Banda Aceh",
      "type": "Kota"
    }
  ],
  "districts": [
    {
      "province_name": "Aceh",
      "city_name": "Kota Banda Aceh",
      "name": "Baiturrahman"
    }
  ],
  "subdistricts": [
    {
      "province_name": "Aceh",
      "city_name": "Kota Banda Aceh",
      "district_name": "Baiturrahman",
      "name": "Peuniti",
      "postal_code": "23116"
    }
  ]
}
```

2. **Simpan file ke storage:**
   ```bash
   mkdir -p storage/data
   # Copy file JSON ke storage/data/indonesia-location.json
   ```

3. **Jalankan import:**
   ```bash
   php artisan location:import-indonesia --source=file --file=storage/data/indonesia-location.json
   ```

### Metode 3: Menggunakan Package Laravel Indonesia Region

Jika ingin menggunakan package Laravel yang sudah jadi:

1. **Install package:**
   ```bash
   composer require laravolt/indonesia
   ```

2. **Publish migration dan seeder:**
   ```bash
   php artisan vendor:publish --provider="Laravolt\Indonesia\ServiceProvider"
   ```

3. **Jalankan migration:**
   ```bash
   php artisan migrate
   ```

4. **Import data:**
   ```bash
   php artisan db:seed --class="Laravolt\Indonesia\Seeds\DatabaseSeeder"
   ```

   **Catatan:** Package ini menggunakan struktur tabel yang berbeda. Anda perlu menyesuaikan atau membuat script migrasi data.

### Metode 4: Import Manual via SQL

Jika Anda memiliki file SQL dengan data lengkap:

1. **Export data dari sumber terpercaya ke format SQL**
2. **Import ke database:**
   ```bash
   mysql -u username -p database_name < indonesia-location-data.sql
   ```
   
   Atau menggunakan phpMyAdmin / MySQL Workbench.

## Format Data yang Dibutuhkan

Command import mengharapkan format JSON dengan struktur berikut:

### Provinsi:
```json
{
  "code": "11",
  "name": "Aceh",
  "country": "Indonesia",
  "description": null
}
```

### Kota/Kabupaten:
```json
{
  "province_code": "11",
  "province_name": "Aceh",
  "name": "Kota Banda Aceh",
  "type": "Kota"
}
```

### Kecamatan:
```json
{
  "province_name": "Aceh",
  "city_name": "Kota Banda Aceh",
  "name": "Baiturrahman"
}
```

### Kelurahan:
```json
{
  "province_name": "Aceh",
  "city_name": "Kota Banda Aceh",
  "district_name": "Baiturrahman",
  "name": "Peuniti",
  "postal_code": "23116"
}
```

## Sumber Data Terpercaya

1. **Badan Pusat Statistik (BPS):**
   - Website: https://www.bps.go.id/
   - Data resmi pemerintah Indonesia

2. **GitHub Repositories:**
   - https://github.com/cahyadsn/wilayah
   - https://github.com/edwardsamuel/Wilayah-Administratif-Indonesia
   - https://github.com/azkadev/wilayah-indonesia

3. **API Publik:**
   - https://dev.farizdotid.com/api/daerahindonesia/
   - https://api.binderbyte.com/wilayah

## Troubleshooting

### Error: "Failed to load data"
- Pastikan file JSON valid dan dapat diakses
- Periksa format JSON sesuai dengan yang diharapkan
- Pastikan koneksi internet jika menggunakan URL

### Error: "Province not found"
- Pastikan data provinsi di-import terlebih dahulu
- Periksa format `province_code` atau `province_name` di data cities/districts/subdistricts

### Data tidak lengkap
- Pastikan semua relasi sudah benar (province -> city -> district -> subdistrict)
- Gunakan `--dry-run` untuk menganalisis data sebelum import
- Periksa log error untuk detail lebih lanjut

## Catatan Penting

1. **Backup Database:** Selalu backup database sebelum melakukan import, terutama jika menggunakan opsi `--clear`

2. **Ukuran Data:** Data lengkap Indonesia bisa sangat besar (80,000+ subdistricts). Proses import mungkin memakan waktu beberapa menit.

3. **Kode Pos:** Tidak semua kelurahan memiliki kode pos. Pastikan data yang digunakan sudah lengkap.

4. **Data Terbaru:** Data wilayah administratif Indonesia bisa berubah seiring waktu. Pastikan menggunakan data terbaru dari BPS.

## Support

Jika mengalami masalah, silakan:
1. Check log error di `storage/logs/laravel.log`
2. Gunakan `--dry-run` untuk debug
3. Hubungi tim development
