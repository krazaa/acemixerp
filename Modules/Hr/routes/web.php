<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\AttendanceController;
use Modules\Hr\Http\Controllers\EmployeeController;
use Modules\Hr\Http\Controllers\EmployeeExitController;
use Modules\Hr\Http\Controllers\HrController;
use Modules\Hr\Http\Controllers\LeaveRequestController;
use Modules\Hr\Http\Controllers\PayrollRunController;
use Modules\Hr\Http\Controllers\SalaryStructureController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('hr', [HrController::class, 'index'])->name('hr.index');
    Route::resource('employees', EmployeeController::class);
    Route::patch('employees/{employee}/activate', [EmployeeController::class, 'activate'])->name('employees.activate');
    Route::resource('salary-structures', SalaryStructureController::class);
    Route::resource('attendances', AttendanceController::class)->only(['index', 'create', 'store']);
    Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'create', 'store']);
    Route::patch('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::patch('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::resource('payroll-runs', PayrollRunController::class)->only(['index', 'create', 'store']);
    Route::patch('payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])->name('payroll-runs.approve');
    Route::patch('payroll-runs/{payrollRun}/finalize', [PayrollRunController::class, 'finalize'])->name('payroll-runs.finalize');
    Route::resource('employee-exits', EmployeeExitController::class)->only(['index', 'create', 'store']);
});
