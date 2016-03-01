#!/bin/sh

path=/etc/local/.dns

type=$1
zone=$2

(

if [ "$type" = "public" ]; then
	cat $path/zone.public $path/zone.public-$zone 2>/dev/null
else
	cat $path/zone.all $path/zone.$zone $path/zone.$zone-dynamic 2>/dev/null
fi

) |grep -v ^$ |grep -v "^#" |awk '{ print $1 " " $2 " " $3 }'
