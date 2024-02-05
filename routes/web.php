<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelController;

use App\Http\Controllers\ExcelCrudController\DynamicCrudController;


Route::prefix('dynamic-crud/{tableName}')->group(function () {
    Route::get('/', [DynamicCrudController::class, 'index'])->name('tables.index');
    Route::get('/create', [DynamicCrudController::class, 'create'])->name('tables.create');
    Route::post('/', [DynamicCrudController::class, 'store'])->name('tables.store');
    Route::get('/{id}', [DynamicCrudController::class, 'show'])->name('tables.show');
    Route::get('/{id}/edit', [DynamicCrudController::class, 'edit'])->name('tables.edit');
   Route::delete('/{id}', [DynamicCrudController::class, 'destroy'])->name('tables.destroy');
});
Route::post('/dynamic-crud/{tableName}', [DynamicCrudController::class, 'store'])->name('tables.store');
Route::put('/dynamic-crud/{tableName}/{id}', [DynamicCrudController::class, 'update'])->name('tables.update');

Route::get('/import', [ExcelController::class, 'showImportForm'])->name('showImportForm');
Route::post('/import', [ExcelController::class, 'import'])->name('import');
Route::get('/export', [ExcelController::class, 'showExportForm'])->name('showExportForm');
Route::get('/export', [ExcelController::class, 'export'])->name('export');
Route::get('/tables', [ExcelController::class, 'showTables'])->name('tables.index');
Route::get('/tables/{tableName}', [ExcelController::class, 'showTableData'])->name('tables.show');

Route::get('/', function () {
    return view('welcome');
});
