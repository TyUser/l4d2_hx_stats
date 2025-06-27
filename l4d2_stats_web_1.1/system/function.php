<?php
// SPDX-License-Identifier: GPL-3.0-only

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (!defined('HX_STATS')) {
    exit();
}

function hx_get_string(string $id): string
{
    if (isset($_GET[$id])) {
        $s = preg_replace('#[^a-zA-Z0-9:_]#', '', $_GET[$id]);
        return substr($s, 0, 64);
    }
    return '';
}

function hx_get_cache(string $s, int $i): bool
{
    if (file_exists($s)) {
        $i2 = filemtime($s);
        if ((time() - $i) < $i2) {
            return true;
        }
    }

    return false;
}

function hx_steam(string $s): string
{
    $parts = explode(':', str_replace('STEAM_', '', $s));
    $iS = (int)$parts[1] + 7960265728 + ((int)$parts[2] * 2);
    return 'https://steamcommunity.com/profiles/7656119' . $iS;
}

class Class_mysqli
{
    private mysqli $hSQL;

    public function __construct(string $host, string $user, string $pass, string $db)
    {
        $this->hSQL = new mysqli($host, $user, $pass, $db);

        if ($this->hSQL->connect_error) {
            throw new RuntimeException("MySQL connection error: " . $this->hSQL->connect_error);
        }

        $this->hSQL->set_charset('utf8mb4');
    }

    public function __destruct()
    {
        $this->hSQL->close();
    }

    public function query_array($query): array
    {
        $result = $this->hSQL->query($query);

        if ($result === false) {
            throw new RuntimeException("Query error: " . $this->hSQL->error);
        }

        $resultArray = [];
        while ($row = $result->fetch_assoc()) {
            $resultArray[] = $row;
        }

        $result->free();
        return $resultArray;
    }
}

class Cache
{
    private const CACHE_DIR = __DIR__ . '/temp/';

    public function __construct()
    {
        if (!file_exists(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }

    public function get_array(string $filename, int $times = 10): ?array
    {
        if ($filename === '') {
            return null;
        }

        if ($times < 1) {
            return null;
        }

        $path = self::CACHE_DIR . $filename;
        if (file_exists($path) && (time() - filemtime($path)) < $times) {
            $data = file_get_contents($path);
            return json_decode($data, true) ?: null;
        }
        return null;
    }

    public function get_string(string $filename, int $times = 10): ?string
    {
        if ($filename === '') {
            return null;
        }

        if ($times < 1) {
            return null;
        }

        $path = self::CACHE_DIR . $filename;
        if (file_exists($path) && (time() - filemtime($path)) < $times) {
            return file_get_contents($path) ?: null;
        }
        return null;
    }

    public function set_array(string $filename, array $data): void
    {
        if ($filename === '') {
            return;
        }

        $path = self::CACHE_DIR . $filename;
        file_put_contents($path, json_encode($data));
    }

    public function set_string(string $filename, string $data): void
    {
        if ($filename === '') {
            return;
        }

        $path = self::CACHE_DIR . $filename;
        file_put_contents($path, $data);
    }
}
