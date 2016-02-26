#!/bin/bash
. /opt/farm/scripts/init
. /opt/farm/scripts/functions.custom



if [ "$1" = "--public" ]; then
	shift
	scope="public"
else
	scope="internal"
fi

if [ "$2" = "" ]; then
	echo "usage: $0 [--public] <dns-server> <domain> [domain] [...]"
	exit 1
elif ! [[ $1 =~ ^[a-z0-9.-]+[.][a-z0-9]+$ ]]; then
	echo "error: parameter $1 not conforming hostname format"
	exit 1
fi

server=$1
key=`ssh_management_key_storage_filename $server`
shift

hour=`date +%H%M`
day=`date +%d`
mon=`date +%m`
year=`date +%Y`

path=/var/cache/dns/$year/${year}${mon}/${year}${mon}${day}/$server/$hour
mkdir -p $path

for domain in $@; do

	file=$path/db.$domain
	/opt/zonemaster/scripts/generate-bind9-file.php $domain $domain $file $scope

	if [ -s $file ]; then
		scp -B -p -i $key $file root@$server:/etc/bind
	fi
done

ssh -i $key root@$server /etc/init.d/bind9 restart >/dev/null
