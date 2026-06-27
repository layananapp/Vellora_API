<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /*
    |--------------------------------------------------
    | BUAT NOTIFIKASI
    |--------------------------------------------------
    */
    public static function create(
        int    $userId,
        string $type,
        string $title,
        string $message,
        array  $data = []
    ): Notification {

        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
            'is_read' => false,
        ]);

    }

    /*
    |--------------------------------------------------
    | NOTIF: PESANAN BERHASIL DIBUAT
    |--------------------------------------------------
    */
    public static function orderPlaced(
        int    $userId,
        int    $orderId,
        string $invoiceNumber,
        float  $total
    ): void {

        self::create(
            $userId,
            'order_placed',
            'Pesanan Berhasil Dibuat 🛍️',
            "Pesanan #{$invoiceNumber} senilai Rp" .
                number_format($total, 0, ',', '.') .
                " telah berhasil dibuat.",
            ['order_id' => $orderId]
        );

    }

    /*
    |--------------------------------------------------
    | NOTIF: PENGINGAT BAYAR
    |--------------------------------------------------
    */
    public static function paymentReminder(
        int    $userId,
        int    $orderId,
        string $invoiceNumber,
        float  $total
    ): void {

        self::create(
            $userId,
            'payment_reminder',
            'Segera Selesaikan Pembayaran ⏳',
            "Pesanan #{$invoiceNumber} senilai Rp" .
                number_format($total, 0, ',', '.') .
                " menunggu pembayaranmu.",
            ['order_id' => $orderId]
        );

    }

    /*
    |--------------------------------------------------
    | NOTIF: PEMBAYARAN DIVERIFIKASI
    |--------------------------------------------------
    */
    public static function paymentVerified(
        int    $userId,
        int    $orderId,
        string $invoiceNumber
    ): void {

        self::create(
            $userId,
            'payment_verified',
            'Pembayaran Dikonfirmasi ✅',
            "Pembayaran untuk pesanan #{$invoiceNumber} " .
                "telah berhasil dikonfirmasi.",
            ['order_id' => $orderId]
        );

    }

    /*
    |--------------------------------------------------
    | NOTIF: PESANAN DIKEMAS
    |--------------------------------------------------
    */
    public static function orderProcessing(
        int    $userId,
        int    $orderId,
        string $invoiceNumber
    ): void {

        self::create(
            $userId,
            'order_processing',
            'Pesanan Sedang Dikemas 📦',
            "Pesanan #{$invoiceNumber} sedang " .
                "disiapkan oleh penjual.",
            ['order_id' => $orderId]
        );

    }

    /*
    |--------------------------------------------------
    | NOTIF: PESANAN DIKIRIM
    |--------------------------------------------------
    */
    public static function orderShipped(
        int    $userId,
        int    $orderId,
        string $invoiceNumber,
        string $courier     = '',
        string $receiptNumber = ''
    ): void {

        $detail = $courier && $receiptNumber
            ? " via {$courier} ({$receiptNumber})"
            : '';

        self::create(
            $userId,
            'order_shipped',
            'Pesanan Sedang Dikirim 🚚',
            "Pesanan #{$invoiceNumber} sedang dalam " .
                "perjalanan{$detail}.",
            [
                'order_id'      => $orderId,
                'courier'       => $courier,
                'receipt_number'=> $receiptNumber,
            ]
        );

    }

    /*
    |--------------------------------------------------
    | NOTIF: PESANAN SELESAI
    |--------------------------------------------------
    */
    public static function orderDelivered(
        int    $userId,
        int    $orderId,
        string $invoiceNumber
    ): void {

        self::create(
            $userId,
            'order_delivered',
            'Pesanan Telah Tiba! 🎉',
            "Pesanan #{$invoiceNumber} telah berhasil " .
                "diterima. Jangan lupa beri ulasan ya!",
            ['order_id' => $orderId]
        );

    }

    /*
    |--------------------------------------------------
    | NOTIF: PESANAN DIBATALKAN
    |--------------------------------------------------
    */
    public static function orderCancelled(
        int    $userId,
        int    $orderId,
        string $invoiceNumber,
        string $reason = ''
    ): void {

        self::create(
            $userId,
            'order_cancelled',
            'Pesanan Dibatalkan ❌',
            "Pesanan #{$invoiceNumber} telah dibatalkan." .
                ($reason ? " Alasan: {$reason}" : ''),
            ['order_id' => $orderId]
        );

    }

}