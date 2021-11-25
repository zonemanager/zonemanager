#!/bin/sh

if [ "$3" = "" ]; then
	echo "usage: $0 <awscli-profile-name> <domain-name> <zone-id>"
	exit 1
elif ! grep -q "\[$1\]" ~/.aws/credentials; then
	echo "error: awscli profile \"$1\" not found"
	exit 1
fi

hour=`date +%H%M`
day=`date +%d`
mon=`date +%m`
year=`date +%Y`

path=~/.zonemanager/cache/$year/${year}${mon}/${year}${mon}${day}/aws/$hour
file=$path/change-$1-$2.json

/opt/zonemanager/scripts/aws/update-zone.php $1 $2 $3 $file
