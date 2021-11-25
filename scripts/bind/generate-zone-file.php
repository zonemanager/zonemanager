#!/usr/bin/php
<?php
require_once dirname(__FILE__)."/functions.php";
require_once dirname(__FILE__)."/../db/functions.php";

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
		$data .= bind_txt_entry("@", "IN TXT", $value);
	} else if (strlen($host) >= abs($offset) && strrpos($host, ".".$outzone, $offset) !== false) {
		$short = substr($host, 0, $offset);
		$data .= bind_txt_entry($short, "IN TXT", $value);
	}
}

$data .= "\n";
foreach ($master["A"] as $host => $ip) {
	if ($host == $outzone) {
		$data .= bind_entry("@", "IN A", $ip);
	} else if (strlen($host) >= abs($offset) && strrpos($host, ".".$outzone, $offset) !== false) {
		$short = substr($host, 0, $offset);
		$data .= bind_entry($short, "IN A", $ip);
	}
}

$data .= "\n";
foreach ($master["CNAME"] as $host => $alias) {
	if (strlen($alias) >= abs($offset) && strrpos($alias, ".".$outzone, $offset) !== false)
		$target = substr($alias, 0, $offset);
	else
		$target = $alias.".";

	if ($host == $outzone) {
		$data .= bind_entry("@", "IN CNAME", $target);
	} else if (strlen($host) >= abs($offset) && strrpos($host, ".".$outzone, $offset) !== false) {
		$short1 = substr($host, 0, $offset);
		$data .= bind_entry($short1, "IN CNAME", $target);
	}
}

$home = getenv("HOME");
$content = file_get_contents("$home/.zonemanager/dns/bind.$outzone");
$content = str_replace("@@entries@@", $data, $content);
$content = str_replace("@@serial@@", date("ymdHi"), $content);
file_put_contents($file, $content);
