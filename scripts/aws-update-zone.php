#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 4)
	die("usage: $argv[0] <awscli-profile-name> <domain-name> <zone-id>\n");

$profile = $argv[1];
$domain = $argv[2];
$zoneid = escapeshellarg($argv[3]);

$aws = aws_client($profile);

$offset = -1 - strlen($domain);
$master = load_dns_entries("public", $domain);
$current = array();
$changes = array();

$types = array("A", "CNAME", "TXT");

$json = shell_exec("$aws route53 list-resource-record-sets --hosted-zone-id $zoneid");
$sets = json_decode($json, true);

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

foreach ($types as $type) {
	foreach ($master[$type] as $host => $value) {

		if ($host != $domain && (strlen($host) < abs($offset) || strrpos($host, ".".$domain, $offset) === false))
			continue;

		if (!isset($current[$type][$host]))
			$changes[] = aws_record_change("CREATE", $type, $host, $value);
		else if ($current[$type][$host] != $value)
			$changes[] = aws_record_change("UPSERT", $type, $host, $value);
	}

	foreach ($current[$type] as $host => $value)
		if (!isset($master[$type][$host]))
			$changes[] = aws_record_change("DELETE", $type, $host, $value);
}

if (!empty($changes)) {
	$request = array(
		"Comment" => "Change from ".date("Y-m-d H:i"),
		"Changes" => $changes,
	);

	$file = tempnam("/tmp", "aws.");
	file_put_contents($file, json_encode($request, JSON_PRETTY_PRINT));

	$json = shell_exec("$aws route53 change-resource-record-sets --hosted-zone-id $zoneid --change-batch file://$file");
	$response = json_decode($json, true);

	if (empty($response["ChangeInfo"]["Status"]) || $response["ChangeInfo"]["Status"] != "PENDING")
		echo "error: wrong response from route53, request details left in file $file\n";
	else
		unlink($file);
}
