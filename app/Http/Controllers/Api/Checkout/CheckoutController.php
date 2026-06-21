<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OrderService;

class CheckoutController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /*
    |--------------------------------------------------
    | POST /api/checkout
    |--------------------------------------------------
    */
    public function checkout(Request $request)
    {
        $user = $request->get('user');

        $validated = $request->validate([
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.name'   => ['required', 'string'],
            'items.*.price'  => ['required', 'numeric'],
            'items.*.qty'    => ['required', 'integer', 'min:1'],
            'items.*.weight' => ['nullable', 'numeric'],
            'items.*.variant'=> ['nullable', 'string'],
            'items.*.image'  => ['nullable', 'string'],
            'items.*.store_id' => ['nullable', 'integer'],
            'items.*.shop'   => ['nullable', 'array'],
            'address_id'     => ['required', 'integer'],
            'payment_method' => ['required', 'in:COD,QRIS,Transfer Bank'],
            'bank_name'      => ['nullable', 'string'],
            'voucher_code'   => ['nullable', 'string'],
        ]);

        return $this->orderService->checkout($user, $validated);
    }
}