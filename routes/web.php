<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisReportV1Controller;
use App\Http\Controllers\AnalysisReportV2Controller;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/analysis/v1/pdf', [AnalysisReportV1Controller::class, 'downloadPdfReportV1']);
Route::get('/analysis/v2/pdf', [AnalysisReportV2Controller::class, 'downloadPdfReportV2']);


Route::get('/dev', function() {
    dd(
        getCountryCodeByPhone('01812961835'),
        getCountryCodeByPhone('+8801313046672'),
        getCountryCodeByPhone('8801313046672'),
        getCountryCodeByPhone('+1-202-555-0185'),
        getCountryCodeByPhone('1-202-555-0185'),
        getCountryCodeByPhone('+1907-555-0699'),
        getCountryCodeByPhone('1907-555-0699'),
    );
});