#!/bin/bash
. /opt/farm/scripts/init



/opt/zonemaster/scripts/generate-hosts.php $OSVER ${HOST##*.} /etc/hosts.new

if [ -s /etc/hosts.new ]; then
	mv -f /etc/hosts.new /etc/hosts
fi
