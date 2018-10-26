#!/bin/sh

if [ "$2" = "" ]; then
	echo "usage: $0 <domain-name> <zone-id>"
	exit 1
elif [ ! -f /etc/local/.cloudflare/$1.headers ]; then
	echo "error: cloudflare credentials for domain \"$1\" not found"
	exit 1
fi

/opt/zonemanager/scripts/cloudflare/update-zone.php $1 $2
