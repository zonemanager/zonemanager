#!/usr/bin/php
<?php
require_once "/opt/zonemaster/includes/functions.php";

if ($argc < 2)
	die("usage: $argv[0] <zone>\n");

$zone = $argv[1];
$assignments = load_dhcp_entries("internal", $zone);

foreach ($assignments as $address => $assign) {
	$ip = $assign[0];
	$alias = $assign[1];
	echo "host $alias {\n\thardware ethernet $address;\n\tfixed-address $ip;\n}\n\n";
}


/*

TODO list:

1. load only assignments served in current physical zone

2. create full isc-dhcp-server configuration

3. consider creating /etc/network/interfaces (or RHEL equivalent) for local server

4. support for MikroTik routers

*/
