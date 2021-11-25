# Amazon Route53


## Before you start with Zone Manager

- register AWS account [here](https://aws.amazon.com/) and configure payment details
- create your access key (either on main account, or better on IAM user with permissions only for Route53)
	- it will look like this: `AKIAABCDEFABCDEFABCD`
	- and secret access key will look like this: `QydrL3cF8w9vQL+tkybvC7N4qQ0BcFlS6hWhkX1g`
- create hosted zone for your domain in Route53
	- you will get zone ID, that will look like this: `Z25SD356N45NLE`
- you will also get 4 DNS server hostnames (note: different for each configured zone) - log in to your domain configuration panel and set custom DNS servers for your domain, then enter servers from given list  (note: you may be asked for just 2 or 3 servers - provide as many as possible there)

## Install Server Farmer and Zone Manager

Zone Manager relies on Server Farmer, so first you need to install it on your server (in the very basic, single server configuration, without installing any roles - see instructions [here](http://serverfarmer.org/getting-started.html)).

Next, install `awscli` AWS command line tool and PHP command line. All required software (including dependencies) is installed by this Server Farmer extension:

```
apt-get install libyaml-dev libpython-dev python-yaml python-pip
pip install awscli
```

## Configure Zone Manager

First, configure AWS account. You will be asked for your access key and secret access key (see above), and some other things, that don't matter for Route53 (so just choose default values). In this example, `zone1` is the name for your AWS account (you can configure Zone Manager to use different AWS account for each hosted zone):

```
aws configure --profile zone1
```

Now let's start creating your zone files. You can just use this script (replace `Z25SD356N45NLE` with your zone ID, and `yourdomain.com` with your domain name):

```
/opt/zonemanager/scripts/aws/scan-zone.php zone1 Z25SD356N45NLE >~/.zonemanager/dns/yourdomain.com
```

It will scan the initial configuration of your domain record sets and will generate the zone
file (which will need some manual fine-tuning later - see below).

**When you finished creating your domain configuration**, add this script to your `/etc/crontab` file (again replacing the example arguments with your real ones):

```
*/10 * * * * root /opt/zonemanager/scripts/aws/cron.sh zone1 yourdomain.com Z25SD356N45NLE
```

This will cause Zone Manager to check each 10 minutes, if Route53 domain configuration is current, and send the updates (note that only the updates are sent, so the whole operation is very efficient and can be performed frequently).



## Domain configuration structure

Zone Manager uses 2 types of domain configuration: `internal` and `public` (Amazon Route53 is always `public`).

Public configuration is divided into 2 files:

`~/.zonemanager/dns/zone.public` - common for all configured domains, holds all static entries (updated manually)

`~/.zonemanager/dns/zone.public-yourdomain.com` - separate for each configured domain, holds all dynamic entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

## Example configuration files

`~/.zonemanager/dns/zone.public` file:

```
# basic domain configuration (static entries)
mx.yourdomain.com                        A        52.35.36.25
```

`~/.zonemanager/dns/zone.public-yourdomain.com` file:

```
# this file contains frequently changed entries and is generated automatically
dynamic.yourdomain.com                   CNAME    ec2-52-10-70-178.us-west-2.compute.amazonaws.com
dynamic2.yourdomain.com                  CNAME    ec2-52-35-36-25.us-west-2.compute.amazonaws.com
dynamic3.yourdomain.com                  CNAME    ec2-52-34-55-16.us-west-2.compute.amazonaws.com
```
