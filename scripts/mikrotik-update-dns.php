#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 3)
	die("usage: $argv[0] <load-zone> <router-hostname[:port]> [debug]\n");

$inzone = $argv[1];
$router = $argv[2];
$debug = (isset($argv[3]) && $argv[3] == "debug");

$master = load_dns_entries("internal", $inzone);

$mikrotik = mikrotik($router);

$data = shell_exec("$mikrotik ip dns export");
$data = str_replace("\\\r\n    ", "", $data);
$lines = explode("\n", $data);

$current = array();
$changes = array();

$types = array("A", "CNAME");

foreach ($lines as $line)
	if (preg_match("#^add address=([0-9.]+) disabled=(yes|no) name=([a-zA-Z0-9-_.]+) ttl#", $line, $matches))
		if ($matches[2] != "yes")
			$current[$matches[3]] = $matches[1];

foreach ($master["A"] as $host => $value) {
	if (strpos($host, "*") !== false) continue;
	if (!isset($current[$host]))
		$changes[] = "ip dns static add address=$value disabled=no name=$host ttl=1d";
	else if ($current[$host] != $value)
		$changes[] = "ip dns static set address=$value [find where name=$host]";
}

foreach ($master["CNAME"] as $host => $value) {
	if (strpos($host, "*") !== false) continue;
	$value2 = $master["A"][$value];
	if (!isset($current[$host]))
		$changes[] = "ip dns static add address=$value2 disabled=no name=$host ttl=1h";
	else if ($current[$host] != $value2)
		$changes[] = "ip dns static set address=$value2 [find where name=$host]";
}

foreach ($current as $host => $value)
	if (!isset($master["A"][$host]) && !isset($master["CNAME"][$host]))
		$changes[] = "ip dns static remove [find where name=$host]";

if (!empty($changes)) {
	if ($debug) print_r($changes);
	foreach ($changes as $change) {
		$out = shell_exec("$mikrotik $change");
		if (!empty($out))
			echo "error: $out\n";
	}
}
