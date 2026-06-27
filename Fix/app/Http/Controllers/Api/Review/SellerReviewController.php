<?php

namespace App\Http\Controllers\Api\Review;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Store;

class SellerReviewController extends Controller
{
    /*
    |--------------------------------------------------
    | GET /api/seller/reviews
    | Semua review produk milik toko seller yang login
    |--------------------------------------------------
    */
    public function index(Request $request)
    {
        $user  = $request->get('user');
        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Anda belum memiliki toko',
            ], 403);
        }

        $reviews = Review::whereHas('product', function ($q) use ($store) {
                $q->where('store_id', $store->id);
            })
            ->with([
                'user:id,name,profile_photo_path',
                'product:id,product_name,store_id',
                'product.images',
            ])
            ->latest()
            ->get()
            ->map(function ($review) {
                // User photo
                $photo = $review->user?->profile_photo_path;
                if ($photo && !str_starts_with($photo, 'http')) {
                    $photo = config('app.url') . '/' . $photo;
                }

                // Product image
                $productImg = $review->product?->images?->first()?->image;
                if ($productImg && !str_starts_with($productImg, 'http')) {
                    $productImg = config('app.url') . '/' . $productImg;
                }

                return [
                    'id'            => $review->id,
                    'product_id'    => $review->product_id,
                    'product_name'  => $review->product?->product_name ?? '-',
                    'product_image' => $productImg,
                    'user_name'     => $review->user?->name ?? 'Pembeli',
                    'user_photo'    => $photo,
                    'rating'        => $review->rating,
                    'review'        => $review->review,
                    'created_at'    => $review->created_at
                        ->translatedFormat('d F Y'),
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $reviews,
        ]);
    }
}
