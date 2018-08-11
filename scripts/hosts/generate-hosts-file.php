#!/usr/bin/php
<?php
require_once dirname(__FILE__)."/../db/functions.php";

if ($argc < 3)
	die("usage: $argv[0] <system> <zone>\n");

$os = $argv[1];
$zone = $argv[2];
$file = dirname(__FILE__)."/../../templates/$os/hosts.tpl";

if (!file_exists($file))
	die("error: unrecognized operating system version\n");

$out = load_dns_entries("internal", $zone);

$flat = array();
$data = "";

foreach ($out["A"] as $host => $ip)
	$flat[$host] = $ip;

foreach ($out["CNAME"] as $host => $alias)
	if (!empty($out["A"][$alias]))
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

$template = file_get_contents($file);
echo str_replace("@@entries@@", $data, $template);
