<?php

namespace App\Http\Controllers\Api\Voucher;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\UserVoucher;

class VoucherController extends Controller
{

    /*
    |--------------------------------------------------
    | BUAT VOUCHER (Admin)
    | POST /api/admin/vouchers
    |--------------------------------------------------
    */
    public function createVoucher(Request $request)
    {
        $validated = $request->validate([
            'code'                => ['required', 'unique:vouchers,code'],
            'voucher_name'        => ['required'],
            'discount_type'       => ['required', 'in:percentage,fixed'],
            'discount_value'      => ['required', 'numeric'],
            'minimum_transaction' => ['nullable', 'numeric'],
            'quota'               => ['required', 'integer'],
            'expired_at'          => ['nullable', 'date']
        ]);

        $voucher = Voucher::create([
            'code'                => $validated['code'],
            'voucher_name'        => $validated['voucher_name'],
            'discount_type'       => $validated['discount_type'],
            'discount_value'      => $validated['discount_value'],
            'minimum_transaction' => $validated['minimum_transaction'] ?? 0,
            'quota'               => $validated['quota'],
            'expired_at'          => $validated['expired_at'] ?? null,
            'is_active'           => true
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Voucher berhasil dibuat',
            'data'    => $voucher
        ]);
    }

    /*
    |--------------------------------------------------
    | GET SEMUA VOUCHER AKTIF
    | GET /api/vouchers
    |--------------------------------------------------
    */
    public function getVouchers()
    {
        $vouchers = Voucher::where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $vouchers
        ]);
    }

    /*
    |--------------------------------------------------
    | KLAIM VOUCHER
    | POST /api/vouchers/{id}/claim
    |--------------------------------------------------
    */
    public function claimVoucher(Request $request, $id)
    {
        $user    = $request->get('user');
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return response()->json([
                'status'  => false,
                'message' => 'Voucher tidak ditemukan'
            ], 404);
        }

        // Cek aktif
        if (! $voucher->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Voucher tidak aktif'
            ], 422);
        }

        // Cek expired
        if ($voucher->expired_at && now()->isAfter($voucher->expired_at)) {
            return response()->json([
                'status'  => false,
                'message' => 'Voucher sudah kedaluwarsa'
            ], 422);
        }

        // Cek quota
        if ($voucher->used >= $voucher->quota) {
            return response()->json([
                'status'  => false,
                'message' => 'Kuota voucher sudah habis'
            ], 422);
        }

        // Cek sudah diklaim
        $alreadyClaimed = UserVoucher::where('user_id', $user->id)
            ->where('voucher_id', $id)
            ->exists();

        if ($alreadyClaimed) {
            return response()->json([
                'status'  => false,
                'message' => 'Kamu sudah pernah mengklaim voucher ini'
            ], 422);
        }

        // Simpan klaim
        UserVoucher::create([
            'user_id'    => $user->id,
            'voucher_id' => $voucher->id,
            'is_used'    => false,
        ]);

        // Increment used count
        $voucher->increment('used');

        return response()->json([
            'status'  => true,
            'message' => 'Voucher berhasil diklaim! 🎉',
            'data'    => $voucher->fresh()
        ]);
    }

    /*
    |--------------------------------------------------
    | VOUCHER MILIK USER
    | GET /api/vouchers/my-vouchers
    |--------------------------------------------------
    */
    public function myVouchers(Request $request)
    {
        $user = $request->get('user');

        $claimed = UserVoucher::where('user_id', $user->id)
            ->with('voucher')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $claimed
        ]);
    }

}