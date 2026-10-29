<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

final class SessionController extends BaseController
{
    public function keepAlive(): ResponseInterface
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON(['status' => 'ok', 'refreshed_at' => time()]);
    }
}
