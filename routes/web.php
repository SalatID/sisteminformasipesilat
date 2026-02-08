<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminCotroller;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PermisionController;
use App\Http\Controllers\RoleController;
// use App\Http\Controllers\PesilatController;
use App\Http\Controllers\AttendanceController;

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

Route::get('/', [WebController::class, 'index']);
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'procLogin'])->name('proc.login');
Route::post('/register/newpassword', [UserManagementController::class, 'newPassword'])->name('newpassword');
Route::get('/register/validate/{token}', [UserManagementController::class, 'emailValidation'])->name('email.validation');
Route::post('/forgot/password', [UserManagementController::class, 'procForgotPassword'])->name('proc.forgot.password');
Route::get('/forgot/password', [UserManagementController::class, 'forgotPassword'])->name('forgot.password');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', [AdminCotroller::class, 'dashboard'])->name('dashboard');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::resource('roles', RoleController::class);
    
    // Route::resource('pesilat', PesilatController::class);
    // Route::post('/pesilat/filter', [PesilatController::class, 'applyFilter'])->name('pesilat.filter');
    // Route::get('/pesilat/filter/reset', [PesilatController::class, 'applyFilter'])->name('pesilat.filter.reset');

    Route::get('attendance/coach', [AttendanceController::class, 'index'])->name('attendance.coach.index');
    Route::get('attendance/coach/sync', [AttendanceController::class, 'syncAttendance'])->name('attendance.coach.sync');
    Route::get('attendance/coach/resend-notif/{id}', [AttendanceController::class, 'resendNotif'])->name('attendance.coach.resend.notif');
    Route::get('attendance/coach/{id}', [AttendanceController::class, 'show'])->name('attendance.coach.show');
    Route::get('attendance/coach/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.coach.edit');
    Route::post('attendance/coach', [AttendanceController::class, 'store'])->name('attendance.coach.store');
    Route::put('attendance/coach/{id}', [AttendanceController::class, 'update'])->name('attendance.coach.update');
    Route::delete('attendance/coach/{id}', [AttendanceController::class, 'destroy'])->name('attendance.coach.destroy');

    Route::get('report/unit-attendance', [AttendanceController::class, 'unitAttendanceReport'])->name('report.unit.attendance.index');
    Route::get('report/attendance-percentage', [AttendanceController::class, 'attendancePercentageReport'])->name('report.attendance.percentage.index');

    Route::get('report/contribution/percoach', [AttendanceController::class, 'contributionPerCoach'])->name('report.contribution.percoach');

    Route::match(['get', 'post'], 'receipt/contribution-unit', [AttendanceController::class, 'contributionReceiptUnit'])->name('receipt.contribution.unit.index');
    // Route::post('receipt/contribution-unit/save', [AttendanceController::class, 'saveContributionReceipt'])->name('receipt.contribution.unit.save');
    Route::get('receipt/contribution-history', [AttendanceController::class, 'contributionHistory'])->name('receipt.contribution.history');
    Route::delete('receipt/contribution/{id}', [AttendanceController::class, 'deleteContribution'])->name('receipt.contribution.delete');
      Route::get('receipt/contribution/approve/{id}', [AttendanceController::class, 'contributionApprove'])->name('receipt.contribution.unit.approve');

    Route::resource('users', UserManagementController::class);
    Route::resource('permissions', PermisionController::class);
    Route::get('/register/resend/link/{token}', [UserManagementController::class, 'resendActivationLink'])->name('users.resend.activation.link');
    /*
        GET           /users                      index   users.index
        GET           /users/create               create  users.create
        POST          /users                      store   users.store
        GET           /users/{user}               show    users.show
        GET           /users/{user}/edit          edit    users.edit
        PUT|PATCH     /users/{user}               update  users.update
        DELETE        /users/{user}               destroy users.destroy
     */
    
});
