#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 2)
	die("usage: $argv[0] <domain-name>\n");

$domain = escapeshellarg($argv[1]);

$data = shell_exec("dig axfr $domain");
$lines = explode("\n", $data);

$current = array();
$current["A"] = array();
$current["CNAME"] = array();

foreach ($lines as $line) {
	if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s[0-9]+\sIN\sA\s([0-9.]+)$#", $line, $matches))
		$current["A"][$matches[1]] = $matches[2];
	if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s[0-9]+\sIN\sCNAME\s([a-zA-Z0-9-_.]+)[.]$#", $line, $matches))
		$current["CNAME"][$matches[1]] = $matches[2];
}

asort($current);

foreach ($current["A"] as $host => $ip)
	echo sprintf("%-40s%-10s%s\n", $host, "A", $ip);

foreach ($current["CNAME"] as $host => $alias)
	echo sprintf("%-40s%-10s%s\n", $host, "CNAME", $alias);
