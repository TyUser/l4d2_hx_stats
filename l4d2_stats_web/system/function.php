<?php
// SPDX-License-Identifier: GPL-3.0-only

declare(strict_types=1);
error_reporting(E_ALL);

if (!defined('HX_STATS')) {
    exit('Direct access protection');
}

class HxUtils
{
    public function sanitizeGetParameter(string $paramName): string
    {
        $value = $_GET[$paramName] ?? '';

        if (!is_string($value)) {
            return '';
        }

        return $this->sanitizeString($value);
    }

    public function sanitizeString(string $name): string
    {
        if ($name === '') {
            return '';
        }

        if (strlen($name) > 64) {
            return '';
        }

        if (!mb_check_encoding($name, 'UTF-8')) {
            return '';
        }

        $sBuf = preg_replace('/[^\p{L}\p{N}:_\-.\s]/u', ' ', $name);
        if ($sBuf === null) {
            return '';
        }

        $sBuf = preg_replace('/\s+/', ' ', $sBuf);
        if ($sBuf === null) {
            return '';
        }

        return trim($sBuf);
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

    public function validateSteamIdFormat(string $steamId): int
    {
        if ($steamId === '') {
            return 0;
        }

        if (preg_match('/^STEAM_\d+:\d+:\d+$/', $steamId)) {
            return 1;
        }

        if (preg_match('/^STEAM_old_\d+:\d+:\d+$/', $steamId)) {
            return 2;
        }

        return 0;
    }
}

class hxDatabase
{
    private mysqli $mysqli;

    public function __construct(string $host, string $user, string $pass, string $db)
    {
        mysqli_report(MYSQLI_REPORT_ERROR);
        try {
            $this->mysqli = new mysqli($host, $user, $pass, $db);
            $this->mysqli->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException('Connection failed: ' . (int)$e->getCode());
        }
    }

    public function query(string $query, array $params = []): array|int
    {
        $stmt = $this->mysqli->prepare($query);

        if ($stmt === false) {
            return 0;
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                $types .= $this->getParamType($param);
            }

            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            return (int)$affectedRows;
        }

        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        $stmt->close();

        return $data;
    }

    private function getParamType(mixed $value): string
    {
        if (is_null($value)) {
            return 's';
        }

        if (is_int($value) || is_bool($value)) {
            return 'i';
        }

        if (is_float($value)) {
            return 'd';
        }

        return 's';
    }

    // идентификатор (ID) последней вставленной записи
    public function lastInsertId(): int
    {
        return (int)$this->mysqli->insert_id;
    }

    // начинает новую транзакцию
    public function beginTransaction(): void
    {
        $this->mysqli->begin_transaction();
    }

    // подтверждает транзакцию
    public function commit(): void
    {
        $this->mysqli->commit();
    }

    // отменяет транзакцию
    public function rollback(): void
    {
        $this->mysqli->rollback();
    }
}

class Cache
{
    private const CACHE_DIR = __DIR__ . '/temp/';
    private const MIN_TTL = 10;
    private const MAX_TTL = 86400;

    public function __construct()
    {
        if (!file_exists(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0750, true);
        }

        // Автоматическая очистка кэша с вероятностью 5%
        if (mt_rand(1, 100) <= 5) {
            $this->cleanExpired();
        }
    }

    public function cleanExpired(): void
    {
        $files = glob(self::CACHE_DIR . '*');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $lastModified = filemtime($file);
            if ($lastModified === false) {
                continue;
            }

            if (time() - $lastModified > self::MAX_TTL) {
                unlink($file);
            }
        }
    }

    public function get_array(string $filename, int $times): ?array
    {
        $data = $this->get_string($filename, $times);
        if ($data === null) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    public function get_string(string $filename, int $times): ?string
    {
        if ($filename === '') {
            return null;
        }

        if ($times < self::MIN_TTL) {
            $times = self::MIN_TTL;
        }

        if ($times > self::MAX_TTL) {
            $times = self::MAX_TTL;
        }

        $path = self::CACHE_DIR . md5($filename);
        if (!file_exists($path)) {
            return null;
        }

        $lastModified = filemtime($path);
        if ($lastModified === false) {
            return null;
        }

        if ((time() - $lastModified) > $times) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        return $content;
    }

    public function set_array(string $filename, ?array $data): void
    {
        if ($data === null) {
            return;
        }

        $json = json_encode($data);
        if ($json === false) {
            return;
        }

        $this->set_string($filename, $json);
    }

    public function set_string(string $filename, ?string $data): void
    {
        if ($filename === '') {
            return;
        }

        if ($data === null) {
            return;
        }

        $path = self::CACHE_DIR . md5($filename);
        file_put_contents($path, $data);
    }
}
