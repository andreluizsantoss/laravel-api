<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function login(Request $request) {

        //* Pegando os dados
        $credenciais = $request->all('email', 'password');

        //* AUTENTICAÇÃO -> através de email e senha
        $token = auth('api')->attempt($credenciais);

        if($token) {
            //* Autenticado com sucesso
            return response()->json(['token' => $token]);
        } else {
            //* Erro de autenticação
            //* Retornar 403 => Forbiden -> Proibido (login inválido)
            return response()->json(['erro' => 'Usuário ou senha inválido!'], 403);
        }

    } 

    public function logout() {
        auth('api')->logout();
        return response()->json(['msg' => 'Logout foi realizado com suesso!']);
    } 

    public function refresh() {
        //* IMPORTANTE - o cliente deve encaminhar um JWT válido
        $token = auth('api')->refresh();
        return response()->json(['token' => $token]);
    } 

    public function me(Request $request) {
        return response()->json(auth()->user());
    } 
}
