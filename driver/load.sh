#!/bin/sh

path=/etc/local/.config
zone=$1

cat $path/zone.all $path/zone.$zone $path/zone.$zone-dynamic 2>/dev/null |grep -v ^$ |grep -v "^#" |awk '{ print $1 " " $2 " " $3 }'
