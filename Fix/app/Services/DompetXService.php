<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DompetXService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('dompetx.base_url', 'https://api.dompetx.com');
        $this->apiKey  = config('dompetx.api_key', '');
    }

    private function generateSignature(string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $body, $this->apiKey);
    }

    private function buildHeaders(string $timestamp, string $body): array
    {
        return [
            'Content-Type'       => 'application/json',
            'X-DOMPAY-API-Key'   => $this->apiKey,
            'X-DOMPAY-Signature' => $this->generateSignature($timestamp, $body),
            'X-DOMPAY-Timestamp' => $timestamp,
            'Idempotency-Key'    => 'req_' . Str::uuid(),
        ];
    }

    public function createPayment(string $method, float $amount, string $reference, array $extra = []): array
    {
        $timestamp = (string) time();
        $payload   = array_merge([
            'method'    => $method,
            'amount'    => (int) $amount,
            'currency'  => 'IDR',
            'reference' => $reference,
        ], $extra);

        $body     = json_encode($payload);
        $response = Http::withHeaders($this->buildHeaders($timestamp, $body))
            ->timeout(30)
            ->post("{$this->baseUrl}/v1/payments", $payload);

        return $response->json() ?? [];
    }

    public function checkStatus(string $dompetxPaymentId): array
    {
        $timestamp = (string) time();
        $body      = '{}';
        $response  = Http::withHeaders($this->buildHeaders($timestamp, $body))
            ->timeout(15)
            ->get("{$this->baseUrl}/v1/payments/check-status/{$dompetxPaymentId}");

        return $response->json() ?? [];
    }

    public function getQrisImageUrl(string $dompetxPaymentId): string
    {
        return "{$this->baseUrl}/v1/qr/{$dompetxPaymentId}";
    }

    public function verifyWebhookSignature(string $rawBody, string $receivedSignature, string $timestamp): bool
    {
        $expected = $this->generateSignature($timestamp, $rawBody);
        return hash_equals($expected, $receivedSignature);
    }
}
