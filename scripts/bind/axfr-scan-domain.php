#!/usr/bin/php
<?php
require_once dirname(__FILE__)."/../functions.php";

if ($argc < 2)
	die("usage: $argv[0] <domain-name>\n");

$domain = $argv[1];

$data = shell_exec("dig axfr ".escapeshellarg($domain));
$lines = explode("\n", $data);

$current = array();
$current["A"] = array();
$current["TXT"] = array();
$current["CNAME"] = array();

$soa = array();
$other = array();

$template = "";
$zonefile = "";

foreach ($lines as $line) {
	if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s+[0-9]+\s+IN\s+A\s+([0-9.]+)$#", $line, $matches))
		$current["A"][$matches[1]] = $matches[2];
	else if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s+[0-9]+\s+IN\s+CNAME\s+([a-zA-Z0-9-_.]+)[.]$#", $line, $matches))
		$current["CNAME"][$matches[1]] = $matches[2];
	else if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s+[0-9]+\s+IN\s+TXT\s+(.+)$#", $line, $matches))
		$current["TXT"][$matches[1]] = $matches[2];
	else if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s+[0-9]+\s+IN\s+SOA\s+([a-zA-Z0-9-_.]+)[.]\s+([a-zA-Z0-9-_.]+)[.]\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)$#", $line, $matches))
		$soa = $matches;
	else if (preg_match("#^([a-zA-Z0-9-_.*]+)[.]\s+[0-9]+\s+IN\s+([A-Z0-9]+)\s+(.*)$#", $line, $matches))
		$other[] = $matches;
	else if (!empty($line))
		$template .= "; $line\n";
}

asort($current);

foreach ($current["A"] as $host => $ip)
	$zonefile .= sprintf("%-40s%-10s%s\n", $host, "A", $ip);

foreach ($current["TXT"] as $host => $value)
	$zonefile .= sprintf("%-40s%-10s%s\n", $host, "TXT", $value);

foreach ($current["CNAME"] as $host => $alias)
	$zonefile .= sprintf("%-40s%-10s%s\n", $host, "CNAME", $alias);

$template .= "\n\$ORIGIN $domain.\n\$TTL 3D\n\n";
$margin = "                                                        ";

if (strpos($soa[2], $domain) !== false)
	$nameserver = str_replace(".$domain", "", $soa[2]);
else
	$nameserver = $soa[2].".";

if (strpos($soa[3], $domain) !== false)
	$hostmaster = str_replace(".$domain", "", $soa[3]);
else
	$hostmaster = $soa[3].".";

$template .= sprintf("%-40sIN    SOA       %s  %s (\n", "@", $nameserver, $hostmaster);
$template .= $margin."@@serial@@  ;serial number\n";
$template .= $margin."2H          ;refresh\n";
$template .= $margin."300         ;retry\n";
$template .= $margin."4W          ;expiration\n";
$template .= $margin."1D )        ;minimum\n;\n\n";

foreach ($other as $record) {
	if ($record[1] == $domain)
		$host = "@";
	else
		$host = $record[1] . ".";
	$host = str_replace(".$domain.", "", $host);
	$record[3] = str_replace(".$domain.", "", $record[3]);
	$template .= sprintf("%-40sIN    %-10s%s\n", $host, $record[2], $record[3]);
}
$template .= "\n@@entries@@\n";

file_put_contents("/etc/local/.dns/bind.$domain.dist", $template);
file_put_contents("/etc/local/.dns/zone.$domain.dist", $zonefile);
