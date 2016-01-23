#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 2)
	die("usage: $argv[0] <router-hostname[:port]>\n");

$router = $argv[1];
$mikrotik = mikrotik($router);

$data = shell_exec("$mikrotik ip dns export");
$data = str_replace("\\\r\n    ", "", $data);
$lines = explode("\n", $data);

$current = array();
$changes = array();

foreach ($lines as $line)
	if (preg_match("#^add address=([0-9.]+) disabled=(yes|no) name=([a-zA-Z0-9-_.]+) ttl=(.*)$#", $line, $matches)) {
		$type = (strpos($matches[4], "d") !== false ? "A" : "CNAME");
		$current[$type][$matches[3]] = $matches[1];
	}

foreach ($current["A"] as $host => $ip) {
	echo sprintf("%-40s%-10s%s\n", $host, "A", $ip);
}

foreach ($current["CNAME"] as $host2 => $ip2) {
	$alias = false;

	foreach ($current["A"] as $host => $ip)
		if ($ip == $ip2)
			$alias = $host;

	if ($alias)
		echo sprintf("%-40s%-10s%s\n", $host2, "CNAME", $alias);
	else
		echo sprintf("%-40s%-10s%-20s%s\n", $host2, "A", $ip2, "# should be CNAME but primary A record not found");
}
