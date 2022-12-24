<?php
// SPDX-License-Identifier: GPL-3.0-only

const HX_STATS = true;
require dirname(__FILE__) . '/system/function.php';
require dirname(__FILE__) . '/system/config.php';
require dirname(__FILE__) . '/SourceQuery/bootstrap.php';

use xPaw\SourceQuery\SourceQuery;

$sg_content1 = '';
$sg_content2 = '';
$sg_content3 = '';
$sg_content4 = '';
$sg_content5 = '';
$ag_symbol = array('(', ')', '%', '$', '<', '>', ',', '.', '"', "'", "!", "SELECT", "UPDATE", "FROM", ";", "+", "-", "*", "@", "^", ":");

$sg_get = hx_get_string('f');
$hg_sql = new Class_mysqli($aDB['server'], $aDB['username'], $aDB['passwd'], $aDB['dbname']);

/*
*	Получаем список топ игроков и кэшируем.
*/
if (!hx_get_cache('temp/top.txt', 60)) {
	$sBuf1 = '<table class="table"><thead><tr><th scope="col">Top Players</th><th scope="col">Points</th></tr></thead><tbody>';
	$aBuf2 = $hg_sql -> query_array('SELECT `Steamid`, `Name`, `Points` FROM `l4d2_stats` ORDER BY `Points` DESC LIMIT 50');
	if ($aBuf2) {
		foreach ($aBuf2 as $a) {
			if (!empty($a)) {
				$sBuf1 .= '<tr>';
				$sBuf1 .= '<td><a href="index.php?f=' . $a['Steamid'] . '" class="link-dark">' . htmlspecialchars($a['Name']) . '</a></td><td>' . $a['Points'] . '</td>';
				$sBuf1 .= '</tr>';
			}
		}
	}
	$sBuf1 .= '</tbody></table>';

	$fp = fopen('temp/top.txt', 'w');
	if ($fp) {
		fwrite($fp, trim($sBuf1));
		fclose($fp);
	}

	unset($sBuf1, $aBuf2);
}

if (!hx_get_cache('temp/players.txt', 10)) {
	$hg_query = new SourceQuery();

	$aInfo = array();
	$aPlayers = array();
	$sBuf3 = '';
	$sName = '';

	try {
		$hg_query -> Connect($sg_ip, $sg_port, 2, 1);
		$aInfo = $hg_query -> GetInfo();
		$aPlayers = $hg_query -> GetPlayers();
	} catch (Exception $e) {
		$Exception = $e;
	} finally {
		$hg_query -> Disconnect();
	}

	$sBuf3 = '<table class="table"><thead><tr><th scope="col">Players ' . $aInfo["Players"] . '/' . $aInfo["MaxPlayers"] . '</th><th scope="col">Frags</th><th scope="col">Time in game</th></tr></thead><tbody>';
	if ($aPlayers[0]) {
		foreach ($aPlayers as $a) {
			if (!empty($a)) {
				$sBuf3 .= '<tr>';
				$sName = str_replace($ag_symbol, ' ', $a['Name']);
				if ($sName) {
					$aBuf4 = $hg_sql -> query_array("SELECT `Steamid` FROM `l4d2_stats` WHERE `Name` LIKE '" . $sName . "' ORDER BY `Time2` DESC LIMIT 1;");

					if ($aBuf4[0]["Steamid"]) {
						$sBuf3 .= '<td><a href="index.php?f=' . $aBuf4[0]["Steamid"] . '" class="link-dark">' . $sName . '</a></td>';
					}
					else {
						$sBuf3 .= '<td>' . $sName . '</td>';
					}
				}
				else {
					$sBuf3 .= '<td>Аноним</td>';
				}

				$sBuf3 .= '<td>' . $a['Frags'] . '</td>';
				$sBuf3 .= '<td>' . $a['TimeF'] . '</td>';
				$sBuf3 .= '</tr>';
			}
		}
	}
	$sBuf3 .= '</tbody></table>';

	$gh1 = fopen('temp/players.txt', 'w');
	if ($gh1) {
		fwrite($gh1, trim($aInfo["HostName"]) . "\n");
		fwrite($gh1, trim($aInfo["Map"]) . "\n");
		fwrite($gh1, trim($sBuf3));
		fclose($gh1);
	}

	unset($aInfo, $aPlayers, $sBuf3, $sName);
}

if ($sg_get) {
	$aBuf5 = array();

	if (strripos($sg_get, 'STEAM_') === false) {
		$aBuf6 = $hg_sql -> query_array("SELECT `Steamid` FROM `l4d2_stats` WHERE `Name` LIKE '%" . $sg_get . "%' ORDER BY `Time2` DESC LIMIT 1;");
		if ($aBuf6[0]["Steamid"]) {
			header('Location: index.php?f=' . $aBuf6[0]["Steamid"]);
			exit();
		}
	}
	else {
		$aBuf5 = $hg_sql -> query_array("SELECT * FROM `l4d2_stats` WHERE `Steamid` LIKE '" . $sg_get . "'");
	}

	if ($aBuf5['0']['Steamid']) {
		$sg_content5 = '<table class="table"><thead><tr><th scope="col">Player: <a class="link-dark" target="_blank" href="' . hx_steam($sg_get) . '">' . htmlspecialchars($aBuf5['0']['Name']) . '</a></th><th scope="col"></th></tr></thead><tbody>';

		$sg_content5 .= '<tr><td>Points: </td><td>' . $aBuf5['0']['Points'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Boomer: </td><td>' . $aBuf5['0']['Boomer'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Charger: </td><td>' . $aBuf5['0']['Charger'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Hunter: </td><td>' . $aBuf5['0']['Hunter'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Infected: </td><td>' . $aBuf5['0']['Infected'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Jockey: </td><td>' . $aBuf5['0']['Jockey'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Smoker: </td><td>' . $aBuf5['0']['Smoker'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Spitter: </td><td>' . $aBuf5['0']['Spitter'] . '</td></tr>';

		$iTimeAll = (int)($aBuf5['0']['Time1'] / 60);

		$sg_content5 .= '<tr><td>Tank: </td><td>' . $aBuf5['0']['Tank'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Witch: </td><td>' . $aBuf5['0']['Witch'] . '</td></tr>';
		$sg_content5 .= '<tr><td>Total game time: </td><td>' . $iTimeAll . ' (hour..)</td></tr>';
		$sg_content5 .= '<tr><td>Last visit: </td><td>' . date('d.m.Y', (int)$aBuf5['0']['Time2']) . '</td></tr>';
		$sg_content5 .= '</tbody></table>';
	}
	else {
		if (strripos($sg_get, 'STEAM_') !== false) {
			$sg_content5 = '<table class="table"><thead><tr><th scope="col">Игрок: <a class="link-dark" target="_blank" href="' . hx_steam($sg_get) . '">' . $sg_get . '</a></th><th scope="col"></th></tr></thead><tbody>';
			$sg_content5 .= '</tbody></table>';
		}
	}

	unset($aBuf5);
}

$h1 = fopen('temp/players.txt', "r");
if ($h1) {
	$sg_content1 = trim(fgets($h1, 4096));
	$sg_content2 = trim(fgets($h1, 4096));
	$sg_content3 = trim(fgets($h1, 4096));
	fclose($h1);
}

$sg_content4 = file_get_contents('temp/top.txt');

echo '<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<title>' . $sg_content1 . '</title>
	<link rel="stylesheet" href="bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
	<div class="container">
		<a class="navbar-brand" href="steam://connect/' . $sg_ip . ':' . $sg_port . '">
			<span class="text-white">Connect -> ' . $sg_ip . ':' . $sg_port . '</span>
		</a>
	</div>
</nav>
<br>
<br>
<br>
<div class="container">
	<h2 style="text-align: center;">' . $sg_content1 . '</h2>
	<h5 style="text-align: center;">' . $sg_content2 . '</h5>
	<br>
	<form action="index.php" method="get" style="text-align: center;">
		<input type="search" size="21" name="f" placeholder="STEAM_ID, Name" maxlength="23">
		<button type="submit">Go</button>
	</form>
	<br>
	<div class="row">
		<div class="col">
			' . $sg_content4 . '
		</div>

		<div class="col">
			' . $sg_content3 . '
			<br>
			' . $sg_content5 . '
		</div>
	</div>
	<br>
	<br>
</div>
</body>
</html>
';
