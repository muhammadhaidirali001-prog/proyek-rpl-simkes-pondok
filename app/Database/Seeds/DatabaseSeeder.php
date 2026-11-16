<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. DATA PENGGUNA (USERS) - Password di-hash BCRYPT
        $now = date('Y-m-d H:i:s');
        $defaultPasswordHash = password_hash('PesantrenSehat2026!', PASSWORD_BCRYPT);

        $users = [
            [
                'id'            => 1,
                'nama'          => 'Ustadz Ahmad Fauzi, S.Pd.I',
                'email'         => 'admin@simkes.pesantren.id',
                'password_hash' => $defaultPasswordHash,
                'role'          => 'Admin',
                'status'        => 'Aktif',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 2,
                'nama'          => 'dr. Fatimah Zahra',
                'email'         => 'dr.fatimah@simkes.pesantren.id',
                'password_hash' => $defaultPasswordHash,
                'role'          => 'Dokter',
                'status'        => 'Aktif',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 3,
                'nama'          => 'Ns. Rahmat Hidayat, S.Kep',
                'email'         => 'perawat.rahmat@simkes.pesantren.id',
                'password_hash' => $defaultPasswordHash,
                'role'          => 'Petugas Kesehatan',
                'status'        => 'Aktif',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 4,
                'nama'          => 'Ustadz Hilman Hakim',
                'email'         => 'pengawas.hilman@simkes.pesantren.id',
                'password_hash' => $defaultPasswordHash,
                'role'          => 'Pengawas Kamar',
                'status'        => 'Aktif',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 5,
                'nama'          => 'Ustadzah Siti Aminah',
                'email'         => 'pengawas.aminah@simkes.pesantren.id',
                'password_hash' => $defaultPasswordHash,
                'role'          => 'Pengawas Kamar',
                'status'        => 'Aktif',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        foreach ($users as $user) {
            $db->table('users')->ignore(true)->insert($user);
        }

        // 2. DATA KAMAR / ASRAMA
        $kamar = [
            ['id' => 1, 'nama_kamar' => 'Kamar Abu Bakar Ash-Shiddiq (Putra)', 'pengawas_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nama_kamar' => 'Kamar Umar bin Khattab (Putra)', 'pengawas_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'nama_kamar' => 'Kamar Khadijah Al-Kubra (Putri)', 'pengawas_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'nama_kamar' => 'Kamar Aisyah binti Abu Bakar (Putri)', 'pengawas_id' => 5, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($kamar as $k) {
            $db->table('kamar')->ignore(true)->insert($k);
        }

        // 3. DATA SANTRI DUMMY (Fiktif, NIS unik)
        $santri = [
            [
                'id'            => 1,
                'nis'           => 'SNTR-2026-001',
                'nama'          => 'Muhammad Zaidan Akbar',
                'jenis_kelamin' => 'Laki-laki',
                'kamar_id'      => 1,
                'kelas'         => 'Kelas 7A - Wustha',
                'kontak_wali'   => '0812-3456-7801 (Bapak Hendra)',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 2,
                'nis'           => 'SNTR-2026-002',
                'nama'          => 'Farhan Al-Ghifari',
                'jenis_kelamin' => 'Laki-laki',
                'kamar_id'      => 1,
                'kelas'         => 'Kelas 8B - Wustha',
                'kontak_wali'   => '0812-3456-7802 (Bapak Suryo)',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 3,
                'nis'           => 'SNTR-2026-003',
                'nama'          => 'Bilal Ramadhan',
                'jenis_kelamin' => 'Laki-laki',
                'kamar_id'      => 2,
                'kelas'         => 'Kelas 9A - Wustha',
                'kontak_wali'   => '0812-3456-7803 (Bapak Ridwan)',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 4,
                'nis'           => 'SNTR-2026-004',
                'nama'          => 'Nayla Syakira Putri',
                'jenis_kelamin' => 'Perempuan',
                'kamar_id'      => 3,
                'kelas'         => 'Kelas 7B - Wustha',
                'kontak_wali'   => '0812-3456-7804 (Ibu Maryam)',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 5,
                'nis'           => 'SNTR-2026-005',
                'nama'          => 'Aisyah Hafizhah Zahra',
                'jenis_kelamin' => 'Perempuan',
                'kamar_id'      => 3,
                'kelas'         => 'Kelas 8A - Wustha',
                'kontak_wali'   => '0812-3456-7805 (Ibu Rahma)',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 6,
                'nis'           => 'SNTR-2026-006',
                'nama'          => 'Salma Mumtazah',
                'jenis_kelamin' => 'Perempuan',
                'kamar_id'      => 4,
                'kelas'         => 'Kelas 9B - Wustha',
                'kontak_wali'   => '0812-3456-7806 (Ibu Dewi)',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        foreach ($santri as $s) {
            $db->table('santri')->ignore(true)->insert($s);
        }

        // 4. DATA OBAT-OBATAN INVENTARIS
        $obat = [
            [
                'id'                   => 1,
                'nama_obat'            => 'Paracetamol 500mg',
                'informasi_penggunaan' => 'Analgetik & antipiretik untuk menurunkan demam dan meredakan nyeri ringan.',
                'stok'                 => 120,
                'satuan'               => 'Tablet',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'id'                   => 2,
                'nama_obat'            => 'Antasida Doen',
                'informasi_penggunaan' => 'Meredakan gejala sakit maag, nyeri lambung, dan kembung.',
                'stok'                 => 85,
                'satuan'               => 'Tablet Kunyah',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'id'                   => 3,
                'nama_obat'            => 'Oralit Sachet 200ml',
                'informasi_penggunaan' => 'Pengganti cairan tubuh untuk mencegah dehidrasi pada santri diare.',
                'stok'                 => 60,
                'satuan'               => 'Sachet',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'id'                   => 4,
                'nama_obat'            => 'Amoxicillin 500mg',
                'informasi_penggunaan' => 'Antibiotik resep dokter untuk infeksi bakteri saluran pernapasan.',
                'stok'                 => 6, // Contoh stok kritis (< 10)
                'satuan'               => 'Kapsul',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'id'                   => 5,
                'nama_obat'            => 'Minyak Kayu Putih 60ml',
                'informasi_penggunaan' => 'Minyak oles pereda perut kembung, masuk angin, dan gigitan serangga.',
                'stok'                 => 24,
                'satuan'               => 'Botol',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'id'                   => 6,
                'nama_obat'            => 'Povidone Iodine 10% (Betadine)',
                'informasi_penggunaan' => 'Antiseptik luka lecet atau luka luar pada santri.',
                'stok'                 => 14,
                'satuan'               => 'Botol',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ];

        foreach ($obat as $o) {
            $db->table('obat')->ignore(true)->insert($o);
        }

        // 5. DATA LAPORAN KESEHATAN
        $laporan = [
            [
                'id'                  => 1,
                'santri_id'           => 1, // Muhammad Zaidan Akbar
                'pelapor_id'          => 4, // Hilman (Pengawas Kamar)
                'tanggal_mulai_sakit' => date('Y-m-d', strtotime('-2 days')),
                'gejala'              => 'Suhu badan tinggi 38.6°C, sakit kepala, batuk berdahak dan pilek.',
                'status'              => 'Dalam Perawatan',
                'tanggal_sembuh'      => null,
                'catatan'             => 'Istirahat di ruang Poskestren putra, diberi minum air hangat cukup.',
                'created_at'          => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at'          => $now,
            ],
            [
                'id'                  => 2,
                'santri_id'           => 2, // Farhan Al-Ghifari
                'pelapor_id'          => 4, // Hilman
                'tanggal_mulai_sakit' => date('Y-m-d', strtotime('-1 days')),
                'gejala'              => 'Nyeri lambung melilit, mual, lemas setelah puasa sunnah dan telat sahur.',
                'status'              => 'Dalam Perawatan',
                'tanggal_sembuh'      => null,
                'catatan'             => 'Diberikan bubur hangat dan obat lambung.',
                'created_at'          => date('Y-m-d H:i:s', strtotime('-1 days')),
                'updated_at'          => $now,
            ],
            [
                'id'                  => 3,
                'santri_id'           => 4, // Nayla Syakira Putri
                'pelapor_id'          => 5, // Siti Aminah
                'tanggal_mulai_sakit' => date('Y-m-d'),
                'gejala'              => 'Tenggorokan nyeri saat menelan, batuk kering ringan.',
                'status'              => 'Baru',
                'tanggal_sembuh'      => null,
                'catatan'             => 'Menunggu giliran pemeriksaan sore hari.',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'id'                  => 4,
                'santri_id'           => 3, // Bilal Ramadhan
                'pelapor_id'          => 4, // Hilman
                'tanggal_mulai_sakit' => date('Y-m-d', strtotime('-5 days')),
                'gejala'              => 'Flu ringan dan bersin-bersin.',
                'status'              => 'Sembuh',
                'tanggal_sembuh'      => date('Y-m-d', strtotime('-1 days')),
                'catatan'             => 'Kondisi sudah pulih total dan kembali aktif mengaji di kelas.',
                'created_at'          => date('Y-m-d H:i:s', strtotime('-5 days')),
                'updated_at'          => date('Y-m-d H:i:s', strtotime('-1 days')),
            ],
        ];

        foreach ($laporan as $l) {
            $db->table('laporan_kesehatan')->ignore(true)->insert($l);
        }

        // 6. DATA PEMERIKSAAN MEDIS (Diverifikasi oleh Tenaga Medis Resmi)
        $pemeriksaan = [
            [
                'id'                      => 1,
                'laporan_id'              => 1, // Laporan Zaidan
                'pemeriksa_id'            => 2, // dr. Fatimah Zahra
                'tanggal_periksa'         => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
                'diagnosis_atau_dugaan'   => 'Faringitis Akut & Febris ec Viral Infection',
                'tindakan'                => 'Pemberian antipiretik, vitamin C, istirahat tirah baring 2 hari.',
                'catatan'                 => 'Jika dalam 3 hari demam tidak turun di atas 39°C, evaluasi cek darah tepi.',
                'diverifikasi_oleh_medis' => 1,
                'created_at'              => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
                'updated_at'              => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
            ],
            [
                'id'                      => 2,
                'laporan_id'              => 2, // Laporan Farhan
                'pemeriksa_id'            => 3, // Ns. Rahmat Hidayat
                'tanggal_periksa'         => date('Y-m-d H:i:s', strtotime('-1 days +1 hours')),
                'diagnosis_atau_dugaan'   => 'Dispepsia Fungsional / Gastritis Ringan',
                'tindakan'                => 'Edukasi pola makan teratur, antasida kunyah, kompres hangat perut.',
                'catatan'                 => 'Hindari makanan pedas dan asam selama masa pemulihan.',
                'diverifikasi_oleh_medis' => 1,
                'created_at'              => date('Y-m-d H:i:s', strtotime('-1 days +1 hours')),
                'updated_at'              => date('Y-m-d H:i:s', strtotime('-1 days +1 hours')),
            ],
            [
                'id'                      => 3,
                'laporan_id'              => 4, // Laporan Bilal (sudah sembuh)
                'pemeriksa_id'            => 3, // Ns. Rahmat Hidayat
                'tanggal_periksa'         => date('Y-m-d H:i:s', strtotime('-5 days +2 hours')),
                'diagnosis_atau_dugaan'   => 'Common Cold (Rinitis Akut)',
                'tindakan'                => 'Istirahat dan hidrasi cairan cukup.',
                'catatan'                 => 'Pemeriksaan kontrol tuntas.',
                'diverifikasi_oleh_medis' => 1,
                'created_at'              => date('Y-m-d H:i:s', strtotime('-5 days +2 hours')),
                'updated_at'              => date('Y-m-d H:i:s', strtotime('-5 days +2 hours')),
            ],
        ];

        foreach ($pemeriksaan as $p) {
            $db->table('pemeriksaan')->ignore(true)->insert($p);
        }

        // 7. DATA PEMBERIAN OBAT (Resep Resmi Terverifikasi)
        $pemberianObat = [
            [
                'id'                      => 1,
                'pemeriksaan_id'          => 1,
                'obat_id'                 => 1, // Paracetamol
                'dosis'                   => '500 mg',
                'aturan_pakai'            => '3 x 1 tablet sesudah makan',
                'durasi'                  => '3 hari (bila demam)',
                'instruksi_petugas'       => 'Minum air putih minimal 2 gelas setelah minum obat.',
                'diverifikasi_oleh_medis' => 1,
                'created_at'              => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
                'updated_at'              => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
            ],
            [
                'id'                      => 2,
                'pemeriksaan_id'          => 2,
                'obat_id'                 => 2, // Antasida
                'dosis'                   => '1 tablet',
                'aturan_pakai'            => '3 x 1 tablet dikunyah 30 menit sebelum makan',
                'durasi'                  => '3 hari',
                'instruksi_petugas'       => 'Kunyah sampai halus sebelum ditelan.',
                'diverifikasi_oleh_medis' => 1,
                'created_at'              => date('Y-m-d H:i:s', strtotime('-1 days +1 hours')),
                'updated_at'              => date('Y-m-d H:i:s', strtotime('-1 days +1 hours')),
            ],
        ];

        foreach ($pemberianObat as $po) {
            $db->table('pemberian_obat')->ignore(true)->insert($po);
        }

        // 8. DATA RIWAYAT STATUS KESEHATAN (Kronologi tetap utuh meskipun status berubah menjadi sembuh)
        $riwayatStatus = [
            [
                'id'          => 1,
                'laporan_id'  => 1,
                'status'      => 'Baru',
                'tanggal'     => date('Y-m-d H:i:s', strtotime('-2 days')),
                'catatan'     => 'Laporan awal santri demam diterima dari pengawas kamar.',
                'pengguna_id' => 4,
                'created_at'  => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id'          => 2,
                'laporan_id'  => 1,
                'status'      => 'Dalam Perawatan',
                'tanggal'     => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
                'catatan'     => 'Santri dipindahkan ke poskestren untuk observasi suhu tubuh.',
                'pengguna_id' => 2,
                'created_at'  => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
            ],
            [
                'id'          => 3,
                'laporan_id'  => 4,
                'status'      => 'Baru',
                'tanggal'     => date('Y-m-d H:i:s', strtotime('-5 days')),
                'catatan'     => 'Laporan gejala flu santri.',
                'pengguna_id' => 4,
                'created_at'  => date('Y-m-d H:i:s', strtotime('-5 days')),
            ],
            [
                'id'          => 4,
                'laporan_id'  => 4,
                'status'      => 'Dalam Perawatan',
                'tanggal'     => date('Y-m-d H:i:s', strtotime('-5 days +2 hours')),
                'catatan'     => 'Istirahat dan minum multivitamin.',
                'pengguna_id' => 3,
                'created_at'  => date('Y-m-d H:i:s', strtotime('-5 days +2 hours')),
            ],
            [
                'id'          => 5,
                'laporan_id'  => 4,
                'status'      => 'Sembuh',
                'tanggal'     => date('Y-m-d H:i:s', strtotime('-1 days')),
                'catatan'     => 'Gejala tuntas, suhu normal 36.6°C, santri dinyatakan sembuh total.',
                'pengguna_id' => 3,
                'created_at'  => date('Y-m-d H:i:s', strtotime('-1 days')),
            ],
        ];

        foreach ($riwayatStatus as $rs) {
            $db->table('riwayat_status')->ignore(true)->insert($rs);
        }

        // 9. AUDIT LOG (Pencatatan aktivitas)
        $auditLogs = [
            [
                'id'               => 1,
                'pengguna_id'      => 1,
                'aksi'             => 'INITIALIZE_SYSTEM',
                'waktu'            => $now,
                'detail_perubahan' => 'Inisialisasi sistem SIMKES Santri dan data referensi awal poskestren.',
                'created_at'       => $now,
            ],
            [
                'id'               => 2,
                'pengguna_id'      => 4,
                'aksi'             => 'INPUT_LAPORAN_KESEHATAN',
                'waktu'            => date('Y-m-d H:i:s', strtotime('-2 days')),
                'detail_perubahan' => 'Input laporan sakit santri Muhammad Zaidan Akbar (Kamar Abu Bakar).',
                'created_at'       => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id'               => 3,
                'pengguna_id'      => 2,
                'aksi'             => 'PEMERIKSAAN_MEDIS',
                'waktu'            => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
                'detail_perubahan' => 'dr. Fatimah melakukan pemeriksaan klinis dan peresepan Paracetamol untuk laporan #1.',
                'created_at'       => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
            ],
        ];

        foreach ($auditLogs as $al) {
            $db->table('audit_log')->ignore(true)->insert($al);
        }
    }
}
