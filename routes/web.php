<?php

//use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RpaController;
use App\Http\Controllers\LeituraController;
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

Route::get('/capturarDados',[RpaController::class,'capturarDados']);
Route::get('/preencherFormulario',[RpaController::class,'preencherFormulario']);
Route::get('/baixarArquivo',[RpaController::class,'baixarArquivo']);
Route::get('/realizarUpload',[RpaController::class,'realizarUpload']);

Route::get('/lerPDF',[LeituraController::class,'lerPDF']);
Route::get('/gerarExcel',[LeituraController::class,'gerarExcel']);

//Route::get('/pdf',[PDFController::class,'index']);
