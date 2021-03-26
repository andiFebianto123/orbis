<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('roles', 'RoleCrudController');
    Route::crud('permissions', 'PermissionCrudController');
    Route::crud('users', 'UserCrudController');
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
}); // this should be the absolute last line of this file