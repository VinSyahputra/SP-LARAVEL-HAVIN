<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);

        $orders = Order::when($search, function ($query) use ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        })
            ->paginate($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'customer_id'   => ['required', 'exists:App\Models\Customer,id'],
            'total_prices'  => ['required', 'integer'],
            'order_date'    => ['required', 'date',],
            'status'        => ['required', 'in:pending,completed,canceled'],
        ]);
        try {
            $discountData = Http::post('http://localhost:3000/calculate-discount', [
                'customer_id' => $validated->validated()['customer_id'],
                'total_prices' => (int) $validated->validated()['total_prices'], // Fixed key here
            ])->throw()->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to calculate discount'], Response::HTTP_BAD_REQUEST);
        }

        if ($validated->fails()) {
            return ApiResponseHelper::responseError($validated->errors(), 'Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order = Order::create([
            'customer_id'  => $validated->validated()['customer_id'],
            'total_prices' => $discountData['final_price'],
            'order_date'   => $validated->validated()['order_date'],
            'status'       => $validated->validated()['status'],
        ]);
        try {
            Http::post('http://localhost:3000/notify-order', [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'total_price' => (int) $order->total_prices,
                'discount'    => $discountData['discount'],
                'final_price' => $discountData['final_price'],
            ])->throw();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order saved, but notification failed'], Response::HTTP_OK);
        }

        return ApiResponseHelper::responseSuccess($order, 'Data saved successfully', Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $orderId)
    {
        try {
            $order = Order::where('id', $orderId)->first();
            if (!$order)
                return ApiResponseHelper::responseError(null, 'Not found', Response::HTTP_NOT_FOUND);

            return ApiResponseHelper::responseSuccess(new OrderResource($order), 'Get detail');
        } catch (\Exception $e) {
            return ApiResponseHelper::responseError(null, 'Not found', Response::HTTP_NOT_FOUND);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'customer_id'   => ['sometimes', 'exists:App\Models\Customer,id'],
            'total_prices'  => ['sometimes', 'integer'],
            'order_date'    => ['sometimes', 'date'],
            'status'        => ['sometimes', 'in:pending,completed,canceled'],
        ]);

        if ($validated->fails()) {
            return ApiResponseHelper::responseError($validated->errors(), 'Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $discountData = Http::post('http://localhost:3000/calculate-discount', [
                'customer_id'  => $validated->validated()['customer_id'],
                'total_prices' => (int)$validated->validated()['total_prices'],
            ])->throw()->json();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to calculate discount'], Response::HTTP_BAD_REQUEST);
        }

        $order = Order::findOrFail($id);
        $order->update([
            'customer_id'  => $validated->validated()['customer_id'],
            'total_prices' => $discountData['final_price'],
            'order_date'   => $validated->validated()['order_date'],
            'status'       => $validated->validated()['status'],
        ]);

        try {
            Http::post('http://localhost:3000/notify-order', [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'total_price' => (int)$order->total_prices,
                'discount'    => $discountData['discount'],
                'final_price' => $discountData['final_price'],
            ])->throw();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order updated, but notification failed'], Response::HTTP_OK);
        }

        return ApiResponseHelper::responseSuccess($order, 'Order updated successfully', Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ApiResponseHelper::responseError(null, 'Order not found', Response::HTTP_NOT_FOUND);
        }

        $order->delete();

        return ApiResponseHelper::responseSuccess(null, 'Order deleted successfully', Response::HTTP_OK);
    }
}
