<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//* Rotas agrupadas
//* Inserido PREFIX => versionamento da api
Route::prefix('v1')->middleware('jwt.auth')->group(function() {
    Route::apiResource('cliente', 'ClienteController');
    Route::apiResource('carro', 'CarroController');
    Route::apiResource('locacao', 'LocacaoController');
    Route::apiResource('marca', 'MarcaController');
    Route::apiResource('modelo', 'ModeloController');

    //*Rotas referente ao usuário
    Route::post('me', 'AuthController@me');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('logout', 'AuthController@logout');
}); 

Route::post('login', 'AuthController@login');

// Route::apiResource('cliente', 'ClienteController')->middleware('jwt.auth');
// Route::apiResource('carro', 'CarroController')->middleware('jwt.auth');
// Route::apiResource('locacao', 'LocacaoController')->middleware('jwt.auth');
// Route::apiResource('marca', 'MarcaController')->middleware('jwt.auth');
// Route::apiResource('modelo', 'ModeloController')->middleware('jwt.auth');