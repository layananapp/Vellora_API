<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Services\NotificationService;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------
    | GET /api/orders/my-orders
    |--------------------------------------------------
    */
    public function getMyOrders(Request $request)
    {
        $user = $request->get('user');

        $orders = Order::where('user_id', $user->id)
            ->with([
                'items',
                'address',
                'payment',
            ])
            ->latest()
            ->get()
            ->map(fn($order) => $this->formatOrder($order));

        return response()->json([
            'status' => true,
            'data'   => $orders,
        ]);
    }

    /*
    |--------------------------------------------------
    | GET /api/orders/{id}
    |--------------------------------------------------
    */
    public function getOrderDetail(Request $request, $id)
    {
        $user = $request->get('user');

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with([
                'items.product.images',
                'items.store',
                'address',
                'payment',
                'histories',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->formatOrderDetail($order),
        ]);
    }

    /*
    |--------------------------------------------------
    | GET /api/orders/{id}/histories
    |--------------------------------------------------
    */
    public function getOrderHistories(Request $request, $id)
    {
        $user  = $request->get('user');

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        $histories = OrderHistory::where('order_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $histories,
        ]);
    }

    /*
    |--------------------------------------------------
    | PUT /api/orders/{id}/cancel
    |--------------------------------------------------
    */
    public function cancelOrder(Request $request, $id)
    {
        $user  = $request->get('user');

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        if (!in_array($order->status, ['pending_payment', 'processing'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesanan tidak dapat dibatalkan pada status ini',
            ], 422);
        }

        // Kembalikan stok
        foreach ($order->items as $item) {
            if ($item->product_id) {
                \App\Models\Product::where('id', $item->product_id)
                    ->increment('stock', $item->qty);
            }
        }

        $order->update(['status' => 'cancelled']);

        NotificationService::orderCancelled(
            $order->user_id,
            $order->id,
            $order->invoice_number ?? '#' . $order->id,
            'Dibatalkan oleh pembeli'
        );

        $order->payment?->update(['status' => 'failed']);

        OrderHistory::create([
            'order_id'    => $order->id,
            'status'      => 'cancelled',
            'description' => 'Pesanan dibatalkan oleh pembeli',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Pesanan berhasil dibatalkan',
        ]);
    }

    /*
    |--------------------------------------------------
    | GET /api/orders/seller/orders (Seller only)
    |--------------------------------------------------
    */
    public function getSellerOrders(Request $request)
    {
        $user  = $request->get('user');
        $store = $user->store;

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Anda belum memiliki toko',
            ], 403);
        }

        // Ambil order yang ada item dari toko ini
        $orderIds = \App\Models\OrderItem::where('store_id', $store->id)
            ->pluck('order_id')
            ->unique();

        $orders = Order::whereIn('id', $orderIds)
            ->with(['items' => fn($q) => $q->where('store_id', $store->id), 'address', 'payment'])
            ->latest()
            ->get()
            ->map(fn($order) => $this->formatOrder($order));

        return response()->json([
            'status' => true,
            'data'   => $orders,
        ]);
    }

    /*
    |--------------------------------------------------
    | GET /api/seller/orders/{id} (Seller only)
    | Detail pesanan yang berisi item dari toko seller
    |--------------------------------------------------
    */
    public function getSellerOrderDetail(Request $request, $id)
    {
        $user  = $request->get('user');
        $store = $user->store;

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Anda belum memiliki toko',
            ], 403);
        }

        // Pastikan order ini mengandung item dari toko seller
        $hasItem = \App\Models\OrderItem::where('order_id', $id)
            ->where('store_id', $store->id)
            ->exists();

        if (!$hasItem) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        $order = Order::where('id', $id)
            ->with([
                'items'           => fn($q) => $q->where('store_id', $store->id),
                'items.product.images',
                'items.store',
                'address',
                'payment',
                'histories',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'Pesanan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->formatOrderDetail($order),
        ]);
    }

    /*
    |--------------------------------------------------
    | FORMAT ORDER (ringkas, untuk list)
    |--------------------------------------------------
    */
    private function formatOrder(Order $order): array
    {
        $firstItem = $order->items->first();

        return [
            'id'             => $order->id,
            'status'         => $order->status,
            'frontend_status'=> Order::STATUS_MAP[$order->status] ?? $order->status,
            'payment_method' => $order->payment_method,
            'bank_name'      => $order->bank_name,
            'total_amount'   => $order->total_amount,
            'shipping_cost'  => $order->shipping_cost,
            'voucher_discount'=> $order->voucher_discount,
            'product_subtotal'=> $order->product_subtotal,
            'payment_expired_at' => $order->payment_expired_at?->toIso8601String(),
            'payment_expired_ms' => $order->payment_expired_at
                ? ($order->payment_expired_at->timestamp * 1000) : null,
            'created_at'     => $order->created_at->toIso8601String(),

            // Untuk tampilan list
            'product_name'   => $firstItem?->product_name ?? 'Produk',
            'product_image'  => $firstItem?->product_image,
            'qty'            => $firstItem?->qty ?? 1,
            'store_name'     => $firstItem?->store_name,
            'items_count'    => $order->items->count(),

            // Payment
            'payment_id'     => $order->payment?->id,
            'payment_status' => $order->payment?->status,
        ];
    }

    /*
    |--------------------------------------------------
    | FORMAT ORDER DETAIL (lengkap)
    |--------------------------------------------------
    */
    private function formatOrderDetail(Order $order): array
    {
        $address = $order->address;

        $items = $order->items->map(function ($item) {
            $img = $item->product_image;

            if ($img && !str_starts_with($img, 'http')) {
                $img = 'https://api.layananapp.my.id/' . $img;
            }

            return [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product_name,
                'product_image'=> $img,
                'price'        => $item->price,
                'qty'          => $item->qty,
                'subtotal'     => $item->subtotal,
                'variant'      => $item->variant,
                'weight'       => $item->weight,
                'store_id'     => $item->store_id,
                'store_name'   => $item->store_name,
                'store_user_id'=> $item->store?->user_id,
            ];
        });

        return [
            'id'              => $order->id,
            'status'          => $order->status,
            'frontend_status' => Order::STATUS_MAP[$order->status] ?? $order->status,
            'payment_method'  => $order->payment_method,
            'bank_name'       => $order->bank_name,
            'total_amount'    => $order->total_amount,
            'shipping_cost'   => $order->shipping_cost,
            'voucher_discount'=> $order->voucher_discount,
            'product_subtotal'=> $order->product_subtotal,
            'courier'         => $order->courier ?? 'Ninja Xpress',
            'receipt_number'  => $order->receipt_number ?? '-',
            'notes'           => $order->notes,
            'payment_expired_at' => $order->payment_expired_at?->toIso8601String(),
            'payment_expired_ms' => $order->payment_expired_at
                ? ($order->payment_expired_at->timestamp * 1000) : null,
            'created_at'      => $order->created_at->toIso8601String(),

            // ITEMS
            'items'           => $items,

            // ALAMAT
            'address' => $address ? [
                'id'             => $address->id,
                'recipient_name' => $address->recipient_name,
                'phone_number'   => $address->phone_number,
                'full_address'   => $address->full_address,
                'detail_address' => $address->detail_address,
                'postal_code'    => $address->postal_code,
            ] : null,

            // PAYMENT
            'payment_id'     => $order->payment?->id,
            'payment_status' => $order->payment?->status,

            // HISTORI
            'histories' => $order->histories->map(fn($h) => [
                'status'      => $h->status,
                'description' => $h->description,
                'time'        => $h->created_at->toIso8601String(),
            ]),
        ];
    }

    public function getAllOrders()
    {
        $orders = Order::with([
            'items',
            'user',
            'payment',
        ])
        ->latest()
        ->get();

        return response()->json([
            'status' => true,
            'data'   => $orders,
        ]);
    }
}