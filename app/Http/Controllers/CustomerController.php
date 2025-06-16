<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $customers = Customer::all();
        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20'
        ]);

        $phone = $validated['phone'];
        $apiKey = 'd1c88843caea406caefaed39a15d11df';

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://phonevalidation.abstractapi.com/v1/?api_key={$apiKey}&phone={$phone}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $phoneData = json_decode($response, true);

            if (isset($phoneData['valid']) && $phoneData['valid'] === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number',
                    'details' => $phoneData
                ], 422);
            }
        } catch (\Exception $e) {
        }

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer Created',
            'data' => $customer
        ], 201);
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
        $customer = Customer::find($id);
        if (!$customer) {
            return response([
                'success' => false,
                'message' => 'Customer Not Found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'address' => 'string',
            'phone' => 'string|max:20'
        ]);

        // Validasi nomor telepon jika ada dalam request
        if (isset($validated['phone'])) {
            $phone = $validated['phone'];
            $apiKey = 'd1c88843caea406caefaed39a15d11df';

            try {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://phonevalidation.abstractapi.com/v1/?api_key={$apiKey}&phone={$phone}");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                $response = curl_exec($ch);
                curl_close($ch);

                $phoneData = json_decode($response, true);

                if (isset($phoneData['valid']) && $phoneData['valid'] === false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid phone number',
                        'details' => $phoneData
                    ], 422);
                }
            } catch (\Exception $e) {
                // Jika API validasi gagal, lanjutkan proses
            }
        }

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
