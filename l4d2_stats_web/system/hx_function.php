<?php
if (!defined('HX_STATS')) {
    exit();
}

class Class_template
{
    private $sBuf1 = '';
    private $sBuf2 = '';
    private $sBuf3 = '';
    private $sBuf4 = '';

    public function set_title($s)
    {
        $this->sBuf1 = $s;
    }

    public function set_description($s)
    {
        $this->sBuf2 = $s;
    }

    public function set_keywords($s)
    {
        $this->sBuf3 = $s;
    }

    public function set_content($s)
    {
        $this->sBuf4 = $s;
    }

    public function get_main_tpl()
    {
        $aBuf1 = array('{hx_title}'
        , '{hx_description}'
        , '{hx_keywords}'
        , '{hx_content}');

        $aBuf2 = array($this->sBuf1
        , $this->sBuf2
        , $this->sBuf3
        , $this->sBuf4);

        $sTpl = file_get_contents('theme/main.tpl');
        $sBuf = str_replace($aBuf1, $aBuf2, $sTpl);
        unset($sTpl, $aBuf1, $aBuf2);
        return $sBuf;
    }
}

class Class_mysqli
{
    private $hSQL;

    public function __construct(&$host, &$user, &$pass, &$db)
    {
        $this->hSQL = new mysqli($host, $user, $pass, $db);
        $this->hSQL->set_charset('utf8');
    }

    public function __destruct()
    {
        $this->hSQL->close();
    }

    public function query_array($Buf)
    {
        $aRow = array();
        $i = 0;

        $h = $this->hSQL->query($Buf);
        if ($h) {
            while ($aRow[$i] = $h->fetch_assoc()) {
                $i += 1;
            }
            $h->free();
        }
        if ($i) {
            return $aRow;
        }
        return 0;
    }
}

/*	Валидация int $_GET запроса	*/
function hx_get_int($id)
{
    if (isset($_GET[$id])) {
        return intval($_GET[$id], 8);
    }
    return 0;
}

/*	Валидация string $_GET запроса	*/
function hx_get_string($id)
{
    if (isset($_GET[$id])) {
        $s = preg_replace('#[^a-zA-Z0-9:_]#', '', $_GET[$id]);
        return substr($s, 0, 64);
    }
    return '';
}

function hx_steam(&$s)
{
    $parts = explode(':', str_replace('STEAM_', '', $s));
    $iS = 7960265728 + $parts[1] + ($parts[2] * 2);
    return 'https://steamcommunity.com/profiles/7656119' . $iS;
}
