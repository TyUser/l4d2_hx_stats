<?php
// SPDX-License-Identifier: GPL-3.0-only

declare(strict_types=1);
error_reporting(E_ALL);

if (!defined('HX_STATS')) {
    exit('Direct access protection');
}

class AppConfig
{
    // Настройки базы данных
    public string $host = 'localhost';              // This is normally set to localhost
    public string $user = '';                       // Database username
    public string $password = '';                   // Database password
    public string $database = '';                   // Database name

    // Настройки сервера
    public int $cache_time = 10;
    public string $ip_l4d2 = 'ip address l4d2 server';
    public int $port_l4d2 = 27015;
}
