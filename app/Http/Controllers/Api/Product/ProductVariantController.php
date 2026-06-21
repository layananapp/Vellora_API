<?php

namespace App\Http\Controllers\Api\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;

class ProductVariantController extends Controller
{
    public function createVariant(Request $request, $productId)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)
            ->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $product = Product::where('store_id', $store->id)
            ->find($productId);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'variant_name' => ['required'],
            'price' => ['required', 'numeric'],
            'stock' => ['required', 'integer']
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'variant_name' => $validated['variant_name'],
            'price' => $validated['price'],
            'stock' => $validated['stock']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Variant berhasil dibuat',
            'data' => $variant
        ]);
    }

    public function getVariants($productId)
    {
        $variants = ProductVariant::where('product_id', $productId)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $variants
        ]);
    }

    public function updateVariant(Request $request, $id)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)
            ->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $variant = ProductVariant::whereHas('product', function ($query) use ($store) {

            $query->where('store_id', $store->id);

        })->find($id);

        if (!$variant) {

            return response()->json([
                'status' => false,
                'message' => 'Variant tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'variant_name' => ['required'],
            'price' => ['required', 'numeric'],
            'stock' => ['required', 'integer']
        ]);

        $variant->update([
            'variant_name' => $validated['variant_name'],
            'price' => $validated['price'],
            'stock' => $validated['stock']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Variant berhasil diupdate',
            'data' => $variant
        ]);
    }

    public function deleteVariant(Request $request, $id)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)
            ->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $variant = ProductVariant::whereHas('product', function ($query) use ($store) {

            $query->where('store_id', $store->id);

        })->find($id);

        if (!$variant) {

            return response()->json([
                'status' => false,
                'message' => 'Variant tidak ditemukan'
            ], 404);
        }

        $variant->delete();

        return response()->json([
            'status' => true,
            'message' => 'Variant berhasil dihapus'
        ]);
    }
}