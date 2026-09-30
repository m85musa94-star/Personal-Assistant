<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\HrController;
use App\Http\Controllers\Hr\LeaveController;
use App\Http\Controllers\Hr\LeaveTypeController;
use App\Http\Controllers\Hr\OrgController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/preferences', [PreferencesController::class, 'update'])->name('preferences');

Route::middleware('guest')->group(function () {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', fn () => view('app'))->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/api/state', [StateController::class, 'show']);
    Route::put('/api/state', [StateController::class, 'update']);

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/', [HrController::class, 'index'])->name('index');
        Route::get('/org', [OrgController::class, 'index'])->name('org');
        Route::resource('employees', EmployeeController::class);
        Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('leave-types', LeaveTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('/leave/create', [LeaveController::class, 'create'])->name('leave.create');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::post('/leave/{leave}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leave}/reject', [LeaveController::class, 'reject'])->name('leave.reject');
        Route::post('/leave/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.in');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.out');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::delete('/attendance/{record}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    });

    Route::middleware('admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
        Route::put('/{user}/role', [UserController::class, 'role'])->name('role');
        Route::put('/{user}/password', [UserController::class, 'password'])->name('password');
    });
});
