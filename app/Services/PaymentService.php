<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\OrderHistory;
use App\Services\NotificationService;


class PaymentService
{
    public function createPayment($user, $orderId, $validated)
    {
        $order = Order::where('user_id', $user->id)
            ->find($orderId);

        if (!$order) {

            return response()->json([
                'status' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'transaction_id' => 'TRX-' . time(),
            'amount' => $order->total_price
        ]);

        PaymentLog::create([
            'payment_id' => $payment->id,
            'status' => 'pending',
            'message' => 'Payment dibuat',
            'payload' => json_encode([
                'payment_method' => $validated['payment_method']
            ])
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment berhasil dibuat',
            'data' => $payment
        ]);
    }

    public function paymentSuccess($paymentId)
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {

            return response()->json([
                'status' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        $payment->update([
            'payment_status' => 'paid',
            'paid_at' => now()
        ]);

        $payment->order->update([
            'status' => 'processed'
        ]);

        OrderHistory::create([
            'order_id' => $payment->order->id,
            'status' => 'processed',
            'description' => 'Payment berhasil dibayar'
        ]);

        NotificationService::create(
            $payment->order->user_id,
            'payment_verified',
            'Payment Berhasil',
            'Payment untuk invoice ' . $payment->order->invoice_number . ' berhasil dibayar',
            ['order_id' => $payment->order->id]
        );

        PaymentLog::create([
            'payment_id' => $payment->id,
            'status' => 'paid',
            'message' => 'Payment berhasil',
            'payload' => json_encode([
                'paid_at' => now()
            ])
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment berhasil dibayar',
            'data' => $payment
        ]);
    }
}