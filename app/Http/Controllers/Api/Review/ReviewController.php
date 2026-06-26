<?php

namespace App\Http\Controllers\Api\Review;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use App\Models\Review;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /*
    |--------------------------------------------------
    | POST /api/reviews
    | Kirim ulasan
    |--------------------------------------------------
    */
    public function createReview(Request $request)
    {
        $user = $request->get('user');

        $validated = $request->validate([
            'product_id'    => ['required', 'exists:products,id'],
            'order_id'      => ['nullable', 'exists:orders,id'],
            'order_item_id' => ['nullable', 'exists:order_items,id'],
            'rating'        => ['required', 'integer', 'min:1', 'max:5'],
            'review'        => ['nullable', 'string', 'min:10', 'max:250'],
        ]);

        return $this->reviewService->createReview($user, $validated);
    }

    /*
    |--------------------------------------------------
    | GET /api/reviews/eligible-items
    | Order items yang bisa diulas (delivered + belum review)
    |--------------------------------------------------
    */
    public function getEligibleItems(Request $request)
    {
        $user = $request->get('user');

        return $this->reviewService->getEligibleItems($user);
    }

    /*
    |--------------------------------------------------
    | GET /api/reviews/my-reviews
    | Review yang sudah dikirim user
    |--------------------------------------------------
    */
    public function getUserReviews(Request $request)
    {
        $user = $request->get('user');

        return $this->reviewService->getUserReviews($user);
    }

    /*
    |--------------------------------------------------
    | GET /api/products/{productId}/reviews
    | Review produk (untuk product-detail)
    |--------------------------------------------------
    */
    public function getProductReviews($productId)
    {
        try {
            $reviews = Review::where('product_id', $productId)
                ->with('user:id,name,profile_photo')
                ->latest()
                ->get()
                ->map(function ($review) {

                    $photo = $review->user?->profile_photo;

                    if ($photo && !str_starts_with($photo, 'http')) {
                        $photo = 'https://api.layananapp.my.id/' . $photo;
                    }

                    return [
                        'id'         => $review->id,
                        'user_name'  => $review->user?->name ?? 'Pengguna',
                        'user_photo' => $photo
                            ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png',
                        'rating'     => $review->rating,
                        'review'     => $review->review,
                        'created_at' => $review->created_at
                            ? $review->created_at->translatedFormat('d F Y')
                            : '',
                    ];
                });

            return response()->json([
                'status' => true,
                'data'   => $reviews,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Debug Error: ' . $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }

    public function getAllReviews()
    {
        $reviews = Review::with(['user', 'product'])->latest()->get();

        return response()->json([
            'status' => true,
            'data'   => $reviews,
        ]);
    }
}