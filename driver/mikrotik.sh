#!/bin/bash

if [ "$1" = "" ]; then exit 1; fi

router=$1
shift

if [ -z "${router##*:*}" ]; then
	host="${router%:*}"
	port="${router##*:}"
else
	host=$router
	port=22
fi


# to make this script work, you have to:
# - create ssh DSA key
# - install it on your router
# - put it in this path (or change the path to point to it)
#
# further details: http://fajne.it/automatyzacja-backupu-routera-mikrotik.html
#
ssh -y -i /etc/local/.ssh/id_backup_mikrotik -p $port admin@$host $@
