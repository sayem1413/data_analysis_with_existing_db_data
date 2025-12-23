<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisReportV1Controller;
use App\Http\Controllers\AnalysisReportV2Controller;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/analysis/v1/pdf', [AnalysisReportV1Controller::class, 'downloadPdfReportV1']);
Route::get('/analysis/v2/pdf', [AnalysisReportV2Controller::class, 'downloadPdfReportV2']);