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


function aws_client($profile = "default")
{
	$_profile = escapeshellarg($profile);
	return "/usr/local/bin/aws --profile $_profile";
}


# https://docs.aws.amazon.com/Route53/latest/APIReference/API_ResourceRecordSet.html
function aws_record_change($action, $type, $host, $value)
{
	$set = array(
		"Action" => $action,
		"ResourceRecordSet" => array(
			"Name" => $host,
			"Type" => $type,
			"TTL" => 300,
			"ResourceRecords" => array(),
		),
	);

	if (strpos($value, "\n") === false) {
		$set["ResourceRecordSet"]["ResourceRecords"][] = array("Value" => $value);
	} else {
		$values = explode("\n", $value);
		foreach ($values as $subvalue)
			$set["ResourceRecordSet"]["ResourceRecords"][] = array("Value" => $subvalue);
	}

	return $set;
}


# http://www.zytrax.com/books/dns/ch8/a.html
function bind_entry($name, $type, $value)
{
	if (strpos($value, "\n") === false)
		return sprintf("%-50s%-10s%s\n", $name, $type, $value);

	$values = explode("\n", $value);
	$first = array_shift($values);
	$data = sprintf("%-50s%-10s%s\n", $name, $type, $first);

	foreach ($values as $subvalue)
		$data .= sprintf("%-50s%-10s%s\n", "", $type, $subvalue);

	return $data;
}


# http://www.zytrax.com/books/dns/ch8/txt.html
function bind_txt_entry($name, $type, $value)
{
	if (strpos($value, "\n") === false)
		return sprintf("%-50s%-10s%s\n", $name, $type, $value);

	$values = explode("\n", $value);
	$first = array_shift($values);
	$last = array_pop($values);
	$data = sprintf("%-50s%-10s(%s\n", $name, $type, $first);

	foreach ($values as $subvalue)
		$data .= sprintf("%-60s%s\n", "", $subvalue);

	$data .= sprintf("%-60s%s)\n", "", $last);
	return $data;
}
