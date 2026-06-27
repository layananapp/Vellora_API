<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\Product;
use App\Mail\SellerRegisteredMail;

class StoreController extends Controller
{

    private function webRoot(string $folder = ''): string
    {
        return base_path('../' . $folder);
    }

    public function createStore(Request $request)
    {
        $user = $request->get('user');

        $validated = $request->validate([
            'store_name'   => ['required'],
            'phone_number' => ['nullable']
        ]);

        if ($user->store) {
            return response()->json([
                'status'  => false,
                'message' => 'User sudah memiliki toko'
            ], 400);
        }

        $store = Store::create([
            'user_id'      => $user->id,
            'store_name'   => $validated['store_name'],
            'phone_number' => $validated['phone_number']
        ]);

        $user->update(['role' => 'seller']);

        try {
            Mail::to($user->email)->send(
                new SellerRegisteredMail(
                    userName:  $user->name,
                    storeName: $store->store_name,
                    userEmail: $user->email
                )
            );
        } catch (\Exception $e) {
            Log::error('Seller email error: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => true,
            'message' => 'Store berhasil dibuat',
            'data'    => [
                'store' => $store,
                'user'  => $user->fresh()
            ]
        ]);
    }

    public function getMyStore(Request $request)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $store
        ]);
    }

    public function getStores()
    {
        $stores = Store::latest()->get();

        return response()->json([
            'status' => true,
            'data'   => $stores
        ]);
    }

    public function getStoreDetail($id)
    {
        $store = Store::find($id);

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $store
        ]);
    }

    public function updateStore(Request $request)
    {
        $user = $request->get('user');

        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'store_name'   => ['required'],
            'phone_number' => ['nullable', 'string'],
            'store_logo'   => ['nullable', 'image']
        ]);

        if ($request->hasFile('store_logo')) {
            $file      = $request->file('store_logo');
            $extension = $file->getClientOriginalExtension();
            $fileName  = time() . '_' . Str::slug(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            ) . '.' . $extension;

            $file->move($this->webRoot('store_logo'), $fileName);
            $store->store_logo = 'store_logo/' . $fileName;
        }

        $store->store_name   = $validated['store_name'];
        $store->phone_number = $validated['phone_number'];
        $store->save();

        return response()->json([
            'status'  => true,
            'message' => 'Store berhasil diupdate',
            'data'    => $store
        ]);
    }

    public function getStoreProducts($id)
    {
        $store = Store::find($id);

        if (!$store) {
            return response()->json([
                'status'  => false,
                'message' => 'Store tidak ditemukan'
            ], 404);
        }

        $products = Product::where('store_id', $store->id)
            ->with(['category', 'images'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $products
        ]);
    }
}
