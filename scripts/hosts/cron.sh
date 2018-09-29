#!/bin/bash
. /opt/farm/scripts/init



/opt/zonemanager/scripts/hosts/generate-hosts-file.php $OSVER ${HOST#*.} >/etc/hosts.new

if [ -s /etc/hosts.new ]; then
	mv -f /etc/hosts.new /etc/hosts
fi
