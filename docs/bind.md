# BIND 9

## Overview

Zone Manager can provision BIND 9 DNS servers. The whole provisioning process is based on Server Farmer ssh/scp-passwordless connections, so you first need to install Server Farmer:

- on all DNS server hosts that you want to provision
- on Zone Manager central instance (it has to be *farm manager* for DNS server hosts)

You can find more instructions about Server Farmer [here](http://serverfarmer.org/getting-started.html).

## Provisioning process

Once you completed creating an initial database for Zone Manager, you can add such scripts to your /etc/crontab file (one entry per DNS server):

```
*/30 * * * * root /opt/zonemanager/scripts/bind/cron.sh your-dns-server.com domain1.com domain2.com
```

You can provision as many domains as you want in one single invocation. How the provisioning process works:

- zone files for each domains are created locally and saved into `/var/cache/dns` directory
- zone files are copied using scp to `/etc/bind` directory on DNS server
- `bind9` service is restarted

Note that restarting all BIND servers at the same time can lead to a network failure. So instead of example `*/30`, each DNS server should be restarted at other time, eg. `1,31`, `2,32` etc.


## Domain configuration structure

Zone Manager uses 2 types of domain configuration: `internal` and `public`. BIND 9 driver, as opposite to other drivers, supports both these types:

`public` is for public (visible to anyone in the Internet) DNS servers

`internal` is for internal (eg. home, office) DNS servers, that either serve internal domains (eg. `*.office`), or override a domain (eg. `*.yourdomain.com`) from public DNS servers with the internal version

Note that Zone Manager can work at both modes at the same time, using the same database. However, **securing BIND server configuration for use as public DNS server is hard and requires frequent attention**, so we encourage to choose other solution instead, preferably Amazon Route53, which is also supported.

### Domain vs zone

In many places, "domain" and "zone" concepts are used interchangeably. And in most cases (except when discussing DNS configuration) it is more or less correct.

Zone Manager however, as DNS configuration tool, is more specific and uses a few different concepts:

- **domain** - refers mostly to the domain name as such - either internal (`office`) or public (`yourdomain.com`)
- **zone** - is a configuration space in DNS server, dedicated for the particular domain (for BIND, each zone has its own configuration file, that holds all the records and settings for this domain)
	- **load zone** - is the Zone Manager zone file pair to load (eg. `home` will load `/etc/local/.dns/zone.home` and `/etc/local/.dns/zone.home-dynamic`, see below for details)
	- **generate zone** - is a domain suffix to strip (eg. `printer.home` entry from Zone Manager database will be written as `printer` in output zone file)
	- **bind zone file** - name of zone file used by BIND server (eg. `/etc/bind/db.home`)

Thanks to separation of **load zone** and **generate zone**, Zone Manager is able to use the same database to generate zone files for many BIND servers in multiple physical locations, eg. for home and office.

### BIND zone replication

Zone Manager assumes that all provisioned BIND servers are configured as master/standalone (without any zone replication). However, Zone Manager generates and uploads only zone files, while the rest of BIND server configuration is to be maintained manually, so you are free to create zone replication.

### Internal zone configuration

Internal configuration is divided into 3 files:

`/etc/local/.dns/zone.all` - common for all configured zones, holds all static entries (updated manually)

`/etc/local/.dns/zone.yourzone` - separate for each configured zone, holds all zone-specific static entries (you can eg. have multiple offices, and in each office set hostname `printer.office` to have different IP address, specific to the local network)

`/etc/local/.dns/zone.yourzone-dynamic` - separate for each configured zone, holds all zone-specific dynamic entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

### Public domain configuration

Public configuration is divided into 2 files:

`/etc/local/.dns/zone.public` - common for all configured domains, holds all static entries (updated manually)

`/etc/local/.dns/zone.public-yourdomain.com` - separate for each configured domain, holds all dynamic entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

### Zone file templates

Zone Manager manages 3 DNS record types: `A`, `CNAME` and `TXT`. It is not enough to fully configure a domain, especially for public visibility.

However, Zone Manager uses zone template files, in which you can add all other record types, as well as other configuration directives for BIND. This is an example zone file template named `/etc/local/.dns/bind.yourdomain.com`:

```
$ORIGIN yourdomain.com.
$TTL 3D

@                                       IN    SOA       dns1  admin (
                                                        @@serial@@  ;serial number
                                                        2H          ;refresh
                                                        300         ;retry
                                                        4W          ;expiration
                                                        1D )        ;minimum
;

@                                       IN    MX        1 aspmx.l.google.com.
@                                       IN    MX        5 alt1.aspmx.l.google.com.
@                                       IN    MX        5 alt2.aspmx.l.google.com.
@                                       IN    MX        10 aspmx2.googlemail.com.
@                                       IN    NS        dns1

@@entries@@
```

Such template should contain 2 keywords, which are expanded by Zone Manager:

- `@@serial@@` - expanded to new zone serial number (current date in `ymdHi` format)
- `@@entries@@` - expanded to generated list of DNS records

All the rest is up to you, as well as all other BIND configuration files.

### Creating initial configuration with AXFR transfer

If you currently have local BIND servers with some zone that you want to migrate to Zone Manager, you can just try to generate it using AXFR transfer. First try to execute this command:

```
dig axfr yourzone
```

It can return empty result (just `dig` headers), which means that you have AXFR transfer disabled (or you have some other software than BIND). Check and enable AXFR, and then use this script:

```
/opt/zonemanager/scripts/bind/axfr-scan-domain.php yourzone
```

It will create 2 files: `/etc/local/.dns/zone.yourzone.dist` and `/etc/local/.dns/bind.yourzone.dist` - after applying some fine-tuning you can rename them, dropping `.dist` suffix.

