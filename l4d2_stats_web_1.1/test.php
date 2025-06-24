<?php

require dirname(__FILE__) . '/system/L4D2ServerQuery.php';
require dirname(__FILE__) . '/SourceQuery/bootstrap.php';

$hg_query = new L4D2ServerQuery('62.113.112.155', 27015);
$aInfo = $hg_query->getServerInfo();
$aPlayers = $hg_query->getPlayerList();

var_dump($aInfo, $aPlayers);
echo '
<p>
</p>';

use xPaw\SourceQuery\SourceQuery;

$hg_query = new SourceQuery();
try {
    $hg_query->Connect('62.113.112.155', 27015, 2, 1);
    $aInfo = $hg_query->GetInfo();
    $aPlayers = $hg_query->GetPlayers();


    var_dump($aInfo, $aPlayers);
} catch (Exception $e) {
    $Exception = $e;
} finally {
    $hg_query->Disconnect();
}