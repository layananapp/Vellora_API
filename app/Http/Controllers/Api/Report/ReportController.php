<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * POST /api/reports
     * Kirim laporan baru (jenis_laporan, judul, deskripsi, foto[] opsional)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_laporan' => ['required', 'string', 'max:100'],
            'judul'         => ['required', 'string', 'max:150'],
            'deskripsi'     => ['required', 'string', 'min:10'],
            'foto.*'        => ['nullable', 'image', 'max:4096'], // max 4MB per foto
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $report = $this->reportService->createReport(
            $request->user(),
            $validator->validated(),
            $request->file('foto') // bisa null kalau gak ada foto
        );

        return response()->json([
            'status'  => true,
            'message' => 'Laporan berhasil dikirim',
            'data'    => $report,
        ], 201);
    }

    /**
     * GET /api/reports
     * Riwayat laporan milik user yang login (opsional, buat halaman "Laporan Saya")
     */
    public function index(Request $request)
    {
        $reports = $this->reportService->getUserReports($request->user());

        return response()->json([
            'status' => true,
            'data'   => $reports,
        ]);
    }

    /**
     * GET /api/admin/reports
     * Ambil semua laporan keluhan oleh admin
     */
    public function getAllReports()
    {
        $reports = $this->reportService->getAllReports();

        return response()->json([
            'status' => true,
            'data'   => $reports,
        ]);
    }

    /**
     * PUT /api/admin/reports/{id}/status
     * Update status laporan keluhan oleh admin
     */
    public function updateReportStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,resolved,rejected']
        ]);

        $report = $this->reportService->updateReportStatus($id, $request->status);

        if (!$report) {
            return response()->json([
                'status'  => false,
                'message' => 'Laporan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Status laporan berhasil diperbarui',
            'data'    => $report
        ]);
    }
}