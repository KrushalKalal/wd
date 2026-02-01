<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StateMasterController;
use App\Http\Controllers\CityMasterController;
use App\Http\Controllers\AreaMasterController;
use App\Http\Controllers\DepartmentMasterController;
use App\Http\Controllers\CategoryOneMasterController;
use App\Http\Controllers\CategoryTwoMasterController;
use App\Http\Controllers\CategoryThreeMasterController;
use App\Http\Controllers\ProductCategoryMasterController;
use App\Http\Controllers\CompanyMasterController;
use App\Http\Controllers\BranchMasterController;
use App\Http\Controllers\StoreMasterController;
use App\Http\Controllers\ProductMasterController;
use App\Http\Controllers\OfferMasterController;
use App\Http\Controllers\QuestionMasterController;
use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\EmployeeMasterController;
use App\Http\Controllers\EmployeeTargetController;
use App\Http\Controllers\StockApprovalController;
use App\Http\Controllers\StoreVisitController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ============================================
// STATE MASTER ROUTES
// ============================================
    Route::get('/state-masters', [StateMasterController::class, 'index'])
        ->name('state-master.index');
    Route::get('/state-masters/create', [StateMasterController::class, 'create'])
        ->name('state-master.create');
    Route::post('/state-masters', [StateMasterController::class, 'store'])
        ->name('state-master.store');
    Route::get('/state-masters/{id}/edit', [StateMasterController::class, 'edit'])
        ->name('state-master.edit');
    Route::post('/state-masters/{id}', [StateMasterController::class, 'update'])
        ->name('state-master.update');
    Route::delete('/state-masters/{id}', [StateMasterController::class, 'destroy'])
        ->name('state-master.destroy');
    Route::post('/state-masters/{id}/toggle', [StateMasterController::class, 'toggleStatus'])
        ->name('state-master.toggle');
    Route::get('/state-master/download-template', [StateMasterController::class, 'downloadTemplate'])
        ->name('state-master.download-template');
    Route::post('/state-master/upload', [StateMasterController::class, 'uploadExcel'])
        ->name('state-master.upload');

    // ============================================
// CITY MASTER ROUTES
// ============================================
    Route::get('/city-masters', [CityMasterController::class, 'index'])
        ->name('city-master.index');
    Route::get('/city-masters/create', [CityMasterController::class, 'create'])
        ->name('city-master.create');
    Route::post('/city-masters', [CityMasterController::class, 'store'])
        ->name('city-master.store');
    Route::get('/city-masters/{id}/edit', [CityMasterController::class, 'edit'])
        ->name('city-master.edit');
    Route::post('/city-masters/{id}', [CityMasterController::class, 'update'])
        ->name('city-master.update');
    Route::delete('/city-masters/{id}', [CityMasterController::class, 'destroy'])
        ->name('city-master.destroy');
    Route::post('/city-masters/{id}/toggle', [CityMasterController::class, 'toggleStatus'])
        ->name('city-master.toggle');
    Route::get('/city-master/download-template', [CityMasterController::class, 'downloadTemplate'])
        ->name('city-master.download-template');
    Route::post('/city-master/upload', [CityMasterController::class, 'uploadExcel'])
        ->name('city-master.upload');


    // ============================================
// AREA MASTER ROUTES
// ============================================
    Route::get('/area-masters', [AreaMasterController::class, 'index'])
        ->name('area-master.index');
    Route::get('/area-masters/create', [AreaMasterController::class, 'create'])
        ->name('area-master.create');
    Route::post('/area-masters', [AreaMasterController::class, 'store'])
        ->name('area-master.store');
    Route::get('/area-masters/{id}/edit', [AreaMasterController::class, 'edit'])
        ->name('area-master.edit');
    Route::post('/area-masters/{id}', [AreaMasterController::class, 'update'])
        ->name('area-master.update');
    Route::delete('/area-masters/{id}', [AreaMasterController::class, 'destroy'])
        ->name('area-master.destroy');
    Route::post('/area-masters/{id}/toggle', [AreaMasterController::class, 'toggleStatus'])
        ->name('area-master.toggle');
    Route::get('/area-master/download-template', [AreaMasterController::class, 'downloadTemplate'])
        ->name('area-master.download-template');
    Route::post('/area-master/upload', [AreaMasterController::class, 'uploadExcel'])
        ->name('area-master.upload');

    // API endpoint for cascading dropdown
    Route::get('/cities/{stateId}', [AreaMasterController::class, 'getCitiesByState'])
        ->name('cities.by-state');

    // ============================================
// DEPARTMENT MASTER ROUTES
// ============================================
    Route::get('/department-masters', [DepartmentMasterController::class, 'index'])
        ->name('department-master.index');
    Route::get('/department-masters/create', [DepartmentMasterController::class, 'create'])
        ->name('department-master.create');
    Route::post('/department-masters', [DepartmentMasterController::class, 'store'])
        ->name('department-master.store');
    Route::get('/department-masters/{id}/edit', [DepartmentMasterController::class, 'edit'])
        ->name('department-master.edit');
    Route::post('/department-masters/{id}', [DepartmentMasterController::class, 'update'])
        ->name('department-master.update');
    Route::delete('/department-masters/{id}', [DepartmentMasterController::class, 'destroy'])
        ->name('department-master.destroy');
    Route::post('/department-masters/{id}/toggle', [DepartmentMasterController::class, 'toggleStatus'])
        ->name('department-master.toggle');
    Route::get('/department-master/download-template', [DepartmentMasterController::class, 'downloadTemplate'])
        ->name('department-master.download-template');
    Route::post('/department-master/upload', [DepartmentMasterController::class, 'uploadExcel'])
        ->name('department-master.upload');

    // ============================================
// CATEGORY ONE MASTER ROUTES
// ============================================
    Route::get('/category-one-masters', [CategoryOneMasterController::class, 'index'])
        ->name('category-one-master.index');
    Route::get('/category-one-masters/create', [CategoryOneMasterController::class, 'create'])
        ->name('category-one-master.create');
    Route::post('/category-one-masters', [CategoryOneMasterController::class, 'store'])
        ->name('category-one-master.store');
    Route::get('/category-one-masters/{id}/edit', [CategoryOneMasterController::class, 'edit'])
        ->name('category-one-master.edit');
    Route::post('/category-one-masters/{id}', [CategoryOneMasterController::class, 'update'])
        ->name('category-one-master.update');
    Route::delete('/category-one-masters/{id}', [CategoryOneMasterController::class, 'destroy'])
        ->name('category-one-master.destroy');
    Route::post('/category-one-masters/{id}/toggle', [CategoryOneMasterController::class, 'toggleStatus'])
        ->name('category-one-master.toggle');
    Route::get('/category-one-master/download-template', [CategoryOneMasterController::class, 'downloadTemplate'])
        ->name('category-one-master.download-template');
    Route::post('/category-one-master/upload', [CategoryOneMasterController::class, 'uploadExcel'])
        ->name('category-one-master.upload');

    // ============================================
// CATEGORY TWO MASTER ROUTES
// ============================================
    Route::get('/category-two-masters', [CategoryTwoMasterController::class, 'index'])
        ->name('category-two-master.index');
    Route::get('/category-two-masters/create', [CategoryTwoMasterController::class, 'create'])
        ->name('category-two-master.create');
    Route::post('/category-two-masters', [CategoryTwoMasterController::class, 'store'])
        ->name('category-two-master.store');
    Route::get('/category-two-masters/{id}/edit', [CategoryTwoMasterController::class, 'edit'])
        ->name('category-two-master.edit');
    Route::post('/category-two-masters/{id}', [CategoryTwoMasterController::class, 'update'])
        ->name('category-two-master.update');
    Route::delete('/category-two-masters/{id}', [CategoryTwoMasterController::class, 'destroy'])
        ->name('category-two-master.destroy');
    Route::post('/category-two-masters/{id}/toggle', [CategoryTwoMasterController::class, 'toggleStatus'])
        ->name('category-two-master.toggle');
    Route::get('/category-two-master/download-template', [CategoryTwoMasterController::class, 'downloadTemplate'])
        ->name('category-two-master.download-template');
    Route::post('/category-two-master/upload', [CategoryTwoMasterController::class, 'uploadExcel'])
        ->name('category-two-master.upload');

    // ============================================
// CATEGORY THREE MASTER ROUTES
// ============================================
    Route::get('/category-three-masters', [CategoryThreeMasterController::class, 'index'])
        ->name('category-three-master.index');
    Route::get('/category-three-masters/create', [CategoryThreeMasterController::class, 'create'])
        ->name('category-three-master.create');
    Route::post('/category-three-masters', [CategoryThreeMasterController::class, 'store'])
        ->name('category-three-master.store');
    Route::get('/category-three-masters/{id}/edit', [CategoryThreeMasterController::class, 'edit'])
        ->name('category-three-master.edit');
    Route::post('/category-three-masters/{id}', [CategoryThreeMasterController::class, 'update'])
        ->name('category-three-master.update');
    Route::delete('/category-three-masters/{id}', [CategoryThreeMasterController::class, 'destroy'])
        ->name('category-three-master.destroy');
    Route::post('/category-three-masters/{id}/toggle', [CategoryThreeMasterController::class, 'toggleStatus'])
        ->name('category-three-master.toggle');
    Route::get('/category-three-master/download-template', [CategoryThreeMasterController::class, 'downloadTemplate'])
        ->name('category-three-master.download-template');
    Route::post('/category-three-master/upload', [CategoryThreeMasterController::class, 'uploadExcel'])
        ->name('category-three-master.upload');

    // ============================================
// PRODUCT CATEGORY MASTER ROUTES
// ============================================
    Route::get('/product-category-masters', [ProductCategoryMasterController::class, 'index'])
        ->name('product-category-master.index');
    Route::get('/product-category-masters/create', [ProductCategoryMasterController::class, 'create'])
        ->name('product-category-master.create');
    Route::post('/product-category-masters', [ProductCategoryMasterController::class, 'store'])
        ->name('product-category-master.store');
    Route::get('/product-category-masters/{id}/edit', [ProductCategoryMasterController::class, 'edit'])
        ->name('product-category-master.edit');
    Route::post('/product-category-masters/{id}', [ProductCategoryMasterController::class, 'update'])
        ->name('product-category-master.update');
    Route::delete('/product-category-masters/{id}', [ProductCategoryMasterController::class, 'destroy'])
        ->name('product-category-master.destroy');
    Route::post('/product-category-masters/{id}/toggle', [ProductCategoryMasterController::class, 'toggleStatus'])
        ->name('product-category-master.toggle');
    Route::get('/product-category-master/download-template', [ProductCategoryMasterController::class, 'downloadTemplate'])
        ->name('product-category-master.download-template');
    Route::post('/product-category-master/upload', [ProductCategoryMasterController::class, 'uploadExcel'])
        ->name('product-category-master.upload');

    // ============================================
// COMPANY MASTER ROUTES
// ============================================
    Route::get('/company-masters', [CompanyMasterController::class, 'index'])
        ->name('company-master.index');
    Route::get('/company-masters/create', [CompanyMasterController::class, 'create'])
        ->name('company-master.create');
    Route::post('/company-masters', [CompanyMasterController::class, 'store'])
        ->name('company-master.store');
    Route::get('/company-masters/{id}/edit', [CompanyMasterController::class, 'edit'])
        ->name('company-master.edit');
    Route::post('/company-masters/{id}', [CompanyMasterController::class, 'update'])
        ->name('company-master.update');
    Route::delete('/company-masters/{id}', [CompanyMasterController::class, 'destroy'])
        ->name('company-master.destroy');
    Route::post('/company-masters/{id}/toggle', [CompanyMasterController::class, 'toggleStatus'])
        ->name('company-master.toggle');
    Route::get('/company-master/download-template', [CompanyMasterController::class, 'downloadTemplate'])
        ->name('company-master.download-template');
    Route::post('/company-master/upload', [CompanyMasterController::class, 'uploadExcel'])
        ->name('company-master.upload');

    // ============================================
// BRANCH MASTER ROUTES
// ============================================
    Route::get('/branch-masters', [BranchMasterController::class, 'index'])
        ->name('branch-master.index');
    Route::get('/branch-masters/create', [BranchMasterController::class, 'create'])
        ->name('branch-master.create');
    Route::post('/branch-masters', [BranchMasterController::class, 'store'])
        ->name('branch-master.store');
    Route::get('/branch-masters/{id}/edit', [BranchMasterController::class, 'edit'])
        ->name('branch-master.edit');
    Route::post('/branch-masters/{id}', [BranchMasterController::class, 'update'])
        ->name('branch-master.update');
    Route::delete('/branch-masters/{id}', [BranchMasterController::class, 'destroy'])
        ->name('branch-master.destroy');
    Route::post('/branch-masters/{id}/toggle', [BranchMasterController::class, 'toggleStatus'])
        ->name('branch-master.toggle');
    Route::get('/branch-master/download-template', [BranchMasterController::class, 'downloadTemplate'])
        ->name('branch-master.download-template');
    Route::post('/branch-master/upload', [BranchMasterController::class, 'uploadExcel'])
        ->name('branch-master.upload');

    // ============================================
// STORE MASTER ROUTES
// ============================================
    Route::get('/store-masters', [StoreMasterController::class, 'index'])
        ->name('store-master.index');
    Route::get('/store-masters/create', [StoreMasterController::class, 'create'])
        ->name('store-master.create');
    Route::post('/store-masters', [StoreMasterController::class, 'store'])
        ->name('store-master.store');
    Route::get('/store-masters/{id}/edit', [StoreMasterController::class, 'edit'])
        ->name('store-master.edit');
    Route::post('/store-masters/{id}', [StoreMasterController::class, 'update'])
        ->name('store-master.update');
    Route::delete('/store-masters/{id}', [StoreMasterController::class, 'destroy'])
        ->name('store-master.destroy');
    Route::post('/store-masters/{id}/toggle', [StoreMasterController::class, 'toggleStatus'])
        ->name('store-master.toggle');
    Route::get('/store-master/download-template', [StoreMasterController::class, 'downloadTemplate'])
        ->name('store-master.download-template');
    Route::post('/store-master/upload', [StoreMasterController::class, 'uploadExcel'])
        ->name('store-master.upload');
    Route::get('/stores/all-active', [StoreMasterController::class, 'getAllActiveStores'])->name('stores.all-active');

    // ============================================
// PRODUCT MASTER ROUTES
// ============================================
    Route::get('/product-masters', [ProductMasterController::class, 'index'])
        ->name('product-master.index');
    Route::get('/product-masters/create', [ProductMasterController::class, 'create'])
        ->name('product-master.create');
    Route::post('/product-masters', [ProductMasterController::class, 'store'])
        ->name('product-master.store');
    Route::get('/product-masters/{id}/edit', [ProductMasterController::class, 'edit'])
        ->name('product-master.edit');
    Route::post('/product-masters/{id}', [ProductMasterController::class, 'update'])
        ->name('product-master.update');
    Route::delete('/product-masters/{id}', [ProductMasterController::class, 'destroy'])
        ->name('product-master.destroy');
    Route::post('/product-masters/{id}/toggle', [ProductMasterController::class, 'toggleStatus'])
        ->name('product-master.toggle');
    Route::get('/product-master/download-template', [ProductMasterController::class, 'downloadTemplate'])
        ->name('product-master.download-template');
    Route::post('/product-master/upload', [ProductMasterController::class, 'uploadExcel'])
        ->name('product-master.upload');

    // ============================================
    // OFFER MASTER ROUTES (NEW)
    // ============================================
    Route::get('/offer-masters', [OfferMasterController::class, 'index'])
        ->name('offer-master.index');
    Route::get('/offer-masters/create', [OfferMasterController::class, 'create'])
        ->name('offer-master.create');
    Route::post('/offer-masters', [OfferMasterController::class, 'store'])
        ->name('offer-master.store');
    Route::get('/offer-masters/{id}/edit', [OfferMasterController::class, 'edit'])
        ->name('offer-master.edit');
    Route::post('/offer-masters/{id}', [OfferMasterController::class, 'update'])
        ->name('offer-master.update');
    Route::delete('/offer-masters/{id}', [OfferMasterController::class, 'destroy'])
        ->name('offer-master.destroy');
    Route::post('/offer-masters/{id}/toggle', [OfferMasterController::class, 'toggleStatus'])
        ->name('offer-master.toggle');
    Route::get('/offer-master/download-template', [OfferMasterController::class, 'downloadTemplate'])
        ->name('offer-master.download-template');
    Route::post('/offer-master/upload', [OfferMasterController::class, 'uploadExcel'])
        ->name('offer-master.upload');
    Route::get('/offer-master/cities/{stateId}', [OfferMasterController::class, 'getCitiesByState'])->name('offer-master.cities.by-state');
    Route::get('/offer-master/areas/{cityId}', [OfferMasterController::class, 'getAreasByCity'])->name('offer-master.areas.by-city');
    Route::get('/offer-master/stores-by-categories', [OfferMasterController::class, 'getStoresByCategories'])->name('offer-master.stores.by-categories');

    // ============================================
// QUESTION MASTER ROUTES
// ============================================
    Route::get('/question-masters', [QuestionMasterController::class, 'index'])
        ->name('question-master.index');
    Route::get('/question-masters/create', [QuestionMasterController::class, 'create'])
        ->name('question-master.create');
    Route::post('/question-masters', [QuestionMasterController::class, 'store'])
        ->name('question-master.store');
    Route::get('/question-masters/{id}/edit', [QuestionMasterController::class, 'edit'])
        ->name('question-master.edit');
    Route::post('/question-masters/{id}', [QuestionMasterController::class, 'update'])
        ->name('question-master.update');
    Route::delete('/question-masters/{id}', [QuestionMasterController::class, 'destroy'])
        ->name('question-master.destroy');
    Route::post('/question-masters/{id}/toggle', [QuestionMasterController::class, 'toggleStatus'])
        ->name('question-master.toggle');
    Route::get('/question-master/download-template', [QuestionMasterController::class, 'downloadTemplate'])
        ->name('question-master.download-template');
    Route::post('/question-master/upload', [QuestionMasterController::class, 'uploadExcel'])
        ->name('question-master.upload');

    // API endpoints for cascading dropdowns
    Route::get('/areas/{cityId}', [StoreMasterController::class, 'getAreasByCity'])
        ->name('areas.by-city');

    // ============================================
// EMPLOYEE MASTER ROUTES
// ============================================
    Route::get('/employee-masters', [EmployeeMasterController::class, 'index'])
        ->name('employee-master.index');
    Route::get('/employee-masters/create', [EmployeeMasterController::class, 'create'])
        ->name('employee-master.create');
    Route::post('/employee-masters', [EmployeeMasterController::class, 'store'])
        ->name('employee-master.store');
    Route::get('/employee-masters/{id}/edit', [EmployeeMasterController::class, 'edit'])
        ->name('employee-master.edit');
    Route::post('/employee-masters/{id}', [EmployeeMasterController::class, 'update'])
        ->name('employee-master.update');
    Route::delete('/employee-masters/{id}', [EmployeeMasterController::class, 'destroy'])
        ->name('employee-master.destroy');
    Route::post('/employee-masters/{id}/toggle', [EmployeeMasterController::class, 'toggleStatus'])
        ->name('employee-master.toggle');
    Route::get('/employee-master/download-template', [EmployeeMasterController::class, 'downloadTemplate'])
        ->name('employee-master.download-template');
    Route::post('/employee-masters/upload', [EmployeeMasterController::class, 'uploadExcel'])
        ->name('employee-master.upload');

    // Store Assignment Routes
    Route::get('/employee-masters/{id}/stores', [EmployeeMasterController::class, 'getAssignedStores'])
        ->name('employee-master.stores');
    Route::post('/employee-masters/{id}/assign-stores', [EmployeeMasterController::class, 'assignStores'])
        ->name('employee-master.assign-stores');
    Route::delete('/employee-masters/{id}/stores/{assignmentId}', [EmployeeMasterController::class, 'removeStoreAssignment'])
        ->name('employee-master.remove-store');

    // ============================================
// EMPLOYEE TARGET ROUTES
// ============================================
    Route::get('/employee-targets', [EmployeeTargetController::class, 'index'])
        ->name('employee-target.index');
    Route::get('/employee-targets/create', [EmployeeTargetController::class, 'create'])
        ->name('employee-target.create');
    Route::post('/employee-targets', [EmployeeTargetController::class, 'store'])
        ->name('employee-target.store');
    Route::get('/employee-targets/{id}/edit', [EmployeeTargetController::class, 'edit'])
        ->name('employee-target.edit');
    Route::post('/employee-targets/{id}', [EmployeeTargetController::class, 'update'])
        ->name('employee-target.update');
    Route::delete('/employee-targets/{id}', [EmployeeTargetController::class, 'destroy'])
        ->name('employee-target.destroy');


    // ============================================
// STORE PRODUCT ROUTES
// ============================================
    Route::get('/store-products', [StoreProductController::class, 'index'])
        ->name('store-product.index');
    Route::get('/store-products/create', [StoreProductController::class, 'create'])
        ->name('store-product.create');
    Route::post('/store-products', [StoreProductController::class, 'store'])
        ->name('store-product.store');
    Route::get('/store-products/{id}/edit', [StoreProductController::class, 'edit'])
        ->name('store-product.edit');
    Route::post('/store-products/{id}', [StoreProductController::class, 'update'])
        ->name('store-product.update');
    Route::delete('/store-products/{id}', [StoreProductController::class, 'destroy'])
        ->name('store-product.destroy');
    Route::get('/store-product/download-template', [StoreProductController::class, 'downloadTemplate'])
        ->name('store-product.download-template');
    Route::post('/store-product/upload', [StoreProductController::class, 'uploadExcel'])
        ->name('store-product.upload');

    // Store Visits
    Route::get('/store-visits', [StoreVisitController::class, 'index'])->name('store-visits.index');
    Route::get('/store-visits/{id}', [StoreVisitController::class, 'show'])->name('store-visits.show');
    Route::post('/store-visits/survey/{answerId}/review', [StoreVisitController::class, 'updateSurveyStatus'])->name('store-visits.survey.review');
    Route::get('/store-visits/statistics', [StoreVisitController::class, 'statistics'])->name('store-visits.statistics');

    // Stock Approvals
    Route::get('/stock-approvals', [StockApprovalController::class, 'index'])->name('stock-approvals.index');
    Route::get('/stock-approvals/{id}', [StockApprovalController::class, 'show'])->name('stock-approvals.show');
    Route::post('/stock-approvals/{id}/approve', [StockApprovalController::class, 'approve'])->name('stock-approvals.approve');
    Route::post('/stock-approvals/{id}/reject', [StockApprovalController::class, 'reject'])->name('stock-approvals.reject');
    Route::post('/stock-approvals/{id}/deliver', [StockApprovalController::class, 'markDelivered'])->name('stock-approvals.deliver');
    Route::post('/stock-approvals/{id}/return', [StockApprovalController::class, 'markReturned'])->name('stock-approvals.return');
    Route::post('/stock-approvals/bulk-approve', [StockApprovalController::class, 'bulkApprove'])->name('stock-approvals.bulk-approve');
    Route::get('/stock-approvals/statistics', [StockApprovalController::class, 'statistics'])->name('stock-approvals.statistics');

});

// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
