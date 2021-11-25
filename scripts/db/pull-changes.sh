#!/bin/sh
# This script allows sharing ~/.zonemanager/dns between many users through Git repository.

if [ -d ~/.zonemanager/dns/.git ]; then
	DIR="`pwd`"
	cd ~/.zonemanager/dns
	if grep -q git@ ~/.zonemanager/dns/.git/config && [ -f ~/.ssh/id_github_zonemanager ]; then
		GIT_SSH=/opt/farm/scripts/git/helper.sh GIT_KEY=~/.ssh/id_github_zonemanager \
		git pull |grep -v "Already up-to-date"
	else
		git pull |grep -v "Already up-to-date"
	fi
	cd "$DIR"
fi
