#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 4)
	die("usage: $argv[0] <load-zone> <generate-zone> <bind9-zone-file>\n");

$inzone = $argv[1];
$outzone = $argv[2];
$file = $argv[3];

$out = load_entries($inzone);
$data = "";

foreach ($out["A"] as $host => $ip) {
	$len = strlen($outzone);
	if (strrpos($host, ".".$outzone, -$len-1) !== false) {
		$short = substr($host, 0, -$len-1);
		$data .= sprintf("%-40s%-10s%s\n", $short, "IN A", $ip);
	}
}

foreach ($out["CNAME"] as $host => $alias) {
	$len = strlen($outzone);
	if (strrpos($host, ".".$outzone, -$len-1) !== false) {
		$short1 = substr($host, 0, -$len-1);
		if (strrpos($alias, ".".$outzone, -$len-1) !== false)
			$data .= sprintf("%-40s%-10s%s\n", $short1, "CNAME", substr($alias, 0, -$len-1));
		else
			$data .= sprintf("%-40s%-10s%s\n", $short1, "CNAME", $alias.".");
	}
}

$template = file_get_contents("/opt/zonemaster/templates/bind9.tpl");
file_put_contents($file, str_replace("@@entries@@", $data, $template));
