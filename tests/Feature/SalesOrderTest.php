<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Task 7 - Test CREATE sales order method
|--------------------------------------------------------------------------
*/

test('can create a new sales order with items', function () {
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
    $response->assertStatus(201)
        ->assertJsonStructure([
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
        ])
        ->assertJson([
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
});

test('validates required fields when creating sales order', function () {
    // Make request with empty data
    $response = $this->postJson('/api/sales-orders', []);
    
    // Assert validation errors
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['reference_no', 'sales_id', 'customer_id', 'items']);
});

test('validates unique reference_no when creating sales order', function () {
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
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['reference_no']);
});

test('validates items array when creating sales order', function () {
    // Create test data
    $sales = Sale::factory()->create();
    $customer = Customer::factory()->create();
    
    // Prepare request data with empty items array
    $data = [
        'reference_no' => 'SO-' . rand(1000, 9999),
        'sales_id' => $sales->id,
        'customer_id' => $customer->id,
        'items' => [] // Empty items array
    ];
    
    // Make request to store endpoint
    $response = $this->postJson('/api/sales-orders', $data);
    
    // Assert validation error for items
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('validates item fields when creating sales order', function () {
    // Create test data
    $sales = Sale::factory()->create();
    $customer = Customer::factory()->create();
    
    // Prepare request data with invalid item data
    $data = [
        'reference_no' => 'SO-' . rand(1000, 9999),
        'sales_id' => $sales->id,
        'customer_id' => $customer->id,
        'items' => [
            [
                'product_id' => 999, // Non-existent product
                'quantity' => 0, // Invalid quantity
                'production_price' => -10, // Invalid price
                'selling_price' => -5 // Invalid price
            ]
        ]
    ];
    
    // Make request to store endpoint
    $response = $this->postJson('/api/sales-orders', $data);
    
    // Assert validation errors for item fields
    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'items.0.product_id',
            'items.0.quantity',
            'items.0.production_price',
            'items.0.selling_price'
        ]);
});