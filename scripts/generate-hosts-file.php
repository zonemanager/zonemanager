#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 3)
	die("usage: $argv[0] <zone> <hosts-file>\n");

$zone = $argv[1];
$file = $argv[2];

$out = load_entries($zone);

$flat = array();
$data = "";

foreach ($out["A"] as $host => $ip)
	$flat[$host] = $ip;

foreach ($out["CNAME"] as $host => $alias)
	$flat[$host] = $out["A"][$alias];

asort($flat);

foreach ($flat as $host => $ip) {
	if (strpos($host, "*") !== false) continue;
	$len = strlen($zone);
	if (strrpos($host, ".".$zone, -$len-1) === false)
		$data .= "$ip\t\t$host\n";
	else {
		$short = substr($host, 0, -$len-1);
		$data .= "$ip\t\t$short $host\n";
	}
}

$template = file_get_contents("/opt/zonemaster/templates/hosts.tpl");
file_put_contents($file, str_replace("@@entries@@", $data, $template));
