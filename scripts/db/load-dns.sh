#!/bin/sh

path=~/.zonemanager/dns

type=$1
zone=$2

(

if [ "$type" = "public" ]; then
	cat $path/zone.public $path/zone.public-$zone 2>/dev/null
else
	cat $path/zone.all $path/zone.$zone $path/zone.$zone-dynamic 2>/dev/null
fi

) |grep -v ^$ |grep -v "^#" |awk '{
x = split($0, a, "\"")
if (x > 1) {$3 = a[2]}
if ($2 == "TXT") {t = "\"" $3 "\""; $3 = t}
print $1 " " $2 " " $3
}'
