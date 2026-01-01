<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');

Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('register', [AuthController::class, 'register'])->name('register.post');

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::prefix('dashboard')->middleware(['auth'])->group(function () {

    Route::get('/admin', [DashboardController::class, 'adminIndex'])
        ->middleware('role:admin,super_admin,staff')
        ->name('dashboard.admin');

    Route::get('/driver', [DashboardController::class, 'driverGuideIndex'])
        ->middleware('role:driver')
        ->name('dashboard.driver');

    Route::get('/guide', [DashboardController::class, 'driverGuideIndex'])
        ->middleware('role:guide')
        ->name('dashboard.guide');

    Route::get('/{any?}', function () {
        return redirect()->route('dashboard');
    })->where('any', '.*')->name('dashboard.fallback');
});

Route::middleware(['auth'])->group(function () {

    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class);
    });

    // Check vehicle availability types (admin + staff can create orders)
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('vehicles/check-availability-types', [VehicleController::class, 'checkAvailabilityTypes'])
            ->name('vehicles.check-availability-types');
        Route::get('vehicles/check-availability-list', [VehicleController::class, 'checkAvailabilityList'])
            ->name('vehicles.check-availability-list');
    });

    // Products: sekarang hanya super_admin + staff (admin tidak lagi)
    Route::middleware(['role:super_admin,staff'])->group(function () {
        Route::resource('products', ProductController::class);
    });

    // Vehicles: sekarang hanya super_admin + staff (admin tidak lagi)
    Route::middleware(['role:super_admin,staff'])->group(function () {
        Route::resource('vehicles', VehicleController::class);
    });

    Route::middleware(['role:staff'])->group(function () {
        Route::get('orders/check-latest', [OrderController::class, 'checkLatest'])
            ->name('orders.check-latest');
    });

    // Orders: super_admin, admin, staff (admin tetap boleh CRUD orders)
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::resource('orders', OrderController::class);
    });

    // Assignments: sekarang hanya super_admin + staff (admin tidak lagi)
    Route::middleware(['role:super_admin,staff'])->group(function () {
        Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
        Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
        Route::put('assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::get('assignments/check-vehicle', [AssignmentController::class, 'checkAvailability'])->name('assignments.check-vehicle');
        Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    });

    Route::middleware(['role:driver,guide'])->group(function () {
        Route::get('assignments/my', [AssignmentController::class, 'myAssignments'])->name('assignments.my');
        Route::post('assignments/{assignment}/status', [AssignmentController::class, 'changeStatus'])->name('assignments.changeStatus');
    });

    // Show assignment: admin dihapus, hanya super_admin, staff, driver, guide
    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])
        ->name('assignments.show')
        ->middleware('role:super_admin,staff,driver,guide');

    // Availability: sekarang hanya super_admin + staff (admin tidak lagi)
    Route::middleware(['role:super_admin,staff'])->group(function () {
        Route::get('availability', [AvailabilityController::class, 'index'])->name('availability.index');
        Route::post('availability/{user}/force', [AvailabilityController::class, 'forceChange'])->name('availability.force');
    });

    Route::middleware(['role:driver,guide'])->group(function () {
        Route::post('availability/toggle', [AvailabilityController::class, 'toggle'])->name('availability.toggle');
    });

    // Work schedules: sekarang hanya super_admin + staff (admin tidak lagi)
    Route::middleware(['role:super_admin,staff'])->group(function () {
        Route::get('work-schedules', [WorkScheduleController::class, 'index'])->name('work-schedules.index');
        Route::post('work-schedules/generate', [WorkScheduleController::class, 'generateForAll'])->name('work-schedules.generate');
        Route::post('work-schedules/bulk', [WorkScheduleController::class, 'bulkUpdate'])->name('work-schedules.bulkUpdate');
        Route::post('work-schedules/reset', [WorkScheduleController::class, 'resetUsedHours'])->name('work-schedules.reset');

        Route::get('work-schedules/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->name('work-schedules.edit');
        Route::put('work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])->name('work-schedules.update');
    });

    Route::middleware(['auth'])->group(function () {

        // Semua yang login bisa lihat halaman reports.index (termasuk admin)
        Route::get('reports', [ReportController::class, 'index'])
            ->name('reports.index');

        // Export laporan sistem: super_admin, admin, staff
        Route::middleware(['role:super_admin,admin,staff'])->group(function () {
            Route::get('reports/export/excel', [ReportController::class, 'exportExcel'])
                ->name('reports.export.excel');

            Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])
                ->name('reports.export.pdf');
        });

        // Export laporan pribadi: driver/guide
        Route::middleware(['role:driver,guide'])->group(function () {
            Route::get('reports/personal/export/pdf', [ReportController::class, 'exportPersonalPdf'])
                ->name('reports.personal.export.pdf');

            Route::get('reports/personal/export/excel', [ReportController::class, 'exportPersonalExcel'])
                ->name('reports.personal.export.excel');
        });
    });

    // Guide & Drivers List
    Route::middleware(['role:super_admin,admin,staff'])->group(function () {
        Route::get('guides-drivers', [App\Http\Controllers\GuidesDriversController::class, 'index'])
            ->name('guides-drivers.index');
    });

    // Notifications
    Route::middleware(['auth'])->group(function () {
        Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.readAll');
        Route::get('notifications/fetch', [\App\Http\Controllers\NotificationController::class, 'fetchLatest'])->name('notifications.fetch');
    });

});

Route::get('/ping', function () {
    return response('pong', 200);
});
