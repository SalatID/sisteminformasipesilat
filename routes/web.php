<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminCotroller;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberRegistrationController;
use App\Http\Controllers\TrainingCenterController;
use App\Http\Controllers\MemberTrainingCenterController;
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

// Public Member Registration Routes
Route::get('/member/register', [MemberRegistrationController::class, 'create'])->name('member.registration.create');
Route::post('/member/register', [MemberRegistrationController::class, 'store'])->name('member.registration.store');
Route::get('/member/register/success', [MemberRegistrationController::class, 'success'])->name('member.registration.success');

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
    
    // Coach Management Routes
    Route::get('/coach', [AdminCotroller::class, 'coachIndex'])->name('coach.index');
    Route::get('/coach/create', [AdminCotroller::class, 'coachCreate'])->name('coach.create');
    Route::post('/coach', [AdminCotroller::class, 'coachStore'])->name('coach.store');
    Route::get('/coach/{id}', [AdminCotroller::class, 'coachShow'])->name('coach.show');
    Route::get('/coach/{id}/edit', [AdminCotroller::class, 'coachEdit'])->name('coach.edit');
    Route::put('/coach/{id}', [AdminCotroller::class, 'coachUpdate'])->name('coach.update');
    Route::delete('/coach/{id}', [AdminCotroller::class, 'coachDestroy'])->name('coach.destroy');
    
    // Coach Exam History Routes
    Route::post('/coach/{coachId}/exam', [AdminCotroller::class, 'coachExamStore'])->name('coach.exam.store');
    Route::delete('/coach/{coachId}/exam/{examId}', [AdminCotroller::class, 'coachExamDestroy'])->name('coach.exam.destroy');
    
    // Member Management Routes
    Route::get('/member', [MemberController::class, 'index'])->name('member.index');
    Route::get('/member/create', [MemberController::class, 'create'])->name('member.create');
    Route::post('/member', [MemberController::class, 'store'])->name('member.store');
    Route::get('/member/{id}', [MemberController::class, 'show'])->name('member.show');
    Route::get('/member/{id}/edit', [MemberController::class, 'edit'])->name('member.edit');
    Route::put('/member/{id}', [MemberController::class, 'update'])->name('member.update');
    Route::delete('/member/{id}', [MemberController::class, 'destroy'])->name('member.destroy');
    
    // Member Exam History Routes
    Route::post('/member/{memberId}/exam', [MemberController::class, 'memberExamStore'])->name('member.exam.store');
    Route::delete('/member/{memberId}/exam/{examId}', [MemberController::class, 'memberExamDestroy'])->name('member.exam.destroy');
    
    // Member Registration Approval Routes
    Route::get('/member/registrations/pending', [MemberController::class, 'pending'])->name('member.registrations.pending');
    Route::post('/member/registrations/{id}/approve', [MemberController::class, 'approveMember'])->name('member.registration.approve');
    Route::post('/member/registrations/{id}/reject', [MemberController::class, 'rejectMember'])->name('member.registration.reject');
    
    // Training Center Management Routes
    Route::resource('training-center', TrainingCenterController::class);
    
    // Member Training Center Routes
    Route::post('/member/{memberId}/training-center/attach', [MemberTrainingCenterController::class, 'attach'])->name('member.training-center.attach');
    Route::delete('/member/{memberId}/training-center/{trainingCenterId}/detach', [MemberTrainingCenterController::class, 'detach'])->name('member.training-center.detach');
    Route::get('/api/member/{memberId}/available-centers', [MemberTrainingCenterController::class, 'getAvailableCenters']);
});
