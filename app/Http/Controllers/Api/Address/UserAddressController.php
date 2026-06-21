<?php

namespace App\Http\Controllers\Api\Address;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\UserAddress;

class UserAddressController extends Controller
{
    public function createAddress(Request $request)
    {
        $user = $request->get('user');

        $validated = $request->validate([
            'recipient_name' => ['required'],
            'phone_number' => ['required'],
            'full_address' => ['required'],
            'detail_address' => ['nullable'],
            'postal_code' => ['nullable'],
            'is_default' => ['nullable', 'boolean']
        ]);

        if (($validated['is_default'] ?? false) == true) {

            UserAddress::where('user_id', $user->id)
                ->update([
                    'is_default' => false
                ]);
        }

        $address = UserAddress::create([
            'user_id' => $user->id,
            'recipient_name' => $validated['recipient_name'],
            'phone_number' => $validated['phone_number'],
            'full_address' => $validated['full_address'],
            'detail_address' => $validated['detail_address'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'is_default' => $validated['is_default'] ?? false
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Alamat berhasil ditambahkan',
            'data' => $address
        ]);
    }

    public function getAddresses(Request $request)
    {
        $user = $request->get('user');

        $addresses = UserAddress::where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $addresses
        ]);
    }

    public function updateAddress(Request $request, $id)
    {
        $user = $request->get('user');

        $address = UserAddress::where('user_id', $user->id)
            ->find($id);

        if (!$address) {

            return response()->json([
                'status' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'recipient_name' => ['required'],
            'phone_number' => ['required'],
            'full_address' => ['required'],
            'detail_address' => ['nullable'],
            'postal_code' => ['nullable'],
            'is_default' => ['nullable', 'boolean']
        ]);

        if (($validated['is_default'] ?? false) == true) {

            UserAddress::where('user_id', $user->id)
                ->update([
                    'is_default' => false
                ]);
        }

        $address->update([
            'recipient_name' => $validated['recipient_name'],
            'phone_number' => $validated['phone_number'],
            'full_address' => $validated['full_address'],
            'detail_address' => $validated['detail_address'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'is_default' => $validated['is_default'] ?? false
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Alamat berhasil diupdate',
            'data' => $address
        ]);
    }

    public function deleteAddress(Request $request, $id)
    {
        $user = $request->get('user');

        $address = UserAddress::where('user_id', $user->id)
            ->find($id);

        if (!$address) {

            return response()->json([
                'status' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }

        $address->delete();

        return response()->json([
            'status' => true,
            'message' => 'Alamat berhasil dihapus'
        ]);
    }
}