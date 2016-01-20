<?php

function load_entries($type, $zone)
{
	$_type = escapeshellarg($type);
	$_zone = escapeshellarg($zone);

	$data = shell_exec("/opt/zonemaster/driver/load.sh $_type $_zone");
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


function aws_client($profile = "default")
{
	$_profile = escapeshellarg($profile);
	return "/usr/local/bin/aws --profile $_profile";
}

function aws_record_change($action, $type, $host, $value)
{
	return array(
		"Action" => $action,
		"ResourceRecordSet" => array(
			"Name" => $host,
			"Type" => $type,
			"TTL" => 300,
			"ResourceRecords" => array( array(
				"Value" => $value,
			) ),
		),
	);
}
