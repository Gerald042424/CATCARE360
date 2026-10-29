<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthSession extends BaseConfig
{
    public int $staffIdleSeconds = 900;
    public int $adopterIdleSeconds = 1800;
    public int $volunteerIdleSeconds = 1800;
    public int $absoluteSeconds = 28800;
    public int $warningSeconds = 120;
}
