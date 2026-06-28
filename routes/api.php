<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Marketing\ProspectController;
use App\Http\Controllers\Marketing\SurveyController;
use App\Http\Controllers\Marketing\QuotationController;
use App\Http\Controllers\Marketing\ContractController;
use App\Http\Controllers\Marketing\JobAdviceController;
use App\Http\Controllers\Marketing\LostUnitReportController;
use App\Http\Controllers\Marketing\SalesActivityController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Http\Controllers\Operational\TeamController;
use App\Http\Controllers\Operational\BuildingController;
use App\Http\Controllers\Operational\MasterRoomController;
use App\Http\Controllers\Operational\RoomRentalUnitController;
use App\Http\Controllers\Operational\JobAssignScheduleController;
use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Http\Controllers\Operational\JobReportController;
use App\Http\Controllers\Operational\TechnicianLocationController;
use App\Http\Controllers\Operational\JobRouteController;
use App\Http\Controllers\Operational\ServiceHistoryController;
use App\Http\Controllers\Operational\UnitInstallationController;
use App\Http\Controllers\Operational\JobSignatureController;
use App\Http\Controllers\Operational\TemperatureRecordController;
use App\Http\Controllers\Api\Mobile\OperationalController;
use App\Http\Controllers\Api\Mobile\JobController;
use App\Http\Controllers\Api\MobileController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\UserPhotoController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\InvoiceFollowUpController;
use App\Http\Controllers\Finance\BankReceiptController;
use App\Http\Controllers\Finance\VirtualAccountImportController;
use App\Http\Controllers\Finance\VirtualAccountExportController;
use App\Http\Controllers\Finance\TaxSettingController;
use App\Http\Controllers\Finance\TaxFileImportController;
use App\Http\Controllers\Finance\TaxFileExportController;
use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Http\Controllers\Warehouse\InventoryRequestController;
use App\Http\Controllers\Warehouse\ProductTypeController;
use App\Http\Controllers\Warehouse\MasterProductController;
use App\Http\Controllers\Warehouse\MasterRentalController;
use App\Http\Controllers\Warehouse\StockOpnameController;
use App\Http\Controllers\Warehouse\StockAdjustmentController;
use App\Http\Controllers\Warehouse\SerialNumberController;
use App\Http\Controllers\Warehouse\UnitOnWallController;
use App\Http\Controllers\System\UserController;
use App\Http\Controllers\System\DepartmentController;
use App\Http\Controllers\System\ProvinceController;
use App\Http\Controllers\System\NotificationController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CustomerController;
use App\Http\Controllers\Company\CustomerContactController;
use App\Http\Controllers\Company\SupplierController;
use App\Http\Controllers\Company\CustomerTaxController;
use App\Http\Controllers\Company\CompanyVirtualAccountController;
use App\Http\Controllers\Company\BankPaymentController;
use App\Http\Controllers\Company\MasterPriceSlabController;
use App\Http\Controllers\Company\AccessManagementController;
use App\Http\Controllers\Other\HakAksesController;
use App\Http\Controllers\Other\MasterOptionController;
use App\Http\Controllers\Settings\ThemeSettingController;



// Public API Routes
Route::post('/login', [AuthController::class, 'apiLogin'])->name('api.login');
Route::post('/logout', [AuthController::class, 'apiLogout'])->name('api.logout');

// Indonesia Regions API Routes (Public)
Route::prefix('v1/regions')->name('regions.')->group(function () {
    Route::get('provinces', [RegionController::class, 'getProvinces'])->name('provinces');
    Route::get('cities', [RegionController::class, 'getCities'])->name('cities');
    Route::get('districts', [RegionController::class, 'getDistricts'])->name('districts');
    Route::get('subdistricts', [RegionController::class, 'getSubdistricts'])->name('subdistricts');
    Route::get('villages', [RegionController::class, 'getVillages'])->name('villages');
});

// Frontend API Routes (no middleware for testing)
Route::get('companies', [App\Http\Controllers\Company\CompanyController::class, 'getCompanies'])->name('api.companies');
Route::get('companies/list', [App\Http\Controllers\Company\CompanyController::class, 'getCompanies'])->name('api.companies.list');
Route::post('companies', [App\Http\Controllers\Company\CompanyController::class, 'store'])->name('api.companies.store');
Route::put('companies/{company}', [App\Http\Controllers\Company\CompanyController::class, 'update'])->name('api.companies.update');
Route::post('companies/bulk-delete', [App\Http\Controllers\Company\CompanyController::class, 'bulkDelete'])->name('api.companies.bulk-delete');

// Billing Group API Routes (for Contract detail page)
// Note: routes/api.php already has '/api' prefix, no need to add it again
Route::get('billing-groups/{billingGroup}', [App\Http\Controllers\Finance\BillingGroupController::class, 'show'])->name('api.billing-groups.show');
Route::put('billing-groups/{billingGroup}', [App\Http\Controllers\Finance\BillingGroupController::class, 'update'])->name('api.billing-groups.update');
Route::delete('billing-groups/{billingGroup}', [App\Http\Controllers\Finance\BillingGroupController::class, 'destroy'])->name('api.billing-groups.destroy');

// Contract-specific Billing Group Management
Route::post('contracts/{contract}/billing-groups', [App\Http\Controllers\Finance\BillingGroupController::class, 'storeForContract'])->name('api.contracts.billing-groups.store');
Route::post('contracts/{contract}/billing-groups/link', [App\Http\Controllers\Finance\BillingGroupController::class, 'linkExistingToContract'])->name('api.contracts.billing-groups.link');
Route::get('contracts/{contract}/billing-groups/coverage', [App\Http\Controllers\Finance\BillingGroupController::class, 'getBuildingCoverage'])->name('api.contracts.billing-groups.coverage');
Route::get('contracts/{contract}/billing-groups/{billingGroup}/buildings', [App\Http\Controllers\Finance\BillingGroupController::class, 'getBuildingsForBillingGroup'])->name('api.contracts.billing-groups.buildings');

// Building Assignment
Route::post('billing-groups/{billingGroup}/buildings', [App\Http\Controllers\Finance\BillingGroupController::class, 'assignBuilding'])->name('api.billing-groups.buildings.assign');
Route::delete('billing-groups/{billingGroup}/buildings/{building}', [App\Http\Controllers\Finance\BillingGroupController::class, 'removeBuilding'])->name('api.billing-groups.buildings.remove');

// Get billing groups by customer
Route::get('customers/{customer}/billing-groups', [App\Http\Controllers\Finance\BillingGroupController::class, 'getByCustomer'])->name('api.customers.billing-groups');

// Missing API Routes for Frontend
Route::get('branches', [App\Http\Controllers\Company\BranchController::class, 'index'])->name('api.branches');
Route::get('warehouses', [App\Http\Controllers\Warehouse\WarehouseController::class, 'index'])->name('api.warehouses');
Route::get('users', [App\Http\Controllers\System\UserController::class, 'index'])->name('api.users');
Route::get('buildings', [App\Http\Controllers\Operational\BuildingController::class, 'index'])->name('api.buildings');
Route::get('buildings/{building}/floors', [App\Http\Controllers\Operational\BuildingController::class, 'getFloors'])->name('api.buildings.floors');
Route::get('floors/{floor}/units', [App\Http\Controllers\Operational\FloorController::class, 'getUnits'])->name('api.floors.units');
Route::get('units/{unit}/rooms', [App\Http\Controllers\Operational\UnitController::class, 'getRooms'])->name('api.units.rooms');
Route::get('customers', [App\Http\Controllers\Company\CustomerController::class, 'index'])->name('api.customers');
Route::get('customers/{customer}/contacts', [App\Http\Controllers\Company\CustomerController::class, 'getContacts'])->name('api.customers.contacts');
Route::get('customers/{customer}/buildings', [App\Http\Controllers\Company\CustomerController::class, 'getBuildings'])->name('api.customers.buildings');
Route::get('customer-contacts', [App\Http\Controllers\Company\CustomerContactController::class, 'getCustomerContacts'])->name('api.customer-contacts');
Route::get('master-rooms', [App\Http\Controllers\Operational\MasterRoomController::class, 'index'])->name('api.master-rooms');
Route::get('master-rentals', [App\Http\Controllers\Warehouse\MasterRentalController::class, 'index'])->name('api.master-rentals');
Route::get('master-products', [App\Http\Controllers\Warehouse\MasterProductController::class, 'index'])->name('api.master-products');
Route::get('quotations/{id}/rental-products', [App\Http\Controllers\Marketing\QuotationController::class, 'getRentalProducts'])->name('api.quotations.rental-products');
Route::get('teams', [App\Http\Controllers\Operational\TeamController::class, 'index'])->name('api.teams');
Route::get('teams-test', function() {
    $teams = \App\Models\Team::where('active_status', '1')->get();
    return response()->json([
        'status' => 'success',
        'data' => $teams,
        'count' => $teams->count()
    ]);
})->name('api.teams.test');

// Public Location API Routes (no middleware required)
Route::prefix('v1/location')->name('location.')->group(function () {
    Route::get('provinces', [App\Http\Controllers\System\ProvinceController::class, 'getProvinces'])->name('provinces');
    Route::get('cities', [App\Http\Controllers\System\ProvinceController::class, 'getCities'])->name('cities');
    Route::get('districts', [App\Http\Controllers\System\ProvinceController::class, 'getDistricts'])->name('districts');
        Route::get('subdistricts', [App\Http\Controllers\System\ProvinceController::class, 'getSubdistricts'])->name('subdistricts');
        Route::get('subdistricts/{subdistrictId}', [App\Http\Controllers\System\ProvinceController::class, 'showSubdistrict'])->name('subdistricts.show');
});

// Protected API Routes
Route::middleware('auth:sanctum')->group(function () {
    // Dashboard API
    Route::get('/dashboard', [AuthController::class, 'apiDashboard'])->name('api.dashboard');
    
    // Mobile API Routes
    Route::prefix('v1/mobile')->name('mobile.')->group(function () {
        Route::get('jobs/today', [App\Http\Controllers\Api\Mobile\JobController::class, 'getTodayJobs'])->name('jobs.today');
        Route::get('jobs/done', [App\Http\Controllers\Api\Mobile\JobController::class, 'getDoneJobs'])->name('jobs.done');
        Route::get('jobs/suspend-dpf', [App\Http\Controllers\Api\Mobile\JobController::class, 'getSuspendDpfJobs'])->name('jobs.suspend-dpf');
        Route::get('jobs/{id}', [App\Http\Controllers\Api\Mobile\JobController::class, 'getJobDetail'])->name('jobs.detail');
        Route::get('jobs/{jobScheduleId}/rooms', [App\Http\Controllers\Api\Mobile\JobController::class, 'getJobRooms'])->name('jobs.rooms');
        Route::get('jobs/{jobScheduleId}/materials', [App\Http\Controllers\Api\Mobile\JobController::class, 'getJobMaterials'])->name('jobs.materials');
        Route::post('jobs/{jobScheduleId}/materials/confirm', [App\Http\Controllers\Api\Mobile\JobController::class, 'confirmMaterials'])->name('jobs.materials.confirm');
        
        // Material Verification (Warehouse Pickup)
        Route::get('jobs/{jobScheduleId}/materials/verification', [App\Http\Controllers\Api\Mobile\MaterialVerificationController::class, 'getMaterialsForVerification'])->name('jobs.materials.verification');
        Route::post('jobs/{jobScheduleId}/materials/verify', [App\Http\Controllers\Api\Mobile\MaterialVerificationController::class, 'verifyMaterials'])->name('jobs.materials.verify');
        
        Route::post('jobs/{jobScheduleId}/arrived', [App\Http\Controllers\Api\Mobile\JobController::class, 'arrivedAtLocation'])->name('jobs.arrived');
        Route::post('jobs/{jobScheduleId}/start', [App\Http\Controllers\Api\Mobile\JobController::class, 'startWork'])->name('jobs.start');
        Route::post('rooms/{roomId}/complete', [App\Http\Controllers\Api\Mobile\JobController::class, 'completeRoom'])->name('rooms.complete');
        Route::post('jobs/{jobScheduleId}/upload-photo', [App\Http\Controllers\Api\Mobile\JobController::class, 'uploadPhoto'])->name('jobs.upload-photo');
        Route::post('jobs/{jobScheduleId}/signature', [App\Http\Controllers\Api\Mobile\JobController::class, 'submitSignature'])->name('jobs.signature');
        Route::post('jobs/{jobScheduleId}/leave', [App\Http\Controllers\Api\Mobile\JobController::class, 'leaveLocation'])->name('jobs.leave');
        Route::post('jobs/{jobScheduleId}/verify', [App\Http\Controllers\Api\Mobile\JobController::class, 'verifyJob'])->name('jobs.verify');
        Route::post('jobs/{jobScheduleId}/favorite', [App\Http\Controllers\Api\Mobile\JobController::class, 'toggleFavorite'])->name('jobs.favorite');
        Route::post('jobs/{id}/swap-serial-number', [App\Http\Controllers\Api\Mobile\JobController::class, 'swapSerialNumber'])->name('jobs.swap-serial-number');

        // Bug #71 (QA): Install Free / Trial jobs need a size-only dropdown
        // (variants of the SAME aroma in sizes <100ml) so the technician can
        // record which trial bottle was installed without changing the aroma.
        Route::get('products/install-free-sizes', [App\Http\Controllers\Api\Mobile\JobController::class, 'installFreeSizeOptions'])->name('products.install-free-sizes');
    });
    
    // User Photo API Route (with authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user/photo', [UserPhotoController::class, 'show'])->name('user.photo');
        Route::get('user/{userId}/photo', [UserPhotoController::class, 'show'])->name('user.photo.id');
    });
    
    // Marketing API Routes
    Route::prefix('v1/marketing')->name('api.marketing.')->group(function () {
        Route::apiResource('prospects', ProspectController::class);
        Route::get('prospects/dashboard', [ProspectController::class, 'dashboard'])->name('prospects.dashboard');
        Route::apiResource('surveys', SurveyController::class);
        Route::apiResource('quotations', QuotationController::class);
        Route::post('quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
        Route::apiResource('contracts', ContractController::class);
        Route::get('contracts/dropdown', [ContractController::class, 'getForDropdown'])->name('contracts.dropdown');
        Route::post('contracts/{contract}/lock', [ContractController::class, 'lock'])->name('contracts.lock');
        Route::post('contracts/{contract}/unlock', [ContractController::class, 'unlock'])->name('contracts.unlock');
        Route::apiResource('job-advices', JobAdviceController::class);
        Route::apiResource('lost-unit-reports', LostUnitReportController::class);
        Route::apiResource('sales-activities', SalesActivityController::class);
        Route::put('contract-rentals/{id}', [App\Http\Controllers\Marketing\ContractRentalController::class, 'update'])->name('contract-rentals.update');
        Route::delete('contract-rentals/{id}', [App\Http\Controllers\Marketing\ContractRentalController::class, 'destroy'])->name('contract-rentals.destroy');
    });

    // Operational API Routes
    Route::prefix('v1/operational')->name('api.operational.')->group(function () {
        Route::apiResource('job-schedules', JobScheduleController::class);
        Route::post('job-schedules/{id}/force-majeure', [JobScheduleController::class, 'reportForceMajeure'])->name('job-schedules.force-majeure');
        Route::post('job-schedules/{id}/reassign', [JobScheduleController::class, 'reassignToBackupTechnician'])->name('job-schedules.reassign');
        Route::post('job-schedules/{id}/reschedule', [JobScheduleController::class, 'rescheduleJob'])->name('job-schedules.reschedule');
        Route::post('job-schedules/{id}/material-return', [JobScheduleController::class, 'handleMaterialReturn'])->name('job-schedules.material-return');
        Route::post('job-schedules/{id}/resolve-force-majeure', [JobScheduleController::class, 'resolveForceMajeure'])->name('job-schedules.resolve-force-majeure');
        Route::get('job-schedules/force-majeure/stats', [JobScheduleController::class, 'getForceMajeureStats'])->name('job-schedules.force-majeure-stats');
        
        // Enhanced Job Management API Routes
        Route::post('job-schedules/{jobSchedule}/assign-team', [JobScheduleController::class, 'assignToTeam'])->name('job-schedules.assign-team');
        Route::get('job-schedules/{jobSchedule}/assignments', [JobScheduleController::class, 'getAssignments'])->name('job-schedules.assignments');
        Route::post('job-assignments/{assignment}/accept', [JobScheduleController::class, 'acceptAssignment'])->name('job-assignments.accept');
        Route::post('job-assignments/{assignment}/start', [JobScheduleController::class, 'startAssignment'])->name('job-assignments.start');
        Route::post('job-assignments/{assignment}/complete', [JobScheduleController::class, 'completeAssignment'])->name('job-assignments.complete');
        
        // Material Management API Routes
        Route::get('job-schedules/{jobSchedule}/materials', [JobScheduleController::class, 'getMaterials'])->name('job-schedules.materials');
        Route::post('job-schedules/{jobSchedule}/materials', [JobScheduleController::class, 'addMaterial'])->name('job-schedules.materials.add');
        Route::post('job-materials/{material}/issue', [JobScheduleController::class, 'issueMaterial'])->name('job-materials.issue');
        Route::post('job-materials/{material}/return', [JobScheduleController::class, 'returnMaterial'])->name('job-materials.return');
        

        // Upload routes (OperationalController)

        Route::post('/upload/photo', [OperationalController::class, 'uploadPhoto'])->name('upload.photo');

        Route::post('/upload/signature', [OperationalController::class, 'uploadSignature'])->name('upload.signature');

        

        // Temperature recording

        Route::post('/temperature/record', [OperationalController::class, 'recordTemperature'])->name('temperature.record');

        

        // Additional job routes with {id} parameter (JobController)

        Route::post('/jobs/{id}/upload-photo', [JobController::class, 'uploadPhoto'])->name('jobs.upload-photo-alt');

        Route::post('/jobs/{id}/signature', [JobController::class, 'submitSignature'])->name('jobs.signature-alt');

        

        // New GPS and Serial Number routes

        Route::post('/jobs/{id}/arrived-at-location', [JobController::class, 'arrivedAtLocation'])->name('jobs.arrived-at-location');

        Route::post('/jobs/{id}/validate-serial-number', [JobController::class, 'validateSerialNumber'])->name('jobs.validate-serial-number');

        Route::get('/jobs/{id}/detail', [JobController::class, 'getJobDetail'])->name('jobs.detail-alt');

        

        
        // Periodic Job Management API Routes
        Route::post('periodic-jobs', [JobScheduleController::class, 'createPeriodicJob'])->name('periodic-jobs.create');
        Route::get('periodic-jobs', [JobScheduleController::class, 'getPeriodicJobs'])->name('periodic-jobs.index');
        Route::post('periodic-jobs/generate', [JobScheduleController::class, 'generatePeriodicJobs'])->name('periodic-jobs.generate');
        
        Route::apiResource('teams', TeamController::class);
        Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
        Route::apiResource('buildings', BuildingController::class);
        Route::apiResource('master-rooms', MasterRoomController::class);
        Route::get('master-rooms/by-building/{buildingId}', [MasterRoomController::class, 'getRoomsByBuildingId'])->name('master-rooms.by-building');
        Route::post('master-rooms/bulk-delete', [MasterRoomController::class, 'bulkDelete'])->name('master-rooms.bulk-delete');
        Route::apiResource('room-rental-units', RoomRentalUnitController::class);
        Route::apiResource('job-assign-schedules', JobAssignScheduleController::class);
        Route::post('job-assign-schedules/bulk-delete', [JobAssignScheduleController::class, 'bulkDelete'])->name('job-assign-schedules.bulk-delete');
        Route::apiResource('job-assign-material-issues', JobAssignMaterialIssueController::class);
        
        // New operational endpoints
        Route::apiResource('job-reports', JobReportController::class);
        Route::post('job-reports/bulk-delete', [JobReportController::class, 'bulkDelete'])->name('job-reports.bulk-delete');
        
        Route::apiResource('technician-locations', TechnicianLocationController::class);
        Route::get('technician-locations/technicians', [TechnicianLocationController::class, 'getTechnicians'])->name('technician-locations.technicians');
        Route::get('technician-locations/latest/{technicianId}', [TechnicianLocationController::class, 'getLatestLocation'])->name('technician-locations.latest');
        Route::get('technician-locations/within-radius', [TechnicianLocationController::class, 'getLocationsWithinRadius'])->name('technician-locations.within-radius');
        Route::get('technician-locations/tracking/{technicianId}', [TechnicianLocationController::class, 'getTechnicianTracking'])->name('technician-locations.tracking');
        Route::post('technician-locations/bulk-delete', [TechnicianLocationController::class, 'bulkDelete'])->name('technician-locations.bulk-delete');
        
        Route::apiResource('job-routes', JobRouteController::class);
        Route::apiResource('service-histories', ServiceHistoryController::class);
        Route::apiResource('unit-installations', UnitInstallationController::class);
        Route::apiResource('job-signatures', JobSignatureController::class);
        Route::apiResource('temperature-records', TemperatureRecordController::class);
    });

    // Finance API Routes
    Route::prefix('v1/finance')->name('api.finance.')->group(function () {
        Route::apiResource('invoices', InvoiceController::class);
        Route::apiResource('invoice-follow-ups', InvoiceFollowUpController::class);
        Route::apiResource('bank-receipts', BankReceiptController::class);
        Route::apiResource('virtual-account-imports', VirtualAccountImportController::class);
        Route::apiResource('virtual-account-exports', VirtualAccountExportController::class);
        Route::apiResource('tax-settings', TaxSettingController::class);
        Route::apiResource('tax-file-imports', TaxFileImportController::class);
        Route::post('tax-file-imports/bulk-delete', [TaxFileImportController::class, 'bulkDelete']);
        Route::post('tax-file-imports/{taxFileImport}/process', [TaxFileImportController::class, 'processImport']);
        Route::get('tax-file-imports/{taxFileImport}/download', [TaxFileImportController::class, 'downloadFile']);
        Route::get('tax-file-imports/{taxFileImport}/error-log', [TaxFileImportController::class, 'downloadErrorLog']);
        Route::get('tax-file-imports/statistics', [TaxFileImportController::class, 'getImportStatistics']);
        Route::get('tax-file-imports/by-date-range', [TaxFileImportController::class, 'getImportsByDateRange']);
        Route::apiResource('tax-file-exports', TaxFileExportController::class);
        Route::post('tax-file-exports/bulk-delete', [TaxFileExportController::class, 'bulkDelete']);
        Route::post('tax-file-exports/{taxFileExport}/generate-espt', [TaxFileExportController::class, 'generateESPTExport']);
    });

    // Warehouse API Routes
    Route::prefix('v1/warehouse')->name('api.warehouse.')->group(function () {

        Route::apiResource('inventory-issuings', InventoryIssuingController::class);
        Route::apiResource('inventory-receivings', InventoryReceivingController::class);
        Route::apiResource('inventory-requests', InventoryRequestController::class);
        Route::apiResource('product-types', ProductTypeController::class)->middleware('permission:product-types.view');
        Route::post('product-types/bulk-delete', [ProductTypeController::class, 'bulkDelete'])->name('product-types.bulk-delete')->middleware('permission:product-types.delete');
        Route::post('product-types/{productType}/toggle-status', [ProductTypeController::class, 'toggleStatus'])->name('product-types.toggle-status')->middleware('permission:product-types.edit');
        Route::get('product-types/statistics', [ProductTypeController::class, 'getProductTypeStatistics'])->name('product-types.statistics')->middleware('permission:product-types.view');
        Route::get('product-types/search', [ProductTypeController::class, 'searchProductTypes'])->name('product-types.search')->middleware('permission:product-types.view');
        Route::apiResource('master-products', MasterProductController::class)->middleware('permission:master-products.view');
        Route::post('master-products/bulk-delete', [MasterProductController::class, 'bulkDelete'])->name('master-products.bulk-delete')->middleware('permission:master-products.delete');
        Route::post('master-products/{masterProduct}/toggle-status', [MasterProductController::class, 'toggleStatus'])->name('master-products.toggle-status')->middleware('permission:master-products.edit');
        Route::get('master-products/statistics', [MasterProductController::class, 'getProductStatistics'])->name('master-products.statistics')->middleware('permission:master-products.view');
        Route::get('master-products/by-type', [MasterProductController::class, 'getProductsByType'])->name('master-products.by-type')->middleware('permission:master-products.view');
        Route::get('master-products/search', [MasterProductController::class, 'searchProducts'])->name('master-products.search')->middleware('permission:master-products.view');
        
        Route::apiResource('master-rentals', MasterRentalController::class)->middleware('permission:master-rentals.view');
        Route::post('master-rentals/bulk-delete', [MasterRentalController::class, 'bulkDelete'])->name('master-rentals.bulk-delete')->middleware('permission:master-rentals.delete');
        Route::post('master-rentals/{masterRental}/toggle-status', [MasterRentalController::class, 'toggleStatus'])->name('master-rentals.toggle-status')->middleware('permission:master-rentals.edit');
        Route::get('master-rentals/statistics', [MasterRentalController::class, 'getRentalStatistics'])->name('master-rentals.statistics')->middleware('permission:master-rentals.view');
        Route::get('master-rentals/by-category', [MasterRentalController::class, 'getRentalsByCategory'])->name('master-rentals.by-category')->middleware('permission:master-rentals.view');
        Route::get('master-rentals/search', [MasterRentalController::class, 'searchRentals'])->name('master-rentals.search')->middleware('permission:master-rentals.view');
        Route::apiResource('stock-opnames', StockOpnameController::class)->middleware('permission:stock-opnames.view');
        Route::post('stock-opnames/bulk-delete', [StockOpnameController::class, 'bulkDelete'])->name('stock-opnames.bulk-delete')->middleware('permission:stock-opnames.delete');
        Route::post('stock-opnames/{stockOpname}/start', [StockOpnameController::class, 'start'])->name('stock-opnames.start')->middleware('permission:stock-opnames.edit');
        Route::post('stock-opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete'])->name('stock-opnames.complete')->middleware('permission:stock-opnames.edit');
        Route::post('stock-opnames/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('stock-opnames.approve')->middleware('permission:stock-opnames.edit');
        Route::get('stock-opnames/dashboard', [StockOpnameController::class, 'dashboard'])->name('stock-opnames.dashboard')->middleware('permission:stock-opnames.view');
        Route::apiResource('stock-adjustments', StockAdjustmentController::class)->middleware('permission:stock-adjustments.view');
        Route::post('stock-adjustments/bulk-delete', [StockAdjustmentController::class, 'bulkDelete'])->name('stock-adjustments.bulk-delete')->middleware('permission:stock-adjustments.delete');
        Route::post('stock-adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve')->middleware('permission:stock-adjustments.edit');
        Route::post('stock-adjustments/{stock_adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject')->middleware('permission:stock-adjustments.edit');
        Route::get('stock-adjustments/dashboard', [StockAdjustmentController::class, 'dashboard'])->name('stock-adjustments.dashboard')->middleware('permission:stock-adjustments.view');
        Route::apiResource('serial-numbers', SerialNumberController::class)->middleware('permission:serial-numbers.view');
        Route::post('serial-numbers/bulk-delete', [SerialNumberController::class, 'bulkDelete'])->name('serial-numbers.bulk-delete')->middleware('permission:serial-numbers.delete');
        Route::apiResource('unit-on-walls', UnitOnWallController::class)->middleware('permission:unit-on-walls.view');
        Route::post('unit-on-walls/bulk-delete', [UnitOnWallController::class, 'bulkDelete'])->name('unit-on-walls.bulk-delete')->middleware('permission:unit-on-walls.delete');
        Route::post('unit-on-walls/{unitOnWall}/update-status', [UnitOnWallController::class, 'updateStatus'])->name('unit-on-walls.update-status')->middleware('permission:unit-on-walls.edit');
        Route::post('unit-on-walls/{unitOnWall}/update-temperature', [UnitOnWallController::class, 'updateTemperature'])->name('unit-on-walls.update-temperature')->middleware('permission:unit-on-walls.edit');
    });

    // System API Routes
    Route::prefix('v1/system')->name('api.system.')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('provinces', ProvinceController::class);
        Route::apiResource('notifications', NotificationController::class);
    });



    // Company API Routes
    Route::prefix('v1/company')->name('api.company.')->group(function () {
        // Company Management
        Route::apiResource('companies', CompanyController::class)->middleware('permission:companies.view');
        Route::get('companies/{company}/settings', [CompanyController::class, 'settings'])->name('companies.settings')->middleware('permission:companies.view');
        Route::put('companies/{company}/settings', [CompanyController::class, 'updateSettings'])->name('companies.settings.update')->middleware('permission:companies.edit');
        Route::get('companies/{company}/documents', [CompanyController::class, 'documents'])->name('companies.documents')->middleware('permission:companies.view');
        Route::post('companies/{company}/documents', [CompanyController::class, 'uploadDocument'])->name('companies.documents.upload')->middleware('permission:companies.edit');
        Route::delete('companies/{company}/documents/{document}', [CompanyController::class, 'deleteDocument'])->name('companies.documents.delete')->middleware('permission:companies.edit');
        Route::get('companies/{company}/notes', [CompanyController::class, 'notes'])->name('companies.notes')->middleware('permission:companies.view');
        Route::post('companies/{company}/notes', [CompanyController::class, 'storeNote'])->name('companies.notes.store')->middleware('permission:companies.edit');
        Route::put('companies/{company}/notes/{note}', [CompanyController::class, 'updateNote'])->name('companies.notes.update')->middleware('permission:companies.edit');
        Route::delete('companies/{company}/notes/{note}', [CompanyController::class, 'deleteNote'])->name('companies.notes.delete')->middleware('permission:companies.edit');
        Route::post('companies/{company}/tags', [CompanyController::class, 'assignTag'])->name('companies.tags.assign')->middleware('permission:companies.edit');
        Route::delete('companies/{company}/tags/{tag}', [CompanyController::class, 'removeTag'])->name('companies.tags.remove')->middleware('permission:companies.edit');
        Route::get('companies/{company}/relationships', [CompanyController::class, 'relationships'])->name('companies.relationships')->middleware('permission:companies.view');
        Route::post('companies/{company}/relationships', [CompanyController::class, 'storeRelationship'])->name('companies.relationships.store')->middleware('permission:companies.edit');
        Route::put('companies/{company}/relationships/{relationship}', [CompanyController::class, 'updateRelationship'])->name('companies.relationships.update')->middleware('permission:companies.edit');
        Route::delete('companies/{company}/relationships/{relationship}', [CompanyController::class, 'deleteRelationship'])->name('companies.relationships.delete')->middleware('permission:companies.edit');
        Route::get('companies/{company}/activities', [CompanyController::class, 'activities'])->name('companies.activities')->middleware('permission:companies.view');
        Route::post('companies/{company}/activities', [CompanyController::class, 'storeActivity'])->name('companies.activities.store')->middleware('permission:companies.edit');
        Route::put('companies/{company}/activities/{activity}', [CompanyController::class, 'updateActivity'])->name('companies.activities.update')->middleware('permission:companies.edit');
        Route::delete('companies/{company}/activities/{activity}', [CompanyController::class, 'deleteActivity'])->name('companies.activities.delete')->middleware('permission:companies.edit');
        Route::get('companies/{company}/communications', [CompanyController::class, 'communications'])->name('companies.communications')->middleware('permission:companies.view');
        Route::post('companies/{company}/communications', [CompanyController::class, 'storeCommunication'])->name('companies.communications.store')->middleware('permission:companies.edit');
        Route::put('companies/{company}/communications/{communication}', [CompanyController::class, 'updateCommunication'])->name('companies.communications.update')->middleware('permission:companies.edit');
        Route::delete('companies/{company}/communications/{communication}', [CompanyController::class, 'deleteCommunication'])->name('companies.communications.delete')->middleware('permission:companies.edit');
        Route::get('companies/{company}/dashboard', [CompanyController::class, 'dashboard'])->name('companies.dashboard')->middleware('permission:companies.view');
        Route::post('companies/bulk-delete', [CompanyController::class, 'bulkDelete'])->name('companies.bulk-delete')->middleware('permission:companies.delete');
        Route::post('companies/bulk-update-status', [CompanyController::class, 'bulkUpdateStatus'])->name('companies.bulk-update-status')->middleware('permission:companies.edit');
        Route::post('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('companies.toggle-status')->middleware('permission:companies.edit');
        Route::get('companies/export', [CompanyController::class, 'export'])->name('companies.export')->middleware('permission:companies.view');
        Route::post('companies/import', [CompanyController::class, 'import'])->name('companies.import')->middleware('permission:companies.edit');
        Route::get('companies/statistics', [CompanyController::class, 'getStatistics'])->name('companies.statistics')->middleware('permission:companies.view');
        Route::get('companies/search', [CompanyController::class, 'searchCompanies'])->name('companies.search')->middleware('permission:companies.view');
        Route::get('companies/by-province', [CompanyController::class, 'getCompaniesByProvince'])->name('companies.by-province')->middleware('permission:companies.view');
        Route::get('companies/list', [CompanyController::class, 'getCompanies'])->name('companies.list')->middleware('permission:companies.view');
        
        // Branch Management
        Route::apiResource('branches', BranchController::class)->middleware('permission:branches.view');
        Route::get('branches/{branch}/settings', [BranchController::class, 'settings'])->name('branches.settings')->middleware('permission:branches.view');
        Route::put('branches/{branch}/settings', [BranchController::class, 'updateSettings'])->name('branches.settings.update')->middleware('permission:branches.edit');
        Route::get('branches/{branch}/warehouses', [BranchController::class, 'warehouses'])->name('branches.warehouses')->middleware('permission:branches.view');
        Route::post('branches/{branch}/warehouses', [BranchController::class, 'assignWarehouse'])->name('branches.warehouses.assign')->middleware('permission:branches.edit');
        Route::delete('branches/{branch}/warehouses/{branchWarehouse}', [BranchController::class, 'removeWarehouse'])->name('branches.warehouses.remove')->middleware('permission:branches.edit');
        Route::post('branches/{branch}/warehouses/{branchWarehouse}/set-primary', [BranchController::class, 'setPrimaryWarehouse'])->name('branches.warehouses.set-primary')->middleware('permission:branches.edit');
        Route::post('branches/bulk-delete', [BranchController::class, 'bulkDelete'])->name('branches.bulk-delete')->middleware('permission:branches.delete');
        Route::post('branches/bulk-update-status', [BranchController::class, 'bulkUpdateStatus'])->name('branches.bulk-update-status')->middleware('permission:branches.edit');
        Route::post('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status')->middleware('permission:branches.edit');
        Route::get('branches/statistics', [BranchController::class, 'getStatistics'])->name('branches.statistics')->middleware('permission:branches.view');
        Route::get('branches/search', [BranchController::class, 'searchBranches'])->name('branches.search')->middleware('permission:branches.view');
        Route::get('branches/by-company', [BranchController::class, 'getBranchesByCompany'])->name('branches.by-company')->middleware('permission:branches.view');
        
        // Customer Management
        Route::apiResource('customers', CustomerController::class)->middleware('permission:customers.view');
        Route::get('customers/{customer}/credit-limits', [CustomerController::class, 'creditLimits'])->name('customers.credit-limits')->middleware('permission:customers.view');
        Route::post('customers/{customer}/credit-limits', [CustomerController::class, 'storeCreditLimit'])->name('customers.credit-limits.store')->middleware('permission:customers.edit');
        Route::put('customers/{customer}/credit-limits/{creditLimit}', [CustomerController::class, 'updateCreditLimit'])->name('customers.credit-limits.update')->middleware('permission:customers.edit');
        Route::delete('customers/{customer}/credit-limits/{creditLimit}', [CustomerController::class, 'deleteCreditLimit'])->name('customers.credit-limits.delete')->middleware('permission:customers.delete');
        Route::get('customers/{customer}/payment-terms', [CustomerController::class, 'paymentTerms'])->name('customers.payment-terms')->middleware('permission:customers.view');
        Route::post('customers/{customer}/payment-terms', [CustomerController::class, 'storePaymentTerm'])->name('customers.payment-terms.store')->middleware('permission:customers.edit');
        Route::put('customers/{customer}/payment-terms/{paymentTerm}', [CustomerController::class, 'updatePaymentTerm'])->name('customers.payment-terms.update')->middleware('permission:customers.edit');
        Route::delete('customers/{customer}/payment-terms/{paymentTerm}', [CustomerController::class, 'deletePaymentTerm'])->name('customers.payment-terms.delete')->middleware('permission:customers.delete');
        Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulk-delete')->middleware('permission:customers.delete');
        Route::post('customers/bulk-update-status', [CustomerController::class, 'bulkUpdateStatus'])->name('customers.bulk-update-status')->middleware('permission:customers.edit');
        Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status')->middleware('permission:customers.edit');
        Route::get('customers/statistics', [CustomerController::class, 'getStatistics'])->name('customers.statistics')->middleware('permission:customers.view');
        Route::get('customers/search', [CustomerController::class, 'searchCustomers'])->name('customers.search')->middleware('permission:customers.view');
        Route::get('customers/by-company', [CustomerController::class, 'getCustomersByCompany'])->name('customers.by-company')->middleware('permission:customers.view');
        
        // Supplier Management
        Route::apiResource('suppliers', SupplierController::class)->middleware('permission:suppliers.view');
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
        Route::get('suppliers/search', [SupplierController::class, 'searchSuppliers'])->name('suppliers.search');
        Route::get('suppliers/by-company', [SupplierController::class, 'getSuppliersByCompany'])->name('suppliers.by-company');
        
        // Legacy routes
        Route::apiResource('customer-taxes', CustomerTaxController::class)->middleware('permission:customer-taxes.view');
        Route::post('customer-taxes/bulk-delete', [CustomerTaxController::class, 'bulkDelete'])->name('customer-taxes.bulk-delete')->middleware('permission:customer-taxes.delete');
        Route::post('customer-taxes/{customerTax}/toggle-status', [CustomerTaxController::class, 'toggleStatus'])->name('customer-taxes.toggle-status')->middleware('permission:customer-taxes.edit');
        
        // Get active tax number for invoice snapshot (per report-mom5.md)
        Route::get('customers/{customerId}/active-tax-number', [CustomerTaxController::class, 'getActiveTaxNumber'])->name('customers.active-tax-number');
        Route::apiResource('company-virtual-accounts', CompanyVirtualAccountController::class)->middleware('permission:company-virtual-accounts.view');
        Route::post('company-virtual-accounts/bulk-delete', [CompanyVirtualAccountController::class, 'bulkDelete'])->name('company-virtual-accounts.bulk-delete')->middleware('permission:company-virtual-accounts.delete');
        Route::post('company-virtual-accounts/{companyVirtualAccount}/toggle-status', [CompanyVirtualAccountController::class, 'toggleStatus'])->name('company-virtual-accounts.toggle-status')->middleware('permission:company-virtual-accounts.edit');
        Route::apiResource('bank-payments', BankPaymentController::class)->middleware('permission:bank-payments.view');
        Route::apiResource('master-price-slabs', MasterPriceSlabController::class)->middleware('permission:master-price-slabs.view');
        Route::post('master-price-slabs/bulk-delete', [MasterPriceSlabController::class, 'bulkDelete'])->name('master-price-slabs.bulk-delete')->middleware('permission:master-price-slabs.delete');
        Route::post('master-price-slabs/{masterPriceSlab}/toggle-status', [MasterPriceSlabController::class, 'toggleStatus'])->name('master-price-slabs.toggle-status')->middleware('permission:master-price-slabs.edit');
        Route::get('master-price-slabs/statistics', [MasterPriceSlabController::class, 'getPriceSlabStatistics'])->name('master-price-slabs.statistics')->middleware('permission:master-price-slabs.view');
        Route::get('master-price-slabs/by-rental', [MasterPriceSlabController::class, 'getPriceSlabsByRental'])->name('master-price-slabs.by-rental')->middleware('permission:master-price-slabs.view');
        Route::get('master-price-slabs/search', [MasterPriceSlabController::class, 'searchPriceSlabs'])->name('master-price-slabs.search')->middleware('permission:master-price-slabs.view');
        Route::post('master-price-slabs/calculate-price', [MasterPriceSlabController::class, 'calculatePrice'])->name('master-price-slabs.calculate-price')->middleware('permission:master-price-slabs.view');
        Route::apiResource('access-management', AccessManagementController::class)->middleware('permission:access-management.view');
        Route::apiResource('price-slabs', MasterPriceSlabController::class)->middleware('permission:master-price-slabs.view');
    });

    // Other API Routes
    Route::prefix('v1/other')->name('api.other.')->group(function () {
        Route::apiResource('hak-akses', HakAksesController::class);
        Route::apiResource('master-options', MasterOptionController::class);
        
        // Customer Portal API
        Route::prefix('customer-portal')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'store']);
            Route::get('/{id}', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'destroy']);
            Route::get('/{id}/sessions', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'getSessions']);
            Route::get('/{id}/activities', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'getActivities']);
            Route::get('/statistics', [App\Http\Controllers\Api\Other\CustomerPortalApiController::class, 'getStatistics']);
        });
    });

    // Setting API Routes
    Route::prefix('v1/setting')->name('api.setting.')->group(function () {
        Route::apiResource('theme-settings', ThemeSettingController::class);
    });

        // Mobile App Routes (Legacy - for backward compatibility)
        Route::prefix('v1/mobile')->name('legacy-mobile.')->group(function () {
            // NOTE: Commented out duplicate routes - using new JobController instead
            // Route::get('/jobs/today', [OperationalController::class, 'getTodayJobs'])->name('jobs.today');
            // Route::get('/jobs/{id}', [OperationalController::class, 'getJobDetail'])->name('jobs.detail');
            
            // Enhanced Job Management for Mobile
            Route::get('/jobs/{id}/assignments', [App\Http\Controllers\Operational\JobScheduleController::class, 'getAssignments'])->name('jobs.assignments');
            Route::post('/jobs/{id}/assign-team', [App\Http\Controllers\Operational\JobScheduleController::class, 'assignToTeam'])->name('jobs.assign-team');
            Route::post('/job-assignments/{assignment}/accept', [App\Http\Controllers\Operational\JobScheduleController::class, 'acceptAssignment'])->name('job-assignments.accept');
            Route::post('/job-assignments/{assignment}/start', [App\Http\Controllers\Operational\JobScheduleController::class, 'startAssignment'])->name('job-assignments.start');
            Route::post('/job-assignments/{assignment}/complete', [App\Http\Controllers\Operational\JobScheduleController::class, 'completeAssignment'])->name('job-assignments.complete');
            
            // Material Management for Mobile (LEGACY - commented to avoid conflict with new mobile API)
            // Route::get('/jobs/{id}/materials', [App\Http\Controllers\Operational\JobScheduleController::class, 'getMaterials'])->name('jobs.materials');
            Route::post('/jobs/{id}/materials', [App\Http\Controllers\Operational\JobScheduleController::class, 'addMaterial'])->name('jobs.materials.add');
            Route::post('/job-materials/{material}/issue', [App\Http\Controllers\Operational\JobScheduleController::class, 'issueMaterial'])->name('job-materials.issue');
            Route::post('/job-materials/{material}/return', [App\Http\Controllers\Operational\JobScheduleController::class, 'returnMaterial'])->name('job-materials.return');
        
        // Location Tracking (as per BRD)
        Route::post('/location/update', [App\Http\Controllers\Operational\TechnicianLocationController::class, 'mobileUpdateLocation'])->name('location.update');
        Route::get('/location/latest/{technicianId}', [App\Http\Controllers\Operational\TechnicianLocationController::class, 'getLatestLocation'])->name('location.latest');
        Route::get('/location/tracking/{technicianId}', [App\Http\Controllers\Operational\TechnicianLocationController::class, 'getTechnicianTracking'])->name('location.tracking');
        Route::post('/jobs/{id}/start', [OperationalController::class, 'startJob'])->name('jobs.start');
        Route::post('/jobs/{id}/complete', [OperationalController::class, 'completeJob'])->name('jobs.complete');
        
        // Force Majeure Management (Mobile)
        Route::post('/jobs/{id}/force-majeure', [OperationalController::class, 'reportForceMajeure'])->name('jobs.force-majeure');
        Route::post('/jobs/{id}/material-return', [OperationalController::class, 'handleMaterialReturn'])->name('jobs.material-return');
        // Job Reports
        Route::post('/job-reports', [OperationalController::class, 'submitJobReport'])->name('job-reports.submit');
        
        // File Uploads
        Route::post('/jobs/{id}/upload-photo', [JobController::class, 'uploadPhoto']);
        Route::post('/jobs/{id}/signature', [JobController::class, 'submitSignature']);
        Route::post('/units/scan-qr', [JobController::class, 'getUnitByQrCode'])->name('units.scan-qr');
        Route::post('/units/save-scanned', [JobController::class, 'saveScannedUnit'])->name('units.save-scanned');
        
        // Temperature Recording
// ... (rest of the code remains the same)
        
        // Profile & Statistics
        Route::get('/profile', [OperationalController::class, 'getTechnicianProfile'])->name('profile');
        Route::get('/statistics', [OperationalController::class, 'getJobStatistics'])->name('statistics');
    });

    // Reports API Routes
    Route::prefix('v1/reports')->name('api.reports.')->group(function () {
        // Dashboard Report
        Route::get('/dashboard/statistics', [App\Http\Controllers\Reports\DashboardReportController::class, 'getStatistics'])->name('dashboard.statistics');
        
        // Operational Reports
        Route::prefix('operational')->name('operational.')->group(function () {
            Route::get('/statistics', [App\Http\Controllers\Reports\OperationalReportController::class, 'getOperationalStatistics'])->name('statistics');
            Route::get('/job-schedule', [App\Http\Controllers\Reports\OperationalReportController::class, 'jobScheduleReport'])->name('job-schedule');
            Route::get('/job-assignment', [App\Http\Controllers\Reports\OperationalReportController::class, 'jobAssignmentReport'])->name('job-assignment');
            Route::get('/material-issue', [App\Http\Controllers\Reports\OperationalReportController::class, 'materialIssueReport'])->name('material-issue');
            Route::get('/team-performance', [App\Http\Controllers\Reports\OperationalReportController::class, 'teamPerformanceReport'])->name('team-performance');
            Route::get('/customer-service', [App\Http\Controllers\Reports\OperationalReportController::class, 'customerServiceReport'])->name('customer-service');
        });
        
        // Financial Reports
        Route::prefix('financial')->name('financial.')->group(function () {
            Route::get('/statistics', [App\Http\Controllers\Reports\FinancialReportController::class, 'getFinancialStatistics'])->name('statistics');
            Route::get('/quotation', [App\Http\Controllers\Reports\FinancialReportController::class, 'quotationReport'])->name('quotation');
            Route::get('/contract', [App\Http\Controllers\Reports\FinancialReportController::class, 'contractReport'])->name('contract');
            Route::get('/invoice', [App\Http\Controllers\Reports\FinancialReportController::class, 'invoiceReport'])->name('invoice');
            Route::get('/payment', [App\Http\Controllers\Reports\FinancialReportController::class, 'paymentReport'])->name('payment');
            Route::get('/revenue', [App\Http\Controllers\Reports\FinancialReportController::class, 'revenueReport'])->name('revenue');
            Route::get('/customer', [App\Http\Controllers\Reports\FinancialReportController::class, 'customerFinancialReport'])->name('customer');
        });
        
        // Inventory Reports
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/statistics', [App\Http\Controllers\Reports\InventoryReportController::class, 'getInventoryStatistics'])->name('statistics');
            Route::get('/stock', [App\Http\Controllers\Reports\InventoryReportController::class, 'stockReport'])->name('stock');
            Route::get('/category', [App\Http\Controllers\Reports\InventoryReportController::class, 'categoryReport'])->name('category');
            Route::get('/brand', [App\Http\Controllers\Reports\InventoryReportController::class, 'brandReport'])->name('brand');
            Route::get('/supplier', [App\Http\Controllers\Reports\InventoryReportController::class, 'supplierReport'])->name('supplier');
            Route::get('/material-issue', [App\Http\Controllers\Reports\InventoryReportController::class, 'materialIssueReport'])->name('material-issue');
            Route::get('/stock-movement', [App\Http\Controllers\Reports\InventoryReportController::class, 'stockMovementReport'])->name('stock-movement');
            Route::get('/low-stock-alert', [App\Http\Controllers\Reports\InventoryReportController::class, 'lowStockAlertReport'])->name('low-stock-alert');
        });
        
        // Customer Reports
        Route::prefix('customer')->name('customer.')->group(function () {
            Route::get('/statistics', [App\Http\Controllers\Reports\CustomerReportController::class, 'getCustomerStatistics'])->name('statistics');
            Route::get('/list', [App\Http\Controllers\Reports\CustomerReportController::class, 'customerListReport'])->name('list');
            Route::get('/activity', [App\Http\Controllers\Reports\CustomerReportController::class, 'customerActivityReport'])->name('activity');
            Route::get('/financial', [App\Http\Controllers\Reports\CustomerReportController::class, 'customerFinancialReport'])->name('financial');
        });
        
        // HR Reports
        Route::prefix('hr')->name('hr.')->group(function () {
            Route::get('/statistics', [App\Http\Controllers\Reports\HrReportController::class, 'getHrStatistics'])->name('statistics');
            Route::get('/team-performance', [App\Http\Controllers\Reports\HrReportController::class, 'teamPerformanceReport'])->name('team-performance');
            Route::get('/team-workload', [App\Http\Controllers\Reports\HrReportController::class, 'teamWorkloadReport'])->name('team-workload');
            Route::get('/job-assignment', [App\Http\Controllers\Reports\HrReportController::class, 'jobAssignmentReport'])->name('job-assignment');
            Route::get('/material-issue', [App\Http\Controllers\Reports\HrReportController::class, 'materialIssueReport'])->name('material-issue');
            Route::get('/team-efficiency', [App\Http\Controllers\Reports\HrReportController::class, 'teamEfficiencyReport'])->name('team-efficiency');
        });
    });

    // Bank Webhook Routes (Public - for external bank integration)
    Route::prefix('bank-webhook')->group(function () {
        Route::post('/virtual-account-payment', [App\Http\Controllers\Finance\BankWebhookController::class, 'handleVirtualAccountPayment'])->name('bank.webhook.va-payment');
        Route::get('/status', [App\Http\Controllers\Finance\BankWebhookController::class, 'status'])->name('bank.webhook.status');
    });

    // Mobile API Routes
    Route::prefix('mobile')->name('mobile-app.')->group(function () {
        // Public routes (no authentication required)
        Route::post('/login', [MobileController::class, 'login'])->name('login');
        
        // Protected routes (authentication required)
        Route::middleware('auth:sanctum')->group(function () {
            // Authentication
            Route::post('/logout', [MobileController::class, 'logout'])->name('logout');
            
            // Dashboard
            Route::get('/dashboard', [MobileController::class, 'dashboard'])->name('dashboard');
            
            // Job Reports
            Route::get('/job-reports', [MobileController::class, 'jobReports'])->name('job-reports');
            Route::put('/job-reports/{id}/status', [MobileController::class, 'updateJobStatus'])->name('job-reports.update-status');
            
            // Maintenance Schedules
            Route::get('/maintenance-schedules', [MobileController::class, 'maintenanceSchedules'])->name('maintenance-schedules');
            Route::put('/maintenance-schedules/{id}/status', [MobileController::class, 'updateMaintenanceStatus'])->name('maintenance-schedules.update-status');
            
            // Emergency Contacts
            Route::get('/emergency-contacts', [MobileController::class, 'emergencyContacts'])->name('emergency-contacts');
            Route::post('/emergency-logs', [MobileController::class, 'createEmergencyLog'])->name('emergency-logs.create');
            
            // User Profile
            Route::get('/profile', [MobileController::class, 'profile'])->name('profile');
            Route::put('/profile', [MobileController::class, 'updateProfile'])->name('profile.update');
            
            // Offline Sync
            Route::post('/sync', [MobileController::class, 'syncOfflineData'])->name('sync');
            Route::get('/offline-data', [MobileController::class, 'getOfflineData'])->name('offline-data');
        });
        
        // Health check endpoint
        Route::get('/health', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Mobile API is running',
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0'
            ]);
        })->name('health');
        });
    });

// Location cascade API routes
Route::get('/cities/{provinceId}', function($provinceId) {
    $cities = \App\Models\City::where('province_id', $provinceId)
        ->orderBy('name')
        ->get(['id', 'name']);
    return response()->json($cities);
});

Route::get('/districts/{cityId}', function($cityId) {
    $districts = \App\Models\District::where('city_id', $cityId)
        ->orderBy('name')
        ->get(['id', 'name']);
    return response()->json($districts);
});

Route::get('/subdistricts/{districtId}', function($districtId) {
    $subdistricts = \App\Models\Subdistrict::where('district_id', $districtId)
        ->orderBy('name')
        ->get(['id', 'name']);
    return response()->json($subdistricts);
});

Route::get('/subdistricts/{subdistrictId}/postal-code', function($subdistrictId) {
    $subdistrict = \App\Models\Subdistrict::find($subdistrictId);
    return response()->json([
        'postal_code' => $subdistrict ? $subdistrict->postal_code : null
    ]);
});

// Mobile API Routes
Route::prefix('v1/mobile')->middleware('auth:sanctum')->group(function () {
    // Job routes
    Route::post('/jobs/{id}/arrived-at-location', [App\Http\Controllers\Api\Mobile\JobController::class, 'arrivedAtLocation']);
    Route::post('/jobs/{id}/validate-serial-number', [App\Http\Controllers\Api\Mobile\JobController::class, 'validateSerialNumber']);
    Route::get('/jobs/{id}/detail', [App\Http\Controllers\Api\Mobile\JobController::class, 'getJobDetail']);
    
    // Unit routes
    Route::post('/units/scan-qr', [App\Http\Controllers\Api\Mobile\JobController::class, 'getUnitByQrCode'])->name('units.scan-qr');
    Route::post('/units/save-scanned', [App\Http\Controllers\Api\Mobile\JobController::class, 'saveScannedUnit'])->name('units.save-scanned');
    
    // Serial Number routes (for material checking)
    Route::post('/serial-numbers/check', [App\Http\Controllers\Api\Mobile\SerialNumberController::class, 'getBySerialNumber'])->name('serial-numbers.check');
    
    // Job verification route
    Route::post('/jobs/{jobScheduleId}/verify', [App\Http\Controllers\Api\Mobile\JobController::class, 'verifyJob'])->name('jobs.verify');
    
    // Leave location route
    Route::post('/jobs/{id}/leave', [App\Http\Controllers\Api\Mobile\JobController::class, 'leaveLocation'])->name('jobs.leave');
});
