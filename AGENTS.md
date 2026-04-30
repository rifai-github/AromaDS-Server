# AGENTS.md

Scope: `server/`.

## Application

Laravel 12 application on PHP 8.2. It contains both:

- a Blade/session-auth ERP web app
- JSON APIs used by `../mobile`

Primary manifests/config:

- `composer.json`: Laravel 12, Sanctum, DomPDF, Excel, Predis, FPDI/FPDF, QR code packages.
- `package.json`: Vite, Tailwind CSS v4, Laravel Vite plugin, Axios, Concurrently, `pdf-lib`.
- `bootstrap/app.php`: Laravel 12 routing, middleware aliases, Sanctum stateful API setup, scheduler.
- `phpunit.xml`: PHPUnit 11 with SQLite in-memory test env; `tests/` is not present in this checkout.
- `docker-compose.yml`: app, nginx, MySQL 8.0, Redis, phpMyAdmin, MailHog.

## Real Folder Structure

Main directories:

- `app/Http/Controllers/`: ERP and API controllers grouped by domain.
- `app/Http/Controllers/Api/Mobile/`: mobile technician API controllers.
- `app/Http/Middleware/`: permission, role, data restriction, logging, frozen account, multi-login, mobile auth, performance middleware.
- `app/Http/Requests/`: form request classes for Company, Finance, Reports.
- `app/Models/`: Eloquent models for ERP domains; `app/Models/Finance/` contains finance-specific models.
- `app/Services/`: domain services and shared services.
- `app/Repositories/`: repositories for Company, Finance, Reports, Other modules plus `BaseRepository`.
- `app/Console/Commands/`: imports, audits, repairs, performance commands, invoice/contract schedule commands.
- `routes/web.php`: web ERP routes and AJAX-style endpoints.
- `routes/api.php`: public APIs, Sanctum APIs, mobile APIs, report APIs, alternate/legacy mobile routes.
- `routes/console.php`: closure Artisan commands, including user utilities and repair commands.
- `resources/views/`: Blade views under `auth`, `company`, `finance`, `marketing`, `operational`, `warehouse`, `reports`, `system`, `settings`, `tax`, and related folders.
- `resources/css/app.css`: Tailwind CSS v4 source.
- `resources/js/app.js`: imports `bootstrap.js`.
- `database/seeders/`: permission, role, company, inventory, finance, tax, marketing, warehouse seeders.
- `database/migrations/`: empty in this checkout.
- `scripts/` and `database/scripts/`: one-off repair/audit/import scripts.

Controller domain folders currently include:

- `Api`: `MobileController`, `RegionController`, `TechnicianController`, `UserPhotoController`, `BaseApiController`.
- `Api/Mobile`: `JobController`, `MaterialVerificationController`, `OperationalController`, `SerialNumberController`.
- `Company`: company, branch, customer, contact, supplier, tax, bank/payment, access management controllers.
- `Finance`: invoice, invoice follow-up/form, billing group, bank receipt/webhook, tax, e-materai, commission, period, cost center, currency controllers.
- `Marketing`: prospect/survey/quotation/contract/job advice/lost unit/pipeline/aroma change/renewal/removal/switching controllers.
- `Operational`: job schedule, team, building/floor/unit/room, material issue assignment, service history, technician location, job report/signature/route controllers.
- `Warehouse`: inventory issuing/receiving/request, product/rental/stock/serial/unit-on-wall/warehouse controllers.
- `Reports`, `System`, `Settings`, `Other`, `Performance`, `Technician`, `Master`.

## Key Server Modules

Mobile API:

- `Api\Mobile\JobController` handles mobile job list/detail, rooms, materials, start work, complete room, photos, signatures, QR/unit scan, scanned unit save, leave, verification, arrived location, serial validation, serial swap.
- `Api\Mobile\MaterialVerificationController` verifies technician warehouse pickup. Its code updates `InventoryIssuing` and `JobSchedule` states around material pickup.
- `Api\Mobile\SerialNumberController` checks serial number existence/status, product metadata, warehouse, and active `UnitOnWall` use before material verification.
- `Api\Mobile\OperationalController` contains older mobile endpoints for jobs, start/complete job, location update, temperature, and job reports.

Operational:

- `Operational\JobScheduleController` is the main web ERP job schedule controller. It includes assignment, material assignment/unassignment, BA date/file handling, force majeure, suspend/DPF, material return, room assignment, team assignment, periodic job endpoints, and report/print paths.
- `app/Services/Operational/JobMaterialCompletionService.php` finalizes materials/serials when jobs are completed and marks install-free item usage.
- `app/Services/Operational/ServiceSchedulingService.php` generates service schedules.

Warehouse:

- `Warehouse\InventoryIssuingController`, `InventoryReceivingController`, `InventoryRequestController`, `SerialNumberController`, `UnitOnWallController`, and related controllers own inventory movement and serial/unit state for the ERP.
- `app/Services/Warehouse/InventoryIssuingService.php` supports issuing workflows.

Finance:

- Finance services include `InvoiceGenerationService`, `BillingGroupService`, `BankReceiptService`, `VirtualAccountImportService`, `VirtualAccountExportService`, `TaxSettingService`, `CommissionCalculationService`, and related payment/period/cost center services.
- Namespaced finance models live in `app/Models/Finance/`, including `Invoice`, `InvoiceDetail`, `BillingGroup`, `VirtualAccount`, `TaxInvoice`, `BankReceipt`, and commission/payment models.

Shared services:

- `AccessControlService`: login/access restrictions.
- `SingleSessionManager`: single-session handling used by auth.
- `DocumentNumberService`: document/job/invoice style numbering support.
- `PhotoUploadService`, `DigitalSignatureService`, `GPSLocationService`, `QRCodeScannerService`, `SmartScentApiService`, `MobileSyncService`.

## API Patterns In This Codebase

Auth:

- Public `POST /api/login` and `POST /api/logout` are defined near the top of `routes/api.php`.
- `AuthController::apiLogin()` accepts `email` and `password`, finds users by email or username, validates active/frozen/access-time checks, creates a Sanctum token named `mobile-app`, and returns `status`, `message`, `token`, and `user`.
- Protected mobile routes are inside `Route::middleware('auth:sanctum')`.

Mobile routes:

- Main mobile routes are grouped as `Route::prefix('v1/mobile')->name('mobile.')` inside the Sanctum group.
- There is also a lower `Route::prefix('v1/mobile')->middleware('auth:sanctum')` block containing alternate routes for arrived-at-location, validate-serial-number, unit scan/save, serial-number check, verify, and leave.
- There is a legacy `v1/mobile` group inside the protected API block. Check for duplicate paths before adding routes.

Response shape:

- Mobile controllers commonly return JSON with `status`, `message`, and `data`.
- Dart repositories read `response.data['data']` for lists and detail objects, so preserve that shape when editing mobile endpoints.

Permissions:

- Middleware aliases are registered in `bootstrap/app.php`: `role`, `permission`, `module.access`, `data.restriction`, login/download/upload/page/report logging, and frozen-account/login-restriction checks.
- `CheckPermission` allows Admin and Management roles before checking `hasAnyPermission()`.

Scheduler:

- `bootstrap/app.php` schedules `job-schedules:update-expected-dates`, `contracts:auto-renew`, and `finance:auto-generate-invoices`.

## Server Side Of Mobile Job Flow

Concrete methods used by mobile:

- `getTodayJobs()` loads authenticated user team IDs, filters `JobSchedule` by active team assignments, excludes `completed`, `done_job`, `selesai`, `suspend`, `dpf`, `undone`, and `meninggalkan_lokasi`, then maps results with `mapJobToArray()`.
- `getJobDetail()` and `getJobRooms()` provide detail and room payloads for `JobDetailPage`.
- `getJobMaterials()` and `confirmMaterials()` support material checklist paths.
- `MaterialVerificationController@getMaterialsForVerification()` and `verifyMaterials()` support warehouse pickup verification and move job status toward `barang_diambil`.
- `arrivedAtLocation()` receives latitude/longitude and updates arrival state.
- `startWork()` updates work-start state.
- `validateSerialNumber()` checks scanned/manual serial numbers against the job.
- `getUnitByQrCode()` and `saveScannedUnit()` support QR/unit capture.
- `completeRoom()` receives room completion data and optional files.
- `verifyJob()` receives notes, signature, PIC name, PIC photo, and `cannot_complete_all_rooms`; helper methods in the controller handle partial completion/follow-up work.
- `leaveLocation()` and `swapSerialNumber()` support leaving and replacement flows.

## Commands

Run from `server/`:

```bash
composer install
npm install
composer run dev
php artisan serve
php artisan queue:listen --tries=1
npm run dev
npm run build
php artisan test
vendor/bin/pint
docker compose up -d
```

`composer run dev` runs Laravel serve, queue listener, Pail, and Vite via Concurrently.

## Server Safety

- `database/migrations/` is empty here. Inspect SQL dumps, models, and live schema context before schema edits.
- Do not run repair/backfill/import commands with write/apply options unless the user explicitly asks.
- Before editing job, room, material, serial number, verification, or partial-completion behavior, inspect `../mobile/lib/features/jobs/data/jobs_repository.dart` and `../mobile/lib/services/sync_service.dart`.
- Do not commit `.env`, `.env.backup`, SQL dumps, `dump.rdb`, storage logs, uploads, or cache contents.
