<?php
// SPDX-License-Identifier: GPL-3.0-only

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (!defined('HX_STATS')) {
    exit();
}

class HxUtils
{
    private array $injectionPatterns = array('!', '"'
    , '#', '$', '%', "'", '(', ')', '*', '*/', '+', ',', '-', '.'
    , '/*', '0x', ':', ';', '<', '=', '>', '@', 'AND'
    , 'DELETE', 'DROP', 'EXEC', 'FROM', 'INSERT', 'OR'
    , 'SELECT', 'TRUNCATE', 'UNION', 'UPDATE', '['
    , '\\', ']', '^', '{', '|', '}', '~');

    public function sanitizeGetParameter(string $paramName): string
    {
        if (isset($_GET[$paramName])) {
            $cleanValue = preg_replace('#[^а-яА-Яa-zA-Z0-9:_]#', '', $_GET[$paramName]);
            return substr($cleanValue, 0, 64);
        }

        return '';
    }

    public function convertSteamId(string $steamId): string
    {
        $cleanId = str_replace('STEAM_', '', $steamId);
        $parts = explode(':', $cleanId);

        if (count($parts) < 3) {
            return '';
        }

        $authServer = (int)$parts[1];
        $accountId = (int)$parts[2];

        $steamId64 = ($accountId * 2) + 0x0110000100000000 + $authServer;
        return 'https://steamcommunity.com/profiles/' . $steamId64;
    }

    public function sanitizeString(string $name): string
    {
        if ($name) {
            return str_replace($this->injectionPatterns, ' ', $name);
        }

        return '';
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
