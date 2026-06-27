<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;

use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Store\StoreController;

use App\Http\Controllers\Api\Product\CategoryController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Product\ProductImageController;
use App\Http\Controllers\Api\Product\ProductVariantController;

use App\Http\Controllers\Api\Checkout\CheckoutController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\Payment\PaymentController;

use App\Http\Controllers\Api\Webhook\DompetXWebhookController;

use App\Http\Controllers\Api\Review\ReviewController;
use App\Http\Controllers\Api\Review\SellerReviewController;
use App\Http\Controllers\Api\Address\UserAddressController;
use App\Http\Controllers\Api\Address\RegionController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Chat\ChatController;
use App\Http\Controllers\Api\Voucher\VoucherController;
use App\Http\Controllers\Api\Report\ReportController;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {

    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:register');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login');

    Route::post('/logout', [LoginController::class, 'logout'])
        ->middleware('jwt');

    Route::post('/google', [GoogleAuthController::class, 'googleLogin'])
        ->middleware('throttle:login');

    Route::post('forgot-password', [ForgotPasswordController::class, 'sendOtp'])
        ->middleware('throttle:otp');

    Route::post('verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
        ->middleware('throttle:otp');

    Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:otp');

    // Protected auth routes (JWT required)
    Route::middleware('jwt')->group(function () {

        Route::get('/profile', function (\Illuminate\Http\Request $request) {
            return response()->json([
                'status' => true,
                'data'   => $request->get('user'),
            ]);
        });

        Route::put('/update-phone', [UserController::class, 'updatePhone']);
        Route::put('/change-password', [UserController::class, 'changePassword']);
        Route::put('/update-profile', [UserController::class, 'updateProfile']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['jwt', 'role:admin'])
    ->group(function () {

        Route::get('/users', [UserController::class, 'getAllUsers']);
        Route::get('/users/{id}', [UserController::class, 'getUserDetail']);
        Route::put('/users/{id}/suspend', [UserController::class, 'suspendUser']);
        Route::put('/users/{id}/unsuspend', [UserController::class, 'unsuspendUser']);
        Route::delete('/users/{id}', [UserController::class, 'deleteUser']);

        Route::post('/categories', [CategoryController::class, 'createCategory']);
        Route::post('/vouchers', [VoucherController::class, 'createVoucher']);
        Route::get('/reports', [ReportController::class, 'getAllReports']);
        Route::put('/reports/{id}/status', [ReportController::class, 'updateReportStatus']);
        Route::get('/reviews', [ReviewController::class, 'getAllReviews']);
        Route::get('/orders', [OrderController::class, 'getAllOrders']);
    });

/*
|--------------------------------------------------------------------------
| Profile Photo Upload (JWT protected, terpisah dari auth prefix)
|--------------------------------------------------------------------------
*/
Route::middleware('jwt')->group(function () {
    Route::post('/upload-profile-photo', [UserController::class, 'uploadProfilePhoto']);
});

/*
|--------------------------------------------------------------------------
| Regions
|--------------------------------------------------------------------------
*/
Route::prefix('regions')->group(function () {

    Route::get('/provinces', [RegionController::class, 'provinces']);
    Route::get('/regencies/{provinceId}', [RegionController::class, 'regencies']);
    Route::get('/districts/{regencyId}', [RegionController::class, 'districts']);

});

/*
|--------------------------------------------------------------------------
| Stores
|--------------------------------------------------------------------------
*/
Route::prefix('stores')->group(function () {

    Route::post('/', [StoreController::class, 'createStore'])->middleware('jwt');
    Route::get('/', [StoreController::class, 'getStores']);
    Route::get('/my-store', [StoreController::class, 'getMyStore'])->middleware('jwt');
    Route::get('/{id}', [StoreController::class, 'getStoreDetail']);
    Route::put('/', [StoreController::class, 'updateStore'])->middleware('jwt');
    Route::post('/upload-store-logo', [StoreController::class, 'uploadStoreLogo'])->middleware('jwt');
    Route::get('/{id}/products', [StoreController::class, 'getStoreProducts']);

});

/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
*/
Route::prefix('products')->group(function () {

    Route::get('/', [ProductController::class, 'getProducts']);

    Route::middleware(['jwt', 'role:seller'])->group(function () {
        Route::post('/', [ProductController::class, 'createProduct']);
        Route::get('/seller', [ProductController::class, 'getMyProducts']);
        Route::put('/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('/{id}', [ProductController::class, 'deleteProduct']);
        Route::post('/{productId}/images', [ProductImageController::class, 'uploadProductImage']);
        Route::post('/{productId}/variants', [ProductVariantController::class, 'createVariant']);
        Route::put('/{id}/toggle-status', [ProductController::class, 'toggleProductStatus']);
    });

    Route::get('/{id}', [ProductController::class, 'getProductDetail']);
    Route::get('/{id}/rating', [ProductController::class, 'getProductRating']);
    Route::get('/{productId}/images', [ProductImageController::class, 'getProductImages']);
    Route::get('/{productId}/variants', [ProductVariantController::class, 'getVariants']);
});

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/
Route::get('/categories', [CategoryController::class, 'getCategories']);

/*
|--------------------------------------------------------------------------
| Product Images / Variants
|--------------------------------------------------------------------------
*/
Route::delete('/product-images/{id}', [ProductImageController::class, 'deleteProductImage'])
    ->middleware(['jwt', 'role:seller']);

Route::put('/variants/{id}', [ProductVariantController::class, 'updateVariant'])
    ->middleware(['jwt', 'role:seller']);

Route::delete('/variants/{id}', [ProductVariantController::class, 'deleteVariant'])
    ->middleware(['jwt', 'role:seller']);

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/
Route::post('/checkout', [CheckoutController::class, 'checkout'])
    ->middleware('jwt');

/*
|--------------------------------------------------------------------------
| Orders
|--------------------------------------------------------------------------
*/
Route::prefix('orders')
    ->middleware('jwt')
    ->group(function () {

        Route::get('/my-orders', [OrderController::class, 'getMyOrders']);
        Route::get('/seller/orders', [OrderController::class, 'getSellerOrders'])
            ->middleware('role:seller');
        Route::get('/{id}', [OrderController::class, 'getOrderDetail']);
        Route::get('/{id}/histories', [OrderController::class, 'getOrderHistories']);
        Route::put('/{id}/cancel', [OrderController::class, 'cancelOrder']);
        Route::put('/{id}/receive', [OrderController::class, 'receiveOrder']);

    });

/*
|--------------------------------------------------------------------------
| Seller (Endpoints khusus seller)
|--------------------------------------------------------------------------
*/
Route::prefix('seller')
    ->middleware(['jwt', 'role:seller'])
    ->group(function () {

        // GET /api/seller/reviews — semua review produk milik toko seller
        Route::get('/reviews', [SellerReviewController::class, 'index']);

        // GET /api/seller/orders/{id} — detail pesanan yang masuk ke toko seller
        Route::get('/orders/{id}', [OrderController::class, 'getSellerOrderDetail']);

        // PUT /api/seller/orders/{id}/status — update status pesanan oleh seller
        Route::put('/orders/{id}/status', [OrderController::class, 'updateSellerOrderStatus']);

    });

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
*/
Route::prefix('payments')->group(function () {

    Route::post('/{orderId}', [PaymentController::class, 'createPayment'])
        ->middleware('jwt');

    Route::get('/{orderId}/status', [PaymentController::class, 'checkStatus'])
        ->middleware('jwt');

    // FIXED: ditambah middleware jwt (sebelumnya route ini publik tanpa auth)
    Route::put('/{paymentId}/success', [PaymentController::class, 'paymentSuccess'])
        ->middleware('jwt');

});

/*
|--------------------------------------------------------------------------
| Webhook DompetX
|--------------------------------------------------------------------------
*/
Route::post('/webhook/dompetx', [DompetXWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Reviews
|--------------------------------------------------------------------------
*/
Route::get('/products/{productId}/reviews', [ReviewController::class, 'getProductReviews']);
Route::get('/products/{productId}/rating',  [ProductController::class, 'getProductRating']);

Route::middleware('jwt')->group(function () {

    Route::post('/reviews', [ReviewController::class, 'createReview']);
    Route::get('/reviews/eligible-items', [ReviewController::class, 'getEligibleItems']);
    Route::get('/reviews/my-reviews', [ReviewController::class, 'getUserReviews']);

});

/*
|--------------------------------------------------------------------------
| Addresses
|--------------------------------------------------------------------------
*/
Route::prefix('addresses')
    ->middleware('jwt')
    ->group(function () {

        Route::post('/', [UserAddressController::class, 'createAddress']);
        Route::get('/', [UserAddressController::class, 'getAddresses']);
        Route::put('/{id}', [UserAddressController::class, 'updateAddress']);
        Route::delete('/{id}', [UserAddressController::class, 'deleteAddress']);
    });

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/
Route::prefix('notifications')
    ->middleware('jwt')
    ->group(function () {

        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/', [NotificationController::class, 'deleteAll']);

    });

/*
|--------------------------------------------------------------------------
| Chat
|--------------------------------------------------------------------------
*/
Route::prefix('chat-rooms')
    ->middleware('jwt')
    ->group(function () {

        Route::post('/', [ChatController::class, 'createRoom']);
        Route::get('/', [ChatController::class, 'getRooms']);
        Route::post('/{roomId}/messages', [ChatController::class, 'sendMessage']);
        Route::get('/{roomId}/messages', [ChatController::class, 'getMessages']);
    });

/*
|--------------------------------------------------------------------------
| Broadcasting Auth
| Fix: JWT middleware set user di $request->get('user'), bukan Auth::user().
| Kita bridge manual supaya Broadcast::auth() bisa kenali user-nya.
|--------------------------------------------------------------------------
*/
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {

    $user = $request->get('user');

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Bridge: set Auth user supaya Broadcast::auth() bisa validasi channel
    Auth::setUser($user);

    return Broadcast::auth($request);

})->middleware('jwt');

/*
|--------------------------------------------------------------------------
| Vouchers
|--------------------------------------------------------------------------
*/
Route::prefix('vouchers')->group(function () {

    Route::get('/', [VoucherController::class, 'getVouchers']);

    Route::get('/my-vouchers', [VoucherController::class, 'myVouchers'])
        ->middleware('jwt');

    Route::post('/{id}/claim', [VoucherController::class, 'claimVoucher'])
        ->middleware('jwt');

});

/*
|--------------------------------------------------------------------------
| Laporan
|--------------------------------------------------------------------------
*/
Route::post('/reports', [ReportController::class, 'store'])
    ->middleware('jwt');

Route::get('/reports', [ReportController::class, 'index'])
    ->middleware('jwt');