<?php
// SPDX-License-Identifier: GPL-3.0-only

const HX_STATS = true;
require dirname(__FILE__) . '/system/function.php';
require dirname(__FILE__) . '/system/configuration.php';
require dirname(__FILE__) . '/system/L4D2ServerQuery.php';

$config = new AppConfig();
$cache = new Cache();
$serverInfo = $cache->get_array('cache_server_info', $config->cache_time);
$players = $cache->get_array('cache_player_list', $config->cache_time);

if ($serverInfo === null) {
    $query = new L4D2ServerQuery($config->ip_l4d2, $config->port_l4d2);

    $serverInfo = $query->getServerInfo();
    $players = $query->getPlayerList();

    $cache->set_array('cache_server_info', $serverInfo);
    $cache->set_array('cache_player_list', $players);
}

if ($serverInfo === null) {
    $serverInfo["HostName"] = '';
    $serverInfo["Map"] = '';
    $serverInfo["Players"] = 0;
    $serverInfo["MaxPlayers"] = 0;
}

$serverName = $serverInfo["HostName"];
$mapName = $serverInfo["Map"];

$playersTable = '<table class="table"><thead><tr>' . '<th scope="col">Игроки ' . $serverInfo["Players"] . '/' . $serverInfo["MaxPlayers"] . '</th>' . '<th scope="col">Фраги</th>' . '<th scope="col">Время в игре</th>' . '</tr></thead><tbody>';

if (!empty($players)) {
    foreach ($players as $player) {
        if (empty($player)) continue;

        $name = htmlspecialchars(trim($player['Name']), ENT_QUOTES, 'UTF-8');
        $name = $name ?: 'Аноним';

        $playersTable .= '<tr>' . '<td>' . $name . '</td>' . '<td>' . ($player['Frags'] ?? 0) . '</td>' . '<td>' . ($player['TimeF'] ?? '00:00') . '</td>' . '</tr>';
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
			<span class="text-white">Подключиться → $config->ip_l4d2:$config->port_l4d2</span>
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