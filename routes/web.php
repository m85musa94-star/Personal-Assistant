<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeRecordController;
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
    Route::get('/tasks', fn () => view('tasks'))->middleware('can:tasks.use')->name('tasks');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/company/switch', CompanySwitchController::class)->name('company.switch');

    Route::middleware('can:tasks.use')->group(function () {
        Route::get('/api/state', [StateController::class, 'show']);
        Route::put('/api/state', [StateController::class, 'update']);
    });

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    // الموظفون (العرض بصلاحية employees.view؛ التعديل يُفحص داخل المتحكم)
    Route::middleware('can:employees.view')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/register', [EmployeeController::class, 'register'])->name('employees.register');
        Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    });
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::middleware('can:employees.view')->group(function () {
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/print', [EmployeeController::class, 'print'])->name('employees.print');
    });
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employee-documents.store');
    Route::put('/employee-documents/{document}', [EmployeeDocumentController::class, 'update'])->name('employee-documents.update');
    Route::delete('/employee-documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employee-documents.destroy');
    Route::post('/employees/{employee}/records', [EmployeeRecordController::class, 'store'])->name('employee-records.store');
    Route::delete('/employee-records/{record}', [EmployeeRecordController::class, 'destroy'])->name('employee-records.destroy');

    // الإجازات
    Route::get('/leaves', [LeaveController::class, 'index'])->middleware('can:leaves.view')->name('leaves.index');
    Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/toggle', [LeaveController::class, 'toggle'])->name('leaves.toggle');

    // السيارات وسجلاتها
    Route::get('/vehicles', [VehicleController::class, 'index'])->middleware('can:vehicles.view')->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->middleware('can:vehicles.view')->name('vehicles.show');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::put('/vehicles/{vehicle}/status', [VehicleController::class, 'status'])->name('vehicles.status');
    Route::post('/vehicles/{vehicle}/documents', [VehicleDocumentController::class, 'store'])->name('vehicle-documents.store');
    Route::put('/vehicle-documents/{document}', [VehicleDocumentController::class, 'update'])->name('vehicle-documents.update');
    Route::delete('/vehicle-documents/{document}', [VehicleDocumentController::class, 'destroy'])->name('vehicle-documents.destroy');
    Route::get('/vehicle-records', [VehicleRecordController::class, 'index'])->middleware('can:vehicles.view')->name('vehicle-records.index');
    Route::post('/vehicles/{vehicle}/records', [VehicleRecordController::class, 'store'])->name('vehicle-records.store');
    Route::delete('/vehicle-records/{record}', [VehicleRecordController::class, 'destroy'])->name('vehicle-records.destroy');

    Route::get('/alerts', [AlertController::class, 'index'])->middleware('can:alerts.view')->name('alerts.index');

    // الإعدادات (القوائم للقراءة لمن يرى الشركات؛ التعديل بصلاحياته)
    Route::prefix('settings')->group(function () {
        Route::middleware('can:companies.view')->group(function () {
            Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
            Route::get('leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
        });
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('companies/{company}', [CompanyController::class, 'show'])->middleware('can:companies.view')->name('companies.show');
        Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::put('leave-types/{leave_type}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
        Route::delete('leave-types/{leave_type}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
    });

    Route::middleware('admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
        Route::put('/{user}/password', [UserController::class, 'password'])->name('password');
    });
});
