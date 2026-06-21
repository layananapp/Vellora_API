<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;

class ReportService
{
    /**
     * Simpan laporan baru beserta foto bukti (kalau ada).
     *
     * @param User $user
     * @param array $validated  ['jenis_laporan', 'judul', 'deskripsi']
     * @param array|null $files array of UploadedFile (dari $request->file('foto'))
     */
    public function createReport(User $user, array $validated, ?array $files = null): Report
    {
        $fotoPaths = [];

        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file) {
                    // disimpan di storage/app/public/reports
                    $fotoPaths[] = $file->store('reports', 'public');
                }
            }
        }

        return Report::create([
            'user_id'       => $user->id,
            'jenis_laporan' => $validated['jenis_laporan'],
            'judul'         => $validated['judul'],
            'deskripsi'     => $validated['deskripsi'],
            'foto_bukti'    => $fotoPaths,
            'status'        => 'pending',
        ]);
    }

    /**
     * Ambil riwayat laporan milik user yang login.
     */
    public function getUserReports(User $user)
    {
        return Report::where('user_id', $user->id)
            ->latest()
            ->get();
    }
}