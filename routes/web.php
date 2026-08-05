<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('forms.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Form Builder
    |--------------------------------------------------------------------------
    */

    Route::livewire('/form-builder/{formId?}', 'form-builder')
        ->name('form-builder');

    /*
    |--------------------------------------------------------------------------
    | Forms List
    |--------------------------------------------------------------------------
    */

    Route::livewire('/forms', 'forms-list')
        ->name('forms.index');

    /*
    |--------------------------------------------------------------------------
    | Import Form
    |--------------------------------------------------------------------------
    */

    Route::livewire('/forms/import', 'form-importer')
        ->name('forms.import');

    /*
    |--------------------------------------------------------------------------
    | Preview Form
    |--------------------------------------------------------------------------
    */

    Route::livewire(
        '/forms/{formId}/preview',
        'form-preview'
    )->name('forms.preview');

    /*
    |--------------------------------------------------------------------------
    | Public Form
    |--------------------------------------------------------------------------
    */

    Route::livewire(
        '/public/forms/{formId}',
        'public-form'
    )->name('forms.public');

    /*
    |--------------------------------------------------------------------------
    | Responses
    |--------------------------------------------------------------------------
    */

    Route::livewire(
        '/forms/{formId}/responses',
        'form-responses'
    )->name('forms.responses');
});

require __DIR__ . '/auth.php';
