<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    // lista todos los clientes registrados
    public function listCustomer()
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data'    => $customers,
        ]);
    }

    // crea un cliente
    public function createCustomer(Request $request)
    {
        $validator = $this->validatorCustomer($request, false);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $c = Customer::create($validator->validated());
            DB::commit();

            return response()->json(['success' => true, 'data' => $c], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // actualiza datos de un cliente
    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $validator = $this->validatorCustomer($request, true);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $customer->update($validator->validated());
            DB::commit();

            return response()->json(['success' => true, 'data' => $customer]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // elimina un cliente
    public function deleteCustomer($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $customer->delete();
        return response()->json(['success' => true, 'message' => 'Cliente eliminado con éxito']);
    }

    // valida los campos
    private function validatorCustomer(Request $request, bool $isUpdate = false)
    {
        $rules = [
            'document' => ['nullable', 'string', 'max:50'],
            'name'     => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'email'    => ['nullable', 'email'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'address'  => ['nullable', 'string', 'max:255'],
        ];

        return Validator::make($request->all(), $rules);
    }
}
