<?php
// SPDX-License-Identifier: GPL-3.0-only

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

if (!defined('HX_STATS')) {
    exit();
}

class AppConfig
{
    // Настройки базы данных
    public string $host = 'localhost';              // This is normally set to localhost
    public string $user = '';                       // Database username
    public string $password = '';                   // Database password
    public string $database = '';                   // Database name

    // Настройки сервера
    public int $cache_time = 5;
    public string $ip_l4d2 = 'ip address l4d2 server';
    public int $port_l4d2 = 27015;
}
