<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\PaymentLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use App\Services\DompetXService;

class PaymentController extends Controller
{
    private $dompetx;

    public function __construct()
    {
        $this->dompetx = new DompetXService();
    }

    /*
    |--------------------------------------------------
    | POST /api/payments/{orderId}
    | Retry payment atau upload bukti manual
    |--------------------------------------------------
    */
    public function createPayment(Request $request, $orderId)
    {
        $user  = $request->get('user');
        $order = Order::where('id', $orderId)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
        }

        if ($order->status !== 'pending_payment') {
            return response()->json(['status' => false, 'message' => 'Pesanan tidak dalam status menunggu pembayaran'], 422);
        }

        if ($order->payment_expired_at && now()->isAfter($order->payment_expired_at)) {
            $order->update(['status' => 'cancelled']);
            $order->payment?->update(['status' => 'expired']);
            return response()->json(['status' => false, 'message' => 'Waktu pembayaran telah habis'], 422);
        }

        $payment = $order->payment;

        // ---- QRIS ----
        if ($order->payment_method === 'QRIS') {

            if ($payment?->transaction_id) {
                // Sudah ada transaction_id → kembalikan QR yang ada
                $qrisImageUrl = $this->dompetx->getQrisImageUrl($payment->transaction_id);
                return response()->json([
                    'status'  => true,
                    'message' => 'Data QRIS',
                    'data'    => [
                        'payment_id'     => $payment->id,
                        'order_id'       => $order->id,
                        'qris_image_url' => $qrisImageUrl,
                        'total'          => $order->total_amount,
                        'expired_at_ms'  => $order->payment_expired_at
                            ? $order->payment_expired_at->timestamp * 1000
                            : (time() + 15 * 60) * 1000,
                    ],
                ]);
            }

            // Belum ada → buat baru ke DompetX
            $response = $this->dompetx->createPayment('QRIS', $order->total_amount, $order->invoice_number, [
                'customer_name'  => $user->name  ?? 'Customer',
                'customer_email' => $user->email ?? '',
            ]);

            // ← FIX: DompetX return di root, bukan di dalam 'data'
            $dompetxId = $response['id'] ?? null;

            if (!$dompetxId) {
                Log::error('[DompetX] Gagal buat QRIS (retry)', $response);
                return response()->json(['status' => false, 'message' => 'Gagal membuat QRIS, coba lagi'], 502);
            }

            $expiredAt    = isset($response['expiresAt'])
                ? \Carbon\Carbon::parse($response['expiresAt'])
                : now()->addMinutes(15);

            // ← FIX: ambil qrImage dari qrData
            $qrisImageUrl = $response['qrData']['qrImage']
                ?? $this->dompetx->getQrisImageUrl($dompetxId);

            $payment?->update(['transaction_id' => $dompetxId]);
            $order->update(['payment_expired_at' => $expiredAt]);

            return response()->json([
                'status'  => true,
                'message' => 'QRIS berhasil dibuat',
                'data'    => [
                    'payment_id'     => $payment->id,
                    'order_id'       => $order->id,
                    'qris_image_url' => $qrisImageUrl,
                    'total'          => $order->total_amount,
                    'expired_at_ms'  => $expiredAt->timestamp * 1000,
                ],
            ]);
        }

        // ---- TRANSFER BANK (VA) ----
        if ($order->payment_method === 'Transfer Bank') {

            if ($payment?->transaction_id) {
                return response()->json([
                    'status'  => true,
                    'message' => 'Data Virtual Account',
                    'data'    => [
                        'payment_id'    => $payment->id,
                        'order_id'      => $order->id,
                        'va_number'     => $payment->va_number,
                        'bank_name'     => $order->bank_name,
                        'total'         => $order->total_amount,
                        'expired_at_ms' => $order->payment_expired_at
                            ? $order->payment_expired_at->timestamp * 1000
                            : (time() + 24 * 60 * 60) * 1000,
                    ],
                ]);
            }

            $bankCodeMap = ['BCA' => 'BCA', 'BNI' => 'BNI', 'BRI' => 'BRI', 'Mandiri' => 'MANDIRI'];
            $bankCode    = $bankCodeMap[$order->bank_name] ?? strtoupper($order->bank_name);

            $response = $this->dompetx->createPayment('VIRTUAL_ACCOUNT', $order->total_amount, $order->invoice_number, [
                'bank_code'     => $bankCode,
                'customer_name' => $user->name ?? 'Customer',
            ]);

            // ← FIX: DompetX return di root
            $dompetxId = $response['id'] ?? null;

            if (!$dompetxId) {
                Log::error('[DompetX] Gagal buat VA (retry)', $response);
                return response()->json(['status' => false, 'message' => 'Gagal membuat Virtual Account, coba lagi'], 502);
            }

            // ← FIX: field VA number di response DompetX
            $vaNumber  = $response['virtualAccountData']['vaNumber']
                ?? $response['vaNumber']
                ?? $response['va_number']
                ?? null;

            $expiredAt = isset($response['expiresAt'])
                ? \Carbon\Carbon::parse($response['expiresAt'])
                : now()->addHours(24);

            $payment?->update(['transaction_id' => $dompetxId, 'va_number' => $vaNumber]);
            $order->update(['payment_expired_at' => $expiredAt]);

            return response()->json([
                'status'  => true,
                'message' => 'Virtual Account berhasil dibuat',
                'data'    => [
                    'payment_id'    => $payment->id,
                    'order_id'      => $order->id,
                    'va_number'     => $vaNumber,
                    'bank_name'     => $order->bank_name,
                    'total'         => $order->total_amount,
                    'expired_at_ms' => $expiredAt->timestamp * 1000,
                ],
            ]);
        }

        // ---- TRANSFER MANUAL: upload bukti ----
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file      = $request->file('payment_proof');
            $fileName  = time() . '_proof_' . $file->getClientOriginalName();
            $file->move(public_path('payment_proofs'), $fileName);
            $proofPath = 'payment_proofs/' . $fileName;
        }

        $payment?->update(['status' => 'paid', 'payment_proof' => $proofPath, 'paid_at' => now()]);
        $order->update(['status' => 'waiting_verification']);

        NotificationService::paymentVerified($user->id, $order->id, $order->invoice_number ?? '#' . $order->id);

        OrderHistory::create([
            'order_id'    => $order->id,
            'status'      => 'waiting_verification',
            'description' => 'Bukti pembayaran dikirim, menunggu verifikasi',
        ]);

        PaymentLog::create([
            'payment_id'  => $payment->id,
            'event'       => 'proof_uploaded',
            'description' => 'Bukti transfer diunggah oleh pembeli',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Bukti pembayaran berhasil dikirim',
            'data'    => ['order_id' => $order->id, 'order_status' => 'waiting_verification'],
        ]);
    }

    /*
    |--------------------------------------------------
    | GET /api/payments/{orderId}/status
    | Polling dari Ionic
    |--------------------------------------------------
    */
    public function checkStatus(Request $request, $orderId)
    {
        $user    = $request->get('user');
        $order   = Order::where('id', $orderId)->where('user_id', $user->id)->first();

        if (!$order || !$order->payment) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $payment   = $order->payment;
        $dompetxId = $payment->transaction_id;

        if (!$dompetxId) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'order_status'   => $order->status,
                    'payment_status' => $payment->status,
                    'is_paid'        => in_array($payment->status, ['paid', 'verified']),
                ],
            ]);
        }

        $dpResponse = $this->dompetx->checkStatus($dompetxId);

        // ← FIX: DompetX return status di root, bukan di dalam 'data'
        $dpStatus = $dpResponse['status'] ?? null;

        Log::info('[DompetX checkStatus]', ['dompetx_status' => $dpStatus, 'response' => $dpResponse]);

        if (in_array($dpStatus, ['success', 'paid', 'settlement', 'SUCCESS', 'PAID', 'SETTLEMENT'])) {
            if ($payment->status !== 'paid') {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
                $order->update(['status' => 'processing']);

                OrderHistory::create([
                    'order_id'    => $order->id,
                    'status'      => 'processing',
                    'description' => 'Pembayaran dikonfirmasi via DompetX (polling)',
                ]);

                PaymentLog::create([
                    'payment_id'  => $payment->id,
                    'event'       => 'polling_confirmed',
                    'description' => "DompetX status: {$dpStatus}",
                    'payload'     => json_encode($dpResponse),
                ]);

                NotificationService::paymentVerified($order->user_id, $order->id, $order->invoice_number ?? '#' . $order->id);
                NotificationService::orderProcessing($order->user_id, $order->id, $order->invoice_number ?? '#' . $order->id);
            }
        }

        $order->refresh();
        $payment->refresh();

        return response()->json([
            'status' => true,
            'data'   => [
                'order_status'   => $order->status,
                'payment_status' => $payment->status,
                'is_paid'        => in_array($payment->status, ['paid', 'verified']),
            ],
        ]);
    }

    /*
    |--------------------------------------------------
    | PUT /api/payments/{paymentId}/success
    | Konfirmasi manual / simulasi
    |--------------------------------------------------
    */
    public function paymentSuccess(Request $request, $paymentId)
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Payment tidak ditemukan'], 404);
        }

        $order = $payment->order;

        if ($order->payment_expired_at && now()->isAfter($order->payment_expired_at)) {
            $order->update(['status' => 'cancelled']);
            $payment->update(['status' => 'expired']);
            return response()->json(['status' => false, 'message' => 'Waktu pembayaran telah habis'], 422);
        }

        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        $order->update(['status' => 'processing']);

        NotificationService::paymentVerified($order->user_id, $order->id, $order->invoice_number ?? '#' . $order->id);
        NotificationService::orderProcessing($order->user_id, $order->id, $order->invoice_number ?? '#' . $order->id);

        OrderHistory::create([
            'order_id'    => $order->id,
            'status'      => 'processing',
            'description' => 'Pembayaran dikonfirmasi manual',
        ]);

        PaymentLog::create([
            'payment_id'  => $payment->id,
            'event'       => 'confirmed',
            'description' => 'Pembayaran dikonfirmasi manual',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Pembayaran berhasil dikonfirmasi',
            'data'    => ['order_id' => $order->id, 'order_status' => 'processing'],
        ]);
    }
}