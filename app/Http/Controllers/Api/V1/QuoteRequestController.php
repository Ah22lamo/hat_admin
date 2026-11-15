<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreQuoteRequestRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteRequestController extends Controller
{
    public function store(
        StoreQuoteRequestRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $quoteRequest = DB::transaction(function () use ($data) {
            $customerData = $data['customer'];

            $customer = Customer::query()
                ->where('phone', $customerData['phone'])
                ->first();

            if ($customer) {
                $customer->update([
                    'name' => $customerData['name'],
                    'email' => $customerData['email'] ?? $customer->email,
                    'customer_type' =>
                        $customerData['customer_type']
                        ?? $customer->customer_type,
                    'address' =>
                        $customerData['address']
                        ?? $customer->address,
                    'notes' =>
                        $customerData['notes']
                        ?? $customer->notes,
                ]);
            } else {
                $customer = Customer::create([
                    'name' => $customerData['name'],
                    'phone' => $customerData['phone'],
                    'email' => $customerData['email'] ?? null,
                    'customer_type' =>
                        $customerData['customer_type'] ?? 'customer',
                    'address' => $customerData['address'] ?? null,
                    'notes' => $customerData['notes'] ?? null,
                    'is_active' => true,
                ]);
            }

            $items = collect($data['requested_items'])
                ->map(function (array $item): array {
                    return [
                        'product_id' => (int) $item['product_id'],
                        'quantity' => (int) $item['quantity'],
                    ];
                })
                ->values()
                ->all();

            $products = Product::query()
                ->whereIn(
                    'id',
                    collect($items)->pluck('product_id')
                )
                ->where('is_active', true)
                ->where('availability', 'available')
                ->pluck('id');

            if ($products->count() !== collect($items)
                ->pluck('product_id')
                ->unique()
                ->count()) {
                abort(
                    422,
                    'يوجد منتج غير متوفر أو غير نشط ضمن الطلب.'
                );
            }

            return QuoteRequest::create([
                'reference' => $this->generateReference(),
                'customer_id' => $customer->id,
                'status' => 'new',
                'requested_items' => $items,
                'approved_amount' => null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $quoteRequest->load('customer');

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب التسعير بنجاح.',
            'data' => $quoteRequest,
        ], 201);
    }

    public function show(QuoteRequest $quoteRequest): JsonResponse
    {
        $quoteRequest->load([
            'customer',
            'assignedTo',
            'order',
        ]);

        return response()->json([
            'success' => true,
            'data' => $quoteRequest,
        ]);
    }

    private function generateReference(): string
    {
        do {
            $reference = 'QTE-' . strtoupper(
                Str::random(8)
            );
        } while (
            QuoteRequest::query()
                ->where('reference', $reference)
                ->exists()
        );

        return $reference;
    }
}