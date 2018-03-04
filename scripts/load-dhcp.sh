#!/bin/sh

path=/etc/local/.dns

zone=$1

cat $path/zone.all $path/zone.$zone $path/zone.$zone-dynamic 2>/dev/null |grep -v ^$ |grep -v "^#" |awk '$4{ print $4 " " $3 " " $1 }' |grep -v "^#"
