<?php
// SPDX-License-Identifier: GPL-3.0-only

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

const HX_STATS = true;
require dirname(__FILE__) . '/system/function.php';
require dirname(__FILE__) . '/system/configuration.php';
require dirname(__FILE__) . '/system/L4D2ServerQuery.php';

$cache = new Cache();
$utils = new HxUtils();
$config = new AppConfig();
$hg_sql = new Class_mysqli($config->host, $config->user, $config->password, $config->database);

$search = $utils->sanitizeGetParameter('f');

$sg_content5 = '';

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

$serverName = $serverInfo["HostName"];
$mapName = $serverInfo["Map"];

// Получаем список топ 50 игроков
$sg_content4 = $cache->get_string('cache_top50', $config->cache_time * 20);
if ($sg_content4 === null) {
    $sTop50 = '';

    $aBuf2 = $hg_sql->query_array('SELECT `Steamid`, `Name`, `Points` FROM `l4d2_stats` ORDER BY `Points` DESC LIMIT 50');
    if ($aBuf2) {
        foreach ($aBuf2 as $a) {
            if (!empty($a)) {
                $sTop50 .= '<tr>';
                $sTop50 .= '<td><a href="index.php?f=' . $a['Steamid'] . '" class="link-dark">' . htmlspecialchars($a['Name'], ENT_QUOTES, 'UTF-8') . '</a></td><td>' . $a['Points'] . '</td>';
                $sTop50 .= '</tr>';
            }
        }
    }

    $sg_content4 = '<table class="table"><thead><tr><th scope="col">Top Players</th><th scope="col">Points</th></tr></thead><tbody>';
    $sg_content4 .= $sTop50;
    $sg_content4 .= '</tbody></table>';
    unset($sTop50, $aBuf2);

    $cache->set_string('cache_top50', $sg_content4);
}

// Получаем и кэшируем список игроков на сервере
$sg_content3 = $cache->get_string('cache_players', $config->cache_time);
if ($sg_content3 === null) {
    $sBuf3 = '';
    $sName = '';

    if (isset ($players[0])) {
        foreach ($players as $a) {
            if (!empty($a)) {
                $sBuf3 .= '<tr>';
                $sName = $utils->sanitizeString($a['Name']);
                if ($sName) {
                    $aBuf4 = $hg_sql->query_array("SELECT `Steamid` FROM `l4d2_stats` WHERE `Name` LIKE '" . $sName . "' ORDER BY `Time2` DESC LIMIT 1;");

                    if (isset ($aBuf4[0]["Steamid"])) {
                        $sBuf3 .= '<td><a href="index.php?f=' . $aBuf4[0]["Steamid"] . '" class="link-dark">' . htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') . '</a></td>';
                    }
                    else {
                        $sBuf3 .= '<td>' . htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') . '</td>';
                    }
                }
                else {
                    $sBuf3 .= '<td>Anonymous</td>';
                }

                $sBuf3 .= '<td>' . $a['Frags'] . '</td>';
                $sBuf3 .= '<td>' . $a['TimeF'] . '</td>';
                $sBuf3 .= '</tr>';
            }
        }
    }

    $sg_content3 = '<table class="table"><thead><tr><th scope="col">Players ' . $serverInfo["Players"] . '/' . $serverInfo["MaxPlayers"] . '</th><th scope="col">Frags</th><th scope="col">Time in game</th></tr></thead><tbody>';
    $sg_content3 .= $sBuf3;
    $sg_content3 .= '</tbody></table>';
    unset($sBuf3, $sName);

    $cache->set_string('cache_players', $sg_content3);
}

// Проверяем поиск
if ($search !== '') {
    $cacheKey = md5($search);
    $sg_content5 = $cache->get_string($cacheKey, $config->cache_time * 20);
    if ($sg_content5 === null) {
        $player = '';
        $aBuf5 = [];

        $isSteamID = preg_match('/^STEAM_\d+:\d+:\d+$/', $search);
        if ($isSteamID) {
            $aBuf5 = $hg_sql->query_array("SELECT * FROM `l4d2_stats` WHERE `Steamid` LIKE '" . $search . "'");
        }
        else {
            $aBuf5 = $hg_sql->query_array("SELECT * FROM `l4d2_stats` WHERE `Name` LIKE '%" . $search . "%' ORDER BY `Time2` DESC LIMIT 1;");
        }

        if (!empty($aBuf5)) {
            $player = '<table class="table"><thead><tr><th scope="col">Player: <a class="link-dark" target="_blank" href="' . $utils->convertSteamId($aBuf5['0']['Steamid']) . '">' . htmlspecialchars($aBuf5['0']['Name']) . '</a></th><th scope="col"></th></tr></thead><tbody>';

            $player .= '<tr><td>Points: </td><td>' . $aBuf5['0']['Points'] . '</td></tr>';
            $player .= '<tr><td>Boomer: </td><td>' . $aBuf5['0']['Boomer'] . '</td></tr>';
            $player .= '<tr><td>Charger: </td><td>' . $aBuf5['0']['Charger'] . '</td></tr>';
            $player .= '<tr><td>Hunter: </td><td>' . $aBuf5['0']['Hunter'] . '</td></tr>';
            $player .= '<tr><td>Infected: </td><td>' . $aBuf5['0']['Infected'] . '</td></tr>';
            $player .= '<tr><td>Jockey: </td><td>' . $aBuf5['0']['Jockey'] . '</td></tr>';
            $player .= '<tr><td>Smoker: </td><td>' . $aBuf5['0']['Smoker'] . '</td></tr>';
            $player .= '<tr><td>Spitter: </td><td>' . $aBuf5['0']['Spitter'] . '</td></tr>';

            $iTimeAll = (int)($aBuf5['0']['Time1'] / 60);

            $player .= '<tr><td>Tank: </td><td>' . $aBuf5['0']['Tank'] . '</td></tr>';
            $player .= '<tr><td>Witch: </td><td>' . $aBuf5['0']['Witch'] . '</td></tr>';
            $player .= '<tr><td>Total game time: </td><td>' . $iTimeAll . ' (hour..)</td></tr>';
            $player .= '<tr><td>Last visit: </td><td>' . date('d.m.Y', (int)$aBuf5['0']['Time2']) . '</td></tr>';
            $player .= '</tbody></table>';
        }
        else {
            if ($isSteamID) {
                $player = '<table class="table"><thead><tr><th scope="col">Игрок: <a class="link-dark" target="_blank" href="' . $utils->convertSteamId($search) . '">' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '</a></th><th scope="col"></th></tr></thead><tbody>';
                $player .= '</tbody></table>';
            }
            else {
                $player = '<div class="alert alert-warning">Player not found</div>';
            }
        }

        $sg_content5 = $player;
        unset($player, $aBuf5);

        $cache->set_string($cacheKey, $sg_content5);
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
			$sg_content4
		</div>

		<div class="col">
			$sg_content3
			<br>
			$sg_content5
		</div>
	</div>
	<br>
	<br>
</div>
</body>
</html>
HTML;

echo $html;