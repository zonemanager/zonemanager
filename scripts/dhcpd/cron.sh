#!/bin/bash
. /opt/farm/scripts/init
. /opt/farm/scripts/functions.custom


if [ "$4" = "" ]; then
	echo "usage: $0 <dhcp-server> <zone> <network-address> <network-mask> [authoritative]"
	exit 1
elif ! [[ $1 =~ ^[a-z0-9.-]+[.][a-z0-9]+$ ]]; then
	echo "error: parameter $1 not conforming hostname format"
	exit 1
fi

server=$1
zone=$2
netaddr=$3
netmask=$4
key=`ssh_dedicated_key_storage_filename $server root`

hour=`date +%H%M`
day=`date +%d`
mon=`date +%m`
year=`date +%Y`

path=/var/cache/dns/$year/${year}${mon}/${year}${mon}${day}/$server/$hour
mkdir -p $path

file=$path/dhcpd.conf
/opt/zonemaster/scripts/dhcpd/generate-config-file.php $zone $netaddr $netmask $file $5

if [ -s $file ]; then
	scp -i $key -B -p -o StrictHostKeyChecking=no $file root@$server:/etc/dhcp
	ssh -i $key root@$server /etc/init.d/isc-dhcp-server restart >/dev/null
fi
