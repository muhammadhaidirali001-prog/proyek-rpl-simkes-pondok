<?php

namespace App\Models;

use CodeIgniter\Model;

class SantriModel extends Model
{
    protected $table            = 'santri';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nis',
        'nama',
        'jenis_kelamin',
        'kamar_id',
        'kelas',
        'kontak_wali',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation - NIS dicegah duplikat dengan aturan is_unique
    protected $validationRules = [
        'id'            => 'permit_empty|is_natural_no_zero',
        'nis'           => 'required|regex_match[/^[a-zA-Z0-9\s\-\.\/]+$/]|min_length[3]|max_length[30]|is_unique[santri.nis,id,{id}]',
        'nama'          => 'required|min_length[3]|max_length[100]',
        'jenis_kelamin' => 'required|in_list[Laki-laki,Perempuan]',
        'kamar_id'      => 'permit_empty|is_natural_no_zero',
        'kelas'         => 'permit_empty|max_length[50]',
        'kontak_wali'   => 'permit_empty|max_length[50]',
    ];

    protected $validationMessages = [
        'nis' => [
            'required'    => 'Nomor Induk Santri (NIS) wajib diisi.',
            'is_unique'   => 'NIS sudah terdaftar untuk santri lain. NIS tidak boleh duplikat.',
            'min_length'  => 'NIS minimal 3 karakter.',
            'max_length'  => 'NIS maksimal 30 karakter.',
        ],
        'nama' => [
            'required'   => 'Nama lengkap santri wajib diisi.',
            'min_length' => 'Nama santri minimal 3 karakter.',
            'max_length' => 'Nama santri maksimal 100 karakter.',
        ],
        'jenis_kelamin' => [
            'required' => 'Jenis kelamin santri wajib dipilih.',
            'in_list'  => 'Jenis kelamin harus Laki-laki atau Perempuan.',
        ],
    ];

    /**
     * Override method update agar primary key disertakan ke data validasi
     * sehingga aturan is_unique[...,id,{id}] dapat mengecualikan ID santri ini sendiri secara tepat
     */
    public function update($id = null, $data = null): bool
    {
        if (is_array($data) && $id !== null && !isset($data[$this->primaryKey])) {
            $data[$this->primaryKey] = $id;
        }
        return parent::update($id, $data);
    }

    /**
     * Ambil data santri beserta kamar dan pengawasnya
     */
    public function getSantriWithKamar(?int $id = null)
    {
        $builder = $this->select('santri.*, kamar.nama_kamar, users.nama as nama_pengawas')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users', 'users.id = kamar.pengawas_id', 'left');

        if ($id !== null) {
            return $builder->where('santri.id', $id)->first();
        }

        return $builder->orderBy('santri.nama', 'ASC')->findAll();
    }

    /**
     * Ambil data detail santri tunggal dengan informasi kamar dan pengawas
     */
    public function getSantriDetail(int $id): ?array
    {
        return $this->select('santri.*, kamar.nama_kamar, kamar.pengawas_id, users.nama as nama_pengawas, users.email as email_pengawas')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users', 'users.id = kamar.pengawas_id', 'left')
            ->where('santri.id', $id)
            ->first();
    }

    /**
     * Siapkan kueri santri dengan filter pencarian, filter kamar, filter kelas, dan pembatasan izin RBAC
     * Mengembalikan objek model/builder agar dapat di-chain dengan ->paginate()
     */
    public function getFilteredSantri(array $filters = [], ?array $allowedKamarIds = null)
    {
        $builder = $this->select('santri.*, kamar.nama_kamar, users.nama as nama_pengawas')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users', 'users.id = kamar.pengawas_id', 'left');

        // 1. Pembatasan akses RBAC untuk pengawas kamar
        if ($allowedKamarIds !== null) {
            if (!empty($allowedKamarIds)) {
                $builder->whereIn('santri.kamar_id', $allowedKamarIds);
            } else {
                // Jika pengawas kamar belum memiliki kamar asuhan, kembalikan kueri kosong
                $builder->where('santri.id', -1);
            }
        }

        // 2. Pencarian berdasarkan NIS atau Nama
        if (!empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);
            $builder->groupStart()
                ->like('santri.nama', $keyword)
                ->orLike('santri.nis', $keyword)
                ->groupEnd();
        }

        // 3. Filter berdasarkan Kamar
        if (!empty($filters['kamar_id'])) {
            $builder->where('santri.kamar_id', (int) $filters['kamar_id']);
        }

        // 4. Filter berdasarkan Kelas
        if (!empty($filters['kelas'])) {
            $builder->where('santri.kelas', trim($filters['kelas']));
        }

        return $builder->orderBy('santri.nama', 'ASC');
    }

    /**
     * Ambil daftar kelas unik untuk opsi filter dropdown
     */
    public function getDaftarKelas(?array $allowedKamarIds = null): array
    {
        $builder = $this->select('DISTINCT(kelas) as kelas')
            ->where('kelas IS NOT NULL', null, false)
            ->where("kelas != ''", null, false);

        if ($allowedKamarIds !== null) {
            if (!empty($allowedKamarIds)) {
                $builder->whereIn('kamar_id', $allowedKamarIds);
            } else {
                return [];
            }
        }

        $results = $builder->orderBy('kelas', 'ASC')->findAll();
        return array_column($results, 'kelas');
    }
}
