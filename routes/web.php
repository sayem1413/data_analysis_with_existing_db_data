<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\DesiredJobTableController;
use App\Http\Controllers\AnalysisReportV1Controller;
use App\Http\Controllers\AnalysisReportV2Controller;
use App\Http\Controllers\ManipulateDbTableController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/analysis/v1/pdf', [AnalysisReportV1Controller::class, 'downloadPdfReportV1']);
Route::get('/analysis/v2/pdf', [AnalysisReportV2Controller::class, 'downloadPdfReportV2']);

Route::get('/update-db', [ManipulateDbTableController::class, 'updateDesiredJobsTable']);

// Route::get('/desired-job-merge', [DesiredJobTableController::class, 'index']);
// Route::get('/desired-job-merge-old', [DesiredJobTableOldController::class, 'index']);
// Route::get('/desired-job-merge1-old', [DesiredJobTableOldController::class, 'index1']);
// Route::get('/desired-job-merge2-old', [DesiredJobTableOldController::class, 'index2']);