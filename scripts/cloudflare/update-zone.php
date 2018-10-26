#!/usr/bin/php
<?php
require_once dirname(__FILE__)."/functions.php";
require_once dirname(__FILE__)."/../db/functions.php";

if ($argc < 3)
	die("usage: $argv[0] <domain-name> <zone-id>\n");

$domain = $argv[1];
$zoneid = urlencode($argv[2]);
$endpoint = "https://api.cloudflare.com/client/v4/zones/$zoneid/dns_records";

$curl = cf_client($domain);

$offset = -1 - strlen($domain);
$master = load_dns_entries("public", $domain);
$current = array();
$changes = array();

$types = array("A", "CNAME", "TXT");

foreach ($types as $type) {
	$command = "$curl -X GET \"$endpoint?type=$type&page=1&per_page=100\"";
	$response = execute($command);

	if ($response["success"] != 1) {
		display_error($command, $curl, $response);
		die();
	}

	foreach ($response["result"] as $entry)
		$current[$type][$entry["name"]][$entry["id"]] = $entry["content"];

	foreach ($master[$type] as $host => $value) {

		if ($host != $domain && (strlen($host) < abs($offset) || strrpos($host, ".".$domain, $offset) === false))
			continue;

		if ($type == "TXT") {
			$lines = explode("\n", str_replace('"', '', $value));
			sort($lines);

			if (!isset($current[$type][$host])) {
				foreach ($lines as $line)
					$changes[] = "$curl -X POST \"$endpoint\" --data '{\"type\":\"$type\",\"name\":\"$host\",\"content\":\"$line\"}'";
			} else if (implode("\n", $lines) != implode("\n", $current[$type][$host])) {
				foreach ($current[$type][$host] as $id => $content)
					$changes[] = "$curl -X DELETE \"$endpoint/$id\"";
				foreach ($lines as $line)
					$changes[] = "$curl -X POST \"$endpoint\" --data '{\"type\":\"$type\",\"name\":\"$host\",\"content\":\"$line\"}'";
			}

		} else {  // A, CNAME

			if (!isset($current[$type][$host]))
				$changes[] = "$curl -X POST \"$endpoint\" --data '{\"type\":\"$type\",\"name\":\"$host\",\"content\":\"$value\"}'";
			else
				foreach ($current[$type][$host] as $id => $content)
					if ($content != $value)
						$changes[] = "$curl -X PUT \"$endpoint/$id\" --data '{\"type\":\"$type\",\"name\":\"$host\",\"content\":\"$value\"}'";
		}
	}

	foreach ($current[$type] as $host => $values)
		if (!isset($master[$type][$host]))
			foreach ($values as $id => $value)
				$changes[] = "$curl -X DELETE \"$endpoint/$id\"";
}

//print_r($changes);

foreach ($changes as $change) {
	$response = execute($change);

	if ($response["success"] != 1) {
		display_error($change, $curl, $response);
	}
}
