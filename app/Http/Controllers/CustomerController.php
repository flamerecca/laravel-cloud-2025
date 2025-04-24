<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class CustomerController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(): ResourceCollection
    {
        return Customer::all()->toResourceCollection();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);

        $customer = Customer::create($validated);
        return $customer->toResource();
    }

    /**
     * @throws Throwable
     */
    public function show(Customer $customer): JsonResource
    {
        return $customer->toResource();
    }

    /**
     * @throws Throwable
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string',
        ]);

        $customer->update($validated);
        return $customer->toResource();
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

