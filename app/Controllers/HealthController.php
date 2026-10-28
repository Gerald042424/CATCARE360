<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

final class HealthController extends BaseController
{
    public function runtime(): ResponseInterface
    {
        return $this->response->setJSON([
            'status' => 'ok',
            'service' => 'catcare360',
        ]);
    }
}
