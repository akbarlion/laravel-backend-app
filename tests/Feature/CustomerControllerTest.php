<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Task 5 - Test CREATE customer method
|--------------------------------------------------------------------------
*/

test('can create a new customer', function () {
    // Mock the phone validation API response
    $this->mock('curl_exec', function () {
        return json_encode(['valid' => true]);
    });

    $customerData = [
        'name' => 'John Doe',
        'address' => '123 Main Street',
        'phone' => '1234567890'
    ];
    
    $response = $this->postJson('/api/customers', $customerData);
    
    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Customer Created',
            'data' => [
                'name' => 'John Doe',
                'address' => '123 Main Street',
                'phone' => '1234567890'
            ]
        ]);
        
    $this->assertDatabaseHas('customers', $customerData);
});

test('validates required fields when creating customer', function () {
    $response = $this->postJson('/api/customers', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'address', 'phone']);
});

test('rejects invalid phone number when creating customer', function () {
    // Mock the phone validation API response for invalid phone
    $this->mock('curl_exec', function () {
        return json_encode(['valid' => false]);
    });

    $customerData = [
        'name' => 'John Doe',
        'address' => '123 Main Street',
        'phone' => 'invalid-phone'
    ];
    
    $response = $this->postJson('/api/customers', $customerData);
    
    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid phone number'
        ]);
        
    $this->assertDatabaseMissing('customers', $customerData);
});

/*
|--------------------------------------------------------------------------
| Task 5 - Test UPDATE customer method
|--------------------------------------------------------------------------
*/

test('can update an existing customer', function () {
    // Create a customer first
    $customer = Customer::factory()->create();
    
    // Mock the phone validation API response
    $this->mock('curl_exec', function () {
        return json_encode(['valid' => true]);
    });

    $updatedData = [
        'name' => 'Jane Doe',
        'address' => '456 New Street',
        'phone' => '9876543210'
    ];
    
    $response = $this->putJson("/api/customers/{$customer->id}", $updatedData);
    
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $updatedData
        ]);
        
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Jane Doe',
        'address' => '456 New Street',
        'phone' => '9876543210'
    ]);
});

test('returns 404 when updating non-existent customer', function () {
    $response = $this->putJson('/api/customers/999', [
        'name' => 'Jane Doe'
    ]);
    
    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Customer Not Found'
        ]);
});

test('validates fields when updating customer', function () {
    $customer = Customer::factory()->create();
    
    $response = $this->putJson("/api/customers/{$customer->id}", [
        'name' => str_repeat('a', 300) // Too long name
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('rejects invalid phone number when updating customer', function () {
    $customer = Customer::factory()->create();
    
    // Mock the phone validation API response for invalid phone
    $this->mock('curl_exec', function () {
        return json_encode(['valid' => false]);
    });

    $response = $this->putJson("/api/customers/{$customer->id}", [
        'phone' => 'invalid-phone'
    ]);
    
    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid phone number'
        ]);
});