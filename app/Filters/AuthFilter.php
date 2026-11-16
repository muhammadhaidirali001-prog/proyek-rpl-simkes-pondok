<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Memeriksa apakah pengguna telah terotentikasi (login)
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return redirect()->to(base_url('/login'))
                ->with('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request
    }
}
