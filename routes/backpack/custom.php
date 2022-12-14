<?php
// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('accountstatus', 'AccountstatusCrudController');
    Route::crud('rcdpwlist', 'RcDpwListCrudController');
    Route::crud('churchentitytype', 'ChurchEntityTypeCrudController');
    Route::crud('servicetype', 'ServiceTypeCrudController');
    Route::crud('titlelist', 'TitleListCrudController');
    Route::crud('ministryrole', 'MinistryRoleCrudController');
    Route::crud('specialrole', 'SpecialRoleCrudController');
    Route::crud('licensetype', 'LicenseTypeCrudController');
    Route::crud('legaldocument', 'LegalDocumentCrudController');
    Route::crud('countrylist', 'CountryListCrudController');
    Route::crud('personel', 'PersonelCrudController');
    Route::crud('appointment_history', 'Appointment_historyCrudController');
    Route::crud('relatedentity', 'RelatedentityCrudController');
    Route::crud('educationbackground', 'EducationBackgroundCrudController');
    Route::crud('statushistory', 'StatusHistoryCrudController');
    Route::crud('specialrolepersonel', 'SpecialRolePersonelCrudController');

    Route::crud('church', 'ChurchCrudController');
    Route::crud('legaldocumentchurch', 'LegalDocumentChurchCrudController');
    Route::crud('servicetimechurch', 'ServiceTimeChurchCrudController');
    Route::crud('statushistorychurch', 'StatusHistoryChurchCrudController');
    Route::crud('relatedentitychurch', 'RelatedEntityChurchCrudController');
    Route::crud('structurechurch', 'StructureChurchCrudController');
    Route::crud('coordinatorchurch', 'CoordinatorChurchCrudController');
    Route::crud('dashboard', 'DashboardCrudController');
    Route::post('dashboard-upload', 'DashboardCrudController@uploadtest');
    Route::crud('quick-report', 'QuickReportCrudController');
    Route::post('quick-report/export-report', 'QuickReportCrudController@exportReport');
    Route::get('churchreport', 'ChurchAnnualReportController@index');
    Route::get('churchannualreportdetail/{year}', 'ChurchAnnualReportController@detail');
    Route::get('churchreportdesigner', 'ChurchAnnualReportController@reportdesigner');
    Route::get('pastorreport', 'PastorAnnualReportController@index');
    Route::get('pastorannualreportdetail/{year}', 'PastorAnnualReportController@detail');
    Route::get('pastorreportdesigner', 'PastorAnnualReportController@reportdesigner');
    Route::get('newchurchreport', 'QuickReportController@newchurch');
    Route::get('newpastorreport', 'QuickReportController@newpastor');
    Route::get('inactivechurch', 'QuickReportController@inactivechurch');
    Route::get('inactivepastor', 'QuickReportController@inactivepastor');
    Route::get('allchurchreport', 'QuickReportController@allchurch');
    Route::get('allpastorreport', 'QuickReportController@allpastor');

    Route::get('toolsupload', 'ToolsUploadController@index');
    Route::get('import-church', 'ToolsUploadController@importchurch');
    Route::post('church-upload', 'ToolsUploadController@uploadchurch');
    Route::get('import-personel', 'ToolsUploadController@importpersonel');
    Route::post('personel-upload', 'ToolsUploadController@uploadpersonel');
    Route::get('maintenance-mode', 'ToolsUploadController@maintenanceMode');
    Route::post('maintenance-mode-update', 'ToolsUploadController@maintenanceModeUpdate');

    Route::get('import-country', 'ToolsUploadController@importcountry');
    Route::post('country-upload', 'CountryListCrudController@uploadcountry');

    Route::get('import-rcdpw', 'ToolsUploadController@importrcdpw');
    Route::post('rcdpw-upload', 'RcDpwListCrudController@uploadrcdpw');
    
    Route::crud('childnamepastors', 'ChildNamePastorsCrudController');
    Route::crud('ministrybackgroundpastor', 'MinistryBackgroundPastorCrudController');
    Route::crud('careerbackgroundpastors', 'CareerBackgroundPastorsCrudController');
    Route::crud('church-annual-report', 'ChurchAnnualReportCrudController');
    Route::post('church-annual-report/export-report', 'ChurchAnnualReportCrudController@exportReport');
    Route::prefix('church-annual-report/{year}')->group(function(){
        Route::crud('detail', 'ChurchAnnualReportCrudController');
        Route::post('detail/export-report', 'ChurchAnnualReportCrudController@exportReport');
    });
    Route::crud('church-report-designer', 'ChurchAnnualReportCrudController');
    Route::post('church-report-designer/export-report','ChurchAnnualReportCrudController@exportReport');
    Route::crud('pastor-annual-report', 'PastorReportAnnualCrudController');
    Route::post('pastor-annual-report/export-report', 'PastorReportAnnualCrudController@exportReport');
    Route::prefix('pastor-annual-report/{year}')->group(function(){
        Route::crud('detail', 'PastorReportAnnualCrudController');
        Route::post('detail/export-report', 'PastorReportAnnualCrudController@exportReport');
    });
    Route::crud('pastor-report-designer', 'PastorReportAnnualCrudController');
    Route::post('pastor-report-designer/export-report', 'PastorReportAnnualCrudController@exportReport');
    Route::get('download-sql','BackupController@downloadDb');
    
    Route::get('ajax-rcdpw','RcDpwListCrudController@ajaxRcdpw');
    
}); // this should be the absolute last line of this file