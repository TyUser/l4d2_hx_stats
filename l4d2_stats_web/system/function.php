<?php
// SPDX-License-Identifier: GPL-3.0-only

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

if (!defined('HX_STATS')) {
    exit();
}

class HxUtils
{
//  '/[^\p{L}\p{N}:_\-.\s]/u'
//  \p{L} - Буквы большинства языков мира
//  \p{N} - Цифры
//  \s - Пробелы

    private const INJECTION_PATTERNS = ['AND'
    , 'DELETE', 'DROP', 'EXEC', 'FROM', 'INSERT', 'OR'
    , 'SELECT', 'TRUNCATE', 'UNION', 'UPDATE'];

    public function sanitizeGetParameter(string $paramName): string
    {
        if (isset($_GET[$paramName])) {
            $cleanValue = $this->sanitizeString($_GET[$paramName]);
            return mb_substr($cleanValue, 0, 64, 'UTF-8');
        }

        return '';
    }

    public function sanitizeString(string $name): string
    {
        if ($name !== '') {
            $sBuf = preg_replace('/[^\p{L}\p{N}:_\-.\s]/u', ' ', $name);
            return str_replace(self::INJECTION_PATTERNS, '', $sBuf);
        }

        return '';
    }

    public function convertSteamId(string $steamId): string
    {
        if (!preg_match('/^STEAM_[0-5]:([0-1]):(\d+)$/', $steamId, $matches)) {
            return '';
        }

        $authServer = (int)$matches[1];
        $accountId = (int)$matches[2];

        $steamId64 = ($accountId * 2) + 0x0110000100000000 + $authServer;
        return 'https://steamcommunity.com/profiles/' . $steamId64;
    }
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

    public function query_array(string $query): array
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

        // Позволяет в автоматическом режиме защищать от бесконтрольного разрастания кэша
        if (mt_rand(1, 100) <= 5) {
            $this->cleanExpired();
        }
    }

    public function cleanExpired(int $maxLifetime = 86400): void
    {
        $files = glob(self::CACHE_DIR . '*');
        $now = time();

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $lastModified = filemtime($file);
            if ($now - $lastModified > $maxLifetime) {
                unlink($file);
            }
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
