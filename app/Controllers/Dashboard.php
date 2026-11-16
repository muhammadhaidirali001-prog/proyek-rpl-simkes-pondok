<?php

namespace App\Controllers;

use App\Models\SantriModel;
use App\Models\KamarModel;
use App\Models\LaporanKesehatanModel;
use App\Models\PemeriksaanModel;
use App\Models\ObatModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $santriModel  = new SantriModel();
        $kamarModel   = new KamarModel();
        $laporanModel = new LaporanKesehatanModel();

        $userId   = (int) session()->get('user_id');
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');

        $isPengawasKamar = ($userRole === UserModel::ROLE_PENGAWAS_KAMAR);
        $allowedKamarIds = null;
        $namaKamarBinaan = [];

        // 1. Backend Role Enforcement untuk Pengawas Kamar
        if ($isPengawasKamar) {
            $kamarBinaan = $kamarModel->where('pengawas_id', $userId)->findAll();
            $allowedKamarIds = array_column($kamarBinaan, 'id');
            $namaKamarBinaan = array_column($kamarBinaan, 'nama_kamar');
        }

        // 2. Ambil parameter filter dari Query String GET
        $keyword       = trim((string) $this->request->getGet('keyword'));
        $kamarId       = $this->request->getGet('kamar_id');
        $status        = trim((string) $this->request->getGet('status'));
        $tanggalMulai  = trim((string) $this->request->getGet('tanggal_mulai'));
        $tanggalAkhir  = trim((string) $this->request->getGet('tanggal_akhir'));
        $periode       = trim((string) $this->request->getGet('periode')) ?: 'semua';

        // Validasi Anti-IDOR pada filter kamar untuk Pengawas Kamar
        if ($isPengawasKamar && !empty($kamarId)) {
            if (!in_array((int)$kamarId, $allowedKamarIds)) {
                $kamarId = null; // Batalkan filter jika di luar kamar binaannya
            }
        }

        // Tentukan rentang tanggal periode
        $startDate = $tanggalMulai ?: null;
        $endDate   = $tanggalAkhir ?: null;

        if ($periode === 'hari_ini') {
            $startDate = date('Y-m-d');
            $endDate   = date('Y-m-d');
        } elseif ($periode === 'minggu_ini') {
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate   = date('Y-m-d', strtotime('sunday this week'));
        } elseif ($periode === 'bulan_ini') {
            $startDate = date('Y-m-01');
            $endDate   = date('Y-m-t');
        }

        $filters = [
            'keyword'       => $keyword,
            'kamar_id'      => $kamarId,
            'status'        => $status,
            'tanggal_mulai' => $startDate,
            'tanggal_akhir' => $endDate,
            'periode'       => $periode,
        ];

        // 3. Kueri Statistik dari Database Sebenarnya (Efisien & Real-time)
        // A. Total Santri
        if ($isPengawasKamar) {
            $totalSantri = !empty($allowedKamarIds) 
                ? $santriModel->whereIn('kamar_id', $allowedKamarIds)->countAllResults() 
                : 0;
        } else {
            $totalSantri = $santriModel->countAllResults();
        }

        // B. Statistik Kesehatan (Sedang Sakit, Perawatan/Pemeriksaan, Sembuh, Dirujuk)
        $filterKamarStats = !empty($kamarId) ? [(int)$kamarId] : $allowedKamarIds;
        $stats = $laporanModel->getDashboardStats($filterKamarStats, $startDate, $endDate);

        $totalSedangSakit     = $stats['sedang_sakit'];
        $totalDalamPeriksa    = $stats['dalam_pemeriksaan'];
        $totalDalamRawat      = $stats['dalam_perawatan'];
        $totalRawatPeriksa    = $stats['rawat_periksa']; // backward compat
        $totalSembuh          = $stats['sembuh'];
        $totalDirujuk         = $stats['dirujuk'];

        // 4. Kueri Ringkasan Jumlah Kasus Berdasarkan Kamar
        $ringkasanKamar = $laporanModel->getRingkasanPerKamar($allowedKamarIds);

        // 5. Kueri Tabel Laporan Sakit Terbaru (dengan Filter & Pencarian)
        $laporanTerbaru = $laporanModel->getLaporanTerbaruDashboard($filters, $allowedKamarIds, 15);

        // 6. Opsi Pilihan Kamar untuk Form Filter
        if ($isPengawasKamar) {
            $daftarKamar = !empty($allowedKamarIds) 
                ? $kamarModel->whereIn('id', $allowedKamarIds)->orderBy('nama_kamar', 'ASC')->findAll() 
                : [];
        } else {
            $daftarKamar = $kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        $daftarStatus = [
            LaporanKesehatanModel::STATUS_DILAPORKAN_SAKIT,
            LaporanKesehatanModel::STATUS_DALAM_PEMERIKSAAN,
            LaporanKesehatanModel::STATUS_DALAM_PERAWATAN,
            LaporanKesehatanModel::STATUS_SEMBUH,
            LaporanKesehatanModel::STATUS_DIRUJUK,
        ];

        return view('dashboard/index', [
            'title'               => 'Dashboard Administrasi Kesehatan - SIMKES Santri',
            'isPengawasKamar'     => $isPengawasKamar,
            'namaKamarBinaan'     => $namaKamarBinaan,
            'totalSantri'         => $totalSantri,
            'totalSedangSakit'    => $totalSedangSakit,
            'totalDalamPeriksa'   => $totalDalamPeriksa,
            'totalDalamRawat'     => $totalDalamRawat,
            'totalRawatPeriksa'   => $totalRawatPeriksa,
            'totalSembuh'         => $totalSembuh,
            'totalDirujuk'        => $totalDirujuk,
            'ringkasanKamar'      => $ringkasanKamar,
            'laporanTerbaru'      => $laporanTerbaru,
            'daftarKamar'         => $daftarKamar,
            'daftarStatus'        => $daftarStatus,
            'filters'             => $filters,
        ]);
    }
}
