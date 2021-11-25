<?php

function cf_client($zone)
{
	$home = getenv("HOME");
	$file = "$home/.zonemanager/accounts/cloudflare/$zone.headers";
	$command = "curl -s -H \"Content-Type: application/json\"";

	$lines = file($file);
	foreach ($lines as $line) {
		$tmp = explode(":", $line);
		$key = trim($tmp[0]);
		$value = trim($tmp[1]);
		$command .= " -H \"$key: $value\"";
	}

	return $command;
}


function execute($command)
{
	$json = shell_exec($command);
	$response = json_decode($json, true);
	return $response;
}


function display_error($command, $curl, $response)
{
	$redacted = str_replace($curl, "curl", $command);
	echo "\n$redacted\n\n";
	print_r($response);
}
