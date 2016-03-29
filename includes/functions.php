<?php

function load_dns_entries($type, $zone)
{
	$_type = escapeshellarg($type);
	$_zone = escapeshellarg($zone);

	$data = shell_exec("/opt/zonemaster/driver/load-dns.sh $_type $_zone");
	$lines = explode("\n", $data);

	$out = array();
	$out["A"] = array();
	$out["CNAME"] = array();

	foreach ($lines as $line) {
		if (empty($line)) continue;
		$tmp = explode(" ", $line, 3);
		$out[$tmp[1]][$tmp[0]] = $tmp[2];
	}

	if (empty($out["A"]))
		throw new Exception("unable to load zone file, aborting");

	return $out;
}

function load_dhcp_entries($zone, $netaddr, $netmask)
{
	$_zone = escapeshellarg($zone);

	$data = shell_exec("/opt/zonemaster/driver/load-dhcp.sh $_zone");
	$lines = explode("\n", $data);

	$maskLong = ip2long($netmask);
	$net = long2ip($maskLong & ip2long($netaddr));
	$out = array();

	foreach ($lines as $line) {
		if (empty($line)) continue;
		$tmp = explode(" ", $line);
		$ip = $tmp[1];
		$start = long2ip($maskLong & ip2long($ip));
		if ($start == $net)
			$out[$tmp[0]] = array($ip, $tmp[2]);
	}

	if (empty($out))
		throw new Exception("unable to load zone file, aborting");

	return $out;
}

function mikrotik($router)
{
	$_router = escapeshellarg($router);
	return "/opt/zonemaster/driver/mikrotik.sh $_router";
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
