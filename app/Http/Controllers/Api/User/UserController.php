<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdatePhoneRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user      = $request->get('user');
        $validated = $request->validated();

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Profile berhasil diupdate',
            'data'    => $user,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user      = $request->get('user');
        $validated = $request->validated();

        if (!Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Password lama salah',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Password berhasil diubah',
        ]);
    }

    public function uploadProfilePhoto(Request $request)
    {
        $user = $request->get('user');

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png'],
        ]);

        $file     = $request->file('profile_photo');
        $fileName = time() . '_' . $file->getClientOriginalName();

        $file->move(public_path('profile_photos'), $fileName);

        $user->update([
            'profile_photo' => 'profile_photos/' . $fileName,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Foto profile berhasil diupload',
            'data'    => [
                'profile_photo' => $user->profile_photo,
            ],
        ]);
    }

    public function getAllUsers()
    {
        $users = \App\Models\User::with('store')->get();

        return response()->json([
            'status' => true,
            'data'   => $users,
        ]);
    }

    public function updatePhone(UpdatePhoneRequest $request)
    {
        $user      = $request->get('user');
        $validated = $request->validated();

        $user->update([
            'phone_number' => $validated['phone_number'],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Nomor handphone berhasil diupdate',
            'data'    => $user,
        ]);
    }

    public function getUserDetail($id)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $user,
        ]);
    }

    public function suspendUser($id)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $user->update([
            'is_suspended' => true,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'User berhasil disuspend',
        ]);
    }

    public function unsuspendUser($id)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $user->update([
            'is_suspended' => false,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'User berhasil diaktifkan',
        ]);
    }

    public function deleteUser($id)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status'  => true,
            'message' => 'User berhasil dihapus',
        ]);
    }
}
