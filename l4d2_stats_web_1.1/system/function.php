<?php
if (!defined('HX_STATS')) {
	exit();
}

function hx_get_string($id): string
{
	if (isset($_GET[$id])) {
		$s = preg_replace('#[^a-zA-Z0-9:_]#', '', $_GET[$id]);
		return substr($s, 0, 64);
	}
	return '';
}

function hx_get_cache($s, $i): int
{
	if (file_exists($s)) {
		$i2 = filemtime($s);
		if ((time() - $i) < $i2) {
			return 1;
		}
	}

	return 0;
}

function hx_steam($s): string
{
	$parts = explode(':', str_replace('STEAM_', '', $s));
	$iS = $parts[1] + 7960265728 + ($parts[2] * 2);
	return 'https://steamcommunity.com/profiles/7656119' . $iS;
}

class Class_mysqli
{
	private $hSQL;

	public function __construct($host, $user, $pass, $db)
	{
		$this -> hSQL = new mysqli($host, $user, $pass, $db);
		$this -> hSQL -> set_charset('utf8');
	}

	public function __destruct()
	{
		$this -> hSQL -> close();
	}

	public function query_array($Buf): ?array
	{
		$aRow = array();
		$i = 0;

		$h = $this -> hSQL -> query($Buf);
		if ($h) {
			while ($aRow[$i] = $h -> fetch_assoc()) {
				$i += 1;
			}
			$h -> free();
		}
		if ($i) {
			return $aRow;
		}
		return NULL;
	}
}
