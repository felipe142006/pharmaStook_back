<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // lista todos los productos creados
    public function listProducts()
    {
        $products = Product::orderBy('name', 'asc')->get();

        return response()->apiOk($products, 200);
    }


    // crea un producto
    public function createProducts(Request $request)
    {
        $validator = $this->validatorProductCreate($request);
        if ($validator->fails()) {
            return response()->apiError(['errors' => $validator->errors()], 400);
        }

        $payload = $validator->validated();
        $payload['created_by'] = $request->user()->id;
        $payload['updated_by'] = $request->user()->id;

        try {
            return DB::transaction(function () use ($payload, $request) {
                $product = Product::create($payload);

                if (!empty($payload['stock'])) {
                    InventoryMovement::create([
                        'product_id'    => $product->id,
                        'type'          => 'in',
                        'quantity'      => (int)$payload['stock'],
                        'reason'        => 'initial',
                        'user_id'       => $request->user()->id,
                        'balance_after' => $product->stock,
                    ]);
                }

                return response()->apiOk($product, 201);
            });
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->apiError(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // responde la info de un producto en especifico
    public function showProducts($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->apiError(['message' => 'Producto no encontrado'], 404);
        }
        return response()->apiOk($product, 200);
    }

    // actualiza un producto
    public function updateProducts(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->apiError(['message' => 'Producto no encontrado'], 404);
        }

        $validator = $this->validatorProductUpdate($request, $product->id);
        if ($validator->fails()) {
            return response()->apiError(['errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['updated_by'] = $request->user()->id;

            $product->update($data);
            DB::commit();

            return response()->apiOk($product, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->apiError(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // elimina un producto del inventario
    public function deleteProducts($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->apiError(['message' => 'Producto no encontrado'], 404);
        }
        $product->delete();
        return response()->apiOk(['message' => 'Producto eliminado con éxito'], 200);
    }

    // genera la alerta cuando detecta que el producto llego al minimo establecido de stock
    public function alerts()
    {
        $today = now()->toDateString();

        $lowStock = Product::whereColumn('stock', '<=', 'min_stock')
            ->where('is_active', true)->get();

        $expired = Product::whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $today)->get();

        $nearExpire = Product::whereNotNull('expires_at')
            ->whereBetween('expires_at', [$today, now()->addDays(15)->toDateString()])
            ->get();

        return response()->json([
            'success'     => true,
            'low_stock'   => $lowStock,
            'expired'     => $expired,
            'near_expire' => $nearExpire,
        ]);
    }

    // valida los campos antes de crear un producto
    private function validatorProductCreate(Request $request)
    {
        $rules = [
            'sku'         => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'min_stock'   => ['nullable', 'integer', 'min:0'],
            'cost'        => ['nullable', 'numeric', 'min:0'],
            'price'       => ['required', 'numeric', 'min:0'],
            'expires_at'  => ['nullable', 'date'],
            'is_active'   => ['boolean']
        ];
        return Validator::make($request->all(), $rules);
    }

    // valida los campos antes de actualizar un producto
    private function validatorProductUpdate(Request $request, int $productId)
    {
        $rules = [
            'sku'         => ['sometimes', 'required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_stock'   => ['nullable', 'integer', 'min:0'],
            'cost'        => ['nullable', 'numeric', 'min:0'],
            'price'       => ['nullable', 'numeric', 'min:0'],
            'expires_at'  => ['nullable', 'date'],
            'is_active'   => ['boolean']
        ];
        return Validator::make($request->all(), $rules);
    }
}
