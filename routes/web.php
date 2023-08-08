<?php
$admin_prefix = config('app.backendRoute') ?? 'admincp';
Auth::routes();

///Module System Core
Route::group(['module'=>'System', 'namespace' => '\App\Modules\System\Controllers'], function () use ($admin_prefix) {
    //Backend
    Route::group(['prefix'=>$admin_prefix, 'middleware' =>['auth','role:BACKEND']], function () {

    });

    //Frontend
    Route::group(['middleware' =>['web']], function () {
        Route::get('/', 'FrontendController@index');
    });

});

///Module User
Route::group(['module'=>'User', 'namespace' => '\App\Modules\User\Controllers'], function () use ($admin_prefix) {
    //Backend
    Route::group(['prefix'=>$admin_prefix, 'middleware' =>['auth','role:BACKEND']], function () {
        Route::resource('users','UserController');
        Route::resource('groups','GroupControler');
        Route::post('users/action','UserController@actions')->name('users.action.post');
    });

    //Frontend
    Route::group(['middleware' =>['web']], function () {

    });
});

///Module Ztest
Route::group(['module'=>'Dns', 'namespace' => '\App\Modules\Dns\Controllers'], function () use ($admin_prefix) {
    //Frontend
    Route::group(['middleware' =>['web']], function () {
        Route::get('dns/record/{domain}', 'DnsApiController@records');
        Route::get('dns/zones', 'DnsApiController@zones');
        Route::get('dns/zone/{domain}', 'DnsApiController@get_zone');
        Route::post('dns/zone/create', 'DnsApiController@zone_create');
        Route::get('dns/zone/delete/{domain}', 'DnsApiController@del_zone');
        Route::post('dns/record/create', 'DnsApiController@create_record');
        Route::post('dns/record/update', 'DnsApiController@update_record');
        Route::post('dns/record/delete', 'DnsApiController@del_record');
    });
});
