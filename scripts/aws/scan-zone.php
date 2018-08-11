#!/usr/bin/php
<?php
require_once dirname(__FILE__)."/functions.php";

if ($argc < 3)
	die("usage: $argv[0] <awscli-profile-name> <zone-id>\n");

$profile = $argv[1];
$zoneid = escapeshellarg($argv[2]);

$aws = aws_client($profile);

$current = array();
$types = array("A", "CNAME", "TXT");

$json = shell_exec("$aws route53 list-resource-record-sets --hosted-zone-id $zoneid");
$sets = json_decode($json, true);

if (empty($sets))
	die("error: invalid zone id\n");

foreach ($sets["ResourceRecordSets"] as $entry) {
	$type = $entry["Type"];
	$tmp = str_replace("\\052", "*", $entry["Name"]);
	$name = substr($tmp, 0, -1);
	$value = $entry["ResourceRecords"][0]["Value"];
	if (in_array($type, $types, true))
		$current[$type][$name] = $value;

	for ($more = 1; $more < 20; $more++) {
		if (empty($entry["ResourceRecords"][$more]))
			break;
		$value = $entry["ResourceRecords"][$more]["Value"];
		$current[$type][$name] .= "\n" . $value;
	}
}

foreach ($types as $type)
	foreach ($current[$type] as $host => $value) {
		if (strpos($value, "\n") === false) {
			echo sprintf("%-60s%-10s%s\n", $host, $type, $value);
		} else {
			$values = explode("\n", $value);
			foreach ($values as $subvalue)
				echo sprintf("%-60s%-10s%s\n", $host, $type, $subvalue);
		}
	}
