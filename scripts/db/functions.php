<?php

function load_dns_entries($type, $zone)
{
	$_type = escapeshellarg($type);
	$_zone = escapeshellarg($zone);

	$dir = dirname(__FILE__);
	$data = shell_exec("$dir/load-dns.sh $_type $_zone");
	$lines = explode("\n", $data);

	$out = array();
	$out["A"] = array();
	$out["TXT"] = array();
	$out["CNAME"] = array();

	foreach ($lines as $line) {
		if (empty($line)) continue;
		$tmp = explode(" ", $line, 3);
		$entry = $tmp[0];
		$type = $tmp[1];
		$value = $tmp[2];

		if (isset($out[$type][$entry]))
			$out[$type][$entry] .= "\n" . $value;
		else
			$out[$type][$entry] = $value;
	}

	if (empty($out["A"]))
		throw new Exception("unable to load zone file, aborting");

	return $out;
}


function load_dhcp_entries($zone, $netaddr, $netmask)
{
	$_zone = escapeshellarg($zone);

	$dir = dirname(__FILE__);
	$data = shell_exec("$dir/load-dhcp.sh $_zone");
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
