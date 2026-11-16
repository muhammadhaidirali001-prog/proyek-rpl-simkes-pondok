<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    /**
     * Mencegah pengguna yang sudah login mengakses kembali halaman /login atau /register
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('isLoggedIn')) {
            return redirect()->to(base_url('/dashboard'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request
    }
}
