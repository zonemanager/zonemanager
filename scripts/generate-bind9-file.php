#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 5)
	die("usage: $argv[0] <load-zone> <generate-zone> <bind9-zone-file> <internal/public>\n");

$inzone = $argv[1];
$outzone = $argv[2];
$file = $argv[3];
$type = $argv[4];

$out = load_entries($type, $inzone);
$data = "";

foreach ($out["TXT"] as $host => $value) {
	$len = strlen($outzone);
	if (strrpos($host, ".".$outzone, -$len-1) !== false) {
		$short = substr($host, 0, -$len-1);
		$data .= sprintf("%-50s%-10s%s\n", $short, "IN TXT", $value);
	} else if ($host == $outzone) {
		$data .= sprintf("%-50s%-10s%s\n", "@", "IN TXT", $value);
	}
}

$data .= "\n";
foreach ($out["A"] as $host => $ip) {
	$len = strlen($outzone);
	if (strrpos($host, ".".$outzone, -$len-1) !== false) {
		$short = substr($host, 0, -$len-1);
		$data .= sprintf("%-50s%-10s%s\n", $short, "IN A", $ip);
	} else if ($host == $outzone) {
		$data .= sprintf("%-50s%-10s%s\n", "@", "IN A", $ip);
	}
}

$data .= "\n";
foreach ($out["CNAME"] as $host => $alias) {
	$len = strlen($outzone);

	if (strrpos($alias, ".".$outzone, -$len-1) !== false)
		$target = substr($alias, 0, -$len-1);
	else
		$target = $alias.".";

	if (strrpos($host, ".".$outzone, -$len-1) !== false) {
		$short1 = substr($host, 0, -$len-1);
		$data .= sprintf("%-50s%-10s%s\n", $short1, "IN CNAME", $target);
	} else if ($host == $outzone) {
		$data .= sprintf("%-50s%-10s%s\n", "@", "IN CNAME", $target);
	}
}

$content = file_get_contents("/etc/local/.dns/bind.$outzone");
$content = str_replace("@@entries@@", $data, $content);
$content = str_replace("@@serial@@", date("ymdhi"), $content);
file_put_contents($file, $content);
