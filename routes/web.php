<?php

use App\Http\Controllers\ClassesController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\Intake\IntakeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    return Auth::check()
        ? Inertia::render('Dashboard')
        : Inertia::render('Auth/Login');
})->name('home')->breadcrumb('Home');

Route::controller(LegalController::class)->group(function () {
    Route::get('/privacy-policy', 'privacyPolicy')->name('privacy-policy')->breadcrumb('Privacy Policy', 'home');
    Route::get('/terms-of-service', 'termsOfService')->name('terms-of-service')->breadcrumb('Terms of Service', 'home');
});

Route::middleware('auth')->name('profile.')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->name('users.')->group(function () {
    Route::get('/users', [UsersController::class, 'list'])->name('list')->breadcrumb('Users', 'home');
    Route::get('/users/create', [UsersController::class, 'create'])->name('create')->breadcrumb('Create User', 'users.list');
    Route::post('/users', [UsersController::class, 'store'])->name('store');
    Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('edit')->breadcrumb(fn ($user) => "Edit $user->first_name $user->last_name", 'users.list');
    Route::put('/users/{user}', [UsersController::class, 'update'])->name('update');
    Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('destroy');
    Route::delete('/users', [UsersController::class, 'destroyMultiple'])->name('destroyMultiple');
    Route::get('/users/{user}', [UsersController::class, 'show'])->name('show')->breadcrumb(fn ($user) => "$user->first_name $user->last_name", 'users.list');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/curriculum', [UsersController::class, 'list'])->name('curriculum.list');
});
Route::middleware(['auth'])->name('curriculum.')->group(function () {
    Route::get('/curriculum', [CurriculumController::class, 'list'])->name('list')->breadcrumb('Curriculum', 'home');
});

Route::middleware(['auth'])->name('classes.')->group(function () {
    Route::get('/classes', [ClassesController::class, 'list'])->name('list')->breadcrumb('Classes', 'home');
});

Route::middleware(['auth'])->name('reports.')->group(function () {
    Route::get('/reports', [ReportsController::class, 'list'])->name('list')->breadcrumb('Reports', 'home');
});

// Route::get('/pdf-fake', [PdfController::class, 'generateFake'])
//     // ->middleware('auth')
//     ->name('test.pdf');

Route::get('/intake/{participantId}/pdf', [IntakeController::class, 'generatePdf']);

require __DIR__.'/auth.php';
