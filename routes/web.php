<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| steward Routes
|--------------------------------------------------------------------------
|
| Here is where you can register routes for your package.
|
*/

Route::get('reservation/{step}', [WizardController::class, 'index'])->name('wizard');