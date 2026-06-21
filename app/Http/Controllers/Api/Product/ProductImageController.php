<?php

namespace App\Http\Controllers\Api\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;

class ProductImageController extends Controller
{
    public function uploadProductImage(Request $request, $productId)
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
            ->find($productId);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak ditemukan'
            ], 404);

        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png']
        ]);

        /*
        =========================
        HAPUS GAMBAR LAMA
        =========================
        */

        $oldImages = ProductImage::where(
            'product_id',
            $product->id
        )->get();

        foreach ($oldImages as $oldImage) {

            $filePath = public_path(
                $oldImage->image
            );

            if (file_exists($filePath)) {

                unlink($filePath);

            }

            $oldImage->delete();

        }

        /*
        =========================
        UPLOAD GAMBAR BARU
        =========================
        */

        $file = $request->file('image');

        $fileName =
            time() . '_' .
            $file->getClientOriginalName();

        $file->move(
            public_path('product_images'),
            $fileName
        );

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image' => 'product_images/' . $fileName
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Image berhasil diupload',
            'data' => $image
        ]);
    }

    public function getProductImages($productId)
    {
        $images = ProductImage::where('product_id', $productId)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $images
        ]);
    }

    public function deleteProductImage(Request $request, $id)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {

            return response()->json([
                'status' => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $image = ProductImage::find($id);

        if (!$image) {

            return response()->json([
                'status' => false,
                'message' => 'Image tidak ditemukan'
            ], 404);
        }

        $product = Product::where('store_id', $store->id)
            ->find($image->product_id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $image->delete();

        return response()->json([
            'status' => true,
            'message' => 'Image berhasil dihapus'
        ]);
    }
}
