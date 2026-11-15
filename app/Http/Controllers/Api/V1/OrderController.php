<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
public function store(StoreOrderRequest $request): JsonResponse    {
        $data = $request->validated();

        $order = DB::transaction(function () use ($data) {

            if (!empty($data['quote_request_id'])) {
                $quoteRequest = QuoteRequest::query()
                    ->lockForUpdate()
                    ->findOrFail($data['quote_request_id']);

                if (
                    (int) $quoteRequest->customer_id !==
                    (int) $data['customer_id']
                ) {
                    abort(
                        422,
                        'طلب التسعير لا ينتمي إلى هذا العميل.'
                    );
                }

                if ($quoteRequest->status !== 'approved') {
                    abort(
                        422,
                        'لا يمكن إنشاء طلب من طلب تسعير غير معتمد.'
                    );
                }
            }

            return Order::create([
                'reference' => $this->generateReference(),
                'customer_id' => $data['customer_id'],
                'quote_request_id' =>
                    $data['quote_request_id'] ?? null,
                'status' => 'pending',
                'total_amount' => $data['total_amount'],
                'currency' => $data['currency'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $order->load([
            'customer',
            'quoteRequest',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح.',
            'data' => $order,
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'customer',
            'quoteRequest',
            'payments',
        ]);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    private function generateReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(
                Str::random(8)
            );
        } while (
            Order::query()
                ->where('reference', $reference)
                ->exists()
        );

        return $reference;
    }
}