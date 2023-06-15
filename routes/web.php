<?php

use App\Http\Controllers\RpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/rpa',[RpaController::class,'index']);
Route::get('/openGoogleSite',[RpaController::class,'openGoogleSite']);

Route::get('/capturarDados',[RpaController::class,'capturarDados']);
Route::get('/preencherFormulario',[RpaController::class,'preencherFormulario']);
Route::get('/baixarArquivo',[RpaController::class,'baixarArquivo']);
Route::get('/realizarUpload',[RpaController::class,'realizarUpload']);
Route::get('/lerPDF',[RpaController::class,'lerPDF']);
