<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    // lista todos los clientes registrados
    public function listCustomer()
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        return response()->apiOk($customers, 200);
    }

    public function showCustomer($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }
        return response()->apiOk($customer, 200);
    }

    // crea un cliente
    public function createCustomer(Request $request)
    {
        $validator = $this->validatorCustomer($request, false, null);
        if ($validator->fails()) {
            return response()->apiError(['errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $c = Customer::create($validator->validated());
            DB::commit();

            return response()->apiOk($c, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->apiError(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // actualiza datos de un cliente
    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->apiError(['message' => 'Cliente no encontrado'], 404);
        }

        $validator = $this->validatorCustomer($request, true, (int)$customer->id);
        if ($validator->fails()) {
            return response()->apiError(['errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $customer->update($validator->validated());
            DB::commit();

            return response()->apiOk($customer, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->apiError(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // elimina un cliente
    public function deleteCustomer($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->apiError(['message' => 'Cliente no encontrado'], 404);
        }

        $customer->delete();
        return response()->apiOk(['message' => 'Cliente eliminado con éxito'], 200);
    }

    // valida los campos
    private function validatorCustomer(Request $request, bool $isUpdate = false, ?int $id = null)
    {
        $rules = [
            'document' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('customers', 'document')->ignore($id),
            ],
            'name'     => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'email'    => ['nullable', 'email'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'address'  => ['nullable', 'string', 'max:255'],
        ];

        $messages = [
            'document.required' => 'El documento es obligatorio.',
            'document.unique'   => 'Este documento ya está registrado.',
        ];

        return Validator::make($request->all(), $rules, $messages);
    }
}
