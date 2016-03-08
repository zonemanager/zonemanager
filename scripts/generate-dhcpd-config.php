#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 5)
	die("usage: $argv[0] <load-zone> <network-address> <network-mask> <out-file> [authoritative]\n");

$inzone = $argv[1];
$netaddr = $argv[2];
$netmask = $argv[3];
$file = $argv[4];
$authoritative = (!empty($argv[5]) && $argv[5] == "authoritative");

$assignments = load_dhcp_entries($inzone);
$data = "";

$maskLong = ip2long($netmask);
$net = long2ip($maskLong & ip2long($netaddr));

foreach ($assignments as $address => $assign) {
	$ip = $assign[0];
	$start = long2ip($maskLong & ip2long($ip));
	if ($start == $net) {
		$alias = $assign[1];
		$data .= "host $alias {\n\thardware ethernet $address;\n\tfixed-address $ip;\n}\n\n";
	}
}

if ($authoritative)
	$authcmd = "authoritative;";
else
	$authcmd = "";

$content = file_get_contents("/etc/local/.dns/dhcpd.$inzone");
$content = str_replace("@@entries@@", $data, $content);
$content = str_replace("@@authoritative@@", $authcmd, $content);
file_put_contents($file, $content);
