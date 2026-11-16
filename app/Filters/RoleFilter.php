<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Memeriksa hak akses pengguna berdasarkan peran (Role-Based Access Control)
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Pastikan sudah login
        if (!$session->get('isLoggedIn')) {
            return redirect()->to(base_url('/login'))
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $userRole = UserModel::normalizeRole($session->get('role') ?? '');

        // 2. Administrator memiliki akses ke seluruh fitur (Superadmin)
        if ($userRole === UserModel::ROLE_ADMIN) {
            return;
        }

        // 3. Periksa kecocokan role terhadap argumen filter yang diizinkan
        if (!empty($arguments)) {
            $allowedRoles = array_map([UserModel::class, 'normalizeRole'], $arguments);

            if (!in_array($userRole, $allowedRoles)) {
                // Akses ditolak secara tegas di backend
                return redirect()->to(base_url('/dashboard'))
                    ->with('error', 'Akses ditolak: Peran Anda (' . esc($session->get('role_display')) . ') tidak memiliki wewenang untuk mengakses halaman ini.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request
    }
}
