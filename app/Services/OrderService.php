<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderHistory;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Product;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Models\UserVoucher;
use App\Services\NotificationService;
use App\Services\DompetXService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private $dompetx;

    public function __construct()
    {
        $this->dompetx = new DompetXService();
    }

    public function checkout($user, array $data)
    {
        $items       = $data['items']          ?? [];
        $addressId   = $data['address_id']     ?? null;
        $payMethod   = $data['payment_method'] ?? null;
        $bankName    = $data['bank_name']      ?? null;
        $voucherCode = $data['voucher_code']   ?? null;

        if (empty($items)) {
            return response()->json(['status' => false, 'message' => 'Tidak ada item untuk di-checkout'], 422);
        }

        if (!$payMethod) {
            return response()->json(['status' => false, 'message' => 'Metode pembayaran wajib dipilih'], 422);
        }

        $address = UserAddress::where('id', $addressId)->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['status' => false, 'message' => 'Alamat tidak valid'], 422);
        }

        $productSubtotal = 0;
        $totalWeight     = 0;
        $orderItems      = [];

        foreach ($items as $item) {
            $product = Product::find($item['product_id'] ?? null);
            $variant = null;
            if (!empty($item['product_variant_id'])) {
                $variant = ProductVariant::find($item['product_variant_id']);
                if ($variant && $product && $variant->product_id !== $product->id) {
                    return response()->json([
                        'status'  => false,
                        'message' => "Variasi tidak cocok dengan produk"
                    ], 422);
                }
            }

            $price = $product ? (float) $product->price : (float) ($item['price'] ?? 0);
            if ($variant) {
                $price = (float) $variant->price;
            }

            $qty = (int) ($item['qty'] ?? 1);

            if ($variant) {
                if ($variant->stock < $qty) {
                    return response()->json([
                        'status'  => false,
                        'message' => "Stok variasi '{$variant->variant_name}' dari produk '{$product->product_name}' tidak mencukupi"
                    ], 422);
                }
            } else if ($product && $product->stock < $qty) {
                return response()->json([
                    'status'  => false,
                    'message' => "Stok produk '{$product->product_name}' tidak mencukupi"
                ], 422);
            }

            $subtotal        = $price * $qty;
            $productSubtotal += $subtotal;
            $totalWeight     += ($item['weight'] ?? 1) * $qty;

            $orderItems[] = [
                'product_id'         => $product?->id,
                'product_variant_id' => $variant?->id,
                'product_name'       => $item['name'] ?? $product?->product_name ?? 'Produk',
                'product_image'      => $item['image'] ?? $product?->images?->first()?->image,
                'price'              => $price,
                'qty'                => $qty,
                'subtotal'           => $subtotal,
                'variant'            => $variant ? $variant->variant_name : ($item['variant'] ?? null),
                'weight'             => $item['weight']  ?? 1,
                'store_id'           => $product?->store_id ?? ($item['store_id'] ?? null),
                'store_name'         => $item['shop']['name'] ?? $product?->store?->store_name ?? null,
            ];
        }

        $shippingCost    = $totalWeight > 5 ? 5000 : 2000;
        $voucherDiscount = 0;
        $voucherId       = null;

        if ($voucherCode) {
            $voucher = Voucher::where('code', $voucherCode)->where('is_active', true)->first();
            if ($voucher) {
                $userVoucher = UserVoucher::where('user_id', $user->id)
                    ->where('voucher_id', $voucher->id)
                    ->where('is_used', false)
                    ->first();
                if ($userVoucher) {
                    $voucherDiscount = $this->calculateDiscount($voucher, $productSubtotal + $shippingCost);
                    $voucherId       = $voucher->id;
                }
            }
        }

        $totalAmount   = max(0, $productSubtotal + $shippingCost - $voucherDiscount);
        $expiredAt     = null;
        if ($payMethod !== 'COD') {
            $minutes   = $payMethod === 'QRIS' ? 15 : 24 * 60;
            $expiredAt = now()->addMinutes($minutes);
        }
        $initialStatus = $payMethod === 'COD' ? 'processing' : 'pending_payment';

        DB::beginTransaction();

        try {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $order = Order::create([
                'user_id'            => $user->id,
                'address_id'         => $address->id,
                'invoice_number'     => $invoiceNumber,
                'payment_method'     => $payMethod,
                'bank_name'          => $bankName,
                'voucher_id'         => $voucherId,
                'voucher_discount'   => $voucherDiscount,
                'shipping_cost'      => $shippingCost,
                'product_subtotal'   => $productSubtotal,
                'total_amount'       => $totalAmount,
                'status'             => $initialStatus,
                'payment_expired_at' => $expiredAt,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            foreach ($items as $item) {
                if (!empty($item['product_variant_id'])) {
                    ProductVariant::where('id', $item['product_variant_id'])->decrement('stock', $item['qty'] ?? 1);
                } else if ($item['product_id'] ?? null) {
                    Product::where('id', $item['product_id'])->decrement('stock', $item['qty'] ?? 1);
                }
            }

            OrderHistory::create([
                'order_id'    => $order->id,
                'status'      => $initialStatus,
                'description' => $payMethod === 'COD' ? 'Pesanan dikonfirmasi, sedang diproses' : 'Menunggu pembayaran',
            ]);

            $payment = Payment::create([
                'order_id'       => $order->id,
                'payment_method' => $payMethod,
                'bank_name'      => $bankName,
                'amount'         => $totalAmount,
                'status'         => $payMethod === 'COD' ? 'paid' : 'pending',
            ]);

            PaymentLog::create([
                'payment_id'  => $payment->id,
                'event'       => 'created',
                'description' => "Payment dibuat dengan metode {$payMethod}",
            ]);

            if ($voucherId) {
                UserVoucher::where('user_id', $user->id)->where('voucher_id', $voucherId)->update(['is_used' => true]);
                Voucher::where('id', $voucherId)->increment('used');
            }

            DB::commit();

            NotificationService::orderPlaced($user->id, $order->id, $order->invoice_number, $totalAmount);
            if ($payMethod !== 'COD') {
                NotificationService::paymentReminder($user->id, $order->id, $order->invoice_number, $totalAmount);
            }

            // Kirim notifikasi ke seller (toko)
            try {
                $storeIds = collect($orderItems)->pluck('store_id')->unique()->filter();
                foreach ($storeIds as $storeId) {
                    $storeModel = \App\Models\Store::find($storeId);
                    if ($storeModel && $storeModel->user_id) {
                        NotificationService::create(
                            $storeModel->user_id,
                            'order_placed',
                            'Pesanan Baru Masuk! 🛒',
                            "Toko Anda menerima pesanan baru #{$order->invoice_number}.",
                            ['order_id' => $order->id]
                        );
                    }
                }
            } catch (\Exception $e) {
                // Abaikan
            }

            // ----------------------------------------------------------------
            // INTEGRASI DOMPETX — setelah DB commit
            // ----------------------------------------------------------------
            $qrisImageUrl = null;
            $vaNumber     = null;

            if ($payMethod === 'QRIS') {
                [$payment, $qrisImageUrl, $expiredAt] = $this->createDompetxQris(
                    $order, $payment, $totalAmount, $invoiceNumber, $user
                );
            }

            if ($payMethod === 'Transfer Bank' && $bankName) {
                [$payment, $vaNumber, $expiredAt] = $this->createDompetxVa(
                    $order, $payment, $totalAmount, $invoiceNumber, $bankName, $user
                );
            }

            return response()->json([
                'status'  => true,
                'message' => 'Pesanan berhasil dibuat',
                'data'    => [
                    'order_id'       => $order->id,
                    'payment_id'     => $payment->id,
                    'payment_method' => $payMethod,
                    'bank_name'      => $bankName,
                    'total'          => $totalAmount,
                    'order_status'   => $initialStatus,
                    'expired_at'     => $expiredAt?->toIso8601String(),
                    'expired_at_ms'  => $expiredAt ? ($expiredAt->timestamp * 1000) : null,
                    'qris_image_url' => $qrisImageUrl,
                    'va_number'      => $vaNumber,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal membuat pesanan: ' . $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // DompetX response format (TANPA wrapper 'data'):
    // { "id": "xxx", "status": "pending", "expiresAt": "...",
    //   "qrData": { "qrImage": "...", "qrString": "..." } }
    // ----------------------------------------------------------------
    private function createDompetxQris(Order $order, Payment $payment, float $amount, string $invoiceNumber, $user): array
    {
        try {
            $response = $this->dompetx->createPayment('QRIS', $amount, $invoiceNumber, [
                'customer_name'  => $user->name  ?? 'Customer',
                'customer_email' => $user->email ?? '',
                'description'    => "Pembayaran pesanan {$invoiceNumber}",
            ]);

            // ← FIX: DompetX return langsung di root, bukan di dalam 'data'
            $dompetxId = $response['id'] ?? null;

            if ($dompetxId) {
                $expiredAt    = isset($response['expiresAt'])
                    ? \Carbon\Carbon::parse($response['expiresAt'])
                    : now()->addMinutes(15);

                // ← FIX: ambil qrImage dari qrData, bukan dari getQrisImageUrl
                $qrisImageUrl = $response['qrData']['qrImage']
                    ?? $this->dompetx->getQrisImageUrl($dompetxId);

                $payment->update(['transaction_id' => $dompetxId]);
                $order->update(['payment_expired_at' => $expiredAt]);

                PaymentLog::create([
                    'payment_id'  => $payment->id,
                    'event'       => 'qris_created',
                    'description' => 'QRIS dibuat via DompetX',
                    'payload'     => json_encode($response),
                ]);

                return [$payment->fresh(), $qrisImageUrl, $expiredAt];
            }

            Log::error('[DompetX] Gagal buat QRIS', $response);
            return [$payment, null, $order->payment_expired_at];

        } catch (\Exception $e) {
            Log::error('[DompetX] Exception QRIS: ' . $e->getMessage());
            return [$payment, null, $order->payment_expired_at];
        }
    }

    private function createDompetxVa(Order $order, Payment $payment, float $amount, string $invoiceNumber, string $bankName, $user): array
    {
        $bankCodeMap = ['BCA' => 'BCA', 'BNI' => 'BNI', 'BRI' => 'BRI', 'Mandiri' => 'MANDIRI'];
        $bankCode    = $bankCodeMap[$bankName] ?? strtoupper($bankName);

        try {
            $response = $this->dompetx->createPayment('VIRTUAL_ACCOUNT', $amount, $invoiceNumber, [
                'bank_code'      => $bankCode,
                'customer_name'  => $user->name  ?? 'Customer',
                'customer_email' => $user->email ?? '',
                'description'    => "Pembayaran pesanan {$invoiceNumber}",
            ]);

            // ← FIX: DompetX return langsung di root
            $dompetxId = $response['id'] ?? null;

            if ($dompetxId) {
                // ← FIX: field VA number di DompetX perlu dikonfirmasi,
                // kemungkinan ada di virtualAccountData atau vaNumber
                $vaNumber  = $response['virtualAccountData']['vaNumber']
                    ?? $response['vaNumber']
                    ?? $response['va_number']
                    ?? null;

                $expiredAt = isset($response['expiresAt'])
                    ? \Carbon\Carbon::parse($response['expiresAt'])
                    : now()->addHours(24);

                $payment->update(['transaction_id' => $dompetxId, 'va_number' => $vaNumber]);
                $order->update(['payment_expired_at' => $expiredAt]);

                PaymentLog::create([
                    'payment_id'  => $payment->id,
                    'event'       => 'va_created',
                    'description' => "VA {$bankName} dibuat via DompetX",
                    'payload'     => json_encode($response),
                ]);

                return [$payment->fresh(), $vaNumber, $expiredAt];
            }

            Log::error('[DompetX] Gagal buat VA', $response);
            return [$payment, null, $order->payment_expired_at];

        } catch (\Exception $e) {
            Log::error('[DompetX] Exception VA: ' . $e->getMessage());
            return [$payment, null, $order->payment_expired_at];
        }
    }

    private function calculateDiscount(Voucher $voucher, float $total): float
    {
        if ($total < $voucher->minimum_transaction) return 0;
        if ($voucher->discount_type === 'percentage') {
            return min($total * ($voucher->discount_value / 100), PHP_FLOAT_MAX);
        }
        return min($voucher->discount_value, $total);
    }
}