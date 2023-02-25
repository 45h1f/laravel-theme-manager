<?php


use Illuminate\Support\Facades\Route;


Route::group(['middleware' => 'web'], function () {
    $namespacePrefix = '\\Ashiful\\Themes\\Http\\Controllers\\';
    Route::get('themes', ['uses' => $namespacePrefix . 'ThemesController@index', 'as' => 'theme.index']);
    Route::get('themes/activate/{theme}', ['uses' => $namespacePrefix . 'ThemesController@activate', 'as' => 'theme.activate']);
    Route::get('themes/options/{theme}', ['uses' => $namespacePrefix . 'ThemesController@options', 'as' => 'theme.options']);
    Route::post('themes/options/{theme}', ['uses' => $namespacePrefix . 'ThemesController@options_save', 'as' => 'theme.options.post']);
    Route::get('themes/options', function () {
        return redirect(route('theme.index'));
    });
    Route::delete('themes/delete', ['uses' => $namespacePrefix . 'ThemesController@delete', 'as' => 'theme.delete']);

});
