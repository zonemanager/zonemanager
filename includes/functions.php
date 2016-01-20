<?php

function load_entries($zone)
{
	$data = shell_exec("/opt/zonemaster/driver/load.sh ".escapeshellarg($zone));
	$lines = explode("\n", $data);

	$out = array();
	$out["A"] = array();
	$out["CNAME"] = array();

	foreach ($lines as $line) {
		if (empty($line)) continue;
		$tmp = explode(" ", $line);
		$out[$tmp[1]][$tmp[0]] = $tmp[2];
	}

	if (empty($out["A"]))
		throw new Exception("unable to load zone file, aborting");

	return $out;
}
