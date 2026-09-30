<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\LeaveTypeController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleDocumentController;
use App\Http\Controllers\VehicleRecordController;
use Illuminate\Support\Facades\Route;

Route::post('/preferences', [PreferencesController::class, 'update'])->name('preferences');

Route::middleware('guest')->group(function () {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/tasks', fn () => view('tasks'))->name('tasks');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/company/switch', CompanySwitchController::class)->name('company.switch');

    Route::get('/api/state', [StateController::class, 'show']);
    Route::put('/api/state', [StateController::class, 'update']);

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    // الموظفون والإجازات
    Route::resource('employees', EmployeeController::class)->except(['edit']);
    Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employee-documents.store');
    Route::put('/employee-documents/{document}', [EmployeeDocumentController::class, 'update'])->name('employee-documents.update');
    Route::delete('/employee-documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employee-documents.destroy');
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/toggle', [LeaveController::class, 'toggle'])->name('leaves.toggle');

    // السيارات وسجلاتها
    Route::resource('vehicles', VehicleController::class)->except(['edit']);
    Route::put('/vehicles/{vehicle}/status', [VehicleController::class, 'status'])->name('vehicles.status');
    Route::post('/vehicles/{vehicle}/documents', [VehicleDocumentController::class, 'store'])->name('vehicle-documents.store');
    Route::put('/vehicle-documents/{document}', [VehicleDocumentController::class, 'update'])->name('vehicle-documents.update');
    Route::delete('/vehicle-documents/{document}', [VehicleDocumentController::class, 'destroy'])->name('vehicle-documents.destroy');
    Route::get('/vehicle-records', [VehicleRecordController::class, 'index'])->name('vehicle-records.index');
    Route::post('/vehicles/{vehicle}/records', [VehicleRecordController::class, 'store'])->name('vehicle-records.store');
    Route::delete('/vehicle-records/{record}', [VehicleRecordController::class, 'destroy'])->name('vehicle-records.destroy');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');

    // الإعدادات
    Route::prefix('settings')->group(function () {
        Route::resource('companies', CompanyController::class);
        Route::resource('leave-types', LeaveTypeController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
        Route::put('/{user}/role', [UserController::class, 'role'])->name('role');
        Route::put('/{user}/password', [UserController::class, 'password'])->name('password');
    });
});
