<?php
// SPDX-License-Identifier: GPL-3.0-only

error_reporting(-1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class L4D2ServerQuery
{
    private const RESPONSE_HEADER = "\xFF\xFF\xFF\xFF";
    private const SERVER_REQUEST_HEADER = "\xFF\xFF\xFF\xFF\x54\x53\x6F\x75\x72\x63\x65\x20\x45\x6E\x67\x69\x6E\x65\x20\x51\x75\x65\x72\x79\x00";
    private const SERVER_CHALLENGE_RESPONSE = self::RESPONSE_HEADER . "\x41";
    private const SERVER_RESPONSE_TYPE = 0x49;      // S2A_INFO_SRC
    private const PLAYER_REQUEST_HEADER = "\x55";   // A2S_PLAYER
    private const PLAYER_CHALLENGE_HEADER = "\x41"; // S2C_CHALLENGE
    private const PLAYER_RESPONSE_TYPE = 0x44;      // S2A_PLAYER

    private string $ip;
    private int $port;

    public function __construct(string $ip, int $port = 27015)
    {
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException("Invalid port number: $port. Port must be between 1 and 65535.");
        }

        if (!$this->validateIpv4($ip)) {
            throw new RuntimeException("Invalid IPv4 address: $ip");
        }

        $this->ip = $ip;
        $this->port = $port;
    }

    private function validateIpv4(string $ip): bool
    {
        $parts = explode('.', $ip);
        if (count($parts) !== 4) {
            return false;
        }

        foreach ($parts as $part) {
            if (!ctype_digit($part)) {
                return false;
            }

            $num = (int)$part;
            if ($num < 0 || $num > 255) {
                return false;
            }

            if (strlen($part) > 1 && $part[0] === '0') {
                return false;
            }
        }

        return true;
    }

    public function getServerInfo(): array
    {
        try {
            $fp = $this->connect();
            $response = $this->sendServerRequest($fp);
            $result = $this->parseServerResponse($response);
            fclose($fp);
            return $result;
        } catch (RuntimeException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function connect()
    {
        $fp = @fsockopen("udp://" . $this->ip, $this->port, $errno, $erst, 3);

        if (!$fp) {
            throw new RuntimeException("Connection failed: $erst (Code: $errno)");
        }

        stream_set_timeout($fp, 3);
        stream_set_blocking($fp, true);
        return $fp;
    }

    private function sendServerRequest($fp): string
    {
        fwrite($fp, self::SERVER_REQUEST_HEADER);
        $response = fread($fp, 4096);

        if (str_starts_with($response, self::SERVER_CHALLENGE_RESPONSE)) {
            $challenge = substr($response, 5, 4);
            fwrite($fp, self::SERVER_REQUEST_HEADER . $challenge);
            $response = fread($fp, 4096);
        }

        if (empty($response)) {
            throw new RuntimeException('Server response empty');
        }

        return $response;
    }

    private function parseServerResponse(string $response): array
    {
        $offset = 0;
        if (!str_starts_with($response, self::RESPONSE_HEADER)) {
            throw new RuntimeException('Invalid server response header');
        }
        $offset += 4;

        if (ord($response[$offset++]) !== self::SERVER_RESPONSE_TYPE) {
            throw new RuntimeException('Invalid server response type');
        }

        $Server = [];
        $Server['Protocol'] = ord($response[$offset++]);
        $Server['HostName'] = $this->readString($response, $offset);
        $Server['Map'] = $this->readString($response, $offset);
        $Server['ModDir'] = $this->readString($response, $offset);
        $Server['ModDesc'] = $this->readString($response, $offset);
        $Server['AppID'] = unpack('v', substr($response, $offset, 2))[1];
        $Server['Players'] = ord($response[$offset += 2]);
        $Server['MaxPlayers'] = ord($response[++$offset]);
        $Server['Bots'] = ord($response[++$offset]);
        $Server['Dedicated'] = ord($response[++$offset]);
        switch ($Server['Dedicated']) {
            case 100:
            {
                $Server['Dedicated'] = 'dedicated';
                break;
            }
            case 108:
            {
                $Server['Dedicated'] = 'listen';
                break;
            }
            case 112:
            {
                $Server['Dedicated'] = 'proxy';
                break;
            }
            default:
            {
                $Server['Dedicated'] = 'unknown';
            }
        }

        $Server['Os'] = ord($response[++$offset]);
        switch ($Server['Os']) {
            case 108:
            {
                $Server['Os'] = 'linux';
                break;
            }
            case 119:
            {
                $Server['Os'] = 'windows';
                break;
            }
            case 109:
            {
                $Server['Os'] = 'mac';
                break;
            }
            default:
            {
                $Server['Os'] = 'unknown';
            }
        }

        $Server['Password'] = ord($response[++$offset]);
        $Server['Secure'] = ord($response[++$offset]);

        return $Server;
    }

    private function readString(string $data, int &$offset): string
    {
        $nullPos = strpos($data, "\0", $offset);
        if ($nullPos === false) {
            $result = substr($data, $offset);
            $offset = strlen($data);
            return $result;
        }

        $result = substr($data, $offset, $nullPos - $offset);
        $offset = $nullPos + 1;
        return $result;
    }

    public function getPlayerList(): array
    {
        try {
            $fp = $this->connect();
            $challenge = $this->getPlayerChallenge($fp);
            $response = $this->sendPlayerRequest($fp, $challenge);
            fclose($fp);
            return $this->parsePlayerResponse($response);
        } catch (RuntimeException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getPlayerChallenge($fp): string
    {
        $request = self::RESPONSE_HEADER . self::PLAYER_REQUEST_HEADER . pack('V', -1);  // FF FF FF FF 55 FF FF FF FF
        fwrite($fp, $request);

        $response = fread($fp, 4096);
        if (!str_starts_with($response, self::RESPONSE_HEADER) || ord($response[4]) !== ord(self::PLAYER_CHALLENGE_HEADER)) {
            throw new RuntimeException('Invalid challenge response');
        }

        return substr($response, 5, 4);
    }

    private function sendPlayerRequest($fp, string $challenge): string
    {
        fwrite($fp, self::RESPONSE_HEADER . self::PLAYER_REQUEST_HEADER . $challenge);
        $response = fread($fp, 4096);

        if (empty($response)) {
            throw new RuntimeException('Empty player response');
        }

        return $response;
    }

    private function parsePlayerResponse(string $response): array
    {
        if (!str_starts_with($response, self::RESPONSE_HEADER) || ord($response[4]) !== self::PLAYER_RESPONSE_TYPE) {
            throw new RuntimeException('Invalid player response header');
        }
        $offset = 5;

        $Players = [];
        $count = ord($response[$offset++]);

        for ($i = 0; $i < $count; $i++) {
            $Player = [];
            $offset++;
            $Player['Name'] = $this->readString($response, $offset);
            $Player['Frags'] = unpack('l', substr($response, $offset, 4))[1];
            $Player['Time'] = (int)unpack('g', substr($response, $offset += 4, 4))[1];
            $Player['TimeF'] = gmdate(($Player['Time'] > 3600 ? 'H:i:s' : 'i:s'), $Player['Time']);

            $Players[] = $Player;

            $offset += 4;
        }

        return $Players;
    }
}
