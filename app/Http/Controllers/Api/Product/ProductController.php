<?php

namespace App\Http\Controllers\Api\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\Store;

class ProductController extends Controller
{
    public function createProduct(Request $request)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_name' => ['required'],
            'description' => ['nullable'],
            'price' => ['required', 'numeric'],
            'stock' => ['required', 'integer'],
            'is_active' => ['required', 'boolean']
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $validated['category_id'] ?? null,
            'product_name' => $validated['product_name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $validated['is_active']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product berhasil dibuat',
            'data' => $product
        ]);
    }

    public function getProducts(Request $request)
    {
        $query = Product::with([
            'images',
            'store',
            'category',
            'variants'
        ]);

        if (!$request->has('all')) {
            $query->where('is_active', true);
        }

        if ($request->search) {

            $query->where(
                'product_name',
                'like',
                '%' . $request->search . '%'
            );
        }

        if ($request->category_id) {

            $query->where(
                'category_id',
                $request->category_id
            );
        }

        $products = $query->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    public function getMyProducts(Request $request)
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

        $query = Product::with([
            'images',
            'category',
            'variants'
        ])
        ->where('store_id', $store->id);

        if ($request->status === 'aktif') {

            $query->where('is_active', true);

        }

        if ($request->status === 'nonaktif') {

            $query->where('is_active', false);

        }

        if ($request->status === 'terhapus') {

            $query->onlyTrashed();

        }

        if ($request->search) {

            $query->where(
                'product_name',
                'like',
                '%' . $request->search . '%'
            );

        }

        $products = $query
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    public function getProductDetail($id)
    {
        $product = Product::with([
            'images',
            'store',
            'category',
            'variants',
            'reviews'
        ])->find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }

    public function toggleProductStatus(Request $request, $id)
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
            ->find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);

        }

        $product->update([
            'is_active' => $product->is_active ? false : true
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Status produk berhasil diubah',
            'data' => $product
        ]);
    }

    public function updateProduct(Request $request, $id)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $product = Product::where('store_id', $store->id)
            ->find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_name' => ['required'],
            'description' => ['nullable'],
            'price' => ['required', 'numeric'],
            'stock' => ['required', 'integer'],
            'is_active' => ['required', 'boolean']
        ]);

        $product->update([
            'category_id' => $validated['category_id'] ?? null,
            'product_name' => $validated['product_name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $validated['is_active']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product berhasil diupdate',
            'data' => $product
        ]);
    }

    public function deleteProduct(Request $request, $id)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $product = Product::where('store_id', $store->id)
            ->find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product berhasil dihapus'
        ]);
    }

    public function getProductRating($id)
    {
        $product = Product::with('reviews')
            ->find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);
        }

        $averageRating = $product->reviews->avg('rating');

        $totalReviews = $product->reviews->count();

        return response()->json([
            'status' => true,
            'data' => [
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews
            ]
        ]);
    }
}