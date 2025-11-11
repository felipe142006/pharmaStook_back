<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
    public function listUser()
    {
        // $data = $this->usuarioService->listUser();
        // return response()->json($data);
        $users = DB::table("users")
            ->select('id', 'name', 'email', 'created_at', 'updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    /** 
     * Funcion para crear los usuarios 
     */
    public function createUser(Request $request)
    {
        // $data = $this->usuarioService->createUser($request);
        // return response()->json($data);
        $validator = $this->validatorUser($request);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')) //guarda la contraseña encriptada
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado con éxito',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /** 
     * Funcion para editar los usuarios 
     */
    public function editUser(Request $request, $id)
    {
        // $data = $this->usuarioService->editUser($request, $id);
        // return response()->json($data);
        $validator = $this->validatorUser($request, $id);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        try {
            DB::beginTransaction();
            $user = User::find($id);
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }

            $user->update([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),  // encripta la nueva contraseña
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado con éxito',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /** 
     * Funcion para eliminar los usuarios 
     */
    public function deleteUser($id)
    {
        // $data = $this->usuarioService->deleteUser($id);
        // return response()->json($data);
        $user = User::find($id);

        if ($user) {
            $user->delete();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado con éxito.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }
    }

    public function validatorUser($data, $userId = null)
    {
        $rules = [
            'name' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => $userId ? 'nullable|min:7|max:20' : 'required|min:7|max:20'
        ];

        $validator = Validator::make($data->all(), $rules);

        return $validator;
    }
}
