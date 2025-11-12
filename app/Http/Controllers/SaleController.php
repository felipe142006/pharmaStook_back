<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;


class SaleController extends Controller
{
    // lista todas las ventas
    public function listSale()
    {
        $sales = Sale::with(['customer'])
            ->withCount('items')
            ->orderBy('issued_at', 'desc')
            ->get();

        return response()->apiOk($sales, 200);
    }


    // muestra la info de una venta en especifico
    public function showSale($id)
    {
        $sale = Sale::with(['items.product', 'customer'])->find($id);
        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }
        return response()->apiOk($sale, 200);
    }

    // descarga
    public function print($id)
    {
        $sale = Sale::with(['items.product', 'customer'])->find($id);

        if (!$sale) {
            return response()->apiError(['message' => 'Factura no encontrada'], 404);
        }

        if (is_null($sale->printed_at)) {
            $sale->update(['printed_at' => now()]);
        }

        $payload = [
            'header' => [
                'invoice_number' => $sale->invoice_number,
                'issued_at'      => $sale->issued_at->format('Y-m-d H:i'),
                'customer'       => $sale->customer?->only(['name', 'document', 'email', 'phone', 'address']),
                'totals'         => [
                    'subtotal' => $sale->subtotal,
                    'discount' => $sale->discount,
                    'tax'      => $sale->tax,
                    'total'    => $sale->total,
                ],
            ],
            'items' => $sale->items->map(fn($i) => [
                'sku'        => $i->product->sku,
                'name'       => $i->product->name,
                'quantity'   => $i->quantity,
                'unit_price' => $i->unit_price,
                'discount'   => $i->discount,
                'line_total' => $i->line_total,
            ]),
        ];

        // genera el PDF
        $pdf = Pdf::loadView('pdf.sale', ['sale' => $payload])
            ->setPaper('a4', 'portrait');

        $fileName = $sale->invoice_number . '.pdf';

        // 🔹 devuelve descarga
        return $pdf->download($fileName);
    }

    // crea/genera una venta
    public function createSale(Request $request)
    {
        $validator = $this->validatorSale($request);
        if ($validator->fails()) {
            return response()->apiError(['errors' => $validator->errors()], 400);
        }
        $data = $validator->validated();
        $user = $request->user();

        try {
            return DB::transaction(function () use ($data, $user) {
                $itemsData = [];
                $subtotal  = 0;

                foreach ($data['items'] as $row) {
                    /** @var Product $p */
                    $p = Product::lockForUpdate()->find($row['product_id']);

                    if (!$p) {
                        abort(422, "Producto {$row['product_id']} no existe.");
                    }
                    if ($p->expires_at && $p->expires_at->isPast()) {
                        abort(422, "El producto {$p->name} está vencido y no se puede vender.");
                    }
                    if ($p->stock < $row['quantity']) {
                        abort(422, "Stock insuficiente para {$p->name}. Disponible: {$p->stock}");
                    }

                    $unitPrice = $p->price;
                    $lineDisc  = (float)($row['discount'] ?? 0);
                    $lineTotal = max(0, ($unitPrice * $row['quantity']) - $lineDisc);
                    $subtotal += $lineTotal;

                    $itemsData[] = [
                        'product'    => $p,
                        'quantity'   => (int)$row['quantity'],
                        'unit_price' => $unitPrice,
                        'discount'   => $lineDisc,
                        'line_total' => $lineTotal,
                    ];
                }

                $headerDiscount = (float)($data['discount'] ?? 0);
                $taxPercent     = (float)($data['tax_percent'] ?? 0);
                $taxBase        = max(0, $subtotal - $headerDiscount);
                $tax            = round($taxBase * ($taxPercent / 100), 2);
                $total          = $taxBase + $tax;

                $sale = Sale::create([
                    'invoice_number' => 'FV-' . str_pad((string)(Sale::max('id') + 1), 8, '0', STR_PAD_LEFT),
                    'customer_id'    => $data['customer_id'] ?? null,
                    'user_id'        => $user->id,
                    'status'         => 'paid',
                    'subtotal'       => $subtotal,
                    'discount'       => $headerDiscount,
                    'tax'            => $tax,
                    'total'          => $total,
                    'issued_at'      => $data['issued_at'] ?? now(),
                ]);

                foreach ($itemsData as $line) {
                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $line['product']->id,
                        'quantity'   => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'discount'   => $line['discount'],
                        'line_total' => $line['line_total'],
                    ]);

                    $line['product']->decrement('stock', $line['quantity']);

                    $newBalance = $line['product']->refresh()->stock;
                    InventoryMovement::create([
                        'product_id'     => $line['product']->id,
                        'type'           => 'out',
                        'quantity'       => -1 * $line['quantity'],
                        'reason'         => 'sale',
                        'reference_type' => Sale::class,
                        'reference_id'   => $sale->id,
                        'user_id'        => $user->id,
                        'balance_after'  => $newBalance,
                    ]);
                }

                return response()->apiOk($sale->load('items.product'), 201);
            });
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->apiError(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // valida los campos
    private function validatorSale(Request $request)
    {
        $rules = [
            'customer_id'             => ['nullable', 'exists:customers,id'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'exists:products,id'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.discount'        => ['nullable', 'numeric', 'min:0'],
            'tax_percent'             => ['nullable', 'numeric', 'min:0'],
            'discount'                => ['nullable', 'numeric', 'min:0'],
            'issued_at'               => ['nullable', 'date'],
        ];

        return Validator::make($request->all(), $rules);
    }
}
