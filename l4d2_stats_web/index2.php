<?php
// SPDX-License-Identifier: GPL-3.0-only

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

const HX_STATS = true;
require __DIR__ . '/system/function.php';
require __DIR__ . '/system/configuration.php';
require __DIR__ . '/system/L4D2ServerQuery.php';

$config = new AppConfig();
$cache = new Cache();

// Получаем кэш L4D2ServerQuery
$serverInfo2 = $cache->get_array('cache_server_info2', $config->cache_time);
$players2 = $cache->get_array('cache_player_list2', $config->cache_time);

// Если кэша нет то подключаемся к L4D2ServerQuery и обновляем кэш
if ($serverInfo2 === null) {
    $query = new L4D2ServerQuery($config->ip_l4d2, $config->port_l4d2);

    $serverInfo2 = $query->getServerInfo();
    $players2 = $query->getPlayerList();

    $cache->set_array('cache_server_info2', $serverInfo2);
    $cache->set_array('cache_player_list2', $players2);
}

$serverName = $serverInfo2["HostName"];
$mapName = $serverInfo2["Map"];

$playersTable = '<table class="table"><thead><tr>' . '<th scope="col">Players ' . $serverInfo2["Players"] . '/' . $serverInfo2["MaxPlayers"] . '</th>' . '<th scope="col">Frags</th>' . '<th scope="col">Time in game</th>' . '</tr></thead><tbody>';

if (!empty($players2)) {
    foreach ($players2 as $player) {
        if (empty($player)) continue;

        $name = htmlspecialchars($player['Name'], ENT_QUOTES, 'UTF-8');
        $name = $name ?: 'Anonymous';

        $playersTable .= '<tr>' . '<td>' . $name . '</td>' . '<td>' . $player['Frags'] . '</td>' . '<td>' . $player['TimeF'] . '</td>' . '</tr>';
    }
}

$playersTable .= '</tbody></table>';

$html = <<<HTML
<!doctype html>
<html lang="ru">
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
			<span class="text-white">Connect → $config->ip_l4d2:$config->port_l4d2</span>
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
	<br>
	<div class="row">
		<div class="col">
			$playersTable
		</div>
	</div>
	<br>
	<br>
</div>
</body>
</html>
HTML;

echo $html;