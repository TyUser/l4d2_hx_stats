<?php
define('HX_STATS', true);
include dirname(__FILE__) . '/system/hx_function.php';
include dirname(__FILE__) . '/system/hx_config.php';

$sql = new Class_mysqli($aDB['server'], $aDB['username'], $aDB['passwd'], $aDB['dbname']);
$sContent = '';

$sGet = hx_get_string('f');
if ($sGet) {
    $aBuf = $sql->query_array("SELECT * FROM `l4d2_stats` WHERE `Steamid` LIKE '" . $sGet . "'");
    if ($aBuf) {
        foreach ($aBuf as $a) {
            if (!empty($a)) {
                $aConf['title'] .= ' ' . $a['Name'];

                $sContent .= '<h2>Player: ' . $a['Name'] . '</h2>';
                $sContent .= '<a class="noob" target="_blank" href="' . hx_steam($sGet) . '">Steam profile</a><br>';
                $sContent .= '<p>Points: ' . $a['Points'] . '</p>';
                $sContent .= '<p>Boomer: ' . $a['Boomer'] . '</p>';
                $sContent .= '<p>Charger: ' . $a['Charger'] . '</p>';
                $sContent .= '<p>Hunter: ' . $a['Hunter'] . '</p>';
                $sContent .= '<p>Infected: ' . $a['Infected'] . '</p>';
                $sContent .= '<p>Jockey: ' . $a['Jockey'] . '</p>';
                $sContent .= '<p>Smoker: ' . $a['Smoker'] . '</p>';
                $sContent .= '<p>Spitter: ' . $a['Spitter'] . '</p>';
                $sContent .= '<p>Tank: ' . $a['Tank'] . '</p>';
                $sContent .= '<p>Witch: ' . $a['Witch'] . '</p>';
                $sContent .= '<p>Total time: ' . (int)($a['Time1'] / 60) . ' (hours)</p>';
                $sContent .= '<p>Last visit: ' . date('d.m.Y', (int)$a['Time2']) . '</p>';
            }
        }
    } else {
        $sContent = '<p>Error 1</p>';
    }
} else {
    $sContent = '<h2>Top players</h2>';
    $sContent .= '<form action="index.php" method="get"><input type="search" size="21" name="f" placeholder="STEAM_ID" maxlength="23"><button type="submit">Go</button></form><br>';
    $aBuf = $sql->query_array("SELECT `Steamid`, `Name`, `Points` FROM `l4d2_stats` ORDER BY `Points` DESC LIMIT 50");
    if ($aBuf) {
        $i = 0;
        foreach ($aBuf as $a) {
            if (!empty($a)) {
                $i += 1;
                $sContent .= '<a class="noob" target="_blank" href="index.php?f=' . $a['Steamid'] . '">' . $i . '. ' . $a['Name'] . ' - ' . $a['Points'] . ' Points</a><br>';
            }
        }
    } else {
        $sContent = '<p>Error 2</p>';
    }
}

$tpl = new Class_template();
$tpl->set_title($aConf['title']);
$tpl->set_description($aConf['description']);
$tpl->set_keywords($aConf['keywords']);
$tpl->set_content($sContent);

echo $tpl->get_main_tpl();
