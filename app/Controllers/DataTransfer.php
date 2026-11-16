<?php

namespace App\Controllers;

use App\Models\SantriModel;
use App\Models\KamarModel;
use App\Models\LaporanKesehatanModel;
use App\Models\PemeriksaanModel;
use App\Models\PemberianObatModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DataTransfer extends BaseController
{
    protected SantriModel $santriModel;
    protected KamarModel $kamarModel;
    protected LaporanKesehatanModel $laporanModel;
    protected PemeriksaanModel $pemeriksaanModel;
    protected PemberianObatModel $pemberianObatModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->santriModel        = new SantriModel();
        $this->kamarModel         = new KamarModel();
        $this->laporanModel       = new LaporanKesehatanModel();
        $this->pemeriksaanModel   = new PemeriksaanModel();
        $this->pemberianObatModel = new PemberianObatModel();
        $this->auditModel         = new AuditLogModel();

        if ($this->request === null) {
            $this->request = service('request');
        }
        if ($this->response === null) {
            $this->response = service('response');
        }
    }

    /**
     * Dapatkan daftar ID kamar yang diizinkan untuk pengguna yang sedang login (RBAC)
     * Mengembalikan array ID untuk pengawas_kamar, atau null jika memiliki akses lintas kamar (Kesehatan/Admin)
     */
    protected function getAllowedKamarIds(): ?array
    {
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $userId   = (int) session()->get('user_id');

        if ($userRole === UserModel::ROLE_PENGAWAS_KAMAR) {
            $kamarBinaan = $this->kamarModel->where('pengawas_id', $userId)->findAll();
            return array_column($kamarBinaan, 'id');
        }

        return null;
    }

    /**
     * Sanitasi sel teks untuk mencegah Formula Injection (CSV/Excel Formula Injection)
     * Karakter berbahaya: '=', '+', '-', '@', '\t', '\r'
     */
    protected function sanitizeCell($value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string) $value;
        if (preg_match('/^[=\+\-@\t\r]/', $str)) {
            return "'" . $str;
        }
        return $str;
    }

    // =========================================================================
    // FITUR IMPOR DATA SANTRI
    // =========================================================================

    /**
     * Halaman form unggah berkas impor santri
     */
    public function impor()
    {
        $allowedKamarIds = $this->getAllowedKamarIds();
        $kamarOptions = [];

        if ($allowedKamarIds !== null) {
            if (!empty($allowedKamarIds)) {
                $kamarOptions = $this->kamarModel->whereIn('id', $allowedKamarIds)->findAll();
            }
        } else {
            $kamarOptions = $this->kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        return view('data_transfer/impor', [
            'title'           => 'Impor Data Santri - SIMKES Santri',
            'isPengawasKamar' => ($allowedKamarIds !== null),
            'kamarOptions'    => $kamarOptions,
        ]);
    }

    /**
     * Unduh berkas template impor data santri (.csv atau .xlsx)
     */
    public function templateSantri(string $format = 'xlsx')
    {
        $headers = [
            'NIS',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Nama Kamar',
            'Kelas',
            'Kontak Wali',
        ];

        // Contoh data sampel yang valid
        $sampleRows = [
            ['SNT1001', 'Ahmad Fauzi', 'Laki-laki', 'Kamar Al-Fath', '10-A', '081234567890'],
            ['SNT1002', 'Fatimah Az-Zahra', 'Perempuan', 'Kamar An-Nur', '10-B', '081298765432'],
        ];

        // Format CSV
        if (strtolower($format) === 'csv') {
            ob_start();
            $output = fopen('php://output', 'w');
            // Tulis BOM UTF-8 agar karakter bahasa dan pemisah dibaca dengan tepat oleh Excel
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            foreach ($sampleRows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            $csvData = ob_get_clean();

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="template_impor_santri.csv"')
                ->setHeader('Cache-Control', 'max-age=0')
                ->setBody($csvData);
        }

        // Format Excel (.xlsx) via PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Santri');

        // Isi Header
        $colIndex = 1;
        foreach ($headers as $header) {
            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . '1';
            $sheet->setCellValue($cellCoord, $header);
            $colIndex++;
        }

        // Format Style Header
        $lastColCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle('A1:' . $lastColCoord)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6EFD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Isi Contoh Data
        $rowIndex = 2;
        foreach ($sampleRows as $row) {
            $colIndex = 1;
            foreach ($row as $val) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
                // Format teks eksplisit untuk NIS dan kontak wali agar tidak menjadi scientific notation
                $sheet->setCellValueExplicit($cellCoord, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $colIndex++;
            }
            $rowIndex++;
        }

        // Auto-fit Column Width
        for ($i = 1; $i <= count($headers); $i++) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colString)->setAutoSize(true);
        }

        ob_start();
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        $excelData = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="template_impor_santri.xlsx"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($excelData);
    }

    /**
     * Proses pengunggahan berkas dan pembuatan pratinjau (Tahap 1 Impor)
     */
    public function prosesPratinjau()
    {
        $file = $this->request->getFile('file_import');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Silakan pilih berkas CSV atau Excel (.xlsx) yang valid untuk diunggah.');
        }

        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format berkas tidak didukung. Harap unggah berkas berformat .csv atau .xlsx.');
        }

        // Batasi ukuran berkas maksimal 5 MB
        if ($file->getSizeByUnit('mb') > 5) {
            return redirect()->back()->with('error', 'Ukuran berkas melebihi batas maksimal 5 MB.');
        }

        $filePath = $file->getTempName();

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
                $reader->setInputEncoding('UTF-8');
            }
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membaca berkas unggahan: ' . $e->getMessage());
        }

        if (empty($rawRows) || count($rawRows) < 2) {
            return redirect()->back()->with('error', 'Berkas tidak memuat baris data yang cukup (minimal baris header dan 1 baris data).');
        }

        // 1. Ekstrak & Validasi Baris Header
        $headerRow = array_shift($rawRows);
        $headerMap = [];
        foreach ($headerRow as $index => $colName) {
            $normalized = strtolower(trim((string) $colName));
            // Hapus karakter non-alfanumerik untuk pencocokan fleksibel
            $cleanKey = preg_replace('/[^a-z0-9]/', '', $normalized);

            if (in_array($cleanKey, ['nis', 'nomorinduk', 'nomorinduksantri'])) {
                $headerMap['nis'] = $index;
            } elseif (in_array($cleanKey, ['nama', 'namalengkap', 'namasantri'])) {
                $headerMap['nama'] = $index;
            } elseif (in_array($cleanKey, ['jeniskelamin', 'jk', 'gender'])) {
                $headerMap['jenis_kelamin'] = $index;
            } elseif (in_array($cleanKey, ['namakamar', 'kamar', 'asrama'])) {
                $headerMap['nama_kamar'] = $index;
            } elseif (in_array($cleanKey, ['kelas', 'tingkat'])) {
                $headerMap['kelas'] = $index;
            } elseif (in_array($cleanKey, ['kontakwali', 'nohpwali', 'teleponwali', 'kontak'])) {
                $headerMap['kontak_wali'] = $index;
            }
        }

        // Validasi kolom wajib ada
        $requiredCols = ['nis', 'nama', 'jenis_kelamin', 'nama_kamar'];
        $missingCols  = [];
        foreach ($requiredCols as $req) {
            if (!isset($headerMap[$req])) {
                $missingCols[] = strtoupper($req);
            }
        }

        if (!empty($missingCols)) {
            return redirect()->back()->with('error', 'Kolom wajib tidak ditemukan dalam berkas: ' . implode(', ', $missingCols) . '. Harap gunakan template yang telah disediakan.');
        }

        // 2. Siapkan Kamar Referensi & Pembatasan RBAC
        $allowedKamarIds = $this->getAllowedKamarIds();
        $allKamar = $this->kamarModel->findAll();
        $kamarByName = [];
        foreach ($allKamar as $k) {
            $kamarByName[strtolower(trim($k['nama_kamar']))] = $k;
        }

        // 3. Ambil seluruh NIS yang telah ada di database untuk validasi anti-duplikasi
        $existingSantri = $this->santriModel->select('nis, nama')->findAll();
        $existingNisMap = [];
        foreach ($existingSantri as $s) {
            $existingNisMap[strtoupper(trim($s['nis']))] = $s['nama'];
        }

        // 4. Validasi Baris Demi Baris
        $validRows   = [];
        $invalidRows = [];
        $seenNisInFile = [];
        $currentFileRow = 1; // Baris 1 adalah header

        foreach ($rawRows as $row) {
            $currentFileRow++;

            // Abaikan jika seluruh kolom baris kosong
            $allEmpty = true;
            foreach ($row as $val) {
                if (trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $rawNis        = isset($headerMap['nis']) ? trim((string) ($row[$headerMap['nis']] ?? '')) : '';
            $rawNama       = isset($headerMap['nama']) ? trim((string) ($row[$headerMap['nama']] ?? '')) : '';
            $rawJK         = isset($headerMap['jenis_kelamin']) ? trim((string) ($row[$headerMap['jenis_kelamin']] ?? '')) : '';
            $rawKamar      = isset($headerMap['nama_kamar']) ? trim((string) ($row[$headerMap['nama_kamar']] ?? '')) : '';
            $rawKelas      = isset($headerMap['kelas']) ? trim((string) ($row[$headerMap['kelas']] ?? '')) : '';
            $rawKontak     = isset($headerMap['kontak_wali']) ? trim((string) ($row[$headerMap['kontak_wali']] ?? '')) : '';

            $rowErrors = [];

            // A. Validasi NIS
            $upperNis = strtoupper($rawNis);
            if ($upperNis === '') {
                $rowErrors[] = 'NIS wajib diisi.';
            } elseif (strlen($upperNis) < 3 || strlen($upperNis) > 30) {
                $rowErrors[] = 'NIS harus berukuran antara 3 hingga 30 karakter.';
            } elseif (!preg_match('/^[A-Z0-9\s\-\.\/]+$/', $upperNis)) {
                $rowErrors[] = 'Format NIS hanya boleh berisi alfanumerik, spasi, tanda strip, atau titik.';
            } elseif (isset($seenNisInFile[$upperNis])) {
                $rowErrors[] = "Duplikasi NIS dalam berkas: NIS '{$upperNis}' sudah tercantum pada baris ke-{$seenNisInFile[$upperNis]}.";
            } elseif (isset($existingNisMap[$upperNis])) {
                $rowErrors[] = "Duplikasi NIS di database: NIS '{$upperNis}' sudah terdaftar atas nama '{$existingNisMap[$upperNis]}'.";
            } else {
                $seenNisInFile[$upperNis] = $currentFileRow;
            }

            // B. Validasi Nama
            if ($rawNama === '') {
                $rowErrors[] = 'Nama santri wajib diisi.';
            } elseif (strlen($rawNama) < 3 || strlen($rawNama) > 100) {
                $rowErrors[] = 'Nama santri minimal 3 karakter dan maksimal 100 karakter.';
            }

            // C. Validasi Jenis Kelamin
            $normalizedJK = '';
            $jkLower = strtolower($rawJK);
            if (in_array($jkLower, ['laki-laki', 'lakilaki', 'l', 'pria', 'ikhwan', 'laki'])) {
                $normalizedJK = 'Laki-laki';
            } elseif (in_array($jkLower, ['perempuan', 'p', 'wanita', 'akhwat'])) {
                $normalizedJK = 'Perempuan';
            } else {
                $rowErrors[] = "Jenis kelamin '{$rawJK}' tidak valid (pilih 'Laki-laki' atau 'Perempuan').";
            }

            // D. Validasi Kamar & Batasan Hak Akses (RBAC)
            $kamarId = null;
            $namaKamarCanonical = $rawKamar;
            if ($rawKamar === '') {
                $rowErrors[] = 'Nama kamar wajib diisi.';
            } else {
                $kamarKey = strtolower($rawKamar);
                if (!isset($kamarByName[$kamarKey])) {
                    $rowErrors[] = "Kamar '{$rawKamar}' tidak ditemukan dalam master data kamar.";
                } else {
                    $kamarData = $kamarByName[$kamarKey];
                    $kamarId   = (int) $kamarData['id'];
                    $namaKamarCanonical = $kamarData['nama_kamar'];

                    // Penegakan hak asuh pengawas kamar
                    if ($allowedKamarIds !== null && !in_array($kamarId, $allowedKamarIds)) {
                        $rowErrors[] = "Akses ditolak: Anda tidak memiliki wewenang untuk memasukkan santri ke '{$namaKamarCanonical}'.";
                    }
                }
            }

            // E. Validasi Kelas & Kontak
            if (strlen($rawKelas) > 50) {
                $rowErrors[] = 'Panjang kelas maksimal 50 karakter.';
            }
            if (strlen($rawKontak) > 50) {
                $rowErrors[] = 'Kontak wali maksimal 50 karakter.';
            }

            $rowData = [
                'row_num'       => $currentFileRow,
                'nis'           => $upperNis,
                'nama'          => $rawNama,
                'jenis_kelamin' => $normalizedJK ?: $rawJK,
                'kamar_id'      => $kamarId,
                'nama_kamar'    => $namaKamarCanonical,
                'kelas'         => $rawKelas,
                'kontak_wali'   => $rawKontak,
                'errors'        => $rowErrors,
                'is_valid'      => empty($rowErrors),
            ];

            if (empty($rowErrors)) {
                $validRows[] = $rowData;
            } else {
                $invalidRows[] = $rowData;
            }
        }

        // Generate token sesi unik untuk pratinjau ini
        $previewToken = bin2hex(random_bytes(16));
        session()->set('import_preview_token', $previewToken);
        session()->set('import_valid_rows_' . $previewToken, $validRows);
        session()->set('import_file_name_' . $previewToken, $file->getClientName());

        return view('data_transfer/pratinjau', [
            'title'        => 'Pratinjau Impor Data Santri - SIMKES Santri',
            'previewToken' => $previewToken,
            'fileName'     => $file->getClientName(),
            'validRows'    => $validRows,
            'invalidRows'  => $invalidRows,
            'totalRows'    => count($validRows) + count($invalidRows),
        ]);
    }

    /**
     * Konfirmasi dan eksekusi penyimpanan data valid ke MySQL (Tahap 2 Impor)
     * Menggunakan database transaction dan pencatatan audit log
     */
    public function simpanImpor()
    {
        $previewToken = (string) $this->request->getPost('preview_token');
        $savedToken   = session()->get('import_preview_token');

        if (!$previewToken || $previewToken !== $savedToken) {
            return redirect()->to(base_url('/data-transfer/impor'))
                ->with('error', 'Sesi pratinjau impor telah kedaluwarsa atau tidak valid. Silakan unggah berkas kembali.');
        }

        $validRows = session()->get('import_valid_rows_' . $previewToken) ?? [];
        $fileName  = session()->get('import_file_name_' . $previewToken) ?? 'berkas_impor';

        if (empty($validRows)) {
            return redirect()->to(base_url('/data-transfer/impor'))
                ->with('error', 'Tidak ada baris data valid yang dapat disimpan ke sistem.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $successCount = 0;
        $userId = (int) session()->get('user_id');

        foreach ($validRows as $row) {
            $dataToInsert = [
                'nis'           => $row['nis'],
                'nama'          => $row['nama'],
                'jenis_kelamin' => $row['jenis_kelamin'],
                'kamar_id'      => $row['kamar_id'],
                'kelas'         => $row['kelas'],
                'kontak_wali'   => $row['kontak_wali'],
            ];

            if ($this->santriModel->insert($dataToInsert)) {
                $successCount++;
            }
        }

        // Catat ke Audit Log
        $this->auditModel->catatAktivitas(
            $userId,
            'IMPOR_SANTRI',
            "Mengimpor {$successCount} data santri dari berkas '{$fileName}'. Seluruh baris disimpan tanpa menimpa data lama."
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to(base_url('/data-transfer/impor'))
                ->with('error', 'Terjadi kesalahan sistem saat menyimpan data santri. Transaksi database dibatalkan (rollback).');
        }

        // Bersihkan sesi pratinjau setelah berhasil
        session()->remove('import_preview_token');
        session()->remove('import_valid_rows_' . $previewToken);
        session()->remove('import_file_name_' . $previewToken);

        return redirect()->to(base_url('/santri'))
            ->with('success', "Alhamdulillah! Sebanyak {$successCount} data santri berhasil diimpor dan disimpan ke database.");
    }

    // =========================================================================
    // FITUR EKSPOR DATA SANTRI & LAPORAN KESEHATAN
    // =========================================================================

    /**
     * Halaman antarmuka opsi ekspor data terpadu
     */
    public function ekspor()
    {
        $allowedKamarIds = $this->getAllowedKamarIds();

        if ($allowedKamarIds !== null) {
            $daftarKamar = !empty($allowedKamarIds)
                ? $this->kamarModel->whereIn('id', $allowedKamarIds)->findAll()
                : [];
        } else {
            $daftarKamar = $this->kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        $daftarKelas = $this->santriModel->getDaftarKelas($allowedKamarIds);
        $daftarStatus = LaporanKesehatanModel::getDaftarStatus();

        return view('data_transfer/ekspor', [
            'title'           => 'Ekspor Data - SIMKES Santri',
            'daftarKamar'     => $daftarKamar,
            'daftarKelas'     => $daftarKelas,
            'daftarStatus'    => $daftarStatus,
            'isPengawasKamar' => ($allowedKamarIds !== null),
        ]);
    }

    /**
     * Ekspor data santri ke format CSV atau Excel (.xlsx)
     */
    public function eksporSantri()
    {
        $allowedKamarIds = $this->getAllowedKamarIds();
        $format  = strtolower((string) ($this->request->getGet('format') ?: ($_GET['format'] ?? 'xlsx')));
        $kamarId = $this->request->getGet('kamar_id') ?: ($_GET['kamar_id'] ?? null);
        $kelas   = trim((string) ($this->request->getGet('kelas') ?: ($_GET['kelas'] ?? '')));
        $keyword = trim((string) ($this->request->getGet('keyword') ?: ($_GET['keyword'] ?? '')));

        $filters = [
            'kamar_id' => $kamarId,
            'kelas'    => $kelas,
            'keyword'  => $keyword,
        ];

        // Eksekusi kueri terfilter dengan pembatasan RBAC
        $builder = $this->santriModel->getFilteredSantri($filters, $allowedKamarIds);
        $dataSantri = $builder->findAll();

        $headers = [
            'ID',
            'NIS',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Kamar',
            'Pengawas Kamar',
            'Kelas',
            'Kontak Wali',
            'Tanggal Terdaftar',
        ];

        $exportRows = [];
        foreach ($dataSantri as $s) {
            $exportRows[] = [
                $this->sanitizeCell($s['id']),
                $this->sanitizeCell($s['nis']),
                $this->sanitizeCell($s['nama']),
                $this->sanitizeCell($s['jenis_kelamin']),
                $this->sanitizeCell($s['nama_kamar'] ?? '-'),
                $this->sanitizeCell($s['nama_pengawas'] ?? '-'),
                $this->sanitizeCell($s['kelas'] ?? '-'),
                $this->sanitizeCell($s['kontak_wali'] ?? '-'),
                $this->sanitizeCell($s['created_at'] ? date('d/m/Y H:i', strtotime($s['created_at'])) : '-'),
            ];
        }

        // Catat Audit Log
        $this->auditModel->catatAktivitas(
            (int) session()->get('user_id'),
            'EKSPOR_SANTRI',
            "Mengekspor " . count($exportRows) . " data santri ke format " . strtoupper($format) . "."
        );

        $filename = 'data_santri_' . date('Ymd_His');

        if ($format === 'csv') {
            return $this->downloadCsv($filename . '.csv', $headers, $exportRows);
        }

        return $this->downloadXlsx($filename . '.xlsx', 'Data Santri', $headers, $exportRows);
    }

    /**
     * Ekspor laporan kesehatan santri ke format CSV atau Excel (.xlsx)
     * Menerapkan masking kolom rekam medis sensitif jika pengguna adalah pengawas_kamar
     */
    public function eksporKesehatan()
    {
        $allowedKamarIds = $this->getAllowedKamarIds();
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $isPengawasKamar = ($allowedKamarIds !== null);

        $format       = strtolower((string) ($this->request->getGet('format') ?: ($_GET['format'] ?? 'xlsx')));
        $kamarId      = $this->request->getGet('kamar_id') ?: ($_GET['kamar_id'] ?? null);
        $status       = trim((string) ($this->request->getGet('status') ?: ($_GET['status'] ?? '')));
        $tanggalMulai = trim((string) ($this->request->getGet('tanggal_mulai') ?: ($_GET['tanggal_mulai'] ?? '')));
        $tanggalAkhir = trim((string) ($this->request->getGet('tanggal_akhir') ?: ($_GET['tanggal_akhir'] ?? '')));

        // Bangun kueri laporan
        $builder = $this->laporanModel->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas,
                kamar.id as kamar_id, kamar.nama_kamar,
                pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left');

        // RBAC Boundary
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) {
                $builder->where('laporan_kesehatan.id', -1);
            } else {
                $builder->whereIn('santri.kamar_id', $allowedKamarIds);
            }
        }

        if (!empty($kamarId)) {
            $builder->where('santri.kamar_id', (int) $kamarId);
        }

        if (!empty($status)) {
            if ($status === 'Dilaporkan Sakit') {
                $builder->whereIn('laporan_kesehatan.status', ['Dilaporkan Sakit', 'Baru']);
            } elseif ($status === 'Dirujuk') {
                $builder->whereIn('laporan_kesehatan.status', ['Dirujuk', 'Rujukan RS']);
            } else {
                $builder->where('laporan_kesehatan.status', $status);
            }
        }

        if (!empty($tanggalMulai)) {
            $builder->where('laporan_kesehatan.tanggal_mulai_sakit >=', $tanggalMulai . ' 00:00:00');
        }
        if (!empty($tanggalAkhir)) {
            $builder->where('laporan_kesehatan.tanggal_mulai_sakit <=', $tanggalAkhir . ' 23:59:59');
        }

        $laporanList = $builder->orderBy('laporan_kesehatan.tanggal_mulai_sakit', 'DESC')->findAll();

        // Siapkan header kolom ekspor
        if ($isPengawasKamar) {
            // Pengawas kamar: kolom rekam medis klinis dokter & obat disembunyikan/dibatasi
            $headers = [
                'ID Kasus',
                'NIS Santri',
                'Nama Santri',
                'Jenis Kelamin',
                'Kamar',
                'Kelas',
                'Tanggal Mulai Sakit',
                'Gejala Awal Dilaporkan',
                'Status Kasus',
                'Tanggal Sembuh',
                'Pelapor',
                'Catatan Umum',
            ];
        } else {
            // Pengawas kesehatan & admin: rekam medis klinis lengkap
            $headers = [
                'ID Kasus',
                'NIS Santri',
                'Nama Santri',
                'Jenis Kelamin',
                'Kamar',
                'Kelas',
                'Tanggal Mulai Sakit',
                'Gejala Awal Dilaporkan',
                'Tanggal Pemeriksaan Terakhir',
                'Dokter / Pemeriksa',
                'Diagnosis Klinis',
                'Tindakan Medis',
                'Terapi / Obat Diberikan',
                'Status Kasus',
                'Tanggal Sembuh',
                'Pelapor',
                'Catatan Kasus',
            ];
        }

        $exportRows = [];
        foreach ($laporanList as $lap) {
            $tglMulai = $lap['tanggal_mulai_sakit'] ? date('d/m/Y H:i', strtotime($lap['tanggal_mulai_sakit'])) : '-';
            $tglSembuh = $lap['tanggal_sembuh'] ? date('d/m/Y', strtotime($lap['tanggal_sembuh'])) : '-';

            if ($isPengawasKamar) {
                $exportRows[] = [
                    $this->sanitizeCell($lap['id']),
                    $this->sanitizeCell($lap['nis']),
                    $this->sanitizeCell($lap['nama_santri']),
                    $this->sanitizeCell($lap['jenis_kelamin']),
                    $this->sanitizeCell($lap['nama_kamar'] ?? '-'),
                    $this->sanitizeCell($lap['kelas'] ?? '-'),
                    $this->sanitizeCell($tglMulai),
                    $this->sanitizeCell($lap['gejala']),
                    $this->sanitizeCell($lap['status']),
                    $this->sanitizeCell($tglSembuh),
                    $this->sanitizeCell($lap['nama_pelapor'] ?? '-'),
                    $this->sanitizeCell($lap['catatan'] ?? '-'),
                ];
            } else {
                // Ambil data pemeriksaan klinis medis & obat jika user adalah pengawas kesehatan atau admin
                $pemeriksaan = $this->pemeriksaanModel->getPemeriksaanByLaporan((int) $lap['id']);
                $diagList    = [];
                $tindakanList= [];
                $dokterList  = [];
                $tglPeriksa  = '-';
                $obatList    = [];

                if (!empty($pemeriksaan)) {
                    $first = $pemeriksaan[0];
                    $tglPeriksa = $first['tanggal_periksa'] ? date('d/m/Y H:i', strtotime($first['tanggal_periksa'])) : '-';
                    foreach ($pemeriksaan as $p) {
                        if (!empty($p['diagnosis_atau_dugaan'])) $diagList[] = $p['diagnosis_atau_dugaan'];
                        if (!empty($p['tindakan'])) $tindakanList[] = $p['tindakan'];
                        if (!empty($p['nama_pemeriksa'])) $dokterList[] = $p['nama_pemeriksa'];

                        $obatData = $this->pemberianObatModel->getObatByPemeriksaan((int) $p['id']);
                        foreach ($obatData as $o) {
                            $obatList[] = "{$o['nama_obat']} ({$o['dosis']}, {$o['aturan_pakai']})";
                        }
                    }
                }

                $exportRows[] = [
                    $this->sanitizeCell($lap['id']),
                    $this->sanitizeCell($lap['nis']),
                    $this->sanitizeCell($lap['nama_santri']),
                    $this->sanitizeCell($lap['jenis_kelamin']),
                    $this->sanitizeCell($lap['nama_kamar'] ?? '-'),
                    $this->sanitizeCell($lap['kelas'] ?? '-'),
                    $this->sanitizeCell($tglMulai),
                    $this->sanitizeCell($lap['gejala']),
                    $this->sanitizeCell($tglPeriksa),
                    $this->sanitizeCell(!empty($dokterList) ? implode('; ', array_unique($dokterList)) : '-'),
                    $this->sanitizeCell(!empty($diagList) ? implode('; ', array_unique($diagList)) : '-'),
                    $this->sanitizeCell(!empty($tindakanList) ? implode('; ', array_unique($tindakanList)) : '-'),
                    $this->sanitizeCell(!empty($obatList) ? implode('; ', array_unique($obatList)) : '-'),
                    $this->sanitizeCell($lap['status']),
                    $this->sanitizeCell($tglSembuh),
                    $this->sanitizeCell($lap['nama_pelapor'] ?? '-'),
                    $this->sanitizeCell($lap['catatan'] ?? '-'),
                ];
            }
        }

        // Catat ke Audit Log
        $this->auditModel->catatAktivitas(
            (int) session()->get('user_id'),
            'EKSPOR_KESEHATAN',
            "Mengekspor " . count($exportRows) . " data laporan kesehatan ke format " . strtoupper($format) . " (Hak akses: {$userRole})."
        );

        $filename = 'laporan_kesehatan_' . date('Ymd_His');

        if ($format === 'csv') {
            return $this->downloadCsv($filename . '.csv', $headers, $exportRows);
        }

        return $this->downloadXlsx($filename . '.xlsx', 'Rekapitulasi Kesehatan', $headers, $exportRows);
    }

    // =========================================================================
    // HELPER PENGUNDUHAN BERKAS
    // =========================================================================

    /**
     * Helper stream berkas CSV dengan UTF-8 BOM
     */
    protected function downloadCsv(string $filename, array $headers, array $rows)
    {
        ob_start();
        $output = fopen('php://output', 'w');
        // Tulis BOM UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    /**
     * Helper stream berkas Excel (.xlsx) dengan PhpSpreadsheet
     */
    protected function downloadXlsx(string $filename, string $sheetTitle, array $headers, array $rows)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        // Tulis Header
        $colIndex = 1;
        foreach ($headers as $header) {
            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . '1';
            $sheet->setCellValue($cellCoord, $header);
            $colIndex++;
        }

        // Format Style Header
        $lastColCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle('A1:' . $lastColCoord)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '198754'], // Hijau Poskestren Sehat
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Tulis Baris Data
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($row as $val) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
                $sheet->setCellValueExplicit($cellCoord, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $colIndex++;
            }
            $rowIndex++;
        }

        // Auto-fit Lebar Kolom
        for ($i = 1; $i <= count($headers); $i++) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colString)->setAutoSize(true);
        }

        ob_start();
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }
}
