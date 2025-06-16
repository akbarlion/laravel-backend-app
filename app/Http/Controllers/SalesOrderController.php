<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use DB;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            // Gunakan paginate untuk membatasi jumlah data
            $sales_orders = SalesOrder::with([
                'items' => function ($query) {
                    $query->select('id', 'order_id', 'product_id', 'quantity', 'selling_price', 'production_price');
                }
            ])->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Success Showing All Data',
                'data' => $sales_orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'reference_no' => 'required|string|max:20|unique:sales_orders',
                'sales_id' => 'required|exists:sales,id',
                'customer_id' => 'required|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.production_price' => 'required|numeric|min:0',
                'items.*.selling_price' => 'required|numeric|min:0'
            ]);

            // Mulai transaksi database
            DB::beginTransaction();

            // Buat sales order
            $salesOrder = SalesOrder::create([
                'reference_no' => $validated['reference_no'],
                'sales_id' => $validated['sales_id'],
                'customer_id' => $validated['customer_id']
            ]);

            // Buat sales order items
            $items = [];
            foreach ($validated['items'] as $item) {
                $orderItem = SalesOrderItem::create([
                    'order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'production_price' => $item['production_price'],
                    'selling_price' => $item['selling_price']
                ]);
                $items[] = $orderItem;
            }

            // Commit transaksi
            DB::commit();

            // Load relasi untuk response
            $salesOrder->load('items');

            return response()->json([
                'status' => true,
                'message' => 'Sales order created successfully',
                'data' => $salesOrder
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error creating sales order: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
