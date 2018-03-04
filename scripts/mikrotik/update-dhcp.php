#!/usr/bin/php
<?php
require_once dirname(__FILE__)."/../functions.php";

if ($argc < 5)
	die("usage: $argv[0] <load-zone> <router-hostname[:port]> <network-address> <network-mask> [debug]\n");

$inzone = $argv[1];
$router = escapeshellarg($argv[2]);
$netaddr = $argv[3];
$netmask = $argv[4];
$debug = (isset($argv[5]) && $argv[5] == "debug");

$assignments = load_dhcp_entries($inzone, $netaddr, $netmask);

$mikrotik = dirname(__FILE__)."/driver.sh $router";
$data = shell_exec("$mikrotik ip dhcp-server lease export");
$data = str_replace("\\\r\n    ", "", $data);
$lines = explode("\n", $data);

$current = array();
$changes = array();

foreach ($lines as $line)
	if (preg_match("#^add address=([0-9.]+) (always-broadcast=yes )?(block-access=yes )?comment=([a-zA-Z0-9-_. \"]+) (disabled=(yes|no) )?mac-address=([A-F0-9:]+) server#", $line, $matches))
		if ($matches[6] != "yes")
			$current[$matches[7]] = $matches[1];

foreach ($assignments as $mac => $assign) {
	$ip = $assign[0];
	$comment = str_replace(" ", "_", $assign[1]);
	if (!isset($current[$mac]))
		$changes[] = "ip dhcp-server lease add address=$ip disabled=no mac-address=$mac comment=$comment server=default";
	else if ($current[$mac] != $ip)
		$changes[] = "ip dhcp-server lease set address=$ip [find where mac-address=$mac]";
}

foreach ($current as $mac => $ip)
	if (!isset($assignments[$mac]))
		$changes[] = "ip dhcp-server lease remove [find where mac-address=$mac]";

if (!empty($changes)) {
	if ($debug) print_r($changes);
	foreach ($changes as $change) {
		$out = shell_exec("$mikrotik $change");
		if (!empty($out))
			echo "error: $out\n";
	}
}
