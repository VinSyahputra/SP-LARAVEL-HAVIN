<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);

        $customers = Customer::when($search, function ($query) use ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        })
            ->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:255'],
        ]);

        if ($validated->fails()) {
            return ApiResponseHelper::responseError($validated->errors(), 'Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $customer = Customer::create($validated->validated());

        return ApiResponseHelper::responseSuccess($customer, 'Data saved successfully', Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show($customerId)
    {

        try {
            $customer = Customer::where('id', $customerId)->first();
            if (!$customer)
                return ApiResponseHelper::responseError(null, 'Not found', Response::HTTP_NOT_FOUND);

            return ApiResponseHelper::responseSuccess(new CustomerResource($customer), 'Get detail');
        } catch (\Exception $e) {
            return ApiResponseHelper::responseError(null, 'Not found', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $customerId)
    {
        $validated = Validator::make($request->all(), [
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', "unique:customers,email,{$customerId}"],
            'phone' => ['sometimes', 'string', 'max:255'],
        ]);

        if ($validated->fails())
            return ApiResponseHelper::responseError($validated->errors(), 'Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY);

        $customer = Customer::find($customerId);
        if (!$customer)
            return ApiResponseHelper::responseError(null, 'Customer not found', Response::HTTP_NOT_FOUND);

        $customer->update($validated->validated());

        return ApiResponseHelper::responseSuccess($customer, 'Data updated successfully', Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer)
            return ApiResponseHelper::responseError(null, 'Customer not found', Response::HTTP_NOT_FOUND);


        $customer->delete();

        return ApiResponseHelper::responseSuccess(null, 'Customer deleted successfully', Response::HTTP_OK);
    }
}
