#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 5)
	die("usage: $argv[0] <load-zone> <generate-zone> <bind9-zone-file> <internal/public>\n");

$inzone = $argv[1];
$outzone = $argv[2];
$file = $argv[3];
$type = $argv[4];

$offset = -1 - strlen($outzone);
$master = load_dns_entries($type, $inzone);
$data = "";

foreach ($master["TXT"] as $host => $value) {
	if ($host == $outzone) {
		$data .= sprintf("%-50s%-10s%s\n", "@", "IN TXT", $value);
	} else if (strlen($host) >= abs($offset) && strrpos($host, ".".$outzone, $offset) !== false) {
		$short = substr($host, 0, $offset);
		$data .= sprintf("%-50s%-10s%s\n", $short, "IN TXT", $value);
	}
}

$data .= "\n";
foreach ($master["A"] as $host => $ip) {
	if ($host == $outzone) {
		$data .= sprintf("%-50s%-10s%s\n", "@", "IN A", $ip);
	} else if (strlen($host) >= abs($offset) && strrpos($host, ".".$outzone, $offset) !== false) {
		$short = substr($host, 0, $offset);
		$data .= sprintf("%-50s%-10s%s\n", $short, "IN A", $ip);
	}
}

$data .= "\n";
foreach ($master["CNAME"] as $host => $alias) {
	if (strrpos($alias, ".".$outzone, $offset) !== false)
		$target = substr($alias, 0, $offset);
	else
		$target = $alias.".";

	if ($host == $outzone) {
		$data .= sprintf("%-50s%-10s%s\n", "@", "IN CNAME", $target);
	} else if (strlen($host) >= abs($offset) && strrpos($host, ".".$outzone, $offset) !== false) {
		$short1 = substr($host, 0, $offset);
		$data .= sprintf("%-50s%-10s%s\n", $short1, "IN CNAME", $target);
	}
}

$content = file_get_contents("/etc/local/.dns/bind.$outzone");
$content = str_replace("@@entries@@", $data, $content);
$content = str_replace("@@serial@@", date("ymdhi"), $content);
file_put_contents($file, $content);
