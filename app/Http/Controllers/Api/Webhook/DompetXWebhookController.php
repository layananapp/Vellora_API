<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\PaymentLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\DompetXService;

/**
 * DompetXWebhookController
 *
 * Menerima callback dari DompetX saat status pembayaran berubah.
 * Route: POST /api/webhook/dompetx  (PUBLIC — tidak pakai JWT)
 *
 * Daftarkan URL ini di dashboard DompetX:
 *   https://domain-kamu.com/api/webhook/dompetx
 */
class DompetXWebhookController extends Controller
{
    private DompetXService $dompetx;

    public function __construct()
    {
        $this->dompetx = new DompetXService();
    }

    public function handle(Request $request)
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-DOMPAY-Signature', '');
        $timestamp = $request->header('X-DOMPAY-Timestamp', '');

        // Verifikasi signature — tolak jika tidak cocok
        if (!$this->dompetx->verifyWebhookSignature($rawBody, $signature, $timestamp)) {
            Log::warning('[DompetX Webhook] Signature invalid', ['ip' => $request->ip()]);
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload   = $request->all();
        $dpStatus  = $payload['status']    ?? null;
        $reference = $payload['reference'] ?? null;
        $dpId      = $payload['id']        ?? null;

        // ── Idempotency guard — cegah double-processing saat DompetX retry ──
        $idempotencyKey = 'webhook_' . ($dpId ?? md5($rawBody));
        if (Cache::has($idempotencyKey)) {
            Log::info('[DompetX Webhook] Already processed', ['key' => $idempotencyKey]);
            return response()->json(['status' => true, 'message' => 'Already processed']);
        }
        Cache::put($idempotencyKey, true, now()->addHours(24));

        Log::info('[DompetX Webhook] Diterima', $payload);

        // Cari payment berdasarkan transaction_id
        $payment = Payment::where('transaction_id', $dpId)->first();

        // Fallback: cari via invoice_number
        if (!$payment && $reference) {
            $order   = Order::where('invoice_number', $reference)->first();
            $payment = $order?->payment;
        }

        if (!$payment) {
            // Tetap return 200 agar DompetX tidak retry
            return response()->json(['status' => true, 'message' => 'Payment not found, ignored']);
        }

        $order = $payment->order;

        switch ($dpStatus) {

            case 'SUCCESS':
            case 'PAID':
            case 'SETTLEMENT':
                if ($payment->status !== 'paid') {
                    $payment->update(['status' => 'paid', 'paid_at' => now()]);
                    $order->update(['status' => 'processing']);

                    OrderHistory::create([
                        'order_id'    => $order->id,
                        'status'      => 'processing',
                        'description' => 'Pembayaran dikonfirmasi otomatis via webhook DompetX',
                    ]);

                    PaymentLog::create([
                        'payment_id'  => $payment->id,
                        'event'       => 'webhook_paid',
                        'description' => "Webhook: status {$dpStatus}",
                        'payload'     => json_encode($payload),
                    ]);

                    NotificationService::paymentVerified($order->user_id, $order->id, $order->invoice_number ?? '#' . $order->id);
                    NotificationService::orderProcessing($order->user_id, $order->id, $order->invoice_number ?? '#' . $order->id);
                }
                break;

            case 'FAILED':
                if (!in_array($payment->status, ['paid', 'failed'])) {
                    $payment->update(['status' => 'failed']);
                    OrderHistory::create([
                        'order_id'    => $order->id,
                        'status'      => 'pending_payment',
                        'description' => 'Pembayaran gagal via DompetX',
                    ]);
                    PaymentLog::create([
                        'payment_id'  => $payment->id,
                        'event'       => 'webhook_failed',
                        'description' => 'Webhook: pembayaran gagal',
                        'payload'     => json_encode($payload),
                    ]);
                }
                break;

            case 'EXPIRED':
                if (!in_array($payment->status, ['paid', 'expired'])) {
                    $payment->update(['status' => 'expired']);
                    $order->update(['status' => 'cancelled']);
                    OrderHistory::create([
                        'order_id'    => $order->id,
                        'status'      => 'cancelled',
                        'description' => 'Pesanan dibatalkan karena waktu pembayaran habis',
                    ]);
                    PaymentLog::create([
                        'payment_id'  => $payment->id,
                        'event'       => 'webhook_expired',
                        'description' => 'Webhook: transaksi expired',
                        'payload'     => json_encode($payload),
                    ]);
                }
                break;
        }

        return response()->json(['status' => true, 'message' => 'Webhook processed']);
    }
}
