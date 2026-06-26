<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Order;

class ReviewService
{
    /*
    |--------------------------------------------------
    | BUAT REVIEW
    |--------------------------------------------------
    */
    public function createReview($user, array $validated)
    {
        $orderItemId = $validated['order_item_id'] ?? null;
        $productId   = $validated['product_id'];
        $orderId     = $validated['order_id'] ?? null;

        // ---- VALIDASI: order_item harus ada ----
        if ($orderItemId) {

            $orderItem = OrderItem::with('order')->find($orderItemId);

            if (!$orderItem) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Item pesanan tidak ditemukan',
                ], 404);
            }

            // Pastikan order_item milik user ini
            if ($orderItem->order->user_id != $user->id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Kamu tidak memiliki akses ke item ini',
                ], 403);
            }

            // Pastikan status order sudah 'completed'
            if ($orderItem->order->status !== 'completed') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Hanya produk yang sudah selesai yang bisa diulas',
                ], 422);
            }

            // Pastikan item belum pernah di-review
            $alreadyReviewed = Review::withTrashed()
                ->where('order_item_id', $orderItemId)
                ->exists();

            if ($alreadyReviewed) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Item ini sudah pernah diulas',
                ], 422);
            }
        }

        // ---- BUAT REVIEW ----
        $review = Review::create([
            'user_id'       => $user->id,
            'product_id'    => $productId,
            'order_id'      => $orderId,
            'order_item_id' => $orderItemId,
            'rating'        => $validated['rating'],
            'review'        => $validated['review'] ?? null,
        ]);

        // ---- UPDATE RATING PRODUK ----
        $product = Product::find($productId);
        if ($product) {
            $product->recalculateRating();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Ulasan berhasil dikirim',
            'data'    => $review->load('user'),
        ]);
    }

    /*
    |--------------------------------------------------
    | ORDER ITEMS YANG BISA DIULAS
    | (status delivered + belum pernah diulas)
    |--------------------------------------------------
    */
    public function getEligibleItems($user)
    {
        // Ambil semua order_item milik user dari order completed
        $orderItems = OrderItem::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'completed');
            })
            // Belum pernah di-review
            ->whereDoesntHave('review')
            ->with([
                'order:id,status,created_at',
                'product:id,product_name,price',
                'product.images',
            ])
            ->latest()
            ->get()
            ->map(function ($item) {
                $img = $item->product_image;

                if (!$img && $item->product?->images?->count()) {
                    $img = $item->product->images->first()->image;
                }

                if ($img && !str_starts_with($img, 'http')) {
                    $img = 'https://api.layananapp.my.id/' . $img;
                }

                return [
                    'order_item_id' => $item->id,
                    'order_id'      => $item->order_id,
                    'product_id'    => $item->product_id,
                    'product_name'  => $item->product_name,
                    'product_image' => $img ?? 'https://picsum.photos/300',
                    'price'         => $item->price,
                    'qty'           => $item->qty,
                    'variant'       => $item->variant,
                    'store_name'    => $item->store_name,
                    'order_date'    => $item->order?->created_at
                        ?->toDateString(),
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $orderItems,
        ]);
    }

    /*
    |--------------------------------------------------
    | REVIEW YANG SUDAH DIKIRIM USER
    |--------------------------------------------------
    */
    public function getUserReviews($user)
    {
        $reviews = Review::where('user_id', $user->id)
            ->with([
                'product:id,product_name',
                'product.images',
                'orderItem:id,store_name,price,qty,variant',
            ])
            ->latest()
            ->get()
            ->map(function ($review) {

                $img = $review->product?->images?->first()?->image;

                if ($img && !str_starts_with($img, 'http')) {
                    $img = 'https://api.layananapp.my.id/' . $img;
                }

                return [
                    'id'            => $review->id,
                    'product_id'    => $review->product_id,
                    'product_name'  => $review->product?->product_name,
                    'product_image' => $img ?? 'https://picsum.photos/300',
                    'store_name'    => $review->orderItem?->store_name,
                    'price'         => $review->orderItem?->price,
                    'qty'           => $review->orderItem?->qty,
                    'variant'       => $review->orderItem?->variant,
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