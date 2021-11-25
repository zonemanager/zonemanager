# Cloudflare


## Before you start with Zone Manager

- register Cloudflare account, configure your plan and payment details
- create your domain configuration - at this point Cloudflare will guess and automatically replicate most DNS records from your current DNS servers - for advanced configurations this list will probably be incomplete, so check it manually and create missing records of types other than A, CNAME and TXT (these will be created by Zone Manager)
- Cloudflare should ask you at this point to change DNS servers for your domain to `*.ns.cloudflare.com` - if you can do it yourself in your domain registrar panel, do it **after** you finish the configuration (especially if you're migrating a domain with working services) - but it you need  to create some support ticket and wait, feel free to do it now
- go to Overview tab for chosen domain (note: for Free plan, your account will support only 1 domain) and copy Zone ID from Domain Summary - this is the key you need to put into cron job
- go to [My Profile](https://dash.cloudflare.com/profile) and copy Global API Key from API Keys section (you will need to enter your account password there) - this is the key you need to put into `*.headers` file (see below)


## Install Server Farmer and Zone Manager

Zone Manager relies on Server Farmer, so first you need to install it on your server (in the very basic, single server configuration, without installing any roles - see instructions [here](http://serverfarmer.org/getting-started.html)).


## Configure Zone Manager

Cloudflare uses more than one API authentication model. This page shows how to authenticate with Global API Key (the simplest one), but with Zone Manager you can use any of them, just by putting all required headers into file mentioned below.

So, let's create an authentication file. For each domain you need a separate one (so you can use many Cloudflare accounts):

```
mkdir -m 0700 -p ~/.zonemanager/accounts/cloudflare
touch ~/.zonemanager/accounts/cloudflare/yourdomain.com.headers
```

How this file should look like:

```
X-Auth-Email: cloudflare@yourdomain.com
X-Auth-Key: BOdWPNFsicROJzo8AbAHAE1tQL3Ot6RcAjiJr
```

Where `X-Auth-Email` is your email address, at which you registered your Cloudflare account, and `X-Auth-Key` is the Global API Key. In general, whatever you put into this file, will be converted into custom http headers sent to Cloudflare during all requests.

Now it's time to add this script to your `/etc/crontab` file (replacing the example arguments with your real ones: domain name and Zone ID copied from Domain Summary):

```
*/10 * * * * root /opt/zonemanager/scripts/cloudflare/cron.sh yourdomain.com b0ff81e3cf4ce1e66a896d829a8931e0
```

This will cause Zone Manager to check each 10 minutes, if Cloudflare domain configuration is current, and send the updates (note that only the updates are sent, so the whole operation is very efficient and can be performed frequently). For the first run, this will cause Zone Manager to create any missing `A`/`CNAME`/`TXT` records that weren't automatically guessed and replicated by Cloudflare during domain setup.


## Domain configuration structure

Zone Manager uses 2 types of domain configuration: `internal` and `public` (Cloudflare is always `public`).

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


## Migrating existing domains

With Zone Manager you can manage exactly the same domain in multiple places at the same time, eg. Amazon Route53 and Cloudflare. **What matters for end clients, is what DNS servers are set in your domain registrar configuration panel.**

Therefore, the proper order of actions during domain migration from one DNS service to another is:
- create the new account and domain configuration
- create cron job for new DNS service and make sure that is works without raising errors
- make sure that records other than `A`/`CNAME`/`TXT` are also properly replicated
- change DNS servers either in your domain configuration panel or by creating a support ticket
- wait until TTL for NS records will expire and frequently check, if your domain still works properly
- **when you're sure that everything went ok and all services are working properly after migration**, disable cron job for old DNS service
