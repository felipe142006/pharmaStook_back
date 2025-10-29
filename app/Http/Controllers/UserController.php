<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $usuarioService;

    /**
    * Instancia el servicio UsuarioService
    */
    public function __construct(UserService $usuarioService)
    {
        $this->usuarioService = $usuarioService;
    }

    /** 
     * Funcion para listar los usuarios 
     */
    public function listUser(){
        $data = $this->usuarioService->listUser();
        return response()->json($data);
    }

    /** 
     * Funcion para crear los usuarios 
     */
    public function createUser(Request $request){
        $data = $this->usuarioService->createUser($request);
        return response()->json($data);
    }

    /** 
     * Funcion para editar los usuarios 
     */
    public function editUser(Request $request, $id){
        $data = $this->usuarioService->editUser($request, $id);
        return response()->json($data);
    }

    /** 
     * Funcion para eliminar los usuarios 
     */
    public function deleteUser($id){
        $data = $this->usuarioService->deleteUser($id);
        return response()->json($data);
    }

}
