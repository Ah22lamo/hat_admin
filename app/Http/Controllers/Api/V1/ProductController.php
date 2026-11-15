<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('availability', 'available')
            ->with([
                'category:id,name,slug',
                'images',
            ])
            ->when(
                $request->filled('category_id'),
                fn ($query) =>
                    $query->where('category_id', $request->integer('category_id'))
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = trim($request->string('search')->toString());

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('item_no', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                }
            )
            ->latest('id')
            ->paginate(
                min($request->integer('per_page', 20), 100)
            );

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless(
            $product->is_active &&
            $product->availability === 'available' &&
            is_null($product->deleted_at),
            404
        );

        $product->load([
            'category:id,name,slug',
            'images',
            'attributeValues.attribute',
        ]);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }
}