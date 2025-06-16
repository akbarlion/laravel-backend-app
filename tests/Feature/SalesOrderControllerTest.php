<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test for task 5 - index method that displays a listing of sales orders
     */
    public function test_index_returns_paginated_sales_orders()
    {
        // Create test data
        $sales = Sale::factory()->create();
        $customer = Customer::factory()->create();
        
        // Create 3 sales orders
        $salesOrders = SalesOrder::factory()->count(3)->create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
        ]);
        
        // Create items for each sales order
        foreach ($salesOrders as $order) {
            $product = Product::factory()->create();
            SalesOrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 2,
                'production_price' => 100,
                'selling_price' => 150,
            ]);
        }

        // Make request to index endpoint
        $response = $this->getJson('/api/sales-orders');
        
        // Assert response structure and status
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'reference_no',
                        'sales_id',
                        'customer_id',
                        'created_at',
                        'updated_at',
                        'items' => [
                            '*' => [
                                'id',
                                'order_id',
                                'product_id',
                                'quantity',
                                'selling_price',
                                'production_price',
                            ]
                        ]
                    ]
                ],
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]
        ]);
        
        // Assert the correct data is returned
        $response->assertJsonCount(3, 'data.data');
        $response->assertJson([
            'status' => true,
            'message' => 'Success Showing All Data',
        ]);
    }

    /**
     * Test for task 7 - store method that creates a new sales order
     */
    public function test_store_creates_new_sales_order()
    {
        // Create test data
        $sales = Sale::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        
        // Prepare request data
        $data = [
            'reference_no' => 'SO-' . rand(1000, 9999),
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'production_price' => 100,
                    'selling_price' => 150
                ]
            ]
        ];
        
        // Make request to store endpoint
        $response = $this->postJson('/api/sales-orders', $data);
        
        // Assert response structure and status
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'reference_no',
                'sales_id',
                'customer_id',
                'created_at',
                'updated_at',
                'items' => [
                    '*' => [
                        'id',
                        'order_id',
                        'product_id',
                        'quantity',
                        'selling_price',
                        'production_price',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);
        
        // Assert the data was stored correctly
        $response->assertJson([
            'status' => true,
            'message' => 'Sales order created successfully',
            'data' => [
                'reference_no' => $data['reference_no'],
                'sales_id' => $data['sales_id'],
                'customer_id' => $data['customer_id'],
            ]
        ]);
        
        // Assert the database has the record
        $this->assertDatabaseHas('sales_orders', [
            'reference_no' => $data['reference_no'],
            'sales_id' => $data['sales_id'],
            'customer_id' => $data['customer_id'],
        ]);
        
        // Get the created sales order
        $salesOrder = SalesOrder::where('reference_no', $data['reference_no'])->first();
        
        // Assert the sales order item was created
        $this->assertDatabaseHas('sales_order_items', [
            'order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'production_price' => 100,
            'selling_price' => 150
        ]);
    }

    /**
     * Test validation errors for store method
     */
    public function test_store_validates_input()
    {
        // Make request with empty data
        $response = $this->postJson('/api/sales-orders', []);
        
        // Assert validation errors
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_no', 'sales_id', 'customer_id', 'items']);
    }

    /**
     * Test store method with invalid reference_no (duplicate)
     */
    public function test_store_validates_unique_reference_no()
    {
        // Create an existing sales order
        $existingOrder = SalesOrder::factory()->create();
        
        // Create test data
        $sales = Sale::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        
        // Prepare request data with duplicate reference_no
        $data = [
            'reference_no' => $existingOrder->reference_no, // Duplicate reference_no
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'production_price' => 100,
                    'selling_price' => 150
                ]
            ]
        ];
        
        // Make request to store endpoint
        $response = $this->postJson('/api/sales-orders', $data);
        
        // Assert validation error for reference_no
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_no']);
    }
}