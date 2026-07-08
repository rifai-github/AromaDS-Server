<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Company\AccessManagementController;
use App\Http\Controllers\Company\BankPaymentController;
use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\CompanyVirtualAccountController;
use App\Http\Controllers\Company\CustomerContactController;
use App\Http\Controllers\Company\CustomerController;
use App\Http\Controllers\Company\CustomerImportController;
use App\Http\Controllers\Company\CustomerTaxController;
use App\Http\Controllers\Company\MasterPriceSlabController;
use App\Http\Controllers\Company\SupplierController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Finance\BankReceiptController;
use App\Http\Controllers\Finance\BillingGroupController;
use App\Http\Controllers\Finance\CommissionWithdrawalController;
use App\Http\Controllers\Finance\CostCenterController;
use App\Http\Controllers\Finance\CurrencyController;
use App\Http\Controllers\Finance\EMateraiTransactionController;
use App\Http\Controllers\Finance\FakturPajakController;
use App\Http\Controllers\Finance\FinanceLogController;
use App\Http\Controllers\Finance\FinancialPeriodController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\InvoiceFollowUpController;
use App\Http\Controllers\Finance\InvoiceFormController;
use App\Http\Controllers\Finance\PaymentMethodController;
use App\Http\Controllers\Finance\SalesCommissionController;
use App\Http\Controllers\Finance\TaxCodeController;
use App\Http\Controllers\Finance\TaxFileExportController;
use App\Http\Controllers\Finance\TaxFileImportController;
use App\Http\Controllers\Finance\TaxInvoiceController;
use App\Http\Controllers\Finance\TaxReportController;
use App\Http\Controllers\Finance\TaxSettingController;
use App\Http\Controllers\Finance\VirtualAccountExportController;
use App\Http\Controllers\Finance\VirtualAccountImportController;
use App\Http\Controllers\Marketing\AromaChangeController;
use App\Http\Controllers\Marketing\ComplaintController;
use App\Http\Controllers\Marketing\ContractAssignedController;
use App\Http\Controllers\Marketing\ContractController;
use App\Http\Controllers\Marketing\ContractRemovalController;
use App\Http\Controllers\Marketing\ContractRenewalController;
use App\Http\Controllers\Marketing\ContractSwitchingController;
use App\Http\Controllers\Marketing\ContractTerminationController;
use App\Http\Controllers\Marketing\ContractWizardController;
use App\Http\Controllers\Marketing\ExtraServiceController;
use App\Http\Controllers\Marketing\JobAdviceController;
use App\Http\Controllers\Marketing\LostUnitReportController;
use App\Http\Controllers\Marketing\MarketingPipelineController;
use App\Http\Controllers\Marketing\MasterCorporateController;
use App\Http\Controllers\Marketing\ProspectController;
use App\Http\Controllers\Marketing\QuotationController;
use App\Http\Controllers\Marketing\QuotationWizardController;
use App\Http\Controllers\Marketing\RentalChangeController;
use App\Http\Controllers\Marketing\SalesActivityController;
use App\Http\Controllers\Marketing\SurveyController;
use App\Http\Controllers\Marketing\SurveyWizardController;
use App\Http\Controllers\Operational\BuildingController;
use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Http\Controllers\Operational\JobAssignScheduleController;
use App\Http\Controllers\Operational\JobReportController;
use App\Http\Controllers\Operational\JobRouteController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Http\Controllers\Operational\JobSignatureController;
use App\Http\Controllers\Operational\MasterRoomController;
use App\Http\Controllers\Operational\RoomRentalUnitController;
use App\Http\Controllers\Operational\ServiceHistoryController;
use App\Http\Controllers\Operational\TeamController;
use App\Http\Controllers\Operational\TechnicianLocationController;
use App\Http\Controllers\Operational\TemperatureRecordController;
use App\Http\Controllers\Operational\UnitInstallationController;
use App\Http\Controllers\Other\HakAksesController;
use App\Http\Controllers\Other\MasterOptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\AnalyticsController;
use App\Http\Controllers\Reports\DashboardController as ReportsDashboardController;
use App\Http\Controllers\Reports\DataExportController;
use App\Http\Controllers\Reports\FinancialReportController;
use App\Http\Controllers\Reports\KpiController;
use App\Http\Controllers\Reports\MarketingReportController;
use App\Http\Controllers\Reports\OperationalReportController;
use App\Http\Controllers\Reports\ReportTemplateController;
use App\Http\Controllers\Reports\WarehouseReportController;
use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\ThemeController;
use App\Http\Controllers\Settings\ThemeSettingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\System\ApiTokenController;
use App\Http\Controllers\System\AuditLogController;
use App\Http\Controllers\System\BackupLogController;
use App\Http\Controllers\System\BackupRestoreController;
use App\Http\Controllers\System\CatalystImportController;
use App\Http\Controllers\System\DepartmentController;
use App\Http\Controllers\System\MaintenanceLogController;
use App\Http\Controllers\System\NotificationController;
use App\Http\Controllers\System\PasswordHistoryController;
use App\Http\Controllers\System\ProvinceController;
use App\Http\Controllers\System\RoleController;
use App\Http\Controllers\System\SystemHealthController;
use App\Http\Controllers\System\SystemLogController;
use App\Http\Controllers\System\UserController;
use App\Http\Controllers\System\UserSessionController;
use App\Http\Controllers\System\WorkingHoursController;
use App\Http\Controllers\Warehouse\InventoryController;
use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Http\Controllers\Warehouse\InventoryRequestController;
use App\Http\Controllers\Warehouse\InventoryRequestImportController;
use App\Http\Controllers\Warehouse\MasterProductController;
use App\Http\Controllers\Warehouse\MasterRentalController;
use App\Http\Controllers\Warehouse\MasterRentalImportController;
use App\Http\Controllers\Warehouse\ProductTypeController;
use App\Http\Controllers\Warehouse\SerialNumberController;
use App\Http\Controllers\Warehouse\SerialNumberImportController;
use App\Http\Controllers\Warehouse\StockAdjustmentController;
use App\Http\Controllers\Warehouse\StockOpnameController;
use App\Http\Controllers\Warehouse\UnitOnWallController;
use App\Http\Controllers\Warehouse\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');
Route::get('/csrf-token', function () {
    $token = csrf_token();

    return response()
        ->json(['token' => $token])
        ->header('X-CSRF-TOKEN', $token);
})->name('csrf-token');

// Public API Routes (no authentication required)
Route::get('/email/verify/{token}', [App\Http\Controllers\Company\CustomerContactController::class, 'verifyEmail'])->name('email.verify');
Route::get('/api/contracts/dropdown', [ContractController::class, 'getForDropdown'])->name('api.contracts.dropdown');
Route::get('/api/contracts/on-wall-units-for-job-advice', [ContractController::class, 'getOnWallUnitsForJobAdvice'])->name('api.contracts.on-wall-units-for-job-advice');
Route::get('/api/quotations/dropdown', [App\Http\Controllers\Marketing\QuotationController::class, 'getForDropdown'])->name('api.quotations.dropdown');
Route::get('/api/contracts/{id}/for-job-advice', [ContractController::class, 'getForJobAdvice'])->name('api.contracts.for-job-advice');
Route::get('/api/quotations/{id}/for-job-advice', [App\Http\Controllers\Marketing\QuotationController::class, 'getForJobAdvice'])->name('api.quotations.for-job-advice');
Route::get('/api/buildings', [App\Http\Controllers\Operational\BuildingController::class, 'index'])->name('api.buildings.public');
Route::get('/api/master-rentals', [App\Http\Controllers\Warehouse\MasterRentalController::class, 'index'])->name('api.master-rentals.public');
Route::get('/warehouse/rental-products/dropdown', function () {
    try {
        $rentals = \App\Models\MasterRental::select('id', 'rental_name', 'rental_code', 'monthly_price', 'unit')
            ->where('is_active', true)
            ->orderBy('rental_name')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rentals]);
    } catch (\Exception $e) {
        \Log::error('Rental products dropdown error: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'data' => []], 500);
    }
})->name('warehouse.rental-products.dropdown');

// API Routes for dynamic loading
Route::get('/api/buildings/{buildingId}/rooms', [App\Http\Controllers\Operational\BuildingController::class, 'getRooms'])->name('api.buildings.rooms');
Route::get('/api/customers/{customerId}/buildings', [App\Http\Controllers\Company\CustomerController::class, 'getBuildings'])->name('web.api.customers.buildings');

// Modal data routes (no auth required for AJAX calls)
Route::get('/warehouse/inventory-receivings/modal-data', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'getModalData'])->name('inventory-receivings.modal-data-public');

// Alternative route for modal data (no auth required)
Route::get('/api/warehouse/inventory-receivings/modal-data', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'getModalData'])->name('api.inventory-receivings.modal-data');
Route::get('/api/warehouse/inventory-receivings/{id}', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'show'])->name('api.inventory-receivings.show');
Route::get('/api/warehouse/inventory-receivings/{id}/edit', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'edit'])->name('api.inventory-receivings.edit');
Route::post('/api/warehouse/inventory-receivings', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'store'])->name('api.inventory-receivings.store');
Route::put('/api/warehouse/inventory-receivings/{id}', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'update'])->name('api.inventory-receivings.update');
Route::post('/api/warehouse/inventory-receivings/bulk-delete', [\App\Http\Controllers\Warehouse\InventoryReceivingController::class, 'bulkDelete'])->name('api.inventory-receivings.bulk-delete');

Route::get('/api/warehouse/inventory-issuings/modal-data', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'getModalData'])->name('api.inventory-issuings.modal-data');
Route::get('/api/warehouse/inventory-issuings/{id}', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'show'])->name('api.inventory-issuings.show');
Route::get('/api/warehouse/inventory-issuings/{id}/edit', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'edit'])->name('api.inventory-issuings.edit');
Route::get('/api/warehouse/inventory-issuings/{id}/details', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'getIssuingDetails'])->name('api.inventory-issuings.details');
Route::post('/api/warehouse/inventory-issuings', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'store'])->name('api.inventory-issuings.store');
Route::put('/api/warehouse/inventory-issuings/{id}', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'update'])->name('api.inventory-issuings.update');
Route::post('/api/warehouse/inventory-issuings/bulk-delete', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'bulkDelete'])->name('api.inventory-issuings.bulk-delete');

// Print Receipt route
Route::get('/warehouse/inventory-issuings/{id}/print-receipt', [\App\Http\Controllers\Warehouse\InventoryIssuingController::class, 'printReceipt'])->name('warehouse.inventory-issuings.print-receipt');

// Products API route
Route::get('/api/products', function () {
    $products = \App\Models\MasterProduct::where('is_active', true)
        ->select('id', 'name', 'sku', 'last_unit_price')
        ->orderBy('name')
        ->get();

    // Add total_stock to each product
    $products->each(function ($product) {
        $product->total_stock = $product->total_stock;
        $product->unit_price = $product->last_unit_price;
    });

    return response()->json([
        'status' => 'success',
        'data' => $products,
    ]);
})->name('api.products');

// Debug route - for troubleshooting inventory transfer data access
// Only works in development environment
Route::get('debug-transfer/{id}', function ($id) {
    try {
        $transfer = App\Models\InventoryTransfer::find($id);
        if (! $transfer) {
            return response()->json(['error' => 'Transfer not found', 'id' => $id]);
        }

        return response()->json(['success' => true, 'data' => $transfer->toArray()]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine()]);
    }
});

// Protected Routes
Route::middleware(['auth', 'download.logging', 'upload.logging', 'pageview.logging', 'report.logging'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/internal/mobile-job-visibility-check', [App\Http\Controllers\Internal\MobileJobVisibilityController::class, 'index'])
        ->name('internal.mobile-job-visibility-check');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    // Web Routes for Views
    // Marketing Routes
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', function () {
            return view('marketing.dashboard');
        })->name('dashboard');

        // Marketing Pipeline Routes
        Route::get('pipeline', [MarketingPipelineController::class, 'index'])->name('pipeline.index');
        Route::get('pipeline/create', [MarketingPipelineController::class, 'create'])->name('pipeline.create');
        Route::post('pipeline', [MarketingPipelineController::class, 'store'])->name('pipeline.store');
        Route::get('pipeline/{pipeline}', [MarketingPipelineController::class, 'show'])->name('pipeline.show');
        Route::get('pipeline/{pipeline}/edit', [MarketingPipelineController::class, 'edit'])->name('pipeline.edit');
        Route::put('pipeline/{pipeline}', [MarketingPipelineController::class, 'update'])->name('pipeline.update');
        Route::delete('pipeline/{pipeline}', [MarketingPipelineController::class, 'destroy'])->name('pipeline.destroy');
        Route::get('pipeline/employees/by-branch-team', [MarketingPipelineController::class, 'getEmployeesByBranchAndTeam'])->name('pipeline.employees.by-branch-team');
        Route::get('pipeline/statistics', [MarketingPipelineController::class, 'getStatistics'])->name('pipeline.statistics');
        Route::post('pipeline/bulk-delete', [MarketingPipelineController::class, 'bulkDelete'])->name('pipeline.bulk-delete');

        Route::resource('prospects', ProspectController::class);
        Route::post('prospects/bulk-delete', [ProspectController::class, 'bulkDelete'])->name('prospects.bulk-delete');

        Route::get('surveys/surveyors', [SurveyController::class, 'getSurveyors'])->name('surveys.surveyors');
        Route::get('surveys/buildings/{customerId}', [SurveyController::class, 'getBuildingsByCustomer'])->name('surveys.buildings');
        Route::post('surveys/bulk-delete', [SurveyController::class, 'bulkDelete'])->name('surveys.bulk-delete');
        Route::resource('surveys', SurveyController::class);
        Route::post('surveys/{survey}/approve', [SurveyController::class, 'approve'])->name('surveys.approve');
        Route::post('surveys/{survey}/reject', [SurveyController::class, 'reject'])->name('surveys.reject');
        Route::post('surveys/{survey}/unpost', [SurveyController::class, 'unpost'])->name('surveys.unpost');
        Route::post('surveys/{survey}/finalize', [SurveyController::class, 'finalize'])->name('surveys.finalize');
        Route::post('surveys/{survey}/update-location-detail', [SurveyController::class, 'updateLocationDetail'])->name('surveys.update-location-detail');

        // Job Advice Routes
        Route::resource('job-advices', JobAdviceController::class);
        Route::post('job-advices/bulk-delete', [JobAdviceController::class, 'bulkDelete'])->name('job-advices.bulk-delete');
        Route::post('job-advices/{job_advice}/finalize', [JobAdviceController::class, 'finalize'])->name('job-advices.finalize');
        Route::post('job-advices/{job_advice}/approve', [JobAdviceController::class, 'approve'])->name('job-advices.approve');
        Route::post('job-advices/{job_advice}/cancel-request', [JobAdviceController::class, 'cancelRequest'])->name('job-advices.cancel-request');
        Route::post('job-advices/{job_advice}/unpost', [JobAdviceController::class, 'unpost'])->name('job-advices.unpost');
        Route::post('job-advices/rooms/{jobAdviceRoom}/update-rental', [JobAdviceController::class, 'updateRoomRental'])->name('job-advices.rooms.update-rental');
        Route::get('surveys/{survey}/download-pdf', [SurveyController::class, 'downloadPdf'])->name('surveys.download-pdf');
        Route::post('surveys/{survey}/store-detail', [SurveyController::class, 'storeDetail'])->name('surveys.store-detail');
        Route::get('surveys/detail/{detail}', [SurveyController::class, 'showDetail'])->name('surveys.detail.show');
        Route::put('surveys/detail/{detail}', [SurveyController::class, 'updateDetail'])->name('surveys.detail.update');
        Route::post('surveys/detail/{detail}/copy', [SurveyController::class, 'copyDetail'])->name('surveys.detail.copy');
        Route::delete('surveys/detail/{detail}', [SurveyController::class, 'destroyDetail'])->name('surveys.detail.destroy');

        // Survey Wizard Routes
        Route::get('surveys/wizard/test', function () {
            return view('marketing.surveys.wizard.test');
        })->name('surveys.wizard.test');

        Route::get('surveys/wizard/simple', function () {
            return view('marketing.surveys.wizard.simple');
        })->name('surveys.wizard.simple');

        Route::prefix('surveys/wizard')->name('surveys.wizard.')->group(function () {
            Route::get('/create', [SurveyWizardController::class, 'create'])->name('create');
            Route::post('/save', [SurveyWizardController::class, 'save'])->name('save');
            Route::get('/get-contacts', [SurveyWizardController::class, 'getContacts'])->name('get-contacts');
            Route::get('/get-buildings', [SurveyWizardController::class, 'getBuildings'])->name('get-buildings');
            Route::get('/get-rooms', [SurveyWizardController::class, 'getRooms'])->name('get-rooms');
            Route::get('/get-customers', [SurveyWizardController::class, 'getCustomers'])->name('get-customers');
            Route::get('/get-marketing-staff', [SurveyWizardController::class, 'getMarketingStaff'])->name('get-marketing-staff');
            Route::get('/detail/{id}', [SurveyWizardController::class, 'getSurveyDetail'])->name('detail');
            Route::put('/detail/{id}', [SurveyWizardController::class, 'updateSurveyDetail'])->name('detail.update');

            // Master Data Creation
            Route::post('/create-master-building', [SurveyWizardController::class, 'createMasterBuilding'])->name('create-master-building');
            Route::post('/create-master-contact', [SurveyWizardController::class, 'createMasterContact'])->name('create-master-contact');
            Route::post('/add-room', [SurveyWizardController::class, 'addRoom'])->name('add-room');

            // Master Data API for dropdowns
            Route::get('/get-room-types', [SurveyWizardController::class, 'getRoomTypes'])->name('get-room-types');
            Route::get('/get-floors', [SurveyWizardController::class, 'getFloors'])->name('get-floors');
            Route::get('/get-scent-intensities', [SurveyWizardController::class, 'getScentIntensities'])->name('get-scent-intensities');
            Route::get('/get-installation-types', [SurveyWizardController::class, 'getInstallationTypes'])->name('get-installation-types');

            // Location cascade API
            Route::get('/get-cities-by-province', [SurveyWizardController::class, 'getCitiesByProvince'])->name('get-cities-by-province');
            Route::get('/get-districts-by-city', [SurveyWizardController::class, 'getDistrictsByCity'])->name('get-districts-by-city');
            Route::get('/get-subdistricts-by-district', [SurveyWizardController::class, 'getSubdistrictsByDistrict'])->name('get-subdistricts-by-district');

            Route::get('/{id}', [SurveyWizardController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [SurveyWizardController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SurveyWizardController::class, 'update'])->name('update');
        });

        // Quotation Wizard Routes
        Route::prefix('quotations/wizard')->name('quotations.wizard.')->group(function () {
            Route::get('/create', [QuotationWizardController::class, 'create'])->name('create');
            Route::post('/store', [QuotationWizardController::class, 'store'])->name('store');
            Route::put('/{id}', [QuotationWizardController::class, 'update'])->name('update'); // Added Update Route
            Route::post('/{id}/approve', [QuotationWizardController::class, 'approveQuotation'])->name('approve');
            Route::post('/{id}/reject', [QuotationWizardController::class, 'rejectQuotation'])->name('reject');
            Route::post('/save', [QuotationWizardController::class, 'save'])->name('save');
            Route::get('/get-surveys-by-customer', [QuotationWizardController::class, 'getSurveysByCustomer'])->name('get-surveys-by-customer');
            Route::get('/get-rooms-by-survey', [QuotationWizardController::class, 'getRoomsBySurvey'])->name('get-rooms-by-survey');
            Route::get('/get-survey-rooms', [QuotationWizardController::class, 'getSurveyRooms'])->name('get-survey-rooms');
            Route::get('/get-products', [QuotationWizardController::class, 'getProducts'])->name('get-products');
            Route::get('/get-aroma-products', [QuotationWizardController::class, 'getAromaProducts'])->name('get-aroma-products');
            Route::get('/get-rental-aroma/{rentalId}', [QuotationWizardController::class, 'getRentalAroma'])->name('get-rental-aroma');
            Route::get('/get-tax-settings', [QuotationWizardController::class, 'getTaxSettings'])->name('get-tax-settings');
            Route::get('/get-pic-contacts', [QuotationWizardController::class, 'getPicContacts'])->name('get-pic-contacts');
            Route::get('/get-user-branches', [QuotationWizardController::class, 'getUserBranches'])->name('get-user-branches');
        });

        // Contract Wizard Routes
        Route::prefix('contracts/wizard')->name('contracts.wizard.')->group(function () {
            Route::get('/create', [ContractWizardController::class, 'create'])->name('create');
            Route::post('/save', [ContractWizardController::class, 'save'])->name('save');
            Route::get('/get-quotation-details', [ContractWizardController::class, 'getQuotationDetails'])->name('get-quotation-details');
            Route::get('/get-bank-payments', [ContractWizardController::class, 'getBankPayments'])->name('get-bank-payments');
            Route::get('/get-buildings-by-customer', [ContractWizardController::class, 'getBuildingsByCustomer'])->name('get-buildings-by-customer');
            Route::get('/get-reusable-billing-groups', [ContractWizardController::class, 'getReusableBillingGroups'])->name('get-reusable-billing-groups');
        });

        // Contract client contact save route (outside wizard prefix for simpler URL)
        Route::post('contracts/save-client-contact', [ContractWizardController::class, 'saveClientContact'])->name('contracts.save-client-contact');

        Route::get('quotations/pending-approvals', [QuotationController::class, 'getPendingApprovals'])->name('quotations.pending-approvals');
        // Override edit route to use Wizard
        Route::get('quotations/{quotation}/edit', [QuotationWizardController::class, 'edit'])->name('quotations.edit');
        Route::resource('quotations', QuotationController::class)->except(['edit']);
        Route::post('quotations/bulk-delete', [QuotationController::class, 'bulkDelete'])->name('quotations.bulk-delete');
        Route::get('quotations/{quotation}/download-pdf', [QuotationController::class, 'downloadPdf'])->name('quotations.download-pdf');
        Route::post('quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])->name('quotations.cancel');
        Route::post('quotations/{quotation}/finalize', [QuotationController::class, 'finalize'])->name('quotations.finalize');
        Route::post('quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
        Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
        Route::post('quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
        Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept');
        Route::post('quotations/{quotation}/update-goal', [QuotationController::class, 'updateGoal'])->name('quotations.update-goal');
        Route::put('quotations/{quotation}/editable-fields', [QuotationController::class, 'updateEditableFields'])->name('quotations.update-editable-fields');
        Route::post('quotations/{quotation}/convert-to-contract', [QuotationController::class, 'convertToContract'])->name('quotations.convert-to-contract');
        Route::post('quotations/{quotation}/copy', [QuotationController::class, 'copy'])->name('quotations.copy');

        // API routes for quotation wizard
        Route::get('contracts/by-marketing/{marketingId}', [QuotationController::class, 'getContractsByMarketing'])->name('contracts.by-marketing');
        Route::get('prospects/{id}/data', [QuotationController::class, 'getProspectData'])->name('prospects.quotation-data');
        Route::get('surveys/{id}/data', [QuotationController::class, 'getSurveyData'])->name('surveys.data');

        // New approval and free trial routes
        Route::get('quotations/{quotation}/approval-summary', [QuotationController::class, 'getApprovalSummary'])->name('quotations.approval-summary');
        Route::post('quotations/{quotation}/validate-bottom-price', [QuotationController::class, 'validateBottomPrice'])->name('quotations.validate-bottom-price');
        Route::get('quotations/{quotation}/price-slab-info', [QuotationController::class, 'getPriceSlabInfo'])->name('quotations.price-slab-info');
        Route::post('quotations/{quotation}/create-revision', [QuotationController::class, 'createRevision'])->name('quotations.create-revision');
        Route::get('quotations/{quotation}/revisions', [QuotationController::class, 'getRevisions'])->name('quotations.revisions');
        Route::get('quotations/{quotation}/free-trials', [QuotationController::class, 'getFreeTrials'])->name('quotations.free-trials');
        Route::get('quotations/{quotation}/can-create-contract', [QuotationController::class, 'canCreateContract'])->name('quotations.can-create-contract');

        // Quotation Details Management
        Route::get('quotations/{quotation}/details', [QuotationController::class, 'getDetails'])->name('quotations.details');
        Route::post('quotations/{quotation}/details', [QuotationController::class, 'addDetail'])->name('quotations.details.add');
        Route::put('quotations/{quotation}/details/{detail}', [QuotationController::class, 'updateDetail'])->name('quotations.details.update');
        Route::delete('quotations/{quotation}/details/{detail}', [QuotationController::class, 'deleteDetail'])->name('quotations.details.delete');

        // Approval Management Routes
        Route::get('approvals', [App\Http\Controllers\Marketing\ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('approvals/{approval}', [App\Http\Controllers\Marketing\ApprovalController::class, 'show'])->name('approvals.show');
        Route::post('approvals/{approval}/approve', [App\Http\Controllers\Marketing\ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approval}/reject', [App\Http\Controllers\Marketing\ApprovalController::class, 'reject'])->name('approvals.reject');
        Route::post('approvals/create', [App\Http\Controllers\Marketing\ApprovalController::class, 'createApproval'])->name('approvals.create');
        Route::get('approvals/quotation/{quotation}/summary', [App\Http\Controllers\Marketing\ApprovalController::class, 'getQuotationApprovalSummary'])->name('approvals.quotation.summary');
        Route::get('approvals/price-slab-info/{rentalId}/{quantity}', [App\Http\Controllers\Marketing\ApprovalController::class, 'getPriceSlabInfo'])->name('approvals.price-slab-info');
        Route::post('approvals/quotation/{quotation}/validate', [App\Http\Controllers\Marketing\ApprovalController::class, 'validateQuotationApproval'])->name('approvals.quotation.validate');

        // Free Trial Management Routes
        Route::get('free-trials', [App\Http\Controllers\Marketing\FreeTrialController::class, 'index'])->name('free-trials.index');
        Route::get('free-trials/{freeTrial}', [App\Http\Controllers\Marketing\FreeTrialController::class, 'show'])->name('free-trials.show');
        Route::get('free-trials/quotation/{quotation}/create', [App\Http\Controllers\Marketing\FreeTrialController::class, 'create'])->name('free-trials.create');
        Route::post('free-trials', [App\Http\Controllers\Marketing\FreeTrialController::class, 'store'])->name('free-trials.store');
        Route::post('free-trials/{freeTrial}/approve', [App\Http\Controllers\Marketing\FreeTrialController::class, 'approve'])->name('free-trials.approve');
        Route::post('free-trials/{freeTrial}/reject', [App\Http\Controllers\Marketing\FreeTrialController::class, 'reject'])->name('free-trials.reject');
        Route::post('free-trials/{freeTrial}/start', [App\Http\Controllers\Marketing\FreeTrialController::class, 'start'])->name('free-trials.start');
        Route::post('free-trials/{freeTrial}/complete', [App\Http\Controllers\Marketing\FreeTrialController::class, 'complete'])->name('free-trials.complete');
        Route::post('free-trials/{freeTrial}/cancel', [App\Http\Controllers\Marketing\FreeTrialController::class, 'cancel'])->name('free-trials.cancel');
        Route::get('free-trials/quotation/{quotation}/list', [App\Http\Controllers\Marketing\FreeTrialController::class, 'getQuotationFreeTrials'])->name('free-trials.quotation.list');
        Route::post('quotations/{quotation}/details/bulk', [QuotationController::class, 'bulkAddDetails'])->name('quotations.details.bulk');
        Route::get('master-rentals', [QuotationController::class, 'getMasterRentals'])->name('master-rentals');
        Route::post('quotations/assign-buildings', [QuotationController::class, 'assignBuildingsToCustomer'])->name('quotations.assign-buildings');

        // Enhanced Approval System Routes
        Route::get('quotations/{quotation}/approval-workflow', [QuotationController::class, 'getApprovalWorkflow'])->name('quotations.approval-workflow');
        Route::post('quotations/{quotation}/request-approval', [QuotationController::class, 'requestApproval'])->name('quotations.request-approval');

        // Auto Approval & Price Slab Routes
        Route::get('quotations/{quotation}/auto-approval-criteria', [QuotationController::class, 'getAutoApprovalCriteriaData'])->name('quotations.auto-approval-criteria');
        Route::get('quotations/{quotation}/auto-approval-status', [QuotationController::class, 'checkAutoApprovalStatus'])->name('quotations.auto-approval-status');

        // Contract Merge Routes (HARUS sebelum resource agar tidak di-shadow oleh {contract})
        Route::get('contracts/merge-wizard', [ContractController::class, 'mergeWizard'])->name('contracts.merge-wizard');
        Route::post('contracts/merge-wizard/save', [ContractController::class, 'saveMergeWizard'])->name('contracts.merge-wizard.save');
        Route::get('contracts/merge-candidates', [ContractController::class, 'getMergeCandidates'])->name('contracts.merge-candidates');
        Route::post('contracts/merge-preview', [ContractController::class, 'previewMerge'])->name('contracts.merge-preview');

        Route::resource('contracts', ContractController::class);

        // Commission Dashboard Routes
        Route::get('commissions/dashboard', [App\Http\Controllers\Marketing\CommissionDashboardController::class, 'index'])->name('commissions.dashboard');
        Route::get('commissions/period/{periodId}', [App\Http\Controllers\Marketing\CommissionDashboardController::class, 'getPeriodDetails'])->name('commissions.period-details');
        Route::get('commissions/{calculationId}', [App\Http\Controllers\Marketing\CommissionDashboardController::class, 'getCommissionDetails'])->name('commissions.details');
        Route::post('contracts/bulk-delete', [ContractController::class, 'bulkDelete'])->name('contracts.bulk-delete');
        Route::get('contracts/get-latest-sq', [ContractController::class, 'getLatestSQ'])->name('contracts.get-latest-sq');
        Route::get('contracts/dropdown', [ContractController::class, 'getForDropdown'])->name('contracts.dropdown');
        Route::post('contracts/{contract}/update-additional-info', [ContractController::class, 'updateAdditionalInfo'])->name('contracts.update-additional-info');
        Route::get('contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
        Route::get('contracts/{contract}/print', [ContractController::class, 'print'])->name('contracts.print');

        // Enhanced Contract Management Routes
        Route::get('contracts/{contract}/remarks', [ContractController::class, 'getRemarks'])->name('contracts.remarks');
        Route::post('contracts/{contract}/remarks', [ContractController::class, 'storeRemark'])->name('contracts.remarks.store');
        Route::put('contracts/{contract}/remarks/{remark}', [ContractController::class, 'updateRemark'])->name('contracts.remarks.update');
        Route::delete('contracts/{contract}/remarks/{remark}', [ContractController::class, 'destroyRemark'])->name('contracts.remarks.destroy');

        Route::get('contracts/{contract}/revisions', [ContractController::class, 'getRevisions'])->name('contracts.revisions');
        Route::post('contracts/{contract}/revisions', [ContractController::class, 'createRevision'])->name('contracts.revisions.create');
        Route::post('contracts/{contract}/revisions/{revision}/approve', [ContractController::class, 'approveRevision'])->name('contracts.revisions.approve');
        Route::post('contracts/{contract}/revisions/{revision}/reject', [ContractController::class, 'rejectRevision'])->name('contracts.revisions.reject');

        Route::post('contracts/{contract}/approve', [ContractController::class, 'approveContract'])->name('contracts.approve');
        Route::post('contracts/{contract}/post', [ContractController::class, 'postContract'])->name('contracts.post');
        Route::post('contracts/{contract}/generate-qr', [ContractController::class, 'generateQrCode'])->name('contracts.generate-qr');

        // Contract Actions
        Route::post('contracts/{contract}/finalize', [ContractController::class, 'finalize'])->name('contracts.finalize');
        Route::post('contracts/{contract}/save-draft', [ContractController::class, 'saveDraft'])->name('contracts.save-draft');
        Route::post('contracts/{contract}/unpost', [ContractController::class, 'unpost'])->name('contracts.unpost');
        Route::post('contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');

        // : Contract Notes Routes
        Route::post('contracts/{contract}/update-notes', [ContractController::class, 'updateNotes'])->name('contracts.update-notes');

        // Contract File Upload & Verification Routes
        Route::post('contracts/{contract}/upload-file', [ContractController::class, 'uploadFile'])->name('contracts.upload-file');
        Route::get('contracts/{contract}/files', [ContractController::class, 'getFiles'])->name('contracts.files');
        Route::post('contracts/{contract}/files/{file}/verify', [ContractController::class, 'verifyFile'])->name('contracts.files.verify');
        Route::post('contracts/{contract}/files/{file}/reject', [ContractController::class, 'rejectFile'])->name('contracts.files.reject');
        Route::delete('contracts/{contract}/files/{file}', [ContractController::class, 'deleteFile'])->name('contracts.files.delete');
        Route::post('contracts/{contract}/files/bulk-approve', [ContractController::class, 'bulkApproveFiles'])->name('contracts.files.bulk-approve');

        // Contract Room Management Routes
        Route::get('contracts/{contract}/buildings-for-rooms', [ContractController::class, 'getBuildingsForRooms'])->name('contracts.buildings-for-rooms');
        Route::get('contracts/rooms/{contractRoom}', [ContractController::class, 'getContractRoom'])->name('contracts.rooms.show');
        Route::post('contracts/{contract}/rooms', [ContractController::class, 'addRoom'])->name('contracts.rooms.store');
        Route::put('contracts/{contract}/rooms/{contractRoom}', [ContractController::class, 'updateRoom'])->name('contracts.rooms.update');
        Route::delete('contracts/rooms/{contractRoom}', [ContractController::class, 'deleteRoom'])->name('contracts.rooms.destroy');

        // Contract Signing Enhancement Routes
        Route::post('contracts/{contract}/digital-signature', [ContractController::class, 'addDigitalSignature'])->name('contracts.digital-signature');
        Route::post('contracts/{contract}/verify-npwp', [ContractController::class, 'verifyNPWP'])->name('contracts.verify-npwp');
        Route::post('contracts/{contract}/generate-schedule', [ContractController::class, 'generateSchedule'])->name('contracts.generate-schedule');
        Route::post('contracts/{contract}/update-status', [ContractController::class, 'updateStatus'])->name('contracts.update-status');
        Route::get('contracts/{contract}/signing-status', [ContractController::class, 'getSigningStatus'])->name('contracts.signing-status');
        Route::post('contracts/{contract}/post', [ContractController::class, 'postContract'])->name('contracts.post');
        Route::put('contracts/{contract}/editable-fields', [ContractController::class, 'updateEditableFields'])->name('contracts.update-editable-fields');
        Route::post('contracts/{contract}/toggle-ba-supported', [ContractController::class, 'toggleBaFilesSupported'])->name('contracts.toggle-ba-supported');
        Route::post('contracts/{contract}/toggle-hold-invoice', [ContractController::class, 'toggleHoldInvoice'])->name('contracts.toggle-hold-invoice');
        Route::post('contracts/{contract}/toggle-contract-target', [ContractController::class, 'toggleContractTarget'])->name('contracts.toggle-contract-target');

        Route::post('contracts/{contract}/update-achiever', [ContractController::class, 'updateAchiever'])->name('contracts.update-achiever');
        Route::post('contracts/{contract}/update-net-value', [ContractController::class, 'updateNetValue'])->name('contracts.update-net-value');

        // executeMerge tetap di sini karena pakai {contract} binding
        Route::post('contracts/{contract}/execute-merge', [ContractController::class, 'executeMerge'])->name('contracts.execute-merge');

        Route::get('users/marketing-list', [JobAdviceController::class, 'getMarketingUsers'])->name('users.marketing-list');
        Route::post('job-advices/{jobAdvice}/add-rooms', [JobAdviceController::class, 'addRooms'])->name('job-advices.add-rooms');
        Route::post('job-advices/{jobAdvice}/submit', [JobAdviceController::class, 'submitForApproval'])->name('job-advices.submit');
        Route::post('job-advices/{jobAdvice}/cancel', [JobAdviceController::class, 'cancel'])->name('job-advices.cancel');
        Route::post('job-advices/{jobAdvice}/reject', [JobAdviceController::class, 'reject'])->name('job-advices.reject');
        Route::post('job-advices/{jobAdvice}/revert-to-draft', [JobAdviceController::class, 'revertToDraft'])->name('job-advices.revert-to-draft');
        Route::delete('job-advices/{jobAdvice}/rooms/{room}', [JobAdviceController::class, 'removeRoom'])->name('job-advices.remove-room');
        Route::get('lost-unit-reports/get-rentals-by-room/{contract}/{room}', [LostUnitReportController::class, 'getRentalsByRoom'])->name('lost-unit-reports.get-rentals-by-room');
        Route::get('lost-unit-reports/get-rentals-by-contract/{contract}', [LostUnitReportController::class, 'getRentalsByContract'])->name('lost-unit-reports.get-rentals-by-contract');
        Route::get('lost-unit-reports/get-rooms-by-contract/{contract}', [LostUnitReportController::class, 'getRoomsByContract'])->name('lost-unit-reports.get-rooms-by-contract');
        Route::get('lost-unit-reports/get-lost-unit-price', [LostUnitReportController::class, 'getLostUnitPrice'])->name('lost-unit-reports.get-lost-unit-price');
        Route::post('lost-unit-reports/bulk-delete', [LostUnitReportController::class, 'bulkDelete'])->name('lost-unit-reports.bulk-delete');
        Route::resource('lost-unit-reports', LostUnitReportController::class);
        Route::post('lost-unit-reports/{lostUnitReport}/finalize', [LostUnitReportController::class, 'finalize'])->name('lost-unit-reports.finalize');
        Route::post('lost-unit-reports/{lostUnitReport}/approve', [LostUnitReportController::class, 'approve'])->name('lost-unit-reports.approve');
        Route::post('lost-unit-reports/{lostUnitReport}/reject', [LostUnitReportController::class, 'reject'])->name('lost-unit-reports.reject');
        Route::post('lost-unit-reports/{lostUnitReport}/unpost', [LostUnitReportController::class, 'unpost'])->name('lost-unit-reports.unpost');
        Route::post('lost-unit-reports/items/{item}/update-price', [LostUnitReportController::class, 'updateItemPrice'])->name('lost-unit-reports.update-item-price');

        // Aroma Change Routes
        Route::get('aroma-changes/get-aroma-products', [AromaChangeController::class, 'getAromaProducts'])->name('aroma-changes.get-aroma-products');
        Route::get('aroma-changes/get-contracts', [AromaChangeController::class, 'getContracts'])->name('aroma-changes.get-contracts');
        Route::get('aroma-changes/get-schedules', [AromaChangeController::class, 'getSchedules'])->name('aroma-changes.get-schedules');
        Route::resource('aroma-changes', AromaChangeController::class);
        Route::post('aroma-changes/{aromaChange}/submit', [AromaChangeController::class, 'submitForApproval'])->name('aroma-changes.submit');
        Route::post('aroma-changes/{aromaChange}/approve', [AromaChangeController::class, 'approve'])->name('aroma-changes.approve');
        Route::post('aroma-changes/{aromaChange}/reject', [AromaChangeController::class, 'reject'])->name('aroma-changes.reject');
        Route::post('aroma-changes/{aromaChange}/apply', [AromaChangeController::class, 'applyChange'])->name('aroma-changes.apply');
        Route::post('aroma-changes/{aromaChange}/cancel', [AromaChangeController::class, 'cancel'])->name('aroma-changes.cancel');
        Route::get('aroma-changes-history', [AromaChangeController::class, 'history'])->name('aroma-changes.history');

        // Stock View (read-only) for Marketing
        Route::get('stock-view', [\App\Http\Controllers\Marketing\StockViewController::class, 'index'])->name('stock-view.index')->middleware('permission:marketing.stock-view.view');

        // Extra Service Routes
        Route::resource('extra-services', ExtraServiceController::class);
        Route::post('extra-services/{extraService}/submit', [ExtraServiceController::class, 'submitForApproval'])->name('extra-services.submit');
        Route::post('extra-services/{extraService}/approve', [ExtraServiceController::class, 'approve'])->name('extra-services.approve');
        Route::post('extra-services/{extraService}/reject', [ExtraServiceController::class, 'reject'])->name('extra-services.reject');
        Route::post('extra-services/{extraService}/cancel', [ExtraServiceController::class, 'cancel'])->name('extra-services.cancel');

        // Contract Removal Routes
        Route::resource('contract-removals', ContractRemovalController::class);
        Route::post('contract-removals/{contractRemoval}/submit', [ContractRemovalController::class, 'submitForApproval'])->name('contract-removals.submit');
        Route::post('contract-removals/{contractRemoval}/approve', [ContractRemovalController::class, 'approve'])->name('contract-removals.approve');
        Route::post('contract-removals/{contractRemoval}/reject', [ContractRemovalController::class, 'reject'])->name('contract-removals.reject');
        Route::post('contract-removals/{contractRemoval}/cancel', [ContractRemovalController::class, 'cancel'])->name('contract-removals.cancel');

        // Rental Change Routes
        Route::resource('rental-changes', RentalChangeController::class);
        Route::post('rental-changes/{rentalChange}/submit', [RentalChangeController::class, 'submitForApproval'])->name('rental-changes.submit');
        Route::post('rental-changes/{rentalChange}/approve', [RentalChangeController::class, 'approve'])->name('rental-changes.approve');
        Route::post('rental-changes/{rentalChange}/reject', [RentalChangeController::class, 'reject'])->name('rental-changes.reject');
        Route::post('rental-changes/{rentalChange}/schedule', [RentalChangeController::class, 'schedule'])->name('rental-changes.schedule');
        Route::post('rental-changes/{rentalChange}/complete', [RentalChangeController::class, 'complete'])->name('rental-changes.complete');
        Route::post('rental-changes/{rentalChange}/cancel', [RentalChangeController::class, 'cancel'])->name('rental-changes.cancel');
        Route::get('rental-changes/contract/{contract}/rooms', [RentalChangeController::class, 'getContractRooms'])->name('rental-changes.contract-rooms');

        // Complaint Routes
        Route::resource('complaints', ComplaintController::class);
        Route::post('complaints/{complaint}/acknowledge', [ComplaintController::class, 'acknowledge'])->name('complaints.acknowledge');
        Route::post('complaints/{complaint}/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');
        Route::post('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('complaints.resolve');
        Route::post('complaints/{complaint}/close', [ComplaintController::class, 'close'])->name('complaints.close');
        Route::post('complaints/{complaint}/reject', [ComplaintController::class, 'reject'])->name('complaints.reject');
        Route::post('complaints/{complaint}/reopen', [ComplaintController::class, 'reopen'])->name('complaints.reopen');
        Route::post('complaints/{complaint}/satisfaction', [ComplaintController::class, 'addSatisfaction'])->name('complaints.add-satisfaction');
        Route::post('complaints/{complaint}/follow-up', [ComplaintController::class, 'setFollowUp'])->name('complaints.set-follow-up');
        Route::get('complaints/statistics', [ComplaintController::class, 'statistics'])->name('complaints.statistics');

        // Contract Renewal Routes
        // IMPORTANT: Specific routes MUST come BEFORE resource routes to avoid route collision
        Route::get('contract-renewals/eligible-contracts', [ContractRenewalController::class, 'getEligibleContracts'])->name('contract-renewals.eligible-contracts');
        Route::get('contract-renewals/{contract}/for-renewal', [ContractRenewalController::class, 'getContractForRenewal'])->name('contract-renewals.for-renewal');
        Route::post('contract-renewals/auto-create', [ContractRenewalController::class, 'autoCreate'])->name('contract-renewals.auto-create');

        // Resource route (will create standard REST routes)
        Route::resource('contract-renewals', ContractRenewalController::class);

        // Additional action routes
        Route::post('contract-renewals/{contractRenewal}/submit-to-customer', [ContractRenewalController::class, 'submitToCustomer'])->name('contract-renewals.submit-to-customer');
        Route::post('contract-renewals/{contractRenewal}/customer-approve', [ContractRenewalController::class, 'customerApprove'])->name('contract-renewals.customer-approve');
        Route::post('contract-renewals/{contractRenewal}/internal-approve', [ContractRenewalController::class, 'internalApprove'])->name('contract-renewals.internal-approve');
        Route::post('contract-renewals/{contractRenewal}/reject', [ContractRenewalController::class, 'reject'])->name('contract-renewals.reject');
        Route::post('contract-renewals/{contractRenewal}/cancel', [ContractRenewalController::class, 'cancel'])->name('contract-renewals.cancel');
        Route::post('contract-renewals/{contractRenewal}/send-reminder', [ContractRenewalController::class, 'sendReminder'])->name('contract-renewals.send-reminder');

        // Contract Termination Routes (SQ Automation)
        // Contract Assigned Routes
        Route::get('contract-assigned/marketing-users', [ContractAssignedController::class, 'getMarketingUsers'])->name('contract-assigned.marketing-users');
        Route::get('contract-assigned/contracts', [ContractAssignedController::class, 'getContracts'])->name('contract-assigned.contracts');
        Route::resource('contract-assigned', ContractAssignedController::class);
        Route::post('contract-assigned/{contractAssigned}/submit', [ContractAssignedController::class, 'submitForApproval'])->name('contract-assigned.submit');
        Route::post('contract-assigned/{contractAssigned}/approve', [ContractAssignedController::class, 'approve'])->name('contract-assigned.approve');
        Route::post('contract-assigned/{contractAssigned}/reject', [ContractAssignedController::class, 'reject'])->name('contract-assigned.reject');
        Route::post('contract-assigned/{contractAssigned}/cancel', [ContractAssignedController::class, 'cancel'])->name('contract-assigned.cancel');
        Route::post('contract-assigned/{contractAssigned}/execute', [ContractAssignedController::class, 'execute'])->name('contract-assigned.execute');

        // Contract Termination Routes
        Route::resource('contract-terminations', ContractTerminationController::class);
        Route::post('contract-terminations/{contractTermination}/submit', [ContractTerminationController::class, 'submitForApproval'])->name('contract-terminations.submit');
        Route::post('contract-terminations/{contractTermination}/approve', [ContractTerminationController::class, 'approve'])->name('contract-terminations.approve');
        Route::post('contract-terminations/{contractTermination}/reject', [ContractTerminationController::class, 'reject'])->name('contract-terminations.reject');
        Route::post('contract-terminations/{contractTermination}/unpost', [ContractTerminationController::class, 'unpost'])->name('contract-terminations.unpost');

        // Contract Switching Routes
        Route::get('contract-switchings/active-contracts', [ContractSwitchingController::class, 'getActiveContracts'])->name('contract-switchings.active-contracts');
        Route::get('contract-switchings/customers', [ContractSwitchingController::class, 'getCustomers'])->name('contract-switchings.customers');
        Route::get('contract-switchings/marketing-users', [ContractSwitchingController::class, 'getMarketingUsers'])->name('contract-switchings.marketing-users');
        Route::resource('contract-switchings', ContractSwitchingController::class);
        Route::post('contract-switchings/{contractSwitching}/submit', [ContractSwitchingController::class, 'submitForApproval'])->name('contract-switchings.submit');
        Route::post('contract-switchings/{contractSwitching}/approve', [ContractSwitchingController::class, 'approve'])->name('contract-switchings.approve');
        Route::post('contract-switchings/{contractSwitching}/reject', [ContractSwitchingController::class, 'reject'])->name('contract-switchings.reject');
        Route::post('contract-switchings/{contractSwitching}/cancel', [ContractSwitchingController::class, 'cancel'])->name('contract-switchings.cancel');
        Route::post('contract-switchings/{contractSwitching}/execute', [ContractSwitchingController::class, 'execute'])->name('contract-switchings.execute');

        Route::post('master-corporates/{code}/submit', [MasterCorporateController::class, 'submitGroup'])->name('master-corporates.submit')->where('code', '.*');
        Route::post('master-corporates/{code}/approve', [MasterCorporateController::class, 'approve'])->name('master-corporates.approve')->where('code', '.*');
        Route::post('master-corporates/{code}/reject', [MasterCorporateController::class, 'reject'])->name('master-corporates.reject')->where('code', '.*');
        Route::post('master-corporates/{code}/unpost', [MasterCorporateController::class, 'unpost'])->name('master-corporates.unpost')->where('code', '.*');
        Route::delete('master-corporates/group/{code}', [MasterCorporateController::class, 'destroyGroup'])->name('master-corporates.destroy-group')->where('code', '.*');
        Route::get('master-corporates/{code}/edit-group', [MasterCorporateController::class, 'editGroup'])->name('master-corporates.edit-group')->where('code', '.*');
        Route::put('master-corporates/{code}/update-group', [MasterCorporateController::class, 'updateGroup'])->name('master-corporates.update-group')->where('code', '.*');
        Route::get('master-corporates/show/{id}', [MasterCorporateController::class, 'show'])->name('master-corporates.show')->where('id', '.*');
        Route::resource('master-corporates', MasterCorporateController::class)->except(['show', 'edit', 'update']);

        Route::resource('sales-activities', SalesActivityController::class);
        Route::post('sales-activities/bulk-delete', [SalesActivityController::class, 'bulkDelete'])->name('sales-activities.bulk-delete');
        Route::get('my-activities', [SalesActivityController::class, 'myActivities'])->name('my-activities');
        Route::get('prospects/{prospect}/data', [SalesActivityController::class, 'getProspectData'])->name('prospects.sales-data');
        Route::get('prospects-list', [SalesActivityController::class, 'getProspects'])->name('prospects.list');
    });

    // Operational Routes
    Route::prefix('operational')->name('operational.')->group(function () {
        // Test endpoint for debugging
        Route::get('/test-buildings', function () {
            try {
                $count = \App\Models\Building::count();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Buildings controller test',
                    'building_count' => $count,
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }
        })->name('test-buildings');

        Route::get('/', function () {
            return view('operational.dashboard');
        })->name('dashboard');
        Route::get('job-schedules/print-csr', [JobScheduleController::class, 'printCsr'])->name('job-schedules.print-csr');
        Route::resource('job-schedules', JobScheduleController::class);
        Route::post('job-schedules/bulk-delete', [JobScheduleController::class, 'bulkDelete'])->name('job-schedules.bulk-delete');
        Route::post('job-schedules/bulk-suspend-dpf', [JobScheduleController::class, 'bulkSuspendDpf'])->name('job-schedules.bulk-suspend-dpf')->middleware('permission:operational.job-schedules-suspend.update,operational.job-schedules-dpf.update,operational.job-schedules.update');
        Route::post('job-schedules/bulk-material-assign', [JobScheduleController::class, 'bulkMaterialAssign'])->name('job-schedules.bulk-material-assign')->middleware('permission:operational.job-schedules-material-assign.update,operational.job-schedules.update');
        Route::post('job-schedules/bulk-unassign-material', [JobScheduleController::class, 'bulkUnassignMaterial'])->name('job-schedules.bulk-unassign-material')->middleware('permission:operational.job-schedules-unassign-material.update,operational.job-schedules.update');
        Route::post('job-schedules/bulk-unassign-team', [JobScheduleController::class, 'bulkUnassignTeam'])->name('job-schedules.bulk-unassign-team')->middleware('permission:operational.job-schedules-unassign-team.update,operational.job-schedules.update');
        Route::post('job-schedules/bulk-update-room-assignment', [JobScheduleController::class, 'bulkUpdateRoomAssignment'])->name('job-schedules.bulk-update-room-assignment');
        Route::get('job-schedules/{jobSchedule}/assignments', [JobScheduleController::class, 'showAssignments'])->name('job-schedules.assignments');
        Route::get('job-schedules/{jobSchedule}/materials', [JobScheduleController::class, 'showMaterials'])->name('job-schedules.materials');
        Route::post('job-schedules/{id}/force-majeure', [JobScheduleController::class, 'reportForceMajeure'])->name('job-schedules.force-majeure');
        Route::post('job-schedules/{id}/reassign', [JobScheduleController::class, 'reassignToBackupTechnician'])->name('job-schedules.reassign');
        Route::post('job-schedules/{id}/reschedule', [JobScheduleController::class, 'rescheduleJob'])->name('job-schedules.reschedule');
        Route::post('job-schedules/{id}/material-return', [JobScheduleController::class, 'handleMaterialReturn'])->name('job-schedules.material-return');
        // STUDY CASE B1: Material return per room routes
        Route::post('job-schedules/{jobSchedule}/rooms/{roomId}/material-return', [JobScheduleController::class, 'createMaterialReturn'])->name('job-schedules.rooms.material-return.create');
        Route::post('job-schedules/{jobSchedule}/rooms/{roomId}/complete-manual', [JobScheduleController::class, 'completeRoomManual'])->name('job-schedules.rooms.complete-manual');
        // Web fallback (APK error): finish a job with Berita Acara (PIC name + signature + photo)
        Route::post('job-schedules/{jobSchedule}/complete-with-ba', [JobScheduleController::class, 'completeWithBa'])
            ->name('job-schedules.complete-with-ba')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        // Web fallback Phase 2: technician location lifecycle from dashboard
        Route::post('job-schedules/{jobSchedule}/arrived', [JobScheduleController::class, 'arrivedAtLocationWeb'])
            ->name('job-schedules.arrived')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/start-work', [JobScheduleController::class, 'startWorkWeb'])
            ->name('job-schedules.start-work')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/leave-location', [JobScheduleController::class, 'leaveLocationWeb'])
            ->name('job-schedules.leave-location')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        // Web fallback Phase 3: material confirm/verify + scanned unit aroma schedule from dashboard
        Route::post('job-schedules/{jobSchedule}/confirm-materials', [JobScheduleController::class, 'confirmMaterialsWeb'])
            ->name('job-schedules.confirm-materials')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/verify-materials', [JobScheduleController::class, 'verifyMaterialsWeb'])
            ->name('job-schedules.verify-materials')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/save-scanned-unit', [JobScheduleController::class, 'saveScannedUnitWeb'])
            ->name('job-schedules.save-scanned-unit')
            ->middleware('permission:operational.job-schedules-complete-ba.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/material-returns/{returnId}/approve', [JobScheduleController::class, 'approveMaterialReturn'])->name('job-schedules.material-returns.approve');
        Route::post('job-schedules/{jobSchedule}/material-returns/{returnId}/reject', [JobScheduleController::class, 'rejectMaterialReturn'])->name('job-schedules.material-returns.reject');
        Route::post('job-schedules/{jobSchedule}/material-returns/{returnId}/complete', [JobScheduleController::class, 'completeMaterialReturn'])->name('job-schedules.material-returns.complete');
        Route::get('job-schedules/{jobSchedule}/material-returns', [JobScheduleController::class, 'getMaterialReturns'])->name('job-schedules.material-returns');
        Route::get('job-schedules/{jobSchedule}/rooms/{roomId}/material-issue-items', [JobScheduleController::class, 'getMaterialIssueItemsForRoom'])->name('job-schedules.rooms.material-issue-items');
        Route::post('job-schedules/{id}/resolve-force-majeure', [JobScheduleController::class, 'resolveForceMajeure'])->name('job-schedules.resolve-force-majeure');
        Route::get('job-schedules/force-majeure/stats', [JobScheduleController::class, 'getForceMajeureStats'])->name('job-schedules.force-majeure-stats');
        Route::post('job-schedules/{jobSchedule}/suspend', [JobScheduleController::class, 'suspend'])->name('job-schedules.suspend')->middleware('permission:operational.job-schedules-suspend.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/unsuspend', [JobScheduleController::class, 'unsuspend'])->name('job-schedules.unsuspend')->middleware('permission:operational.job-schedules-suspend.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/dpf', [JobScheduleController::class, 'markAsDpf'])->name('job-schedules.dpf')->middleware('permission:operational.job-schedules-dpf.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/undone', [JobScheduleController::class, 'undoneJob'])->name('job-schedules.undone');
        Route::post('job-schedules/{jobSchedule}/update-ba-date', [JobScheduleController::class, 'updateBaDate'])->name('job-schedules.update-ba-date');
        Route::post('job-schedules/{jobSchedule}/extend-day', [JobScheduleController::class, 'extendDay'])->name('job-schedules.extend-day');
        Route::post('job-schedules/{jobSchedule}/unassign-team', [JobScheduleController::class, 'unassignTeam'])->name('job-schedules.unassign-team')->middleware('permission:operational.job-schedules-unassign-team.update,operational.job-schedules.update');
        Route::post('job-schedules/{jobSchedule}/unpost-issue', [JobScheduleController::class, 'unpostIssue'])->name('job-schedules.unpost-issue')->middleware('permission:operational.job-schedules-unpost-issue.update,operational.job-schedules.update');
        Route::post('job-schedules/{id}/suspend-room', [JobScheduleController::class, 'suspendRoom'])->name('job-schedules.suspend-room');

        Route::post('job-schedules/{id}/assign-room', [JobScheduleController::class, 'assignRoom'])->name('job-schedules.assign-room');
        Route::get('job-schedules/{id}/check-assignments', [JobScheduleController::class, 'checkAssignments'])->name('job-schedules.check-assignments');
        Route::post('job-schedules/check-bulk-assignments', [JobScheduleController::class, 'checkBulkAssignments'])->name('job-schedules.check-bulk-assignments');

        // Enhanced Job Management Routes
        Route::post('job-schedules/{jobSchedule}/assign-team', [JobScheduleController::class, 'assignToTeam'])->name('job-schedules.assign-team')->middleware('permission:operational.job-schedules-assign-team.update,operational.job-schedules.update');
        Route::get('job-schedules/{jobSchedule}/assignments/api', [JobScheduleController::class, 'getAssignments'])->name('job-schedules.assignments.api');
        Route::post('job-assignments/{assignment}/accept', [JobScheduleController::class, 'acceptAssignment'])->name('job-assignments.accept');
        Route::post('job-assignments/{assignment}/start', [JobScheduleController::class, 'startAssignment'])->name('job-assignments.start');
        Route::post('job-assignments/{assignment}/complete', [JobScheduleController::class, 'completeAssignment'])->name('job-assignments.complete');

        // Material Management Routes
        Route::get('job-schedules/{jobSchedule}/materials/api', [JobScheduleController::class, 'getMaterials'])->name('job-schedules.materials.api');
        Route::post('job-schedules/{jobSchedule}/materials', [JobScheduleController::class, 'addMaterial'])->name('job-schedules.materials.add');
        // STUDY CASE B2: Per-room assignment routes
        Route::post('job-schedules/{jobSchedule}/rooms/{roomId}/assignment', [JobScheduleController::class, 'updateRoomAssignment'])->name('job-schedules.rooms.assignment.update');
        Route::delete('job-schedules/{jobSchedule}/rooms/{roomId}/assignment', [JobScheduleController::class, 'removeRoomAssignment'])->name('job-schedules.rooms.assignment.remove');
        Route::post('job-materials/{material}/issue', [JobScheduleController::class, 'issueMaterial'])->name('job-materials.issue');
        Route::post('job-materials/{material}/return', [JobScheduleController::class, 'returnMaterial'])->name('job-materials.return');

        // Periodic Job Management Routes
        Route::post('periodic-jobs', [JobScheduleController::class, 'createPeriodicJob'])->name('periodic-jobs.create');
        Route::get('periodic-jobs', [JobScheduleController::class, 'getPeriodicJobs'])->name('periodic-jobs.index');
        Route::post('periodic-jobs/generate', [JobScheduleController::class, 'generatePeriodicJobs'])->name('periodic-jobs.generate');

        // BA Files Routes (Berita Acara)
        Route::post('job-schedules/{jobSchedule}/ba-files', [JobScheduleController::class, 'uploadBaFile'])->name('job-schedules.ba-files.upload');
        Route::get('job-schedules/{jobSchedule}/ba-files', [JobScheduleController::class, 'getBaFiles'])->name('job-schedules.ba-files.index');
        Route::get('job-schedules/ba-files/{baFile}/preview', [JobScheduleController::class, 'previewBaFile'])->name('job-schedules.ba-files.preview');
        Route::post('job-schedules/ba-files/{baFile}/update-checkbox', [JobScheduleController::class, 'updateBaFileCheckbox'])->name('job-schedules.ba-files.update-checkbox');
        Route::post('job-schedules/ba-files/{baFile}/approve', [JobScheduleController::class, 'approveBaFile'])->name('job-schedules.ba-files.approve');
        Route::post('job-schedules/ba-files/{baFile}/reject', [JobScheduleController::class, 'rejectBaFile'])->name('job-schedules.ba-files.reject');
        Route::delete('job-schedules/ba-files/{baFile}', [JobScheduleController::class, 'deleteBaFile'])->name('job-schedules.ba-files.delete');

        Route::get('force-majeure-dashboard', function () {
            return view('operational.force-majeure-dashboard');
        })->name('force-majeure-dashboard');
        Route::resource('teams', TeamController::class);
        Route::post('teams/bulk-delete', [TeamController::class, 'bulkDelete'])->name('teams.bulk-delete');
        Route::resource('buildings', BuildingController::class);
        Route::post('buildings/bulk-delete', [BuildingController::class, 'bulkDelete'])->name('buildings.bulk-delete');

        // Operational Area Validation API routes
        Route::get('api/check-operational-area/{building}', [BuildingController::class, 'checkOperationalArea'])->name('api.check-operational-area');
        Route::get('api/check-operational-area-by-city/{city}', [BuildingController::class, 'checkOperationalAreaByCity'])->name('api.check-operational-area-by-city');
        Route::get('api/check-operational-area-by-survey/{surveyId}', [BuildingController::class, 'checkOperationalAreaBySurvey'])->name('api.check-operational-area-by-survey');

        // Debug route for buildings index
        Route::get('buildings-debug', function () {
            try {
                \Illuminate\Support\Facades\Log::info('Debug route accessed');
                $buildings = \App\Models\Building::limit(5)->get();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Debug route works',
                    'building_count' => $buildings->count(),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Debug route error', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }
        })->name('buildings.debug');
        Route::get('master-rooms/room-types', [MasterRoomController::class, 'roomTypes'])->name('master-rooms.room-types');
        Route::resource('master-rooms', MasterRoomController::class);
        Route::post('master-rooms/bulk-delete', [MasterRoomController::class, 'bulkDelete'])->name('master-rooms.bulk-delete');
        Route::resource('room-rental-units', RoomRentalUnitController::class);
        Route::resource('job-assign-schedules', JobAssignScheduleController::class);
        Route::post('job-assign-schedules/bulk-delete', [JobAssignScheduleController::class, 'bulkDelete'])->name('job-assign-schedules.bulk-delete');

        // Variant Change Routes for Material Issue (MUST be BEFORE resource route)
        Route::get('job-assign-material-issues/products-by-variant', [JobAssignMaterialIssueController::class, 'getProductsByVariant'])->name('job-assign-material-issues.products-by-variant');
        Route::get('job-assign-material-issues/brand-lines-variants', [JobAssignMaterialIssueController::class, 'getBrandLinesAndVariants'])->name('job-assign-material-issues.brand-lines-variants');

        // Material Copy & Delete Routes (MUST be BEFORE resource route)
        Route::post('job-assign-material-issues/copy-material', [JobAssignMaterialIssueController::class, 'copyMaterial'])->name('job-assign-material-issues.copy-material')->middleware('permission:operational.job-assign-material-issues.update');
        Route::delete('job-assign-material-issues/delete-copied-material/{itemId}', [JobAssignMaterialIssueController::class, 'deleteCopiedMaterial'])->name('job-assign-material-issues.delete-copied-material')->middleware('permission:operational.job-assign-material-issues.update');

        // Qty Issue Autosave & Material Update Routes
        Route::put('job-assign-material-issues/update-qty/{itemId}', [JobAssignMaterialIssueController::class, 'updateQtyIssue'])->name('job-assign-material-issues.update-qty')->middleware('permission:operational.job-assign-material-issues.update');
        Route::put('job-assign-material-issues/update-material/{itemId}', [JobAssignMaterialIssueController::class, 'updateMaterial'])->name('job-assign-material-issues.update-material')->middleware('permission:operational.job-assign-material-issues.update');

        Route::resource('job-assign-material-issues', JobAssignMaterialIssueController::class)->only(['create', 'store'])->middleware('permission:operational.job-assign-material-issues.create');
        Route::resource('job-assign-material-issues', JobAssignMaterialIssueController::class)->only(['edit', 'update'])->middleware('permission:operational.job-assign-material-issues.update');
        Route::resource('job-assign-material-issues', JobAssignMaterialIssueController::class)->only(['index', 'show'])->middleware('permission:operational.job-assign-material-issues.view');
        Route::resource('job-assign-material-issues', JobAssignMaterialIssueController::class)->only(['destroy'])->middleware('permission:operational.job-assign-material-issues.delete');
        Route::post('job-assign-material-issues/bulk-delete', [JobAssignMaterialIssueController::class, 'bulkDelete'])->name('job-assign-material-issues.bulk-delete')->middleware('permission:operational.job-assign-material-issues.delete');
        Route::post('job-assign-material-issues/{jobAssignMaterialIssue}/approve', [JobAssignMaterialIssueController::class, 'approve'])->name('job-assign-material-issues.approve')->middleware('permission:operational.job-assign-material-issues.approve,operational.job-assign-material-issues.update');
        Route::post('job-assign-material-issues/{jobAssignMaterialIssue}/unapprove', [JobAssignMaterialIssueController::class, 'unapprove'])->name('job-assign-material-issues.unapprove')->middleware('permission:operational.job-assign-material-issues.approve,operational.job-assign-material-issues.update');
        Route::post('job-assign-material-issues/submit-issue', [JobAssignMaterialIssueController::class, 'submitIssue'])->name('job-assign-material-issues.submit-issue')->middleware('permission:operational.job-assign-material-issues.update');
        Route::post('job-assign-material-issues/{jobAssignMaterialIssue}/unissue', [JobAssignMaterialIssueController::class, 'unissue'])->name('job-assign-material-issues.unissue')->middleware('permission:operational.job-assign-material-issues.update');
        Route::post('job-assign-material-issues/bulk-unissue', [JobAssignMaterialIssueController::class, 'bulkUnissue'])->name('job-assign-material-issues.bulk-unissue')->middleware('permission:operational.job-assign-material-issues.update');
        Route::get('job-assign-material-issues/job-assign-schedule/{id}/details', [JobAssignMaterialIssueController::class, 'getJobAssignScheduleDetails'])->name('job-assign-material-issues.job-assign-schedule.details');
        Route::post('job-assign-material-issues/{jobAssignMaterialIssue}/request-variant-change', [JobAssignMaterialIssueController::class, 'requestVariantChange'])->name('job-assign-material-issues.request-variant-change');
        Route::post('job-assign-material-issues/{jobAssignMaterialIssue}/approve-variant-change', [JobAssignMaterialIssueController::class, 'approveVariantChange'])->name('job-assign-material-issues.approve-variant-change');
        Route::get('job-assign-material-issues/{jobAssignMaterialIssue}/pending-variant-changes', [JobAssignMaterialIssueController::class, 'getPendingVariantChanges'])->name('job-assign-material-issues.pending-variant-changes');

        // New operational routes
        Route::resource('job-reports', JobReportController::class);
        Route::post('job-reports/bulk-delete', [JobReportController::class, 'bulkDelete'])->name('job-reports.bulk-delete');

        // Mobile API endpoints for job reports
        Route::post('job-reports/validate-qr', [JobReportController::class, 'validateQRCode'])->name('job-reports.validate-qr');
        Route::post('job-reports/get-location', [JobReportController::class, 'getCurrentLocation'])->name('job-reports.get-location');
        Route::post('job-reports/upload-photos', [JobReportController::class, 'uploadPhotos'])->name('job-reports.upload-photos');
        Route::post('job-reports/save-signature', [JobReportController::class, 'saveSignature'])->name('job-reports.save-signature');

        // Mandatory QR Scan endpoints
        Route::post('job-reports/{jobReport}/mandatory-qr-scan', [JobReportController::class, 'processMandatoryQRScan'])->name('job-reports.mandatory-qr-scan');
        Route::get('job-reports/{jobReport}/qr-scan-status', [JobReportController::class, 'getMandatoryQRScanStatus'])->name('job-reports.qr-scan-status');

        Route::get('technician-locations/technicians', [TechnicianLocationController::class, 'getTechnicians'])->name('technician-locations.technicians');
        Route::resource('technician-locations', TechnicianLocationController::class);
        Route::get('technician-locations/latest/{technicianId}', [TechnicianLocationController::class, 'getLatestLocation'])->name('technician-locations.latest');
        Route::get('technician-locations/within-radius', [TechnicianLocationController::class, 'getLocationsWithinRadius'])->name('technician-locations.within-radius');
        Route::get('technician-locations/tracking/{technicianId}', [TechnicianLocationController::class, 'getTechnicianTracking'])->name('technician-locations.tracking');
        Route::get('technician-locations/real-time', [TechnicianLocationController::class, 'getRealTimeLocations'])->name('technician-locations.real-time');
        Route::get('technician-locations/stats', [TechnicianLocationController::class, 'getTrackingStats'])->name('technician-locations.stats');

        // Google Maps Settings
        Route::get('system/google-maps', [App\Http\Controllers\System\GoogleMapsSettingsController::class, 'index'])->name('system.google-maps.index');
        Route::post('api/v1/system/google-maps/settings', [App\Http\Controllers\System\GoogleMapsSettingsController::class, 'saveSettings'])->name('system.google-maps.save');
        Route::post('api/v1/system/google-maps/test', [App\Http\Controllers\System\GoogleMapsSettingsController::class, 'testApiKey'])->name('system.google-maps.test');
        Route::get('api/v1/system/google-maps/api-key', [App\Http\Controllers\System\GoogleMapsSettingsController::class, 'getApiKey'])->name('system.google-maps.api-key');
        Route::post('technician-locations/bulk-delete', [TechnicianLocationController::class, 'bulkDelete'])->name('technician-locations.bulk-delete');

        Route::resource('job-routes', JobRouteController::class);
        Route::resource('service-histories', ServiceHistoryController::class);
        Route::resource('unit-installations', UnitInstallationController::class);
        Route::resource('job-signatures', JobSignatureController::class);
        Route::resource('temperature-records', TemperatureRecordController::class);
    });

    // Finance Routes
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', function () {
            return view('finance.dashboard');
        })->name('dashboard');
        Route::resource('invoices', InvoiceController::class)->middleware('permission:finance.invoices.view');
        Route::post('invoices/{invoice}/email', [InvoiceController::class, 'emailInvoice'])->name('finance.invoices.email')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/rental-details/{rentalDetail}/update-price', [InvoiceController::class, 'updateRentalPrice'])->name('finance.invoices.update-rental-price');
        Route::post('invoices/{invoice}/update-discount', [InvoiceController::class, 'updateDiscount'])->name('finance.invoices.update-discount');
        Route::post('invoices/{invoice}/update-notes', [InvoiceController::class, 'updateNotes'])->name('finance.invoices.update-notes');
        Route::post('invoices/{invoice}/update-internal-notes', [InvoiceController::class, 'updateInternalNotes'])->name('finance.invoices.update-internal-notes');
        Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export')->middleware('permission:finance.invoices.view');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/regenerate', [InvoiceController::class, 'regenerate'])->name('invoices.regenerate')->middleware('permission:finance.invoices.create');
        Route::post('invoices/regenerate-missing', [InvoiceController::class, 'regenerateMissing'])->name('invoices.regenerate-missing')->middleware('permission:finance.invoices.create');
        Route::post('invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])->name('invoices.bulk-delete')->middleware('permission:finance.invoices.delete');
        Route::post('invoices/bulk-send', [InvoiceController::class, 'bulkSend'])->name('invoices.bulk-send')->middleware('permission:finance.invoices.update');
        Route::post('invoices/auto-generate', [InvoiceController::class, 'autoGenerateInvoice'])->name('invoices.auto-generate')->middleware('permission:finance.invoices.create');
        Route::get('invoices/contracts-ready', [InvoiceController::class, 'getContractsReadyForInvoice'])->name('invoices.contracts-ready')->middleware('permission:finance.invoices.view');
        Route::post('invoices/{invoice}/update-delivery', [InvoiceController::class, 'updateDeliveryInfo'])->name('invoices.update-delivery')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/cancel-faktur', [InvoiceController::class, 'cancelFakturPajak'])->name('invoices.cancel-faktur')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/upload', [InvoiceController::class, 'uploadFile'])->name('invoices.upload');
        Route::get('invoices/files/download-attachment', [InvoiceController::class, 'downloadFile'])->name('invoices.download-attachment');
        Route::match(['get', 'post'], 'invoices/{invoice}/download-combined', [InvoiceController::class, 'downloadCombined'])->name('invoices.download-combined')->middleware('permission:finance.invoices.view');
        Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/tax-approve', [InvoiceController::class, 'taxApprove'])->name('invoices.tax-approve')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/update-date-preference', [InvoiceController::class, 'updateDatePreference'])->name('invoices.update-date-preference')->middleware('permission:finance.invoices.update');
        Route::post('invoices/{invoice}/reload-tax', [InvoiceController::class, 'reloadTaxData'])->name('invoices.reload-tax')->middleware('permission:finance.invoices.update');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'printInvoice'])->name('invoices.print')->middleware('permission:finance.invoices.view');
        Route::get('invoices/{invoice}/delivery-receipt', [InvoiceController::class, 'exportDeliveryReceipt'])->name('invoices.delivery-receipt')->middleware('permission:finance.invoices.view');
        // Invoice Generation Enhancement routes
        Route::post('invoices/auto-generate-rental-period', [InvoiceController::class, 'autoGenerateInvoiceForRentalPeriod'])->name('invoices.auto-generate-rental-period')->middleware('permission:finance.invoices.create');
        Route::get('invoices/rental-periods/{contractId}', [InvoiceController::class, 'getRentalPeriods'])->name('invoices.rental-periods')->middleware('permission:finance.invoices.view');
        Route::post('invoices/generate-multiple-periods', [InvoiceController::class, 'generateInvoicesForMultiplePeriods'])->name('invoices.generate-multiple-periods')->middleware('permission:finance.invoices.create');
        Route::resource('invoice-follow-ups', InvoiceFollowUpController::class)->middleware('permission:invoice-follow-ups.view');
        Route::post('invoice-follow-ups/bulk-delete', [InvoiceFollowUpController::class, 'bulkDelete'])->name('invoice-follow-ups.bulk-delete')->middleware('permission:invoice-follow-ups.delete');
        Route::get('invoice-follow-ups/invoice/{invoiceId}', [InvoiceFollowUpController::class, 'getInvoiceFollowUps'])->name('invoice-follow-ups.get-by-invoice')->middleware('permission:invoice-follow-ups.view');
        Route::get('invoice-follow-ups/create-for-invoice/{invoiceId}', [InvoiceFollowUpController::class, 'createForInvoice'])->name('invoice-follow-ups.create-for-invoice')->middleware('permission:invoice-follow-ups.create');
        Route::post('invoice-follow-ups/store-for-invoice/{invoiceId}', [InvoiceFollowUpController::class, 'storeForInvoice'])->name('invoice-follow-ups.store-for-invoice')->middleware('permission:invoice-follow-ups.create');

        // Invoice Forms routes
        Route::resource('invoice-forms', InvoiceFormController::class)->middleware('permission:finance.invoice-forms.view');
        Route::post('invoice-forms/bulk-delete', [InvoiceFormController::class, 'bulkDelete'])->name('invoice-forms.bulk-delete')->middleware('permission:finance.invoice-forms.delete');
        Route::post('invoice-forms/bulk-approve', [InvoiceFormController::class, 'bulkApprove'])->name('invoice-forms.bulk-approve')->middleware('permission:finance.invoice-forms.update');
        Route::get('invoice-forms/export', [InvoiceFormController::class, 'export'])->name('invoice-forms.export')->middleware('permission:finance.invoice-forms.view');
        Route::post('invoice-forms/{invoiceForm}/submit', [InvoiceFormController::class, 'submit'])->name('invoice-forms.submit')->middleware('permission:finance.invoice-forms.update');
        Route::post('invoice-forms/{invoiceForm}/approve', [InvoiceFormController::class, 'approve'])->name('invoice-forms.approve')->middleware('permission:finance.invoice-forms.update');
        Route::post('invoice-forms/{invoiceForm}/reject', [InvoiceFormController::class, 'reject'])->name('invoice-forms.reject')->middleware('permission:finance.invoice-forms.update');
        Route::post('invoice-forms/{invoiceForm}/draft', [InvoiceFormController::class, 'draft'])->name('invoice-forms.draft')->middleware('permission:finance.invoice-forms.update');

        // Bank Receipts with additional routes
        Route::resource('bank-receipts', BankReceiptController::class)->middleware('permission:finance.bank-receipts.view');
        Route::post('bank-receipts/{bankReceipt}/verify', [BankReceiptController::class, 'verify'])->name('bank-receipts.verify')->middleware('permission:finance.bank-receipts.update');
        Route::post('bank-receipts/{bankReceipt}/reject', [BankReceiptController::class, 'reject'])->name('bank-receipts.reject')->middleware('permission:finance.bank-receipts.update');
        Route::post('bank-receipts/{bankReceipt}/process', [BankReceiptController::class, 'process'])->name('bank-receipts.process')->middleware('permission:finance.bank-receipts.update');
        Route::post('bank-receipts/bulk-verify', [BankReceiptController::class, 'bulkVerify'])->name('bank-receipts.bulk-verify')->middleware('permission:finance.bank-receipts.update');
        Route::post('bank-receipts/bulk-delete', [BankReceiptController::class, 'bulkDelete'])->name('bank-receipts.bulk-delete')->middleware('permission:finance.bank-receipts.delete');
        Route::get('bank-receipts/export', [BankReceiptController::class, 'export'])->name('bank-receipts.export')->middleware('permission:bank-receipts.view');
        Route::get('bank-receipts/statistics', [BankReceiptController::class, 'statistics'])->name('bank-receipts.statistics')->middleware('permission:bank-receipts.view');

        // Virtual Account Imports with additional routes
        Route::resource('virtual-account-imports', VirtualAccountImportController::class);
        Route::post('virtual-account-imports/{virtualAccountImport}/process', [VirtualAccountImportController::class, 'process'])->name('virtual-account-imports.process');
        Route::post('virtual-account-imports/{virtualAccountImport}/retry', [VirtualAccountImportController::class, 'retry'])->name('virtual-account-imports.retry');
        Route::post('virtual-account-imports/bulk-process', [VirtualAccountImportController::class, 'bulkProcess'])->name('virtual-account-imports.bulk-process');
        Route::post('virtual-account-imports/bulk-delete', [VirtualAccountImportController::class, 'bulkDelete'])->name('virtual-account-imports.bulk-delete');
        Route::get('virtual-account-imports/export', [VirtualAccountImportController::class, 'export'])->name('virtual-account-imports.export');
        Route::get('virtual-account-imports/statistics', [VirtualAccountImportController::class, 'statistics'])->name('virtual-account-imports.statistics');
        Route::get('virtual-account-imports/{virtualAccountImport}/download', [VirtualAccountImportController::class, 'download'])->name('virtual-account-imports.download');
        Route::get('virtual-account-imports/{virtualAccountImport}/preview', [VirtualAccountImportController::class, 'preview'])->name('virtual-account-imports.preview');
        Route::get('virtual-account-imports/{virtualAccountImport}/errors', [VirtualAccountImportController::class, 'errors'])->name('virtual-account-imports.errors');

        // API endpoints for modal system
        Route::get('virtual-account-imports/{virtualAccountImport}/data', [VirtualAccountImportController::class, 'getImportData'])->name('virtual-account-imports.data');
        Route::post('virtual-account-imports/api', [VirtualAccountImportController::class, 'storeApi'])->name('virtual-account-imports.store-api');
        Route::put('virtual-account-imports/{virtualAccountImport}/api', [VirtualAccountImportController::class, 'updateApi'])->name('virtual-account-imports.update-api');
        Route::resource('virtual-account-exports', VirtualAccountExportController::class);
        Route::post('virtual-account-exports/{virtualAccountExport}/process', [VirtualAccountExportController::class, 'process'])->name('virtual-account-exports.process');
        Route::post('virtual-account-exports/{virtualAccountExport}/retry', [VirtualAccountExportController::class, 'retry'])->name('virtual-account-exports.retry');
        Route::post('virtual-account-exports/bulk-process', [VirtualAccountExportController::class, 'bulkProcess'])->name('virtual-account-exports.bulk-process');
        Route::post('virtual-account-exports/bulk-delete', [VirtualAccountExportController::class, 'bulkDelete'])->name('virtual-account-exports.bulk-delete');
        Route::get('virtual-account-exports/export', [VirtualAccountExportController::class, 'export'])->name('virtual-account-exports.export');
        Route::get('virtual-account-exports/statistics', [VirtualAccountExportController::class, 'statistics'])->name('virtual-account-exports.statistics');
        Route::get('virtual-account-exports/trends', [VirtualAccountExportController::class, 'trends'])->name('virtual-account-exports.trends');
        Route::get('virtual-account-exports/summary-by-status', [VirtualAccountExportController::class, 'summaryByStatus'])->name('virtual-account-exports.summary-by-status');
        Route::get('virtual-account-exports/summary-by-bank', [VirtualAccountExportController::class, 'summaryByBank'])->name('virtual-account-exports.summary-by-bank');
        Route::get('virtual-account-exports/{virtualAccountExport}/download', [VirtualAccountExportController::class, 'download'])->name('virtual-account-exports.download');
        Route::resource('tax-settings', TaxSettingController::class)->middleware('permission:tax-settings.view');
        Route::get('tax-codes', [TaxCodeController::class, 'index'])->name('tax-codes.index')->middleware('permission:finance.tax-codes.view');
        Route::put('tax-codes/{taxCode}', [TaxCodeController::class, 'update'])->name('tax-codes.update')->middleware('permission:finance.tax-codes.edit');
        Route::post('tax-settings/{taxSetting}/activate', [TaxSettingController::class, 'activate'])->name('tax-settings.activate')->middleware('permission:tax-settings.edit');
        Route::post('tax-settings/{taxSetting}/deactivate', [TaxSettingController::class, 'deactivate'])->name('tax-settings.deactivate')->middleware('permission:tax-settings.edit');
        Route::post('tax-settings/bulk-activate', [TaxSettingController::class, 'bulkActivate'])->name('tax-settings.bulk-activate')->middleware('permission:tax-settings.edit');
        Route::post('tax-settings/bulk-deactivate', [TaxSettingController::class, 'bulkDeactivate'])->name('tax-settings.bulk-deactivate')->middleware('permission:tax-settings.edit');
        Route::post('tax-settings/bulk-delete', [TaxSettingController::class, 'bulkDelete'])->name('tax-settings.bulk-delete')->middleware('permission:tax-settings.delete');
        Route::get('tax-settings/export', [TaxSettingController::class, 'export'])->name('tax-settings.export')->middleware('permission:tax-settings.view');
        Route::get('tax-settings/statistics', [TaxSettingController::class, 'statistics'])->name('tax-settings.statistics')->middleware('permission:tax-settings.view');
        Route::get('tax-settings/trends', [TaxSettingController::class, 'trends'])->name('tax-settings.trends')->middleware('permission:tax-settings.view');
        Route::get('tax-settings/summary-by-type', [TaxSettingController::class, 'summaryByType'])->name('tax-settings.summary-by-type')->middleware('permission:tax-settings.view');
        Route::get('tax-settings/summary-by-status', [TaxSettingController::class, 'summaryByStatus'])->name('tax-settings.summary-by-status')->middleware('permission:tax-settings.view');
        Route::resource('tax-file-imports', TaxFileImportController::class);
        Route::post('tax-file-imports/bulk-delete', [TaxFileImportController::class, 'bulkDelete'])->name('tax-file-imports.bulk-delete');
        Route::post('tax-file-imports/{taxFileImport}/process', [TaxFileImportController::class, 'processImport'])->name('tax-file-imports.process');
        Route::get('tax-file-imports/{taxFileImport}/download', [TaxFileImportController::class, 'downloadFile'])->name('tax-file-imports.download');
        Route::get('tax-file-imports/{taxFileImport}/error-log', [TaxFileImportController::class, 'downloadErrorLog'])->name('tax-file-imports.error-log');
        Route::resource('tax-file-exports', TaxFileExportController::class);
        Route::post('tax-file-exports/bulk-delete', [TaxFileExportController::class, 'bulkDelete'])->name('tax-file-exports.bulk-delete');
        Route::post('tax-file-exports/{taxFileExport}/generate-espt', [TaxFileExportController::class, 'generateESPTExport'])->name('tax-file-exports.generate-espt');

        // Tax Management routes for new models
        Route::resource('tax-invoices', TaxInvoiceController::class);
        Route::post('tax-invoices/bulk-delete', [TaxInvoiceController::class, 'bulkDelete'])->name('tax-invoices.bulk-delete');
        Route::post('tax-invoices/{taxInvoice}/apply-e-materai', [TaxInvoiceController::class, 'applyEMaterai'])->name('tax-invoices.apply-e-materai');

        Route::resource('tax-reports', TaxReportController::class);
        Route::post('tax-reports/bulk-delete', [TaxReportController::class, 'bulkDelete'])->name('tax-reports.bulk-delete');
        Route::post('tax-reports/{taxReport}/generate', [TaxReportController::class, 'generateReport'])->name('tax-reports.generate');

        Route::resource('e-materai-transactions', EMateraiTransactionController::class);
        Route::post('e-materai-transactions/bulk-delete', [EMateraiTransactionController::class, 'bulkDelete'])->name('e-materai-transactions.bulk-delete');
        Route::post('e-materai-transactions/{eMateraiTransaction}/retry', [EMateraiTransactionController::class, 'retry'])->name('e-materai-transactions.retry');

        Route::resource('faktur-pajak', FakturPajakController::class)->middleware('permission:faktur-pajak.view');
        Route::get('faktur-pajak/export', [FakturPajakController::class, 'export'])->name('faktur-pajak.export')->middleware('permission:faktur-pajak.view');
        Route::post('faktur-pajak/{fakturPajak}/submit', [FakturPajakController::class, 'submit'])->name('faktur-pajak.submit')->middleware('permission:faktur-pajak.edit');
        Route::post('faktur-pajak/{fakturPajak}/approve', [FakturPajakController::class, 'approve'])->name('faktur-pajak.approve')->middleware('permission:faktur-pajak.edit');
        Route::post('faktur-pajak/{fakturPajak}/reject', [FakturPajakController::class, 'reject'])->name('faktur-pajak.reject')->middleware('permission:faktur-pajak.edit');
        Route::post('faktur-pajak/{fakturPajak}/draft', [FakturPajakController::class, 'draft'])->name('faktur-pajak.draft')->middleware('permission:faktur-pajak.edit');

        // New Finance routes
        Route::resource('sales-commissions', SalesCommissionController::class);

        // Billing Groups - Moved to Contract Management (accessed via Contract detail page)
        // Route::resource('billing-groups', BillingGroupController::class)->middleware('permission:billing-groups.view');

        // Billing Group from Contract routes
        Route::get('contracts/{contract}/billing-groups/add', [BillingGroupController::class, 'createForContract'])->name('billing-groups.add');
        Route::post('contracts/{contract}/billing-groups/store', [BillingGroupController::class, 'storeForContract'])->name('billing-groups.store-for-contract');
        Route::get('contracts/{contract}/billing-groups/{billingGroup}/edit', [BillingGroupController::class, 'editForContract'])->name('billing-groups.edit-for-contract');
        Route::put('billing-groups/{billingGroup}', [BillingGroupController::class, 'update'])->name('billing-groups.update');
        Route::delete('billing-groups/{billingGroup}', [BillingGroupController::class, 'destroy'])->name('billing-groups.destroy');

        // // Billing Group Building Management Routes
        // Route::get('billing-groups/{billingGroup}/buildings', [BillingGroupController::class, 'getBuildings'])->name('billing-groups.buildings');
        // Route::post('billing-groups/{billingGroup}/buildings', [BillingGroupController::class, 'assignBuilding'])->name('billing-groups.buildings.assign');
        // Route::delete('billing-groups/{billingGroup}/buildings/{building}', [BillingGroupController::class, 'removeBuilding'])->name('billing-groups.buildings.remove');
        // Route::put('billing-groups/{billingGroup}/buildings/{building}', [BillingGroupController::class, 'updateBuildingAssignment'])->name('billing-groups.buildings.update');

        // // Billing Group Enhanced Features Routes
        // Route::get('billing-groups/va-list', [BillingGroupController::class, 'getVirtualAccountList'])->name('billing-groups.va-list');
        // Route::post('billing-groups/generate-va', [BillingGroupController::class, 'generateVirtualAccount'])->name('billing-groups.generate-va');
        // Route::post('billing-groups/validate-va', [BillingGroupController::class, 'validateVirtualAccount'])->name('billing-groups.validate-va');
        // Route::post('billing-groups/validate-top', [BillingGroupController::class, 'validateTopSynchronization'])->name('billing-groups.validate-top');
        // Route::post('billing-groups/{billingGroup}/generate-invoices', [BillingGroupController::class, 'generateInvoicesForPeriod'])->name('billing-groups.generate-invoices');
        // // VA Rule Enhancement routes
        // Route::post('billing-groups/generate-va-number', [BillingGroupController::class, 'generateVirtualAccountNumber'])->name('billing-groups.generate-va-number');
        // Route::post('billing-groups/validate-va-number', [BillingGroupController::class, 'validateVirtualAccountNumber'])->name('billing-groups.validate-va-number');
        // Route::get('billing-groups/available-va-numbers', [BillingGroupController::class, 'getAvailableVirtualAccounts'])->name('billing-groups.available-va-numbers');
        // Route::get('billing-groups/va-statistics', [BillingGroupController::class, 'getVirtualAccountStatistics'])->name('billing-groups.va-statistics');
        // Route::post('billing-groups/reserve-va-number', [BillingGroupController::class, 'reserveVirtualAccountNumber'])->name('billing-groups.reserve-va-number');
        Route::resource('payment-methods', PaymentMethodController::class);
        Route::resource('currencies', CurrencyController::class);
        Route::resource('financial-periods', FinancialPeriodController::class);
        Route::resource('cost-centers', CostCenterController::class);
        Route::resource('commission-withdrawals', CommissionWithdrawalController::class);
        Route::resource('finance-logs', FinanceLogController::class);

        // Commission System Routes
        Route::get('commissions/statistics', [App\Http\Controllers\Finance\CommissionController::class, 'statistics'])->name('commissions.statistics');
        Route::resource('commissions', App\Http\Controllers\Finance\CommissionController::class);
        Route::post('commissions/{commission}/approve', [App\Http\Controllers\Finance\CommissionController::class, 'approve'])->name('commissions.approve');
        Route::post('commissions/{commission}/mark-paid', [App\Http\Controllers\Finance\CommissionController::class, 'markAsPaid'])->name('commissions.mark-paid');
        Route::post('commissions/{commission}/cancel', [App\Http\Controllers\Finance\CommissionController::class, 'cancel'])->name('commissions.cancel');

        // Achievement System Routes
        Route::get('achievements/statistics', [App\Http\Controllers\Finance\AchievementController::class, 'statistics'])->name('achievements.statistics');
        Route::get('achievements/performance-report', [App\Http\Controllers\Finance\AchievementController::class, 'performanceReport'])->name('achievements.performance-report');
        Route::resource('achievements', App\Http\Controllers\Finance\AchievementController::class);

        // Achievement Period Routes
        Route::resource('achievement-periods', App\Http\Controllers\Finance\AchievementPeriodController::class);

        // Commission Payment Routes
        Route::resource('commission-payments', App\Http\Controllers\Finance\CommissionPaymentController::class);
        Route::post('commission-payments/{commissionPayment}/mark-processing', [App\Http\Controllers\Finance\CommissionPaymentController::class, 'markAsProcessing'])->name('commission-payments.mark-processing');
        Route::post('commission-payments/{commissionPayment}/mark-completed', [App\Http\Controllers\Finance\CommissionPaymentController::class, 'markAsCompleted'])->name('commission-payments.mark-completed');
        Route::post('commission-payments/{commissionPayment}/mark-failed', [App\Http\Controllers\Finance\CommissionPaymentController::class, 'markAsFailed'])->name('commission-payments.mark-failed');
        Route::post('commission-payments/{commissionPayment}/cancel', [App\Http\Controllers\Finance\CommissionPaymentController::class, 'cancel'])->name('commission-payments.cancel');

        // Commission System Routes
        Route::resource('commission-levels', App\Http\Controllers\Finance\CommissionLevelController::class);
        Route::resource('marketing-levels', App\Http\Controllers\Finance\MarketingLevelController::class);
        Route::resource('cr-variables', App\Http\Controllers\Finance\CrVariableController::class);
        Route::post('cr-variables/{crVariable}/set-default', [App\Http\Controllers\Finance\CrVariableController::class, 'setDefault'])->name('cr-variables.set-default');

        Route::resource('marketing-targets', App\Http\Controllers\Finance\MarketingTargetController::class);
        Route::post('marketing-targets/{marketingTarget}/lock', [App\Http\Controllers\Finance\MarketingTargetController::class, 'lock'])->name('marketing-targets.lock');
        Route::post('marketing-targets/{marketingTarget}/unlock', [App\Http\Controllers\Finance\MarketingTargetController::class, 'unlock'])->name('marketing-targets.unlock');

        Route::resource('renewal-contract-assignments', App\Http\Controllers\Finance\RenewalContractAssignmentController::class);
        Route::post('renewal-contract-assignments/{renewalContractAssignment}/lock', [App\Http\Controllers\Finance\RenewalContractAssignmentController::class, 'lock'])->name('renewal-contract-assignments.lock');
        Route::post('renewal-contract-assignments/{renewalContractAssignment}/unlock', [App\Http\Controllers\Finance\RenewalContractAssignmentController::class, 'unlock'])->name('renewal-contract-assignments.unlock');

        Route::resource('commission-transfers', App\Http\Controllers\Finance\CommissionTransferController::class);
        Route::post('commission-transfers/{commissionTransfer}/approve', [App\Http\Controllers\Finance\CommissionTransferController::class, 'approve'])->name('commission-transfers.approve');
        Route::post('commission-transfers/{commissionTransfer}/reject', [App\Http\Controllers\Finance\CommissionTransferController::class, 'reject'])->name('commission-transfers.reject');
        Route::get('commission-transfers/calculations/{contractId}', [App\Http\Controllers\Finance\CommissionTransferController::class, 'getCalculationsByContract'])->name('commission-transfers.calculations');
        Route::get('commission-transfers/contracts/{userId}', [App\Http\Controllers\Finance\CommissionTransferController::class, 'getContractsByUser'])->name('commission-transfers.contracts');
    });

    // Warehouse Routes
    Route::get('api/warehouses', [WarehouseController::class, 'getWarehouses'])->name('api.warehouses');
    Route::get('api/branches', [BranchController::class, 'getBranches'])->name('api.branches');
    Route::get('api/users', [UserController::class, 'getUsers'])->name('api.users');
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', function () {
            return view('warehouse.dashboard');
        })->name('dashboard');
        Route::post('inventory-issuings/{id}/process', [InventoryIssuingController::class, 'process'])->name('inventory-issuings.process')->middleware('permission:warehouse.inventory-issuings.update');
        Route::post('inventory-issuings/{id}/draft', [InventoryIssuingController::class, 'draft'])->name('inventory-issuings.draft')->middleware('permission:warehouse.inventory-issuings.update');
        Route::post('inventory-issuings/{id}/finalize', [InventoryIssuingController::class, 'finalize'])->name('inventory-issuings.finalize')->middleware('permission:warehouse.inventory-issuings.update');
        Route::post('inventory-issuings/{id}/unpost', [InventoryIssuingController::class, 'unpost'])->name('inventory-issuings.unpost')->middleware('permission:warehouse.inventory-issuings.update');
        Route::post('inventory-issuings/{id}/scan-serial-number', [InventoryIssuingController::class, 'scanSerialNumber'])->name('inventory-issuings.scan-serial-number')->middleware('permission:warehouse.inventory-issuings.update');

        // Change Aroma shortcut (Top Management only) — bypasses the Marketing > Aroma
        // Switching approval workflow, so it's gated by a dedicated permission rather than
        // the general inventory-issuings.update permission.
        Route::get('inventory-issuings/get-replacement-candidates', [InventoryIssuingController::class, 'getReplacementCandidates'])->name('inventory-issuings.get-replacement-candidates');
        Route::post('inventory-issuings/items/{item}/change-aroma', [InventoryIssuingController::class, 'changeAroma'])->name('inventory-issuings.change-aroma')->middleware('permission:warehouse.inventory-issuings.change-aroma-direct');

        // Manual Inventory Issuing API Routes
        Route::post('inventory-issuings/store-manual', [InventoryIssuingController::class, 'storeManual'])->name('inventory-issuings.store-manual')->middleware('permission:warehouse.inventory-issuings.create');
        Route::get('inventory-issuings/rentals/{rentalId}/products', [InventoryIssuingController::class, 'getRentalProducts'])->name('inventory-issuings.rental-products');
        Route::get('inventory-issuings/products/{productId}/serials', [InventoryIssuingController::class, 'getAvailableSerials'])->name('inventory-issuings.available-serials');
        Route::get('inventory-issuings/users/{userId}/teams', [InventoryIssuingController::class, 'getUserTeams'])->name('inventory-issuings.user-teams');
        Route::get('inventory-issuings/team-data/{team}/members', [InventoryIssuingController::class, 'getTeamMembers'])->name('inventory-issuings.team-members');

        Route::post('inventory-issuings/{id}/send', [InventoryIssuingController::class, 'send'])->name('inventory-issuings.send');
        Route::post('inventory-issuings/{id}/receive', [InventoryIssuingController::class, 'receive'])->name('inventory-issuings.receive');
        Route::get('inventory-issuings/all-products', [InventoryIssuingController::class, 'getAllProducts'])->name('inventory-issuings.all-products');
        Route::post('inventory-issuings/{id}/add-item', [InventoryIssuingController::class, 'addItem'])->name('inventory-issuings.add-item');
        Route::delete('inventory-issuings/{id}/delete-item/{itemId}', [InventoryIssuingController::class, 'deleteItem'])->name('inventory-issuings.delete-item');
        Route::get('inventory-issuings/modal-data', [InventoryIssuingController::class, 'getModalData'])->name('inventory-issuings.modal-data');
        Route::get('inventory-issuings/{id}/details', [InventoryIssuingController::class, 'getIssuingDetails'])->name('inventory-issuings.details');

        Route::resource('inventory-issuings', InventoryIssuingController::class)->except(['create', 'edit'])->middleware('permission:warehouse.inventory-issuings.view');
        Route::resource('inventory-receivings', InventoryReceivingController::class)->middleware('permission:warehouse.inventory-receivings.view');
        Route::post('inventory-receivings/{inventoryReceiving}/finalize', [InventoryReceivingController::class, 'finalize'])->name('inventory-receivings.finalize')->middleware('permission:warehouse.inventory-receivings.update');
        Route::post('inventory-receivings/{inventoryReceiving}/scan-serial-number', [InventoryReceivingController::class, 'scanSerialNumber'])->name('inventory-receivings.scan-serial-number')->middleware('permission:warehouse.inventory-receivings.update');
        Route::post('inventory-receivings/{inventoryReceiving}/update-item-quantity', [InventoryReceivingController::class, 'updateItemQuantity'])->name('inventory-receivings.update-item-quantity')->middleware('permission:warehouse.inventory-receivings.update');
        Route::post('inventory-receivings/{inventoryReceiving}/delete-serial-number', [InventoryReceivingController::class, 'deleteSerialNumber'])->name('inventory-receivings.delete-serial-number')->middleware('permission:warehouse.inventory-receivings.delete');
        Route::get('inventory-receivings/modal-data', [InventoryReceivingController::class, 'getModalData'])->name('inventory-receivings.modal-data')->middleware('permission:warehouse.inventory-receivings.view');
        Route::get('inventory-requests/import-template', [InventoryRequestImportController::class, 'template'])->name('inventory-requests.import-template')->middleware('permission:warehouse.inventory-requests.create');
        Route::post('inventory-requests/import-preview', [InventoryRequestImportController::class, 'preview'])->name('inventory-requests.import-preview')->middleware('permission:warehouse.inventory-requests.create');
        Route::post('inventory-requests/import', [InventoryRequestImportController::class, 'import'])->name('inventory-requests.import')->middleware('permission:warehouse.inventory-requests.create');
        Route::resource('inventory-requests', InventoryRequestController::class)->middleware('permission:warehouse.inventory-requests.view');
        Route::post('inventory-requests/{inventoryRequest}/approve', [InventoryRequestController::class, 'approve'])->name('inventory-requests.approve')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/{inventoryRequest}/reject', [InventoryRequestController::class, 'reject'])->name('inventory-requests.reject')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/{inventoryRequest}/assign-warehouse', [InventoryRequestController::class, 'assignWarehouse'])->name('inventory-requests.assign-warehouse')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/{inventoryRequest}/complete-shipping', [InventoryRequestController::class, 'completeShipping'])->name('inventory-requests.complete-shipping')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/{inventoryRequest}/complete-issue', [InventoryRequestController::class, 'completeIssue'])->name('inventory-requests.complete-issue')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/items/{itemId}/update-qty', [InventoryRequestController::class, 'updateItemQty'])->name('inventory-requests.items.update-qty')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/{inventoryRequest}/bulk-update-qty', [InventoryRequestController::class, 'bulkUpdateItemQty'])->name('inventory-requests.bulk-update-qty')->middleware('permission:warehouse.inventory-requests.update');
        Route::post('inventory-requests/{inventoryRequest}/back-to-pending', [InventoryRequestController::class, 'backToPending'])->name('inventory-requests.back-to-pending')->middleware('permission:warehouse.inventory-requests.update');
        Route::get('inventory-requests/{inventoryRequest}/available-products', [InventoryRequestController::class, 'getAvailableProducts'])->name('inventory-requests.available-products')->middleware('permission:warehouse.inventory-requests.view');
        Route::post('inventory-requests/{inventoryRequest}/add-item', [InventoryRequestController::class, 'addItem'])->name('inventory-requests.add-item')->middleware('permission:warehouse.inventory-requests.update');
        Route::delete('inventory-requests/{inventoryRequest}/remove-item/{itemId}', [InventoryRequestController::class, 'removeItem'])->name('inventory-requests.remove-item')->middleware('permission:warehouse.inventory-requests.update');

        // Inventory Transfer API routes (must be before main route to avoid conflicts)
        Route::get('inventory-transfers/api/warehouses', [InventoryController::class, 'getWarehouses'])->name('inventory-transfers.api.warehouses');
        Route::get('inventory-transfers/api/transfer-warehouses/{fromWarehouseId?}', [InventoryController::class, 'getTransferWarehouses'])->name('inventory-transfers.api.transfer-warehouses');
        Route::get('inventory-transfers/api/products/{warehouseId}', [InventoryController::class, 'getProductsWithStock'])->name('inventory-transfers.api.products');
        Route::get('inventory-transfers/api/users', [InventoryController::class, 'getUsers'])->name('inventory-transfers.api.users');
        Route::get('inventory-transfers/api/get-transfer/{id}', [InventoryController::class, 'getTransfer'])->name('inventory-transfers.api.get-transfer');
        Route::post('inventory-transfers/api/store', [InventoryController::class, 'storeTransfer'])->name('inventory-transfers.api.store');
        Route::put('inventory-transfers/api/{id}/update', [InventoryController::class, 'updateTransfer'])->name('inventory-transfers.api.update');
        Route::delete('inventory-transfers/api/{id}/delete', [InventoryController::class, 'deleteTransfer'])->name('inventory-transfers.api.delete');
        Route::post('inventory-transfers/api/bulk-delete', [InventoryController::class, 'bulkDeleteTransfers'])->name('inventory-transfers.api.bulk-delete');

        Route::resource('product-types', ProductTypeController::class)->middleware('permission:warehouse.product-types.view');
        Route::post('product-types/bulk-delete', [ProductTypeController::class, 'bulkDelete'])->name('product-types.bulk-delete')->middleware('permission:warehouse.product-types.delete');
        Route::post('product-types/{productType}/toggle-status', [ProductTypeController::class, 'toggleStatus'])->name('product-types.toggle-status')->middleware('permission:warehouse.product-types.update');
        Route::get('product-types/statistics', [ProductTypeController::class, 'getProductTypeStatistics'])->name('product-types.statistics')->middleware('permission:warehouse.product-types.view');
        Route::get('product-types/search', [ProductTypeController::class, 'searchProductTypes'])->name('product-types.search')->middleware('permission:warehouse.product-types.view');
        Route::get('product-categories', [ProductTypeController::class, 'getProductCategories'])->name('product-categories')->middleware('permission:warehouse.product-types.view');
        Route::post('product-types/update-categories', [ProductTypeController::class, 'updateCategories'])->name('product-types.update-categories')->middleware('permission:warehouse.product-types.update');

        // Brand Variants (Master Data)
        Route::get('brand-variants/data', [\App\Http\Controllers\Warehouse\BrandVariantController::class, 'data'])->name('brand-variants.data')->middleware('permission:warehouse.variant.view');
        Route::get('brand-variants/by-brand-line', [\App\Http\Controllers\Warehouse\BrandVariantController::class, 'getByBrandLine'])->name('brand-variants.by-brand-line')->middleware('permission:warehouse.variant.view');
        Route::resource('brand-variants', \App\Http\Controllers\Warehouse\BrandVariantController::class)->middleware('permission:warehouse.variant.view');

        // Master Products - specific routes MUST come before resource route to prevent shadowing
        Route::get('master-products/statistics', [MasterProductController::class, 'getProductStatistics'])->name('master-products.statistics')->middleware('permission:warehouse.master-products.view');
        Route::get('master-products/by-type', [MasterProductController::class, 'getProductsByType'])->name('master-products.by-type')->middleware('permission:warehouse.master-products.view');
        Route::get('master-products/search', [MasterProductController::class, 'searchProducts'])->name('master-products.search')->middleware('permission:warehouse.master-products.view');
        Route::get('master-products/product-types-by-category', [MasterProductController::class, 'getProductTypesByCategory'])->name('master-products.product-types-by-category')->middleware('permission:warehouse.master-products.view');
        Route::get('master-products/import-template', [\App\Http\Controllers\Warehouse\MasterProductImportController::class, 'template'])->name('master-products.import-template')->middleware('permission:warehouse.master-products.create');
        Route::post('master-products/import-preview', [\App\Http\Controllers\Warehouse\MasterProductImportController::class, 'preview'])->name('master-products.import-preview')->middleware('permission:warehouse.master-products.view');
        Route::post('master-products/import', [\App\Http\Controllers\Warehouse\MasterProductImportController::class, 'import'])->name('master-products.import')->middleware('permission:warehouse.master-products.create');
        Route::post('master-products/bulk-delete', [MasterProductController::class, 'bulkDelete'])->name('master-products.bulk-delete')->middleware('permission:warehouse.master-products.delete');
        Route::get('master-products/test-upload', function () {
            return view('warehouse.master-products.test-upload');
        })->name('master-products.test-upload');

        // Master Products resource route (must come AFTER specific routes)
        Route::resource('master-products', MasterProductController::class)->middleware('permission:warehouse.master-products.view');
        Route::post('master-products/{masterProduct}/toggle-status', [MasterProductController::class, 'toggleStatus'])->name('master-products.toggle-status')->middleware('permission:warehouse.master-products.update');
        Route::post('master-products/update-categories', [MasterProductController::class, 'updateCategories'])->name('master-products.update-categories')->middleware('permission:warehouse.master-products.update');

        // Packaging Sizes Management
        Route::resource('packaging-sizes', \App\Http\Controllers\Warehouse\PackagingSizeController::class)->middleware('permission:warehouse.master-products.view');
        Route::get('packaging-sizes/api/active', [\App\Http\Controllers\Warehouse\PackagingSizeController::class, 'getActivePackagingSizes'])->name('packaging-sizes.api.active')->middleware('permission:warehouse.master-products.view');

        // Master Rentals DataTable endpoint (must be BEFORE resource route)
        Route::get('master-rentals/datatable', [MasterRentalController::class, 'datatable'])->name('master-rentals.datatable')->middleware('permission:warehouse.master-rentals.view');
        // Master Rentals resource routes with specific permissions
        Route::get('master-rentals', [MasterRentalController::class, 'index'])->name('master-rentals.index')->middleware('permission:warehouse.master-rentals.view');
        Route::get('master-rentals/create', [MasterRentalController::class, 'create'])->name('master-rentals.create')->middleware('permission:warehouse.master-rentals.create');
        Route::post('master-rentals', [MasterRentalController::class, 'store'])->name('master-rentals.store')->middleware('permission:warehouse.master-rentals.create');
        Route::get('master-rentals/import-template', [MasterRentalImportController::class, 'template'])->name('master-rentals.import-template')->middleware('permission:warehouse.master-rentals.create');
        Route::post('master-rentals/import-preview', [MasterRentalImportController::class, 'preview'])->name('master-rentals.import-preview')->middleware('permission:warehouse.master-rentals.create');
        Route::post('master-rentals/import', [MasterRentalImportController::class, 'import'])->name('master-rentals.import')->middleware('permission:warehouse.master-rentals.create');
        Route::get('master-rentals/{masterRental}', [MasterRentalController::class, 'show'])->name('master-rentals.show')->middleware('permission:warehouse.master-rentals.view');
        Route::get('master-rentals/{masterRental}/edit', [MasterRentalController::class, 'edit'])->name('master-rentals.edit')->middleware('permission:warehouse.master-rentals.update');
        Route::put('master-rentals/{masterRental}', [MasterRentalController::class, 'update'])->name('master-rentals.update')->middleware('permission:warehouse.master-rentals.update');
        Route::delete('master-rentals/{masterRental}', [MasterRentalController::class, 'destroy'])->name('master-rentals.destroy')->middleware('permission:warehouse.master-rentals.delete');
        Route::post('master-rentals/bulk-delete', [MasterRentalController::class, 'bulkDelete'])->name('master-rentals.bulk-delete')->middleware('permission:warehouse.master-rentals.delete');
        Route::post('master-rentals/{masterRental}/toggle-status', [MasterRentalController::class, 'toggleStatus'])->name('master-rentals.toggle-status')->middleware('permission:warehouse.master-rentals.update');
        Route::get('master-rentals/statistics', [MasterRentalController::class, 'getRentalStatistics'])->name('master-rentals.statistics')->middleware('permission:warehouse.master-rentals.view');
        Route::get('master-rentals/by-category', [MasterRentalController::class, 'getRentalsByCategory'])->name('master-rentals.by-category')->middleware('permission:warehouse.master-rentals.view');
        Route::get('master-rentals/search', [MasterRentalController::class, 'searchRentals'])->name('master-rentals.search')->middleware('permission:warehouse.master-rentals.view');

        // Master Rental Details Routes
        Route::get('master-rentals/{masterRental}/details/{detail}', [MasterRentalController::class, 'detailsShow'])->name('master-rentals.details.show')->middleware('permission:warehouse.master-rentals.view');
        Route::post('master-rentals/{masterRental}/details', [MasterRentalController::class, 'detailsStore'])->name('master-rentals.details.store')->middleware('permission:warehouse.master-rentals.update');
        Route::put('master-rentals/{masterRental}/details/{detail}', [MasterRentalController::class, 'detailsUpdate'])->name('master-rentals.details.update')->middleware('permission:warehouse.master-rentals.update');
        Route::delete('master-rentals/{masterRental}/details/{detail}', [MasterRentalController::class, 'detailsDestroy'])->name('master-rentals.details.destroy')->middleware('permission:warehouse.master-rentals.delete');
        Route::get('master-rentals/{masterRental}/details/{detail}/materials', [MasterRentalController::class, 'getMaterialList'])->name('master-rentals.details.materials')->middleware('permission:warehouse.master-rentals.view');
        Route::post('master-rentals/{masterRental}/details/{detail}/materials', [MasterRentalController::class, 'saveMaterialList'])->name('master-rentals.details.materials.save')->middleware('permission:warehouse.master-rentals.update');

        // Master Rental Prices Routes
        Route::post('master-rentals/{masterRental}/prices', [MasterRentalController::class, 'pricesStore'])->name('master-rentals.prices.store')->middleware('permission:warehouse.master-rentals.update');
        Route::put('master-rentals/{masterRental}/prices/{price}', [MasterRentalController::class, 'pricesUpdate'])->name('master-rentals.prices.update')->middleware('permission:warehouse.master-rentals.update');
        Route::delete('master-rentals/{masterRental}/prices/{price}', [MasterRentalController::class, 'pricesDestroy'])->name('master-rentals.prices.destroy')->middleware('permission:warehouse.master-rentals.delete');
        Route::resource('stocks', StockOpnameController::class)->except(['create', 'edit'])->middleware('permission:warehouse.stock-opnames.view');
        Route::post('stock-opnames/bulk-delete', [StockOpnameController::class, 'bulkDelete'])->name('stock-opnames.bulk-delete')->middleware('permission:warehouse.stock-opnames.delete');
        Route::post('stock-opnames/{stockOpname}/start', [StockOpnameController::class, 'start'])->name('stock-opnames.start')->middleware('permission:warehouse.stock-opnames.update');
        Route::post('stock-opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete'])->name('stock-opnames.complete')->middleware('permission:warehouse.stock-opnames.update');
        Route::post('stock-opnames/{stockOpname}/submit', [StockOpnameController::class, 'submit'])->name('stock-opnames.submit')->middleware('permission:warehouse.stock-opnames.update');
        Route::post('stock-opnames/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('stock-opnames.approve')->middleware('permission:warehouse.stock-opnames.update');
        Route::post('stock-opnames/{stockOpname}/unpost', [StockOpnameController::class, 'unpost'])->name('stock-opnames.unpost')->middleware('permission:warehouse.stock-opnames.approve');
        Route::post('stock-opnames/{stockOpname}/import-stock', [StockOpnameController::class, 'importStock'])->name('stock-opnames.import-stock')->middleware('permission:warehouse.stock-opnames.update');
        Route::get('stock-opnames/{stockOpname}/export-stock', [StockOpnameController::class, 'exportStock'])->name('stock-opnames.export-stock')->middleware('permission:warehouse.stock-opnames.view');
        Route::post('stock-opnames/{stockOpname}/create-adjustment', [StockOpnameController::class, 'createAdjustment'])->name('stock-opnames.create-adjustment')->middleware('permission:warehouse.stock-opnames.update');
        Route::post('stock-opnames/details/{detail}/update', [StockOpnameController::class, 'updateDetail'])->name('stock-opnames.details.update')->middleware('permission:warehouse.stock-opnames.update');
        Route::get('stock-opnames/dashboard', [StockOpnameController::class, 'dashboard'])->name('stock-opnames.dashboard')->middleware('permission:warehouse.stock-opnames.view');
        Route::resource('stock-opnames', StockOpnameController::class)->except(['create', 'edit'])->middleware('permission:warehouse.stock-opnames.view');
        Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create')->middleware('permission:warehouse.stock-adjustments.view');
        Route::resource('stock-adjustments', StockAdjustmentController::class)->except(['create', 'edit'])->middleware('permission:warehouse.stock-adjustments.view');
        Route::post('stock-adjustments/bulk-delete', [StockAdjustmentController::class, 'bulkDelete'])->name('stock-adjustments.bulk-delete')->middleware('permission:warehouse.stock-adjustments.delete');
        Route::post('stock-adjustments/{stock_adjustment}/add-item', [StockAdjustmentController::class, 'addItem'])->name('stock-adjustments.add-item')->middleware('permission:warehouse.stock-adjustments.update');
        Route::delete('stock-adjustments/items/{item}', [StockAdjustmentController::class, 'destroyItem'])->name('stock-adjustments.destroy-item')->middleware('permission:warehouse.stock-adjustments.update');
        Route::post('stock-adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve')->middleware('permission:warehouse.stock-adjustments.update');
        Route::post('stock-adjustments/{stock_adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject')->middleware('permission:warehouse.stock-adjustments.update');
        Route::post('stock-adjustments/{stock_adjustment}/rollback', [StockAdjustmentController::class, 'rollback'])->name('stock-adjustments.rollback')->middleware('permission:warehouse.stock-adjustments.rollback');
        Route::get('stock-adjustments/dashboard', [StockAdjustmentController::class, 'dashboard'])->name('stock-adjustments.dashboard')->middleware('permission:warehouse.stock-adjustments.view');
        Route::get('serial-numbers/import-template', [SerialNumberImportController::class, 'template'])->name('serial-numbers.import-template')->middleware('permission:warehouse.serial-numbers.create');
        Route::post('serial-numbers/import-preview', [SerialNumberImportController::class, 'preview'])->name('serial-numbers.import-preview')->middleware('permission:warehouse.serial-numbers.create');
        Route::post('serial-numbers/import', [SerialNumberImportController::class, 'import'])->name('serial-numbers.import')->middleware('permission:warehouse.serial-numbers.create');
        Route::resource('serial-numbers', SerialNumberController::class)->middleware('permission:warehouse.serial-numbers.view');
        Route::post('serial-numbers/bulk-delete', [SerialNumberController::class, 'bulkDelete'])->name('serial-numbers.bulk-delete')->middleware('permission:warehouse.serial-numbers.delete');
        Route::resource('unit-on-walls', UnitOnWallController::class)->middleware('permission:warehouse.unit-on-walls.view');
        Route::post('unit-on-walls/bulk-delete', [UnitOnWallController::class, 'bulkDelete'])->name('unit-on-walls.bulk-delete')->middleware('permission:warehouse.unit-on-walls.delete');
        Route::post('unit-on-walls/{unitOnWall}/update-status', [UnitOnWallController::class, 'updateStatus'])->name('unit-on-walls.update-status')->middleware('permission:warehouse.unit-on-walls.update');
        Route::post('unit-on-walls/{unitOnWall}/update-temperature', [UnitOnWallController::class, 'updateTemperature'])->name('unit-on-walls.update-temperature')->middleware('permission:warehouse.unit-on-walls.update');

        // Warehouses DataTable endpoint (must be BEFORE resource route)
        Route::get('warehouses/datatable', [WarehouseController::class, 'datatable'])->name('warehouses.datatable')->middleware('permission:warehouse.warehouses.view');
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index')->middleware('permission:warehouse.warehouses.view');
        Route::get('warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create')->middleware('permission:warehouse.warehouses.create');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store')->middleware('permission:warehouse.warehouses.create');
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show')->middleware('permission:warehouse.warehouses.view');
        Route::get('warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit')->middleware('permission:warehouse.warehouses.update');
        Route::match(['put', 'patch'], 'warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update')->middleware('permission:warehouse.warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy')->middleware('permission:warehouse.warehouses.delete');
        Route::get('warehouses/{warehouse}/export-stock', [WarehouseController::class, 'exportStock'])->name('warehouses.export-stock')->middleware('permission:warehouse.warehouses.view');
        Route::get('warehouses/{warehouse}/detail-stock/{product}', [WarehouseController::class, 'detailStock'])->name('warehouses.detail-stock')->middleware('permission:warehouse.warehouses.view');
        Route::post('warehouses/bulk-delete', [WarehouseController::class, 'bulkDelete'])->name('warehouses.bulk-delete')->middleware('permission:warehouse.warehouses.delete');
        Route::post('warehouses/bulk-delete', [WarehouseController::class, 'bulkDelete'])->name('warehouses.bulk-delete')->middleware('permission:warehouse.warehouses.delete');

        // Product Structure Management
        Route::prefix('product-structure')->name('product-structure.')->group(function () {
            Route::get('categories', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'categories'])->name('categories')->middleware('permission:warehouse.product-structure.view');
            Route::get('categories/{category}', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'showCategory'])->name('categories.show')->middleware('permission:warehouse.product-structure.view');
            Route::post('categories', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'storeCategory'])->name('categories.store')->middleware('permission:warehouse.product-structure.update');
            Route::put('categories/{category}', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'updateCategory'])->name('categories.update')->middleware('permission:warehouse.product-structure.update');
            Route::delete('categories/{category}', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'destroyCategory'])->name('categories.destroy')->middleware('permission:warehouse.product-structure.delete');

            Route::get('structure', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'structure'])->name('structure')->middleware('permission:warehouse.product-structure.view');

            Route::get('products/{product}/photos', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'photos'])->name('photos')->middleware('permission:warehouse.product-structure.view');
            Route::post('products/{product}/photos', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'storePhoto'])->name('photos.store')->middleware('permission:warehouse.product-structure.update');
            Route::delete('photos/{photo}', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'destroyPhoto'])->name('photos.destroy')->middleware('permission:warehouse.product-structure.delete');
            Route::post('photos/{photo}/set-primary', [\App\Http\Controllers\Warehouse\ProductStructureController::class, 'setPrimaryPhoto'])->name('photos.set-primary')->middleware('permission:warehouse.product-structure.update');
        });

        // Rental Management
        Route::prefix('rental-management')->name('rental-management.')->group(function () {
            Route::get('service-frequencies', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'serviceFrequencies'])->name('service-frequencies')->middleware('permission:warehouse.master-rentals.view');
            Route::get('service-frequencies/{frequency}', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'showServiceFrequency'])->name('service-frequencies.show')->middleware('permission:warehouse.master-rentals.view');
            Route::post('service-frequencies', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'storeServiceFrequency'])->name('service-frequencies.store')->middleware('permission:warehouse.master-rentals.update');
            Route::put('service-frequencies/{frequency}', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'updateServiceFrequency'])->name('service-frequencies.update')->middleware('permission:warehouse.master-rentals.update');
            Route::delete('service-frequencies/{frequency}', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'destroyServiceFrequency'])->name('service-frequencies.destroy')->middleware('permission:warehouse.master-rentals.delete');

            Route::get('rentals/{rental}/components', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'components'])->name('components')->middleware('permission:warehouse.master-rentals.view');
            Route::post('rentals/{rental}/components', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'storeComponent'])->name('components.store')->middleware('permission:warehouse.master-rentals.update');
            Route::delete('components/{component}', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'destroyComponent'])->name('components.destroy')->middleware('permission:warehouse.master-rentals.delete');

            Route::get('rentals/{rental}/bottom-prices', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'bottomPrices'])->name('bottom-prices')->middleware('permission:warehouse.master-rentals.view');
            Route::post('rentals/{rental}/bottom-prices', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'storeBottomPrice'])->name('bottom-prices.store')->middleware('permission:warehouse.master-rentals.update');
            Route::put('bottom-prices/{bottomPrice}', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'updateBottomPrice'])->name('bottom-prices.update')->middleware('permission:warehouse.master-rentals.update');
            Route::delete('bottom-prices/{bottomPrice}', [\App\Http\Controllers\Warehouse\RentalManagementController::class, 'destroyBottomPrice'])->name('bottom-prices.destroy')->middleware('permission:warehouse.master-rentals.delete');
        });

        // Inventory Logistics Management
        Route::prefix('inventory-logistics')->name('inventory-logistics.')->group(function () {
            Route::get('tracking', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'tracking'])->name('tracking')->middleware('permission:warehouse.warehouses.view');
            Route::get('tracking/{tracking}', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'showTracking'])->name('tracking.show')->middleware('permission:warehouse.warehouses.view');
            Route::post('tracking', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'createTracking'])->name('tracking.store')->middleware('permission:warehouse.warehouses.update');
            Route::put('tracking/{tracking}/status', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'updateTrackingStatus'])->name('tracking.update-status')->middleware('permission:warehouse.warehouses.update');

            Route::get('berita-acara', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'beritaAcara'])->name('berita-acara')->middleware('permission:warehouse.warehouses.view');
            Route::post('berita-acara', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'createBeritaAcara'])->name('berita-acara.store')->middleware('permission:warehouse.warehouses.update');
            Route::post('berita-acara/{beritaAcara}/approve', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'approveBeritaAcara'])->name('berita-acara.approve')->middleware('permission:warehouse.warehouses.update');

            Route::get('purchasing-requests', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'purchasingRequests'])->name('purchasing-requests')->middleware('permission:warehouse.warehouses.view');
            Route::post('purchasing-requests', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'createPurchasingRequest'])->name('purchasing-requests.store')->middleware('permission:warehouse.warehouses.update');
            Route::post('purchasing-requests/{purchasingRequest}/approve', [\App\Http\Controllers\Warehouse\InventoryLogisticsController::class, 'approvePurchasingRequest'])->name('purchasing-requests.approve')->middleware('permission:warehouse.warehouses.update');
        });

        // Main inventory transfers route (must come after ALL other routes to avoid conflicts)
        Route::get('inventory-transfers', [InventoryController::class, 'index'])->name('inventory-transfers.index');
        Route::get('inventory-transfers/{id}', [InventoryController::class, 'showTransfer'])->name('inventory-transfers.show')->where('id', '[0-9]+');
    });

    // System Routes
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/', function () {
            return view('system.dashboard');
        })->name('dashboard');
        Route::get('backup-restore', [BackupRestoreController::class, 'index'])->name('backup-restore.index')->middleware('permission:system.backup-restore');
        Route::get('catalyst-import', [CatalystImportController::class, 'index'])->name('catalyst-import.index')->middleware('permission:system.backup-restore');
        Route::post('catalyst-import/run', [CatalystImportController::class, 'run'])->name('catalyst-import.run')->middleware('permission:system.backup-restore.import');
        Route::get('backup-restore/template-all', [BackupRestoreController::class, 'downloadAllTemplates'])->name('backup-restore.template-all')->middleware('permission:system.backup-restore.template');
        Route::get('backup-restore/export-all', [BackupRestoreController::class, 'exportAll'])->name('backup-restore.export-all')->middleware('permission:system.backup-restore.export');
        Route::post('backup-restore/import-all', [BackupRestoreController::class, 'importAll'])->name('backup-restore.import-all')->middleware('permission:system.backup-restore.import');
        Route::delete('backup-restore/delete-all', [BackupRestoreController::class, 'destroyAll'])->name('backup-restore.destroy-all')->middleware('permission:system.backup-restore.delete');
        Route::get('backup-restore/{module}/template', [BackupRestoreController::class, 'downloadTemplate'])->name('backup-restore.template')->middleware('permission:system.backup-restore.template');
        Route::get('backup-restore/{module}/export', [BackupRestoreController::class, 'export'])->name('backup-restore.export')->middleware('permission:system.backup-restore.export');
        Route::post('backup-restore/{module}/import', [BackupRestoreController::class, 'import'])->name('backup-restore.import')->middleware('permission:system.backup-restore.import');
        Route::delete('backup-restore/{module}', [BackupRestoreController::class, 'destroy'])->name('backup-restore.destroy')->middleware('permission:system.backup-restore.delete');
        Route::get('master-term-of-payments', [\App\Http\Controllers\System\TermOfPaymentController::class, 'index'])->name('master-term-of-payments.index')->middleware('permission:system.master-term-of-payments');
        Route::post('master-term-of-payments', [\App\Http\Controllers\System\TermOfPaymentController::class, 'store'])->name('master-term-of-payments.store')->middleware('permission:system.master-term-of-payments.create');
        Route::put('master-term-of-payments/{masterTermOfPayment}', [\App\Http\Controllers\System\TermOfPaymentController::class, 'update'])->name('master-term-of-payments.update')->middleware('permission:system.master-term-of-payments.update');
        Route::delete('master-term-of-payments/{masterTermOfPayment}', [\App\Http\Controllers\System\TermOfPaymentController::class, 'destroy'])->name('master-term-of-payments.destroy')->middleware('permission:system.master-term-of-payments.delete');
        Route::post('master-term-of-payments/{masterTermOfPayment}/toggle-status', [\App\Http\Controllers\System\TermOfPaymentController::class, 'toggleStatus'])->name('master-term-of-payments.toggle-status')->middleware('permission:system.master-term-of-payments.update');
        Route::resource('users', UserController::class);
        Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
        Route::resource('departments', DepartmentController::class);
        Route::post('departments/bulk-delete', [DepartmentController::class, 'bulkDelete'])->name('departments.bulk-delete');
        Route::resource('provinces', ProvinceController::class);
        Route::post('provinces/bulk-delete', [ProvinceController::class, 'bulkDelete'])->name('provinces.bulk-delete');
        Route::get('provinces/{id}/delete-info/{type}', [ProvinceController::class, 'getDeleteInfo'])->name('provinces.delete-info');

        // City routes
        Route::get('provinces/{provinceId}/cities', [ProvinceController::class, 'getCities'])->name('provinces.cities');
        Route::get('cities/{cityId}', [ProvinceController::class, 'showCity'])->name('cities.show');
        Route::get('cities/{cityId}/edit', [ProvinceController::class, 'editCity'])->name('cities.edit');
        Route::post('cities', [ProvinceController::class, 'storeCity'])->name('cities.store');
        Route::put('cities/{cityId}', [ProvinceController::class, 'updateCity'])->name('cities.update');
        Route::delete('cities/{cityId}', [ProvinceController::class, 'destroyCity'])->name('cities.destroy');
        Route::post('cities/bulk-delete', [ProvinceController::class, 'bulkDeleteCities'])->name('cities.bulk-delete');

        // District routes
        Route::get('cities/{cityId}/districts', [ProvinceController::class, 'getDistricts'])->name('cities.districts');
        Route::get('districts/{districtId}', [ProvinceController::class, 'showDistrict'])->name('districts.show');
        Route::get('districts/{districtId}/edit', [ProvinceController::class, 'editDistrict'])->name('districts.edit');
        Route::post('districts', [ProvinceController::class, 'storeDistrict'])->name('districts.store');
        Route::put('districts/{districtId}', [ProvinceController::class, 'updateDistrict'])->name('districts.update');
        Route::delete('districts/{districtId}', [ProvinceController::class, 'destroyDistrict'])->name('districts.destroy');
        Route::post('districts/bulk-delete', [ProvinceController::class, 'bulkDeleteDistricts'])->name('districts.bulk-delete');

        // Subdistrict routes
        Route::get('districts/{districtId}/subdistricts', [ProvinceController::class, 'getSubdistricts'])->name('districts.subdistricts');
        Route::get('subdistricts/{subdistrictId}', [ProvinceController::class, 'showSubdistrict'])->name('subdistricts.show');
        Route::get('subdistricts/{subdistrictId}/edit', [ProvinceController::class, 'editSubdistrict'])->name('subdistricts.edit');
        Route::post('subdistricts', [ProvinceController::class, 'storeSubdistrict'])->name('subdistricts.store');
        Route::put('subdistricts/{subdistrictId}', [ProvinceController::class, 'updateSubdistrict'])->name('subdistricts.update');
        Route::delete('subdistricts/{subdistrictId}', [ProvinceController::class, 'destroySubdistrict'])->name('subdistricts.destroy');
        Route::post('subdistricts/bulk-delete', [ProvinceController::class, 'bulkDeleteSubdistricts'])->name('subdistricts.bulk-delete');
        Route::resource('notifications', NotificationController::class);
        Route::post('roles/{id}/duplicate', [RoleController::class, 'duplicate'])->name('roles.duplicate');
        Route::resource('roles', RoleController::class);
        Route::post('roles/bulk-delete', [RoleController::class, 'bulkDelete'])->name('roles.bulk-delete');
        Route::post('roles/{role}/activate', [RoleController::class, 'activate'])->name('roles.activate');
        Route::post('roles/{role}/deactivate', [RoleController::class, 'deactivate'])->name('roles.deactivate');
        Route::post('roles/{role}/assign-users', [RoleController::class, 'assignUsers'])->name('roles.assign-users');
        Route::get('roles/export', [RoleController::class, 'export'])->name('roles.export');

        // Department Roles Management
        Route::resource('department-roles', \App\Http\Controllers\System\DepartmentRoleController::class);
        Route::post('department-roles/bulk-assign', [\App\Http\Controllers\System\DepartmentRoleController::class, 'bulkAssignUsers'])->name('department-roles.bulk-assign');

        // Position Roles Management
        Route::resource('position-roles', \App\Http\Controllers\System\PositionRoleController::class);
        Route::post('position-roles/bulk-assign', [\App\Http\Controllers\System\PositionRoleController::class, 'bulkAssignUsers'])->name('position-roles.bulk-assign');
        Route::resource('working-hours', WorkingHoursController::class);
        Route::post('working-hours/bulk-delete', [WorkingHoursController::class, 'bulkDelete'])->name('working-hours.bulk-delete');
        Route::get('working-hours/user/{userId}', [WorkingHoursController::class, 'getUserWorkingHours'])->name('working-hours.user');
        Route::get('working-hours/user/{userId}/date/{date}', [WorkingHoursController::class, 'getWorkingHoursForDate'])->name('working-hours.user-date');
        Route::post('working-hours/exceptions', [WorkingHoursController::class, 'createException'])->name('working-hours.exceptions');
        Route::get('working-hours/export', [WorkingHoursController::class, 'export'])->name('working-hours.export');
        Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show']);
        Route::get('audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('audit-logs/user/{userId}', [AuditLogController::class, 'getUserActivity'])->name('audit-logs.user');
        Route::get('audit-logs/model/{modelType}/{modelId}', [AuditLogController::class, 'getModelHistory'])->name('audit-logs.model');
        Route::get('audit-logs/summary', [AuditLogController::class, 'getActivitySummary'])->name('audit-logs.summary');
        Route::get('audit-logs/top-users', [AuditLogController::class, 'getTopUsers'])->name('audit-logs.top-users');
        Route::get('audit-logs/recent', [AuditLogController::class, 'getRecentActivity'])->name('audit-logs.recent');
        Route::post('audit-logs/cleanup', [AuditLogController::class, 'cleanup'])->name('audit-logs.cleanup');
        Route::resource('system-logs', SystemLogController::class)->only(['index', 'show']);
        Route::get('system-logs/export', [SystemLogController::class, 'export'])->name('system-logs.export');
        Route::get('system-logs/summary', [SystemLogController::class, 'getLogSummary'])->name('system-logs.summary');
        Route::get('system-logs/errors', [SystemLogController::class, 'getRecentErrors'])->name('system-logs.errors');
        Route::get('system-logs/warnings', [SystemLogController::class, 'getRecentWarnings'])->name('system-logs.warnings');
        Route::get('system-logs/level/{level}', [SystemLogController::class, 'getLogsByLevel'])->name('system-logs.level');
        Route::get('system-logs/time-range', [SystemLogController::class, 'getLogsByTimeRange'])->name('system-logs.time-range');
        Route::post('system-logs/cleanup', [SystemLogController::class, 'cleanup'])->name('system-logs.cleanup');
        Route::get('system-logs/statistics', [SystemLogController::class, 'getLogStatistics'])->name('system-logs.statistics');
        Route::resource('api-tokens', ApiTokenController::class);
        Route::post('api-tokens/bulk-delete', [ApiTokenController::class, 'bulkDelete'])->name('api-tokens.bulk-delete');
        Route::post('api-tokens/{apiToken}/regenerate', [ApiTokenController::class, 'regenerateToken'])->name('api-tokens.regenerate');
        Route::post('api-tokens/{apiToken}/extend', [ApiTokenController::class, 'extendExpiry'])->name('api-tokens.extend');
        Route::get('api-tokens/user/{userId}', [ApiTokenController::class, 'getUserTokens'])->name('api-tokens.user');
        Route::get('api-tokens/expiring', [ApiTokenController::class, 'getExpiringTokens'])->name('api-tokens.expiring');
        Route::get('api-tokens/export', [ApiTokenController::class, 'export'])->name('api-tokens.export');
        Route::resource('user-sessions', UserSessionController::class)->only(['index', 'show', 'destroy']);
        Route::post('user-sessions/bulk-delete', [UserSessionController::class, 'bulkDelete'])->name('user-sessions.bulk-delete');
        Route::get('user-sessions/user/{userId}', [UserSessionController::class, 'getUserSessions'])->name('user-sessions.user');
        Route::get('user-sessions/active', [UserSessionController::class, 'getActiveSessions'])->name('user-sessions.active');
        Route::get('user-sessions/expired', [UserSessionController::class, 'getExpiredSessions'])->name('user-sessions.expired');
        Route::post('user-sessions/cleanup', [UserSessionController::class, 'cleanupExpiredSessions'])->name('user-sessions.cleanup');
        Route::get('user-sessions/statistics', [UserSessionController::class, 'getSessionStatistics'])->name('user-sessions.statistics');
        Route::get('user-sessions/export', [UserSessionController::class, 'export'])->name('user-sessions.export');
        Route::resource('password-histories', PasswordHistoryController::class)->only(['index', 'show']);
        Route::get('password-histories/user/{userId}', [PasswordHistoryController::class, 'getUserPasswordHistory'])->name('password-histories.user');
        Route::get('password-histories/recent', [PasswordHistoryController::class, 'getRecentPasswordChanges'])->name('password-histories.recent');
        Route::get('password-histories/export', [PasswordHistoryController::class, 'export'])->name('password-histories.export');
        Route::resource('maintenance-logs', MaintenanceLogController::class);
        Route::get('maintenance-logs/upcoming', [MaintenanceLogController::class, 'getUpcomingMaintenance'])->name('maintenance-logs.upcoming');
        Route::get('maintenance-logs/statistics', [MaintenanceLogController::class, 'getMaintenanceStatistics'])->name('maintenance-logs.statistics');
        Route::get('maintenance-logs/export', [MaintenanceLogController::class, 'export'])->name('maintenance-logs.export');
        Route::resource('backup-logs', BackupLogController::class)->only(['index', 'show']);
        Route::get('backup-logs/statistics', [BackupLogController::class, 'getBackupStatistics'])->name('backup-logs.statistics');
        Route::get('backup-logs/recent', [BackupLogController::class, 'getRecentBackups'])->name('backup-logs.recent');
        Route::get('backup-logs/type/{type}', [BackupLogController::class, 'getBackupsByType'])->name('backup-logs.type');
        Route::get('backup-logs/export', [BackupLogController::class, 'export'])->name('backup-logs.export');
        Route::resource('system-health', SystemHealthController::class)->only(['index', 'show']);
        Route::get('system-health/overall', [SystemHealthController::class, 'getOverallHealth'])->name('system-health.overall');
        Route::get('system-health/component/{component}', [SystemHealthController::class, 'getComponentHealth'])->name('system-health.component');
        Route::get('system-health/statistics', [SystemHealthController::class, 'getHealthStatistics'])->name('system-health.statistics');
        Route::get('system-health/critical', [SystemHealthController::class, 'getCriticalIssues'])->name('system-health.critical');
        Route::get('system-health/warnings', [SystemHealthController::class, 'getWarnings'])->name('system-health.warnings');
        Route::get('system-health/export', [SystemHealthController::class, 'export'])->name('system-health.export');
    });

    // Company Routes
    Route::prefix('company')->name('company.')->group(function () {
        Route::get('/', function () {
            return view('company.dashboard');
        })->name('dashboard');

        // Company Management
        Route::resource('companies', CompanyController::class)->middleware('permission:company.companies.view');
        Route::get('companies/{company}/settings', [CompanyController::class, 'settings'])->name('companies.settings')->middleware('permission:company.companies.view');
        Route::put('companies/{company}/settings', [CompanyController::class, 'updateSettings'])->name('companies.settings.update')->middleware('permission:company.companies.update');
        Route::get('companies/{company}/documents', [CompanyController::class, 'documents'])->name('companies.documents')->middleware('permission:company.companies.view');
        Route::post('companies/{company}/documents', [CompanyController::class, 'uploadDocument'])->name('companies.documents.upload')->middleware('permission:company.companies.update');
        Route::delete('companies/{company}/documents/{document}', [CompanyController::class, 'deleteDocument'])->name('companies.documents.delete')->middleware('permission:company.companies.update');
        Route::get('companies/{company}/notes', [CompanyController::class, 'notes'])->name('companies.notes')->middleware('permission:companies.view');
        Route::post('companies/{company}/notes', [CompanyController::class, 'storeNote'])->name('companies.notes.store')->middleware('permission:company.companies.update');
        Route::put('companies/{company}/notes/{note}', [CompanyController::class, 'updateNote'])->name('companies.notes.update')->middleware('permission:company.companies.update');
        Route::delete('companies/{company}/notes/{note}', [CompanyController::class, 'deleteNote'])->name('companies.notes.delete')->middleware('permission:company.companies.update');
        Route::post('companies/{company}/tags', [CompanyController::class, 'assignTag'])->name('companies.tags.assign')->middleware('permission:company.companies.update');
        Route::delete('companies/{company}/tags/{tag}', [CompanyController::class, 'removeTag'])->name('companies.tags.remove')->middleware('permission:company.companies.update');
        Route::get('companies/{company}/relationships', [CompanyController::class, 'relationships'])->name('companies.relationships')->middleware('permission:companies.view');
        Route::post('companies/{company}/relationships', [CompanyController::class, 'storeRelationship'])->name('companies.relationships.store')->middleware('permission:company.companies.update');
        Route::put('companies/{company}/relationships/{relationship}', [CompanyController::class, 'updateRelationship'])->name('companies.relationships.update')->middleware('permission:company.companies.update');
        Route::delete('companies/{company}/relationships/{relationship}', [CompanyController::class, 'deleteRelationship'])->name('companies.relationships.delete')->middleware('permission:company.companies.update');
        Route::get('companies/{company}/activities', [CompanyController::class, 'activities'])->name('companies.activities')->middleware('permission:companies.view');
        Route::post('companies/{company}/activities', [CompanyController::class, 'storeActivity'])->name('companies.activities.store')->middleware('permission:company.companies.update');
        Route::put('companies/{company}/activities/{activity}', [CompanyController::class, 'updateActivity'])->name('companies.activities.update')->middleware('permission:company.companies.update');
        Route::delete('companies/{company}/activities/{activity}', [CompanyController::class, 'deleteActivity'])->name('companies.activities.delete')->middleware('permission:company.companies.update');
        Route::get('companies/{company}/communications', [CompanyController::class, 'communications'])->name('companies.communications')->middleware('permission:companies.view');
        Route::post('companies/{company}/communications', [CompanyController::class, 'storeCommunication'])->name('companies.communications.store')->middleware('permission:company.companies.update');
        Route::put('companies/{company}/communications/{communication}', [CompanyController::class, 'updateCommunication'])->name('companies.communications.update')->middleware('permission:company.companies.update');
        Route::delete('companies/{company}/communications/{communication}', [CompanyController::class, 'deleteCommunication'])->name('companies.communications.delete')->middleware('permission:company.companies.update');
        Route::get('companies/{company}/dashboard', [CompanyController::class, 'dashboard'])->name('companies.dashboard')->middleware('permission:companies.view');
        Route::post('companies/bulk-delete', [CompanyController::class, 'bulkDelete'])->name('companies.bulk-delete')->middleware('permission:companies.delete');
        Route::post('companies/bulk-update-status', [CompanyController::class, 'bulkUpdateStatus'])->name('companies.bulk-update-status')->middleware('permission:company.companies.update');
        Route::post('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('companies.toggle-status')->middleware('permission:company.companies.update');
        Route::get('companies/export', [CompanyController::class, 'export'])->name('companies.export')->middleware('permission:companies.view');
        Route::post('companies/import', [CompanyController::class, 'import'])->name('companies.import')->middleware('permission:company.companies.update');
        Route::get('companies/statistics', [CompanyController::class, 'getStatistics'])->name('companies.statistics')->middleware('permission:companies.view');

        // Branch Management
        // Multi-Branch User Assignment (must be before Route::resource to avoid {branch} wildcard capture)
        Route::get('branches/user-assignments', [App\Http\Controllers\Company\BranchUserController::class, 'index'])->name('branches.user-assignments')->middleware('permission:company.branches.view');
        Route::get('branches/user-assignments/{user}', [App\Http\Controllers\Company\BranchUserController::class, 'getUserBranches'])->name('branches.user-assignments.get')->middleware('permission:company.branches.view');
        Route::post('branches/user-assignments', [App\Http\Controllers\Company\BranchUserController::class, 'updateUserBranches'])->name('branches.user-assignments.update')->middleware('permission:company.branches.update');

        Route::resource('branches', BranchController::class)->middleware('permission:company.branches.view');
        Route::get('branches/{branch}/settings', [BranchController::class, 'settings'])->name('branches.settings')->middleware('permission:company.branches.view');
        Route::put('branches/{branch}/settings', [BranchController::class, 'updateSettings'])->name('branches.settings.update')->middleware('permission:company.branches.update');
        Route::get('branches/{branch}/warehouses', [BranchController::class, 'warehouses'])->name('branches.warehouses')->middleware('permission:company.branches.view');
        Route::post('branches/{branch}/warehouses', [BranchController::class, 'assignWarehouse'])->name('branches.warehouses.assign')->middleware('permission:company.branches.update');
        Route::delete('branches/{branch}/warehouses/{branchWarehouse}', [BranchController::class, 'removeWarehouse'])->name('branches.warehouses.remove')->middleware('permission:company.branches.update');
        Route::post('branches/{branch}/warehouses/{branchWarehouse}/set-primary', [BranchController::class, 'setPrimaryWarehouse'])->name('branches.warehouses.set-primary')->middleware('permission:company.branches.update');
        Route::post('branches/bulk-delete', [BranchController::class, 'bulkDelete'])->name('branches.bulk-delete')->middleware('permission:company.branches.delete');
        Route::post('branches/bulk-update-status', [BranchController::class, 'bulkUpdateStatus'])->name('branches.bulk-update-status')->middleware('permission:company.branches.update');
        Route::post('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status')->middleware('permission:company.branches.update');
        Route::get('branches/statistics', [BranchController::class, 'getStatistics'])->name('branches.statistics')->middleware('permission:company.branches.view');

        // Operational Areas
        Route::get('branches/{branch}/operational-areas', [BranchController::class, 'operationalAreas'])->name('branches.operational-areas')->middleware('permission:company.branches.view');
        Route::get('branches/{branch}/operational-areas/{operationalArea}', [BranchController::class, 'showOperationalArea'])->name('branches.operational-areas.show')->middleware('permission:company.branches.view');
        Route::post('branches/{branch}/operational-areas', [BranchController::class, 'storeOperationalArea'])->name('branches.operational-areas.store')->middleware('permission:company.branches.update');
        Route::post('branches/{branch}/operational-areas/sync', [BranchController::class, 'syncOperationalAreas'])->name('branches.operational-areas.sync')->middleware('permission:company.branches.update');
        Route::get('branches/{branch}/operational-areas-cities', [BranchController::class, 'getOperationalAreaCities'])->name('branches.operational-areas.cities')->middleware('permission:company.branches.view');
        Route::put('branches/{branch}/operational-areas/{operationalArea}', [BranchController::class, 'updateOperationalArea'])->name('branches.operational-areas.update')->middleware('permission:company.branches.update');
        Route::delete('branches/{branch}/operational-areas/{operationalArea}', [BranchController::class, 'destroyOperationalArea'])->name('branches.operational-areas.destroy')->middleware('permission:company.branches.delete');

        // Branch PICs
        Route::get('branches/{branch}/pics', [BranchController::class, 'pics'])->name('branches.pics')->middleware('permission:company.branches.view');
        Route::get('branches/{branch}/pics/{pic}', [BranchController::class, 'showPic'])->name('branches.pics.show')->middleware('permission:company.branches.view');
        Route::post('branches/{branch}/pics', [BranchController::class, 'storePic'])->name('branches.pics.store')->middleware('permission:company.branches.update');
        Route::put('branches/{branch}/pics/{pic}', [BranchController::class, 'updatePic'])->name('branches.pics.update')->middleware('permission:company.branches.update');
        Route::delete('branches/{branch}/pics/{pic}', [BranchController::class, 'destroyPic'])->name('branches.pics.destroy')->middleware('permission:company.branches.delete');
        Route::post('branches/{branch}/pics/{pic}/set-primary', [BranchController::class, 'setPrimaryPic'])->name('branches.pics.set-primary')->middleware('permission:company.branches.update');

        // Customer Management
        Route::get('customers/import-template', [CustomerImportController::class, 'template'])->name('customers.import-template')->middleware('permission:company.customers.create');
        Route::post('customers/import-preview', [CustomerImportController::class, 'preview'])->name('customers.import-preview')->middleware('permission:company.customers.create');
        Route::post('customers/import', [CustomerImportController::class, 'import'])->name('customers.import')->middleware('permission:company.customers.create');
        Route::resource('customers', CustomerController::class)->middleware('permission:company.customers.view');
        Route::get('customers/{customer}/details', [CustomerController::class, 'details'])->name('customers.details')->middleware('permission:company.customers.view');

        // Customer Contacts Management
        Route::get('customer-contacts/get-by-customer', [CustomerContactController::class, 'getByCustomer'])->name('customer-contacts.get-by-customer');
        Route::get('customer-contacts/by-customer/{customerId}', [CustomerContactController::class, 'getByCustomerId'])->name('customer-contacts.by-customer');
        Route::get('customer-contacts/get-customer-contacts', [CustomerContactController::class, 'getCustomerContacts'])->name('customer-contacts.get-customer-contacts');
        Route::resource('customer-contacts', CustomerContactController::class)->middleware('permission:company.customers.view');
        Route::post('customer-contacts/{customerContact}/resend-verification', [CustomerContactController::class, 'sendVerificationEmail'])->name('customer-contacts.resend-verification')->middleware('permission:company.customers.update');
        Route::get('customers/{customer}/credit-limits', [CustomerController::class, 'creditLimits'])->name('customers.credit-limits')->middleware('permission:company.customers.view');
        Route::post('customers/{customer}/credit-limits', [CustomerController::class, 'storeCreditLimit'])->name('customers.credit-limits.store')->middleware('permission:company.customers.update');
        Route::put('customers/{customer}/credit-limits/{creditLimit}', [CustomerController::class, 'updateCreditLimit'])->name('customers.credit-limits.update')->middleware('permission:company.customers.update');
        Route::delete('customers/{customer}/credit-limits/{creditLimit}', [CustomerController::class, 'deleteCreditLimit'])->name('customers.credit-limits.delete')->middleware('permission:company.customers.delete');

        // Customer Status Management (PKP & Active Status)
        Route::get('customers/{customer}/payment-terms', [CustomerController::class, 'paymentTerms'])->name('customers.payment-terms')->middleware('permission:customers.view');
        Route::post('customers/{customer}/payment-terms', [CustomerController::class, 'storePaymentTerm'])->name('customers.payment-terms.store')->middleware('permission:company.customers.update');
        Route::put('customers/{customer}/payment-terms/{paymentTerm}', [CustomerController::class, 'updatePaymentTerm'])->name('customers.payment-terms.update')->middleware('permission:company.customers.update');
        Route::delete('customers/{customer}/payment-terms/{paymentTerm}', [CustomerController::class, 'deletePaymentTerm'])->name('customers.payment-terms.delete')->middleware('permission:customers.delete');
        Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulk-delete')->middleware('permission:company.customers.delete');
        Route::post('customers/bulk-update-status', [CustomerController::class, 'bulkUpdateStatus'])->name('customers.bulk-update-status')->middleware('permission:company.customers.update');
        Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status')->middleware('permission:company.customers.update');
        Route::get('customers/statistics', [CustomerController::class, 'getStatistics'])->name('customers.statistics')->middleware('permission:customers.view');

        // Supplier Management
        Route::resource('suppliers', SupplierController::class)->middleware('permission:suppliers.view');
        Route::get('suppliers/{supplier}/credit-limits', [SupplierController::class, 'creditLimits'])->name('suppliers.credit-limits');
        Route::post('suppliers/{supplier}/credit-limits', [SupplierController::class, 'storeCreditLimit'])->name('suppliers.credit-limits.store');
        Route::put('suppliers/{supplier}/credit-limits/{creditLimit}', [SupplierController::class, 'updateCreditLimit'])->name('suppliers.credit-limits.update');
        Route::delete('suppliers/{supplier}/credit-limits/{creditLimit}', [SupplierController::class, 'deleteCreditLimit'])->name('suppliers.credit-limits.delete');
        Route::get('suppliers/{supplier}/payment-terms', [SupplierController::class, 'paymentTerms'])->name('suppliers.payment-terms');
        Route::post('suppliers/{supplier}/payment-terms', [SupplierController::class, 'storePaymentTerm'])->name('suppliers.payment-terms.store');
        Route::put('suppliers/{supplier}/payment-terms/{paymentTerm}', [SupplierController::class, 'updatePaymentTerm'])->name('suppliers.payment-terms.update');
        Route::delete('suppliers/{supplier}/payment-terms/{paymentTerm}', [SupplierController::class, 'deletePaymentTerm'])->name('suppliers.payment-terms.delete');
        Route::post('suppliers/bulk-delete', [SupplierController::class, 'bulkDelete'])->name('suppliers.bulk-delete');
        Route::post('suppliers/bulk-update-status', [SupplierController::class, 'bulkUpdateStatus'])->name('suppliers.bulk-update-status');
        Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
        Route::get('suppliers/statistics', [SupplierController::class, 'getStatistics'])->name('suppliers.statistics');

        // Legacy routes
        Route::get('customer-taxes/get-info/{customerId}', [CustomerTaxController::class, 'getCustomerTaxInfo'])->name('customer-taxes.get-info')->middleware('permission:marketing.customer-taxes.view');
        Route::resource('customer-taxes', CustomerTaxController::class)->middleware('permission:marketing.customer-taxes.view');
        Route::post('customer-taxes/bulk-delete', [CustomerTaxController::class, 'bulkDelete'])->name('customer-taxes.bulk-delete')->middleware('permission:marketing.customer-taxes.delete');
        Route::post('customer-taxes/{customerTax}/toggle-status', [CustomerTaxController::class, 'toggleStatus'])->name('customer-taxes.toggle-status')->middleware('permission:marketing.customer-taxes.update');

        // Company Virtual Accounts - Must be before Route::resource to avoid wildcard capture
        Route::get('company-virtual-accounts/get-company-code', [CompanyVirtualAccountController::class, 'getCompanyCode'])->name('company-virtual-accounts.get-company-code')->middleware('permission:company-virtual-accounts.view');
        Route::post('company-virtual-accounts/set-company-code', [CompanyVirtualAccountController::class, 'setCompanyCode'])->name('company-virtual-accounts.set-company-code')->middleware('permission:marketing.company-virtual-accounts.update');
        Route::post('company-virtual-accounts/bulk-delete', [CompanyVirtualAccountController::class, 'bulkDelete'])->name('company-virtual-accounts.bulk-delete')->middleware('permission:company-virtual-accounts.delete');
        Route::resource('company-virtual-accounts', CompanyVirtualAccountController::class)->middleware('permission:marketing.company-virtual-accounts.view');
        Route::post('company-virtual-accounts/{companyVirtualAccount}/toggle-status', [CompanyVirtualAccountController::class, 'toggleStatus'])->name('company-virtual-accounts.toggle-status')->middleware('permission:marketing.company-virtual-accounts.update');
        Route::resource('bank-payments', BankPaymentController::class)->middleware('permission:company.bank-payments.view'); // Allow all authenticated users to view bank payments
        Route::post('bank-payments/bulk-delete', [BankPaymentController::class, 'bulkDelete'])->name('bank-payments.bulk-delete')->middleware('permission:company.bank-payments.delete');
        Route::resource('master-price-slabs', MasterPriceSlabController::class)->middleware('permission:company.master-price-slabs.view');
        Route::post('master-price-slabs/bulk-delete', [MasterPriceSlabController::class, 'bulkDelete'])->name('master-price-slabs.bulk-delete')->middleware('permission:company.master-price-slabs.delete');
        Route::post('master-price-slabs/{masterPriceSlab}/toggle-status', [MasterPriceSlabController::class, 'toggleStatus'])->name('master-price-slabs.toggle-status')->middleware('permission:company.master-price-slabs.update');
        Route::get('master-price-slabs/statistics', [MasterPriceSlabController::class, 'getPriceSlabStatistics'])->name('master-price-slabs.statistics')->middleware('permission:company.master-price-slabs.view');
        Route::get('master-price-slabs/by-rental', [MasterPriceSlabController::class, 'getPriceSlabsByRental'])->name('master-price-slabs.by-rental')->middleware('permission:company.master-price-slabs.view');
        Route::get('master-price-slabs/search', [MasterPriceSlabController::class, 'searchPriceSlabs'])->name('master-price-slabs.search')->middleware('permission:company.master-price-slabs.view');
        Route::post('master-price-slabs/calculate-price', [MasterPriceSlabController::class, 'calculatePrice'])->name('master-price-slabs.calculate-price')->middleware('permission:company.master-price-slabs.view');
        Route::resource('master-banks', \App\Http\Controllers\Master\BankController::class)->middleware('permission:company.master-banks.view');
        Route::post('master-banks/bulk-delete', [\App\Http\Controllers\Master\BankController::class, 'bulkDelete'])->name('master-banks.bulk-delete')->middleware('permission:company.master-banks.delete');
        Route::resource('access-management', AccessManagementController::class)->middleware('permission:system.access-management.view');
        Route::post('access-management/bulk-delete', [AccessManagementController::class, 'bulkDelete'])->name('access-management.bulk-delete')->middleware('permission:system.access-management.delete');
        Route::post('access-management/{id}/assign-users', [AccessManagementController::class, 'assignUsers'])->name('access-management.assign-users')->middleware('permission:system.access-management.update');
        Route::get('access-management/{id}/assigned-users', [AccessManagementController::class, 'getAssignedUsers'])->name('access-management.assigned-users')->middleware('permission:system.access-management.view');
        Route::get('access-management/{id}/available-users', [AccessManagementController::class, 'getAvailableUsers'])->name('access-management.available-users')->middleware('permission:system.access-management.view');
        Route::resource('price-slabs', MasterPriceSlabController::class)->middleware('permission:company.master-price-slabs.view');
    });

    // Other Routes
    Route::prefix('other')->name('other.')->group(function () {
        Route::get('/', function () {
            return view('other.dashboard');
        })->name('dashboard');
        Route::resource('hak-akses', HakAksesController::class);
        Route::get('master-options/{masterOption}/details', [\App\Http\Controllers\Other\OptionDetailController::class, 'index'])->name('master-options.details');
        Route::resource('master-options', MasterOptionController::class);
        Route::post('master-options/bulk-delete', [MasterOptionController::class, 'bulkDelete'])->name('master-options.bulk-delete');
        Route::get('option-details/create', [\App\Http\Controllers\Other\OptionDetailController::class, 'createWithQuery'])->name('option-details.create.query');
        Route::get('option-details/create/{masterOption}', [\App\Http\Controllers\Other\OptionDetailController::class, 'create'])->name('option-details.create');
        Route::get('test-route/{id}', function ($id) {
            return 'Test route with ID: '.$id;
        })->name('test.route');
        Route::get('test-model/{masterOption}', function (\App\Models\MasterOption $masterOption) {
            return 'Test model binding with ID: '.$masterOption->id;
        })->name('test.model');
        Route::get('debug-route', function () {
            $masterOption = \App\Models\MasterOption::find(41);

            return 'Debug: '.route('other.option-details.create', $masterOption);
        })->name('debug.route');
        Route::post('option-details/{masterOption}', [\App\Http\Controllers\Other\OptionDetailController::class, 'store'])->name('option-details.store');
        Route::get('option-details/{optionDetail}', [\App\Http\Controllers\Other\OptionDetailController::class, 'show'])->name('option-details.show');
        Route::get('option-details/{optionDetail}/edit', [\App\Http\Controllers\Other\OptionDetailController::class, 'edit'])->name('option-details.edit');
        Route::put('option-details/{optionDetail}', [\App\Http\Controllers\Other\OptionDetailController::class, 'update'])->name('option-details.update');
        Route::delete('option-details/{optionDetail}', [\App\Http\Controllers\Other\OptionDetailController::class, 'destroy'])->name('option-details.destroy');

        // Customer Portal Routes
        Route::prefix('customer-portal')->name('customer-portal.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Other\CustomerPortalController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Other\CustomerPortalController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Other\CustomerPortalController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Other\CustomerPortalController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Other\CustomerPortalController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Other\CustomerPortalController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Other\CustomerPortalController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Other\CustomerPortalController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Other\CustomerPortalController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/statistics', [\App\Http\Controllers\Other\CustomerPortalController::class, 'getStatistics'])->name('statistics');
        });

        // Customer Interactions Routes
        Route::prefix('customer-interactions')->name('customer-interactions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('/statistics', [\App\Http\Controllers\Other\CustomerInteractionController::class, 'getStatistics'])->name('statistics');
        });

        // Customer Feedback Routes
        Route::prefix('customer-feedback')->name('customer-feedback.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('/statistics', [\App\Http\Controllers\Other\CustomerFeedbackController::class, 'getStatistics'])->name('statistics');
        });

        // IoT Devices Routes
        Route::prefix('iot-devices')->name('iot-devices.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Other\IotDeviceController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Other\IotDeviceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Other\IotDeviceController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Other\IotDeviceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Other\IotDeviceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Other\IotDeviceController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Other\IotDeviceController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Other\IotDeviceController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Other\IotDeviceController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{id}/data', [\App\Http\Controllers\Other\IotDeviceController::class, 'getDeviceData'])->name('data');
            Route::get('/{id}/alerts', [\App\Http\Controllers\Other\IotDeviceController::class, 'getDeviceAlerts'])->name('alerts');
            Route::get('/statistics', [\App\Http\Controllers\Other\IotDeviceController::class, 'getStatistics'])->name('statistics');
        });

        // Support Tickets Routes
        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Other\SupportTicketController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Other\SupportTicketController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Other\SupportTicketController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Other\SupportTicketController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Other\SupportTicketController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Other\SupportTicketController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Other\SupportTicketController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Other\SupportTicketController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{id}/add-message', [\App\Http\Controllers\Other\SupportTicketController::class, 'addMessage'])->name('add-message');
            Route::post('/{id}/assign', [\App\Http\Controllers\Other\SupportTicketController::class, 'assignTo'])->name('assign');
            Route::post('/{id}/update-status', [\App\Http\Controllers\Other\SupportTicketController::class, 'updateStatus'])->name('update-status');
            Route::get('/statistics', [\App\Http\Controllers\Other\SupportTicketController::class, 'getStatistics'])->name('statistics');
        });

        // External APIs Routes
        Route::prefix('external-apis')->name('external-apis.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Other\ExternalApiController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Other\ExternalApiController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Other\ExternalApiController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Other\ExternalApiController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Other\ExternalApiController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Other\ExternalApiController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Other\ExternalApiController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Other\ExternalApiController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Other\ExternalApiController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{id}/logs', [\App\Http\Controllers\Other\ExternalApiController::class, 'getApiLogs'])->name('logs');
            Route::post('/{id}/test', [\App\Http\Controllers\Other\ExternalApiController::class, 'testApi'])->name('test');
            Route::get('/statistics', [\App\Http\Controllers\Other\ExternalApiController::class, 'getStatistics'])->name('statistics');
        });
    });

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        // System Settings
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index'])->name('index');
            Route::get('/create', [SystemSettingController::class, 'create'])->name('create');
            Route::post('/', [SystemSettingController::class, 'store'])->name('store');
            Route::get('/{systemSetting}', [SystemSettingController::class, 'show'])->name('show');
            Route::get('/{systemSetting}/edit', [SystemSettingController::class, 'edit'])->name('edit');
            Route::put('/{systemSetting}', [SystemSettingController::class, 'update'])->name('update');
            Route::delete('/{systemSetting}', [SystemSettingController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [SystemSettingController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{systemSetting}/activate', [SystemSettingController::class, 'activate'])->name('activate');
            Route::post('/{systemSetting}/deactivate', [SystemSettingController::class, 'deactivate'])->name('deactivate');
            Route::get('/export', [SystemSettingController::class, 'export'])->name('export');
        });

        // Theme Settings
        Route::prefix('themes')->name('themes.')->group(function () {
            Route::get('/', [ThemeController::class, 'index'])->name('index');
            Route::get('/create', [ThemeController::class, 'create'])->name('create');
            Route::post('/', [ThemeController::class, 'store'])->name('store');
            Route::get('/{theme}', [ThemeController::class, 'show'])->name('show');
            Route::get('/{theme}/edit', [ThemeController::class, 'edit'])->name('edit');
            Route::put('/{theme}', [ThemeController::class, 'update'])->name('update');
            Route::delete('/{theme}', [ThemeController::class, 'destroy'])->name('destroy');
            Route::post('/{theme}/set-default', [ThemeController::class, 'setDefault'])->name('set-default');
            Route::post('/{theme}/activate', [ThemeController::class, 'activate'])->name('activate');
            Route::post('/{theme}/deactivate', [ThemeController::class, 'deactivate'])->name('deactivate');
            Route::post('/{theme}/apply', [ThemeController::class, 'apply'])->name('apply');
            Route::get('/{theme}/preview', [ThemeController::class, 'preview'])->name('preview');
            Route::get('/export', [ThemeController::class, 'export'])->name('export');
        });

        Route::get('/theme', [SettingsController::class, 'theme'])->name('theme');
        Route::get('/layout', [SettingsController::class, 'layout'])->name('layout');
        Route::get('/colors', [SettingsController::class, 'colors'])->name('colors');

        // System Settings
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::get('/email', [SettingsController::class, 'email'])->name('email');
        Route::get('/backup', [SettingsController::class, 'backup'])->name('backup');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');

        // User Settings
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::get('/password', [SettingsController::class, 'password'])->name('password');
        Route::get('/preferences', [SettingsController::class, 'preferences'])->name('preferences');

        // Notification Settings
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/sounds', [SettingsController::class, 'sounds'])->name('sounds');

        // Integration Settings
        Route::get('/api', [SettingsController::class, 'api'])->name('api');
        Route::get('/webhooks', [SettingsController::class, 'webhooks'])->name('webhooks');

        // Advanced Settings
        Route::get('/logs', [SettingsController::class, 'logs'])->name('logs');
        Route::get('/maintenance', [SettingsController::class, 'maintenance'])->name('maintenance');
    });

    // Legacy Setting Routes (for backward compatibility)
    Route::prefix('setting')->name('setting.')->group(function () {
        Route::get('/', function () {
            return view('setting.dashboard');
        })->name('dashboard');
        Route::resource('theme-settings', ThemeSettingController::class);
    });

    // Report Routes
    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/', function () {
            return view('report.dashboard');
        })->name('dashboard');
        Route::get('warehouse', [WarehouseReportController::class, 'index'])->name('warehouse.index');
        Route::get('operational', [OperationalReportController::class, 'index'])->name('operational.index');
        Route::get('finance', [FinancialReportController::class, 'index'])->name('finance.index');
        Route::get('marketing', [MarketingReportController::class, 'index'])->name('marketing.index');
    });

    // New Report Module Web Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        // Main Reports Index
        Route::get('/', function () {
            return view('reports.index');
        })->name('index');

        // Dashboard Management
        Route::get('dashboard', [ReportsDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('dashboard/create', [ReportsDashboardController::class, 'create'])->name('dashboard.create');
        Route::get('dashboard/{dashboard}', [ReportsDashboardController::class, 'show'])->name('dashboard.show');
        Route::get('dashboard/{dashboard}/edit', [ReportsDashboardController::class, 'edit'])->name('dashboard.edit');

        // Report Templates
        Route::get('template', [ReportTemplateController::class, 'index'])->name('template.index');
        Route::get('template/create', [ReportTemplateController::class, 'create'])->name('template.create');
        Route::get('template/{template}', [ReportTemplateController::class, 'show'])->name('template.show');
        Route::get('template/{template}/edit', [ReportTemplateController::class, 'edit'])->name('template.edit');

        // Data Export
        Route::get('export', [DataExportController::class, 'index'])->name('export.index');
        Route::get('export/create', [DataExportController::class, 'create'])->name('export.create');
        Route::get('export/{export}', [DataExportController::class, 'show'])->name('export.show');
        Route::get('export/{export}/edit', [DataExportController::class, 'edit'])->name('export.edit');

        // KPI Management
        Route::get('kpi', [KpiController::class, 'index'])->name('kpi.index');
        Route::get('kpi/create', [KpiController::class, 'create'])->name('kpi.create');
        Route::get('kpi/{kpi}', [KpiController::class, 'show'])->name('kpi.show');
        Route::get('kpi/{kpi}/edit', [KpiController::class, 'edit'])->name('kpi.edit');

        // Analytics
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('analytics/create', [AnalyticsController::class, 'create'])->name('analytics.create');
        Route::get('analytics/{analytics}', [AnalyticsController::class, 'show'])->name('analytics.show');
        Route::get('analytics/{analytics}/edit', [AnalyticsController::class, 'edit'])->name('analytics.edit');

        // Report Alerts
        Route::get('alerts', [\App\Http\Controllers\Reports\ReportAlertController::class, 'index'])->name('alerts.index');
        Route::get('alerts/create', [\App\Http\Controllers\Reports\ReportAlertController::class, 'create'])->name('alerts.create');
        Route::get('alerts/{alert}', [\App\Http\Controllers\Reports\ReportAlertController::class, 'show'])->name('alerts.show');
        Route::get('alerts/{alert}/edit', [\App\Http\Controllers\Reports\ReportAlertController::class, 'edit'])->name('alerts.edit');

        // Report Favorites
        Route::get('favorites', [\App\Http\Controllers\Reports\ReportFavoriteController::class, 'index'])->name('favorites.index');

        // Report History
        Route::get('history', [\App\Http\Controllers\Reports\ReportHistoryController::class, 'index'])->name('history.index');
        Route::get('history/{history}', [\App\Http\Controllers\Reports\ReportHistoryController::class, 'show'])->name('history.show');
    });
});

// Reports Routes
Route::prefix('reports')->name('reports.')->middleware(['auth'])->group(function () {
    // Dashboard Report
    Route::get('/dashboard', [App\Http\Controllers\Reports\DashboardReportController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/statistics', [App\Http\Controllers\Reports\DashboardReportController::class, 'getStatistics'])->name('dashboard.statistics');

    // Marketing Reports
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\MarketingReportController::class, 'index'])->name('index');
        Route::get('/prospect', [App\Http\Controllers\Reports\MarketingReportController::class, 'prospectReport'])->name('prospect');
        Route::get('/survey', [App\Http\Controllers\Reports\MarketingReportController::class, 'surveyReport'])->name('survey');
        Route::get('/quotation', [App\Http\Controllers\Reports\MarketingReportController::class, 'quotationReport'])->name('quotation');
        Route::get('/contract', [App\Http\Controllers\Reports\MarketingReportController::class, 'contractReport'])->name('contract');
        Route::get('/job-advice', [App\Http\Controllers\Reports\MarketingReportController::class, 'jobAdviceReport'])->name('job-advice');
        Route::get('/sales-activity', [App\Http\Controllers\Reports\MarketingReportController::class, 'salesActivityReport'])->name('sales-activity');

        // Export routes
        Route::post('/export/prospect', [App\Http\Controllers\Reports\MarketingReportController::class, 'exportProspectReport'])->name('export.prospect');
        Route::post('/export/prospect/pdf', [App\Http\Controllers\Reports\MarketingReportController::class, 'exportProspectReportPdf'])->name('export.prospect.pdf');
        Route::post('/export/survey', [App\Http\Controllers\Reports\MarketingReportController::class, 'exportSurveyReport'])->name('export.survey');
        Route::post('/export/survey/pdf', [App\Http\Controllers\Reports\MarketingReportController::class, 'exportSurveyReportPdf'])->name('export.survey.pdf');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\MarketingReportController::class, 'getMarketingStatistics'])->name('statistics');
    });

    // Operational Reports
    Route::prefix('operational')->name('operational.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\OperationalReportController::class, 'index'])->name('index');
        Route::get('/job-schedule', [App\Http\Controllers\Reports\OperationalReportController::class, 'jobScheduleReport'])->name('job-schedule');
        Route::get('/job-assignment', [App\Http\Controllers\Reports\OperationalReportController::class, 'jobAssignmentReport'])->name('job-assignment');
        Route::get('/material-issue', [App\Http\Controllers\Reports\OperationalReportController::class, 'materialIssueReport'])->name('material-issue');
        Route::get('/team-performance', [App\Http\Controllers\Reports\OperationalReportController::class, 'teamPerformanceReport'])->name('team-performance');
        Route::get('/customer-service', [App\Http\Controllers\Reports\OperationalReportController::class, 'customerServiceReport'])->name('customer-service');

        // Export routes
        Route::post('/export/job-schedule', [App\Http\Controllers\Reports\OperationalReportController::class, 'exportJobScheduleReport'])->name('export.job-schedule');
        Route::post('/export/job-assignment', [App\Http\Controllers\Reports\OperationalReportController::class, 'exportJobAssignmentReport'])->name('export.job-assignment');
        Route::post('/export/material-issue', [App\Http\Controllers\Reports\OperationalReportController::class, 'exportMaterialIssueReport'])->name('export.material-issue');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\OperationalReportController::class, 'getOperationalStatistics'])->name('statistics');
    });

    // Financial Reports
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\FinancialReportController::class, 'index'])->name('index');
        Route::get('/quotation', [App\Http\Controllers\Reports\FinancialReportController::class, 'quotationReport'])->name('quotation');
        Route::get('/contract', [App\Http\Controllers\Reports\FinancialReportController::class, 'contractReport'])->name('contract');
        Route::get('/invoice', [App\Http\Controllers\Reports\FinancialReportController::class, 'invoiceReport'])->name('invoice');
        Route::get('/payment', [App\Http\Controllers\Reports\FinancialReportController::class, 'paymentReport'])->name('payment');
        Route::get('/revenue', [App\Http\Controllers\Reports\FinancialReportController::class, 'revenueReport'])->name('revenue');
        Route::get('/customer', [App\Http\Controllers\Reports\FinancialReportController::class, 'customerFinancialReport'])->name('customer');

        // Export routes
        Route::post('/export/quotation', [App\Http\Controllers\Reports\FinancialReportController::class, 'exportQuotationReport'])->name('export.quotation');
        Route::post('/export/contract', [App\Http\Controllers\Reports\FinancialReportController::class, 'exportContractReport'])->name('export.contract');
        Route::post('/export/invoice', [App\Http\Controllers\Reports\FinancialReportController::class, 'exportInvoiceReport'])->name('export.invoice');
        Route::post('/export/payment', [App\Http\Controllers\Reports\FinancialReportController::class, 'exportPaymentReport'])->name('export.payment');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\FinancialReportController::class, 'getFinancialStatistics'])->name('statistics');
    });

    // Inventory Reports
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\InventoryReportController::class, 'index'])->name('index');
        Route::get('/stock', [App\Http\Controllers\Reports\InventoryReportController::class, 'stockReport'])->name('stock');
        Route::get('/category', [App\Http\Controllers\Reports\InventoryReportController::class, 'categoryReport'])->name('category');
        Route::get('/brand', [App\Http\Controllers\Reports\InventoryReportController::class, 'brandReport'])->name('brand');
        Route::get('/supplier', [App\Http\Controllers\Reports\InventoryReportController::class, 'supplierReport'])->name('supplier');
        Route::get('/material-issue', [App\Http\Controllers\Reports\InventoryReportController::class, 'materialIssueReport'])->name('material-issue');
        Route::get('/stock-movement', [App\Http\Controllers\Reports\InventoryReportController::class, 'stockMovementReport'])->name('stock-movement');
        Route::get('/low-stock-alert', [App\Http\Controllers\Reports\InventoryReportController::class, 'lowStockAlertReport'])->name('low-stock-alert');

        // Export routes
        Route::post('/export/stock', [App\Http\Controllers\Reports\InventoryReportController::class, 'exportStockReport'])->name('export.stock');
        Route::post('/export/material-issue', [App\Http\Controllers\Reports\InventoryReportController::class, 'exportMaterialIssueReport'])->name('export.material-issue');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\InventoryReportController::class, 'getInventoryStatistics'])->name('statistics');
    });

    // Customer Reports
    Route::prefix('customer')->name('customer.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\CustomerReportController::class, 'index'])->name('index');
        Route::get('/list', [App\Http\Controllers\Reports\CustomerReportController::class, 'customerListReport'])->name('list');
        Route::get('/activity', [App\Http\Controllers\Reports\CustomerReportController::class, 'customerActivityReport'])->name('activity');
        Route::get('/financial', [App\Http\Controllers\Reports\CustomerReportController::class, 'customerFinancialReport'])->name('financial');

        // Export routes
        Route::post('/export/list', [App\Http\Controllers\Reports\CustomerReportController::class, 'exportCustomerListReport'])->name('export.list');
        Route::post('/export/financial', [App\Http\Controllers\Reports\CustomerReportController::class, 'exportCustomerFinancialReport'])->name('export.financial');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\CustomerReportController::class, 'getCustomerStatistics'])->name('statistics');
    });

    // HR Reports
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\HrReportController::class, 'index'])->name('index');
        Route::get('/team-performance', [App\Http\Controllers\Reports\HrReportController::class, 'teamPerformanceReport'])->name('team-performance');
        Route::get('/team-workload', [App\Http\Controllers\Reports\HrReportController::class, 'teamWorkloadReport'])->name('team-workload');
        Route::get('/job-assignment', [App\Http\Controllers\Reports\HrReportController::class, 'jobAssignmentReport'])->name('job-assignment');
        Route::get('/material-issue', [App\Http\Controllers\Reports\HrReportController::class, 'materialIssueReport'])->name('material-issue');
        Route::get('/team-efficiency', [App\Http\Controllers\Reports\HrReportController::class, 'teamEfficiencyReport'])->name('team-efficiency');

        // Export routes
        Route::post('/export/team-performance', [App\Http\Controllers\Reports\HrReportController::class, 'exportTeamPerformanceReport'])->name('export.team-performance');
        Route::post('/export/job-assignment', [App\Http\Controllers\Reports\HrReportController::class, 'exportJobAssignmentReport'])->name('export.job-assignment');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\HrReportController::class, 'getHrStatistics'])->name('statistics');
    });

    // Warehouse Reports
    Route::prefix('warehouse-reports')->name('warehouse-reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Reports\WarehouseReportController::class, 'index'])->name('index');
        Route::get('/inventory', [App\Http\Controllers\Reports\WarehouseReportController::class, 'inventory'])->name('inventory');
        Route::get('/stock', [App\Http\Controllers\Reports\WarehouseReportController::class, 'stock'])->name('stock');
        Route::get('/movements', [App\Http\Controllers\Reports\WarehouseReportController::class, 'movements'])->name('movements');
        Route::get('/performance', [App\Http\Controllers\Reports\WarehouseReportController::class, 'performance'])->name('performance');
        Route::get('/stock-opname', [App\Http\Controllers\Reports\WarehouseReportController::class, 'stockOpname'])->name('stock-opname');

        // Export routes
        Route::post('/export/inventory', [App\Http\Controllers\Reports\WarehouseReportController::class, 'exportInventoryReport'])->name('export.inventory');
        Route::post('/export/movements', [App\Http\Controllers\Reports\WarehouseReportController::class, 'exportMovementsReport'])->name('export.movements');
        Route::get('/export/stock-opname', [App\Http\Controllers\Reports\WarehouseReportController::class, 'exportStockOpname'])->name('export.stock-opname');
        Route::get('/export/stock-opname/pdf', [App\Http\Controllers\Reports\WarehouseReportController::class, 'exportStockOpnamePdf'])->name('export.stock-opname.pdf');
        Route::get('/export/stock', [App\Http\Controllers\Reports\WarehouseReportController::class, 'exportStock'])->name('export.stock');
        Route::get('/export/stock/pdf', [App\Http\Controllers\Reports\WarehouseReportController::class, 'exportStockPdf'])->name('export.stock.pdf');

        // API routes
        Route::get('/statistics', [App\Http\Controllers\Reports\WarehouseReportController::class, 'getWarehouseStatistics'])->name('statistics');
    });
});

// Audit Trail Routes (Outside reports prefix)
Route::prefix('audit-trails')->name('audit-trails.')->middleware(['auth'])->group(function () {
    Route::get('/', [AuditTrailController::class, 'index'])->name('index');
    Route::get('/login-history', [AuditTrailController::class, 'loginHistory'])->name('login-history');
    Route::get('/export', [AuditTrailController::class, 'export'])->name('export');
    Route::get('/{tableName}/{recordId}', [AuditTrailController::class, 'show'])->name('show');
});

// Access Control Routes (Outside reports prefix)
Route::prefix('access-control')->name('access-control.')->middleware(['auth'])->group(function () {
    Route::get('/', [AccessControlController::class, 'index'])->name('index')->middleware('permission:access-control.view');
    Route::post('/users/{user}/access-level', [AccessControlController::class, 'setAccessLevel'])->name('set-access-level')->middleware('permission:access-control.update');
    Route::post('/users/{user}/login-restrictions', [AccessControlController::class, 'setLoginRestrictions'])->name('set-login-restrictions')->middleware('permission:access-control.update');
    Route::post('/users/{user}/check-access', [AccessControlController::class, 'checkAccess'])->name('check-access');
    Route::get('/users/{user}/check-login-time', [AccessControlController::class, 'checkLoginTime'])->name('check-login-time');
    Route::get('/users/{user}/access-summary', [AccessControlController::class, 'getUserAccessSummary'])->name('access-summary')->middleware('permission:access-control.view');
    Route::post('/users/{user}/toggle-multi-login', [AccessControlController::class, 'toggleMultiLogin'])->name('toggle-multi-login')->middleware('permission:access-control.update');
    Route::post('/users/{user}/toggle-freeze', [AccessControlController::class, 'toggleFreeze'])->name('toggle-freeze')->middleware('permission:access-control.update');
    Route::post('/users/{user}/toggle-screenshot', [AccessControlController::class, 'toggleScreenshot'])->name('toggle-screenshot')->middleware('permission:access-control.update');
});

// File Management Routes
Route::prefix('files')->name('files.')->middleware(['auth'])->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::get('/categories', [FileController::class, 'categories'])->name('categories');
    Route::post('/upload', [FileController::class, 'store'])->name('store');
    Route::get('/{file}/download', [FileController::class, 'download'])->name('download');
    Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
});

// Emergency Contact Routes
Route::prefix('emergency-contacts')->name('emergency-contacts.')->middleware(['auth'])->group(function () {
    Route::get('/', [EmergencyContactController::class, 'index'])->name('index');
    Route::get('/create', [EmergencyContactController::class, 'create'])->name('create');
    Route::post('/', [EmergencyContactController::class, 'store'])->name('store');
    Route::get('/{emergencyContact}', [EmergencyContactController::class, 'show'])->name('show');
    Route::get('/{emergencyContact}/edit', [EmergencyContactController::class, 'edit'])->name('edit');
    Route::put('/{emergencyContact}', [EmergencyContactController::class, 'update'])->name('update');
    Route::delete('/{emergencyContact}', [EmergencyContactController::class, 'destroy'])->name('destroy');
    Route::patch('/{emergencyContact}/toggle-status', [EmergencyContactController::class, 'toggleStatus'])->name('toggle-status');

    // Emergency Logs
    Route::get('/emergency-logs', [EmergencyContactController::class, 'emergencyLogs'])->name('emergency-logs');
    Route::get('/emergency-logs/create', [EmergencyContactController::class, 'createEmergencyLog'])->name('create-emergency-log');
    Route::post('/emergency-logs', [EmergencyContactController::class, 'storeEmergencyLog'])->name('store-emergency-log');
    Route::get('/emergency-logs/{emergencyLog}', [EmergencyContactController::class, 'showEmergencyLog'])->name('show-emergency-log');
    Route::patch('/emergency-logs/{emergencyLog}/status', [EmergencyContactController::class, 'updateEmergencyLogStatus'])->name('update-emergency-log-status');
    Route::post('/emergency-logs/{emergencyLog}/response-action', [EmergencyContactController::class, 'addResponseAction'])->name('add-response-action');

    // Statistics
    Route::get('/statistics', [EmergencyContactController::class, 'statistics'])->name('statistics');
});

// Data Hierarchy Enhancement Routes
Route::prefix('data-hierarchy')->name('data-hierarchy.')->middleware(['auth'])->group(function () {
    Route::get('/customer/{customerId}', [App\Http\Controllers\DataHierarchyController::class, 'getCustomerHierarchy'])->name('customer');
    Route::get('/contract/{contractId}', [App\Http\Controllers\DataHierarchyController::class, 'getContractHierarchy'])->name('contract');
    Route::get('/building/{buildingId}', [App\Http\Controllers\DataHierarchyController::class, 'getBuildingHierarchy'])->name('building');
    Route::get('/statistics', [App\Http\Controllers\DataHierarchyController::class, 'getHierarchyStatistics'])->name('statistics');
    Route::get('/search', [App\Http\Controllers\DataHierarchyController::class, 'searchHierarchy'])->name('search');
    Route::get('/tree', [App\Http\Controllers\DataHierarchyController::class, 'getHierarchyTree'])->name('tree');
    Route::get('/visualization', [App\Http\Controllers\DataHierarchyController::class, 'getHierarchyVisualization'])->name('visualization');
    Route::get('/export', [App\Http\Controllers\DataHierarchyController::class, 'exportHierarchy'])->name('export');
});

// Building Multi-User Enhancement Routes
Route::prefix('building-multi-user')->name('building-multi-user.')->middleware(['auth'])->group(function () {
    Route::get('/building/{buildingId}/users', [App\Http\Controllers\BuildingMultiUserController::class, 'getBuildingUsers'])->name('building-users');
    Route::post('/assign-user', [App\Http\Controllers\BuildingMultiUserController::class, 'assignUser'])->name('assign-user');
    Route::delete('/remove-user', [App\Http\Controllers\BuildingMultiUserController::class, 'removeUser'])->name('remove-user');
    Route::post('/set-primary-user', [App\Http\Controllers\BuildingMultiUserController::class, 'setPrimaryUser'])->name('set-primary-user');
    Route::put('/update-user-role', [App\Http\Controllers\BuildingMultiUserController::class, 'updateUserRole'])->name('update-user-role');
    Route::get('/user/{userId}/buildings', [App\Http\Controllers\BuildingMultiUserController::class, 'getUserBuildings'])->name('user-buildings');
    Route::get('/statistics', [App\Http\Controllers\BuildingMultiUserController::class, 'getBuildingStatistics'])->name('statistics');
    Route::post('/bulk-assign-users', [App\Http\Controllers\BuildingMultiUserController::class, 'bulkAssignUsers'])->name('bulk-assign-users');
    Route::get('/available-users', [App\Http\Controllers\BuildingMultiUserController::class, 'getAvailableUsers'])->name('available-users');
});

// Job Reference Enhancement Routes
Route::prefix('job-reference')->name('job-reference.')->middleware(['auth'])->group(function () {
    Route::post('/generate', [App\Http\Controllers\JobReferenceController::class, 'generateJobReference'])->name('generate');
    Route::post('/assign', [App\Http\Controllers\JobReferenceController::class, 'assignJobReference'])->name('assign');
    Route::get('/details', [App\Http\Controllers\JobReferenceController::class, 'getJobReferenceDetails'])->name('details');
    Route::get('/search', [App\Http\Controllers\JobReferenceController::class, 'searchJobReferences'])->name('search');
    Route::get('/statistics', [App\Http\Controllers\JobReferenceController::class, 'getJobReferenceStatistics'])->name('statistics');
    Route::post('/validate', [App\Http\Controllers\JobReferenceController::class, 'validateJobReference'])->name('validate');
    Route::post('/bulk-generate', [App\Http\Controllers\JobReferenceController::class, 'bulkGenerateJobReferences'])->name('bulk-generate');
    Route::get('/history', [App\Http\Controllers\JobReferenceController::class, 'getJobReferenceHistory'])->name('history');
    Route::get('/without-references', [App\Http\Controllers\JobReferenceController::class, 'getJobSchedulesWithoutReferences'])->name('without-references');
    Route::post('/auto-assign', [App\Http\Controllers\JobReferenceController::class, 'autoAssignJobReferences'])->name('auto-assign');
});

// Building Marketing Enhancement Routes
Route::prefix('building-marketing')->name('building-marketing.')->middleware(['auth'])->group(function () {
    Route::post('/assign-building', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'assignBuilding'])->name('assign-building');
    Route::delete('/remove-building', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'removeBuilding'])->name('remove-building');
    Route::get('/pipeline/{pipelineId}/buildings', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'getPipelineBuildings'])->name('pipeline-buildings');
    Route::get('/pipeline/{pipelineId}/available-buildings', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'getAvailableBuildings'])->name('available-buildings');
    Route::post('/auto-assign-from-surveys', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'autoAssignFromSurveys'])->name('auto-assign-from-surveys');
    Route::get('/statistics', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'getStatistics'])->name('statistics');
    Route::get('/buildings-by-status', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'getBuildingsByStatus'])->name('buildings-by-status');
    Route::post('/bulk-assign', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'bulkAssignBuildings'])->name('bulk-assign');
    Route::get('/building/{buildingId}/details', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'getBuildingDetails'])->name('building-details');
    Route::get('/search-buildings', [App\Http\Controllers\Marketing\BuildingMarketingController::class, 'searchBuildings'])->name('search-buildings');
});

// Multiple Survey Enhancement Routes
Route::prefix('multiple-survey')->name('multiple-survey.')->middleware(['auth'])->group(function () {
    Route::post('/add-survey', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'addSurvey'])->name('add-survey');
    Route::delete('/remove-survey', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'removeSurvey'])->name('remove-survey');
    Route::get('/quotation/{quotationId}/surveys', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'getQuotationSurveys'])->name('quotation-surveys');
    Route::get('/quotation/{quotationId}/available-surveys', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'getAvailableSurveys'])->name('available-surveys');
    Route::post('/bulk-add-surveys', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'bulkAddSurveys'])->name('bulk-add-surveys');
    Route::get('/quotation/{quotationId}/statistics', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'getQuotationStatistics'])->name('quotation-statistics');
    Route::get('/quotation/{quotationId}/validate', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'validateQuotation'])->name('validate-quotation');
    Route::get('/quotation/{quotationId}/survey/{surveyId}/details', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'getSurveyDetails'])->name('survey-details');
    Route::post('/quotation/{quotationId}/reorder-surveys', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'reorderSurveys'])->name('reorder-surveys');
    Route::post('/quotation/{quotationId}/update-totals', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'updateQuotationTotals'])->name('update-totals');
    Route::get('/quotation/{quotationId}/with-surveys', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'getQuotationWithSurveys'])->name('quotation-with-surveys');
    Route::get('/quotation/{quotationId}/search-surveys', [App\Http\Controllers\Marketing\MultipleSurveyController::class, 'searchSurveys'])->name('search-surveys');
});

// Survey Customer Enhancement Routes
Route::prefix('survey-customer')->name('survey-customer.')->middleware(['auth'])->group(function () {
    Route::post('/add-survey-from-same-customer', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'addSurveyFromSameCustomer'])->name('add-survey-from-same-customer');
    Route::get('/quotation/{quotationId}/surveys-from-same-customer', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getSurveysFromSameCustomer'])->name('surveys-from-same-customer');
    Route::get('/quotation/{quotationId}/customer-surveys', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getCustomerSurveysForQuotation'])->name('customer-surveys');
    Route::get('/quotation/{quotationId}/customer-info', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getCustomerInfoForQuotation'])->name('customer-info');
    Route::get('/quotation/{quotationId}/validate-customer-surveys', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'validateCustomerSurveys'])->name('validate-customer-surveys');
    Route::get('/quotation/{quotationId}/customer-survey-statistics', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getCustomerSurveyStatistics'])->name('customer-survey-statistics');
    Route::post('/bulk-add-surveys-from-same-customer', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'bulkAddSurveysFromSameCustomer'])->name('bulk-add-surveys-from-same-customer');
    Route::get('/customer/{customerId}/survey-history', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getCustomerSurveyHistory'])->name('customer-survey-history');
    Route::get('/quotation/{quotationId}/customer-survey-recommendations', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getCustomerSurveyRecommendations'])->name('customer-survey-recommendations');
    Route::post('/quotation/{quotationId}/update-totals', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'updateQuotationTotals'])->name('update-totals');
    Route::get('/quotation/{quotationId}/with-customer-surveys', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getQuotationWithCustomerSurveys'])->name('quotation-with-customer-surveys');
    Route::get('/quotation/{quotationId}/search-customer-surveys', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'searchCustomerSurveys'])->name('search-customer-surveys');
    Route::get('/customer/{customerId}/survey-analytics', [App\Http\Controllers\Marketing\SurveyCustomerController::class, 'getCustomerSurveyAnalytics'])->name('customer-survey-analytics');
});

// Technician Package Enhancement Routes
Route::prefix('technician-package')->name('technician-package.')->middleware(['auth'])->group(function () {
    Route::post('/create-package', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'createPackage'])->name('create-package');
    Route::post('/complete-item', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'completeItem'])->name('complete-item');
    Route::post('/uncomplete-item', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'uncompleteItem'])->name('uncomplete-item');
    Route::post('/complete-package', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'completePackage'])->name('complete-package');
    Route::get('/package/{packageId}', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getPackage'])->name('get-package');
    Route::get('/job-schedule/{jobScheduleId}/packages', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getPackagesForJobSchedule'])->name('packages-for-job-schedule');
    Route::get('/technician/{technicianId}/packages', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getPackagesForTechnician'])->name('packages-for-technician');
    Route::get('/package/{packageId}/statistics', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getPackageStatistics'])->name('package-statistics');
    Route::get('/technician/{technicianId}/analytics', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getTechnicianAnalytics'])->name('technician-analytics');
    Route::get('/package/{packageId}/validate', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'validatePackageCompletion'])->name('validate-package');
    Route::get('/package/{packageId}/progress', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getPackageProgress'])->name('package-progress');
    Route::get('/technician/{technicianId}/dashboard', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getTechnicianDashboard'])->name('technician-dashboard');
    Route::get('/technician/{technicianId}/search-packages', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'searchPackages'])->name('search-packages');
    Route::get('/package/{packageId}/items', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'getPackageItems'])->name('package-items');
    Route::put('/package/{packageId}/item/{itemId}', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'updatePackageItem'])->name('update-package-item');
    Route::delete('/package/{packageId}/item/{itemId}', [App\Http\Controllers\Technician\TechnicianPackageController::class, 'deletePackageItem'])->name('delete-package-item');
});

// Bank Receipt Enhancement Routes
Route::prefix('bank-receipt-enhancement')->name('bank-receipt-enhancement.')->middleware(['auth'])->group(function () {
    Route::post('/auto-populate-from-invoice', [App\Http\Controllers\Finance\BankReceiptController::class, 'autoPopulateFromInvoice'])->name('auto-populate-from-invoice');
    Route::get('/invoice-data', [App\Http\Controllers\Finance\BankReceiptController::class, 'getInvoiceData'])->name('invoice-data');
    Route::post('/validate-invoice', [App\Http\Controllers\Finance\BankReceiptController::class, 'validateInvoice'])->name('validate-invoice');
    Route::post('/create-from-invoice', [App\Http\Controllers\Finance\BankReceiptController::class, 'createFromInvoice'])->name('create-from-invoice');
    Route::get('/available-invoices', [App\Http\Controllers\Finance\BankReceiptController::class, 'getAvailableInvoices'])->name('available-invoices');
    Route::get('/analytics', [App\Http\Controllers\Finance\BankReceiptController::class, 'getAnalytics'])->name('analytics');
    Route::post('/auto-match-invoice/{bankReceipt}', [App\Http\Controllers\Finance\BankReceiptController::class, 'autoMatchWithInvoice'])->name('auto-match-invoice');
    Route::get('/enhanced-statistics', [App\Http\Controllers\Finance\BankReceiptController::class, 'getEnhancedStatistics'])->name('enhanced-statistics');
});

// Role Group Enhancement Routes
Route::prefix('role-groups')->name('system.role-groups.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\System\RoleGroupController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\System\RoleGroupController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\System\RoleGroupController::class, 'store'])->name('store');
    Route::get('/{roleGroup}', [App\Http\Controllers\System\RoleGroupController::class, 'show'])->name('show');
    Route::get('/{roleGroup}/edit', [App\Http\Controllers\System\RoleGroupController::class, 'edit'])->name('edit');
    Route::put('/{roleGroup}', [App\Http\Controllers\System\RoleGroupController::class, 'update'])->name('update');
    Route::delete('/{roleGroup}', [App\Http\Controllers\System\RoleGroupController::class, 'destroy'])->name('destroy');
    Route::post('/{roleGroup}/add-role', [App\Http\Controllers\System\RoleGroupController::class, 'addRole'])->name('add-role');
    Route::delete('/{roleGroup}/remove-role', [App\Http\Controllers\System\RoleGroupController::class, 'removeRole'])->name('remove-role');
    Route::get('/{roleGroup}/available-roles', [App\Http\Controllers\System\RoleGroupController::class, 'getAvailableRoles'])->name('available-roles');
    Route::get('/statistics', [App\Http\Controllers\System\RoleGroupController::class, 'statistics'])->name('statistics');
    Route::post('/{roleGroup}/duplicate', [App\Http\Controllers\System\RoleGroupController::class, 'duplicate'])->name('duplicate');
    Route::get('/{roleGroup}/permissions', [App\Http\Controllers\System\RoleGroupController::class, 'getPermissions'])->name('permissions');
    Route::post('/validate', [App\Http\Controllers\System\RoleGroupController::class, 'validate'])->name('validate');
    Route::post('/bulk-action', [App\Http\Controllers\System\RoleGroupController::class, 'bulkAction'])->name('bulk-action');
});

// Customer Type Management Routes
Route::prefix('system/customer-types')->name('system.customer-types.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\System\CustomerTypeController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\System\CustomerTypeController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\System\CustomerTypeController::class, 'store'])->name('store');
    Route::get('/{customerType}', [App\Http\Controllers\System\CustomerTypeController::class, 'show'])->name('show');
    Route::get('/{customerType}/edit', [App\Http\Controllers\System\CustomerTypeController::class, 'edit'])->name('edit');
    Route::put('/{customerType}', [App\Http\Controllers\System\CustomerTypeController::class, 'update'])->name('update');
    Route::delete('/{customerType}', [App\Http\Controllers\System\CustomerTypeController::class, 'destroy'])->name('destroy');
    Route::get('/api/list', [App\Http\Controllers\System\CustomerTypeController::class, 'getCustomerTypes'])->name('api.list');
});

// Location API Routes for Cascade Dropdown
Route::prefix('api')->group(function () {
    Route::get('/cities/{provinceId}', function ($provinceId) {
        $cities = \App\Models\City::where('province_id', $provinceId)->orderBy('name')->get(['id', 'name']);

        return response()->json($cities);
    });

    Route::get('/districts/{cityId}', function ($cityId) {
        $districts = \App\Models\District::where('city_id', $cityId)->orderBy('name')->get(['id', 'name']);

        return response()->json($districts);
    });

    Route::get('/subdistricts/{districtId}', function ($districtId) {
        $subdistricts = \App\Models\Subdistrict::where('district_id', $districtId)->orderBy('name')->get(['id', 'name']);

        return response()->json($subdistricts);
    });

    Route::get('/subdistricts/{subdistrictId}/postal-code', function ($subdistrictId) {
        $subdistrict = \App\Models\Subdistrict::find($subdistrictId);

        return response()->json(['postal_code' => $subdistrict->postal_code ?? '']);
    });
});

// Salutation Management Routes
Route::prefix('system/salutations')->name('system.salutations.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\System\SalutationController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\System\SalutationController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\System\SalutationController::class, 'store'])->name('store');
    Route::get('/{salutation}/edit', [App\Http\Controllers\System\SalutationController::class, 'edit'])->name('edit');
    Route::put('/{salutation}', [App\Http\Controllers\System\SalutationController::class, 'update'])->name('update');
    Route::delete('/{salutation}', [App\Http\Controllers\System\SalutationController::class, 'destroy'])->name('destroy');
});

// Position Management Routes
Route::prefix('system/positions')->name('system.positions.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\System\PositionController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\System\PositionController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\System\PositionController::class, 'store'])->name('store');
    Route::get('/{position}/edit', [App\Http\Controllers\System\PositionController::class, 'edit'])->name('edit');
    Route::put('/{position}', [App\Http\Controllers\System\PositionController::class, 'update'])->name('update');
    Route::delete('/{position}', [App\Http\Controllers\System\PositionController::class, 'destroy'])->name('destroy');
});

// Access Control Management Routes
Route::prefix('access-control')->name('access-control.')->group(function () {
    Route::get('/', [AccessControlController::class, 'index'])->name('index')->middleware('permission:access-control.view');

    // User Access Configuration
    Route::post('users/{user}/access-level', [AccessControlController::class, 'setAccessLevel'])->name('users.access-level')->middleware('permission:access-control.update');
    Route::post('users/{user}/login-restriction', [AccessControlController::class, 'setLoginRestrictions'])->name('users.login-restriction')->middleware('permission:access-control.update');

    // Feature Toggles (Single click actions)
    Route::post('users/{user}/toggle-multi-login', [AccessControlController::class, 'toggleMultiLogin'])->name('users.toggle-multi-login')->middleware('permission:access-control.update');
    Route::post('users/{user}/toggle-freeze', [AccessControlController::class, 'toggleFreeze'])->name('users.toggle-freeze')->middleware('permission:access-control.update');
    Route::post('users/{user}/toggle-screenshot', [AccessControlController::class, 'toggleScreenshot'])->name('users.toggle-screenshot')->middleware('permission:access-control.update');

    // Information & Checks
    Route::get('users/{user}/summary', [AccessControlController::class, 'getUserAccessSummary'])->name('users.summary')->middleware('permission:access-control.view');
    Route::post('users/check-access', [AccessControlController::class, 'checkAccess'])->name('users.check-access');
});
