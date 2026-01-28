<?php
// SPDX-License-Identifier: GPL-3.0-only

//declare(strict_types=1);
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

const HX_STATS = true;
require __DIR__ . '/system/function.php';
require __DIR__ . '/system/configuration.php';
require __DIR__ . '/system/L4D2ServerQuery.php';

$cache = new Cache();
$utils = new HxUtils();
$config = new AppConfig();
$sql = new hxDatabase($config->host, $config->user, $config->password, $config->database);

$search = $utils->sanitizeGetParameter('f');

$sg_search_player = '';

// Получаем кэш L4D2ServerQuery
$serverInfo = $cache->get_array('cache_server_info', $config->cache_time);
$players = $cache->get_array('cache_player_list', $config->cache_time);

// Если кэша нет то подключаемся к L4D2ServerQuery и обновляем кэш
if ($serverInfo === null) {
    $query = new L4D2ServerQuery($config->ip_l4d2, $config->port_l4d2);

    $serverInfo = $query->getServerInfo();
    $players = $query->getPlayerList();

    $cache->set_array('cache_server_info', $serverInfo);
    $cache->set_array('cache_player_list', $players);
}

$serverName = $serverInfo["HostName"] ?? '';
$mapName = $serverInfo["Map"] ?? '';

// Получаем список топ 50 игроков
$sg_top50_players = $cache->get_string('cache_top50', $config->cache_time * 20);
if ($sg_top50_players === null) {
    $sTop50 = '';
    $processedPlayers = 0;

    $aBuf2 = $sql->query('SELECT `Steamid`, `Name`, `Points` FROM `l4d2_stats` WHERE `Points` > 0 ORDER BY `Points` DESC LIMIT 50;');
    if (!empty($aBuf2)) {
        foreach ($aBuf2 as $a) {
            if (!empty($a)) {
                $sTop50 .= '<tr>';
                $sTop50 .= '<td><a href="index.php?f=' . $a['Steamid'] . '" class="link-dark">' . htmlspecialchars($a['Name'], ENT_QUOTES, 'UTF-8') . '</a></td><td>' . (int)$a['Points'] . '</td>';
                $sTop50 .= '</tr>';

                $processedPlayers += 1;
            }
        }
    }

    $sg_top50_players = '<table class="table"><thead><tr><th scope="col">Top Players</th><th scope="col">Points</th></tr></thead><tbody>';
    $sg_top50_players .= $sTop50;
    $sg_top50_players .= '</tbody></table>';
    unset($sTop50, $aBuf2);

    if ($processedPlayers > 0) {
        $cache->set_string('cache_top50', $sg_top50_players);
    }
}

// Получаем и кэшируем список игроков на сервере
$sg_server_players = $cache->get_string('cache_players', $config->cache_time);
if ($sg_server_players === null) {
    $sBuf3 = '';
    $sName = '';
    $processedPlayers = 0;

    if (!empty($players)) {
        foreach ($players as $a) {
            if (!empty($a)) {
                $sBuf3 .= '<tr>';
                $sName = $utils->sanitizeString($a['Name']);
                if ($sName) {
                    $aBuf4 = $sql->query("SELECT `Steamid` FROM `l4d2_stats` WHERE `Name` LIKE ? ORDER BY `Time2` DESC LIMIT 1;", [$sName]);

                    if (!empty($aBuf4[0]["Steamid"])) {
                        $sBuf3 .= '<td><a href="index.php?f=' . $aBuf4[0]["Steamid"] . '" class="link-dark">' . htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') . '</a></td>';
                    }
                    else {
                        $sBuf3 .= '<td>' . htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') . '</td>';
                    }
                }
                else {
                    $sBuf3 .= '<td>Anonymous</td>';
                }

                $sBuf3 .= '<td>' . (int)$a['Frags'] . '</td>';
                $sBuf3 .= '<td>' . $a['TimeF'] . '</td>';
                $sBuf3 .= '</tr>';

                $processedPlayers += 1;
            }
        }
    }

    $playersCount = (int)($serverInfo["Players"] ?? 0);
    $maxPlayers = (int)($serverInfo["MaxPlayers"] ?? 0);

    $sg_server_players = '<table class="table"><thead><tr><th scope="col">Players ' . $playersCount . '/' . $maxPlayers . '</th><th scope="col">Frags</th><th scope="col">Time in game</th></tr></thead><tbody>';
    $sg_server_players .= $sBuf3;
    $sg_server_players .= '</tbody></table>';
    unset($sBuf3, $sName);

    if ($processedPlayers > 0) {
        $cache->set_string('cache_players', $sg_server_players);
    }
}

// Проверяем поиск
if ($search !== '') {
    $cacheKey = md5($search);
    $sg_search_player = $cache->get_string($cacheKey, $config->cache_time * 20);
    if ($sg_search_player === null) {
        $player = '';
        $aBuf5 = [];

        $isSteamID = $utils->validateSteamIdFormat($search);
        if ($isSteamID > 0) {
            $aBuf5 = $sql->query("SELECT * FROM `l4d2_stats` WHERE `Steamid` = ?;", [$search]);
        }
        else {
            $searchTerm = "%" . $search . "%";
            $aBuf5 = $sql->query("SELECT * FROM `l4d2_stats` WHERE `Name` LIKE ? ORDER BY `Time2` DESC LIMIT 1;", [$searchTerm]);
        }

        if (!empty($aBuf5) && isset($aBuf5[0])) {
            if ($isSteamID == 1) {
                $player = '<table class="table"><thead><tr><th scope="col">Player: <a class="link-dark" target="_blank" href="' . $utils->convertSteamId($aBuf5[0]['Steamid']) . '">' . htmlspecialchars($aBuf5[0]['Name']) . '</a></th><th scope="col"></th></tr></thead><tbody>';
            }
            else if ($isSteamID == 2) {
                $player = '<table class="table"><thead><tr><th scope="col">Player: ' . htmlspecialchars($aBuf5[0]['Name']) . '</th><th scope="col"></th></tr></thead><tbody>';
            }

            $player .= '<tr><td>Points: </td><td>' . (int)$aBuf5[0]['Points'] . '</td></tr>';
            $player .= '<tr><td>Boomer: </td><td>' . (int)$aBuf5[0]['Boomer'] . '</td></tr>';
            $player .= '<tr><td>Charger: </td><td>' . (int)$aBuf5[0]['Charger'] . '</td></tr>';
            $player .= '<tr><td>Hunter: </td><td>' . (int)$aBuf5[0]['Hunter'] . '</td></tr>';
            $player .= '<tr><td>Infected: </td><td>' . (int)$aBuf5[0]['Infected'] . '</td></tr>';
            $player .= '<tr><td>Jockey: </td><td>' . (int)$aBuf5[0]['Jockey'] . '</td></tr>';
            $player .= '<tr><td>Smoker: </td><td>' . (int)$aBuf5[0]['Smoker'] . '</td></tr>';
            $player .= '<tr><td>Spitter: </td><td>' . (int)$aBuf5[0]['Spitter'] . '</td></tr>';

            $iTimeAll = (int)($aBuf5[0]['Time1'] / 60);

            $player .= '<tr><td>Tank: </td><td>' . (int)$aBuf5[0]['Tank'] . '</td></tr>';
            $player .= '<tr><td>Witch: </td><td>' . (int)$aBuf5[0]['Witch'] . '</td></tr>';
            $player .= '<tr><td>Total game time: </td><td>' . $iTimeAll . ' (hour..)</td></tr>';
            $player .= '<tr><td>Last visit: </td><td>' . date('d.m.Y', (int)$aBuf5[0]['Time2']) . '</td></tr>';
            $player .= '</tbody></table>';
        }
        else {
            $player = '<table class="table"><thead><tr><th scope="col">Player: ';
            if ($isSteamID) {
                $player .= '<a class="link-dark" target="_blank" href="' . $utils->convertSteamId($search) . '">' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '</a>';
            }
            else {
                $player .= 'Player not found';
            }
            $player .= '</th><th scope="col"></th></tr></thead><tbody></tbody></table>';
        }

        $sg_search_player = $player;
        unset($player, $aBuf5);

        $cache->set_string($cacheKey, $sg_search_player);
    }
}

$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<title>$serverName</title>
	<link rel="stylesheet" href="bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
	<div class="container">
		<a class="navbar-brand" href="steam://connect/$config->ip_l4d2:$config->port_l4d2">
			<span class="text-white">Connect -> $config->ip_l4d2:$config->port_l4d2</span>
		</a>
	</div>
</nav>
<br>
<br>
<br>
<div class="container">
	<h2 style="text-align: center;">$serverName</h2>
	<h5 style="text-align: center;">$mapName</h5>
	<br>
	<form action="index.php" method="get" style="text-align: center;">
		<input type="search" size="21" name="f" placeholder="STEAM_ID or player name" maxlength="23">
		<button type="submit">Go</button>
	</form>
	<br>
	<div class="row">
		<div class="col">
			$sg_top50_players
		</div>

		<div class="col">
			$sg_server_players
			<br>
			$sg_search_player
		</div>
	</div>
	<br>
	<br>
</div>
</body>
</html>
HTML;

echo $html;