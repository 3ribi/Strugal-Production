<?php

use App\Http\Controllers\LaquageReportExportController;
use App\Livewire\Admin\Departments\Index as DepartmentsIndex;
use App\Livewire\Admin\Users\Create as CreateUser;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Dashboard;
use App\Livewire\Departments\Laquage\CreateReport;
use App\Livewire\Departments\Laquage\Reports;
use App\Livewire\Departments\Laquage\ShowReport;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Départements
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'department:laquage'])
    ->prefix('departements/laquage')
    ->name('departments.laquage.')
    ->group(function () {
        Route::get('rapports', Reports::class)->name('index');
        Route::get('rapports/nouveau', CreateReport::class)->name('create');
        Route::get('rapports/{reportId}', ShowReport::class)->whereNumber('reportId')->name('show');
        Route::get('rapports/{report}/excel', LaquageReportExportController::class)
            ->whereNumber('report')
            ->name('export');
    });

Route::middleware(['auth', 'department:emballage'])->group(function () {
    Route::view('departements/emballage', 'departments.emballage')->name('departments.emballage');
});

// Anciennes URLs conservées pour ne pas casser les favoris.
Route::middleware('auth')->group(function () {
    Route::redirect('laquage', '/departements/laquage/rapports');
    Route::redirect('emballage', '/departements/emballage');
    Route::redirect('departments/laquage/report/create', '/departements/laquage/rapports/nouveau');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', CreateUser::class)->name('users.create');
        Route::get('/users/{user}/edit', UsersEdit::class)->name('users.edit');

        Route::get('/departments', DepartmentsIndex::class)->name('departments.index');
    });

require __DIR__.'/settings.php';
