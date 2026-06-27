<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;

class CartService
{
    public function addToCart($user, $validated)
    {
        $product = Product::find($validated['product_id']);

        if (!$product->is_active) {

            return response()->json([
                'status' => false,
                'message' => 'Product tidak aktif'
            ], 400);
        }

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id
        ]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->first();

        if ($cartItem) {

            $cartItem->increment(
                'quantity',
                $validated['quantity']
            );

        } else {

            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'variant_id' => $validated['variant_id'] ?? null,
                'quantity' => $validated['quantity']
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Product berhasil ditambahkan ke cart',
            'data' => $cartItem
        ]);
    }
}