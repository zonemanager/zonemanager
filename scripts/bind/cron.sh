#!/bin/bash

if [ "$1" = "--public" ]; then
	shift
	scope="public"
else
	scope="internal"
fi

if [ "$3" = "" ]; then
	echo "usage: $0 [--public] <dns-server[:port]> <zone> <domain> [domain] [...]"
	exit 1
elif ! [[ $1 =~ ^[a-z0-9.-]+[.][a-z0-9]+([:][0-9]+)?$ ]]; then
	echo "error: parameter $1 not conforming hostname format"
	exit 1
fi

server=$1
inzone=$2

if [ -z "${server##*:*}" ]; then
	host="${server%:*}"
	port="${server##*:}"
else
	host=$server
	port=22
fi

key=`/opt/farm/ext/keys/get-ssh-dedicated-key.sh $host root`
shift
shift

hour=`date +%H%M`
day=`date +%d`
mon=`date +%m`
year=`date +%Y`

path=~/.zonemanager/cache/$year/${year}${mon}/${year}${mon}${day}/$host/$hour
mkdir -p $path

for domain in $@; do

	file=$path/db.$domain
	/opt/zonemanager/scripts/bind/generate-zone-file.php $inzone $domain $file $scope

	if [ -s $file ]; then
		scp -B -p -i $key -P $port -o StrictHostKeyChecking=no $file root@$host:/etc/bind
	fi
done

ssh -i $key -p $port root@$host /etc/init.d/bind9 restart >/dev/null
