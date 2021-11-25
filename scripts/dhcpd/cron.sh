#!/bin/bash

if [ "$4" = "" ]; then
	echo "usage: $0 <dhcp-server[:port]> <zone> <network-address> <network-mask> [authoritative]"
	exit 1
elif ! [[ $1 =~ ^[a-z0-9.-]+[.][a-z0-9]+([:][0-9]+)?$ ]]; then
	echo "error: parameter $1 not conforming hostname format"
	exit 1
fi

server=$1
zone=$2
netaddr=$3
netmask=$4

if [ -z "${server##*:*}" ]; then
	host="${server%:*}"
	port="${server##*:}"
else
	host=$server
	port=22
fi

key=`/opt/farm/ext/keys/get-ssh-dedicated-key.sh $host root`

hour=`date +%H%M`
day=`date +%d`
mon=`date +%m`
year=`date +%Y`

path=~/.zonemanager/cache/$year/${year}${mon}/${year}${mon}${day}/$host/$hour
mkdir -p $path

file=$path/dhcpd.conf
/opt/zonemanager/scripts/dhcpd/generate-config-file.php $zone $netaddr $netmask $file $5

if [ -s $file ]; then
	scp -i $key -P $port -B -p -o StrictHostKeyChecking=no $file root@$host:/etc/dhcp
	ssh -i $key -p $port root@$host /etc/init.d/isc-dhcp-server restart >/dev/null
fi
