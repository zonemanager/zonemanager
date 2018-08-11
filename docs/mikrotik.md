# MikroTik RouterOS-based routers

## Before you start

ZoneMaster connects to MikroTik routers using ssh, with key authentication. Before going further, you need to generate ssh DSA (not RSA) key pair and install public key on all routers you plan to connect to.

```
ssh-keygen -t dsa -f /etc/local/.ssh/id_backup_mikrotik
```

Now, you have 2 files:

`/etc/local/.ssh/id_backup_mikrotik` is the private key, and you should protect it before any unauthorized access

`/etc/local/.ssh/id_backup_mikrotik.pub` is the public key, and you should install it on your routers:

```
scp -P 10022 /etc/local/.ssh/id_backup_mikrotik.pub admin@router.yourdomain.com:
```

Then log in to your router using ssh (authenticating with password for the last time), and import the key to your user:

```
ssh -p 10022 admin@router.yourdomain.com
[admin@router] > user ssh-keys import public-key-file=id_backup_mikrotik.pub user=admin
```

## Configure DNS

If you already have some static DNS entries configured on your MikroTik router, you can scan them and automatically create zone files:

```
/opt/zonemaster/scripts/mikrotik/scan-dns.php router.yourdomain.com >/etc/local/.dns/zone.all.new
```

This script will generate the zone file (which will need some manual fine-tuning later - see below). To avoid overwriting your `zone.all` main zone file, the above example appends ".new" suffix to the file name (you have to either rename it to `zone.all` or copy chosen entries between these files).

**When you finished creating your zone configuration**, add this script to your /etc/crontab file (one entry per router):

```
0 * * * * root /opt/zonemaster/scripts/mikrotik/update-dns.php yourzone router.yourdomain.com
0 * * * * root /opt/zonemaster/scripts/mikrotik/update-dns.php office2 office2-router.yourdomain.com
0 * * * * root /opt/zonemaster/scripts/mikrotik/update-dns.php office3 office3-router.yourdomain.com
```

In the above example there are 3 internal zones: `home`, `office1` and `office2`. If you only have one router, you can name your zone just like your local domain suffix, eg. `home`. Each physical network is a separate zone. See below for details.

## Domain configuration structure

ZoneMaster uses 2 types of domain configuration: `internal` and `public` (MikroTik is always `internal`).

Internal configuration is divided into 3 files:

`/etc/local/.dns/zone.all` - common for all configured zones, holds all static entries (updated manually)

`/etc/local/.dns/zone.yourzone` - separate for each configured zone, holds all zone-specific static entries (you can eg. have multiple offices, and in each office set hostname `printer.office` to have different IP address, specific to the local network)

`/etc/local/.dns/zone.yourzone-dynamic` - separate for each configured zone, holds all zone-specific dynamic entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

All these files support optional 4th column with MAC address. Only entries with MAC address are used for DHCP configuration.

## DNS record types

ZoneMaster manages 3 DNS record types: `A`, `CNAME` and `TXT`. However, static DNS service in MikroTik routers supports only `A` records. So:

- `TXT` records are not supported (and just ignored)
- ZoneMaster converts `CNAME` records on-the-fly to `A` records, expanding alias hostname to IP (however this requires that alias hostname has its entry in ZoneMaster database)
- when scanning existing static DNS, ZoneMaster checks `ttl` field of each record (entries valid for day or longer are considered `A` records, while entries valid for hours are converted back to `CNAME` records)

## Example configuration files

`/etc/local/.dns/zone.all` file:

```
# network devices
router.home                              A          192.168.8.1
router.office1                           A          192.168.11.1
router.office2                           A          192.168.12.1

# home servers
printer.home                             A          192.168.8.2       23:54:23:35:45:65
samba.home                               A          192.168.8.3       76:65:54:43:32:21
movies.home                              CNAME      samba.home

# company servers
server.dc1                               A          192.168.26.3      75:2c:35:23:54:94
printer.dc1                              A          192.168.26.4      75:2c:3f:45:43:23

# CRM - directly from internal network
crm.companydomain.com                    CNAME      server.dc1
```

`/etc/local/.dns/zone.home` file:

```
printer.internal                         CNAME      printer.home
```

`/etc/local/.dns/zone.dc1` file:

```
printer.internal                         CNAME      printer.dc1
```

## Configure DHCP

ZoneMaster can also manage MikroTik DHCP Server service, using the same zone configuration files. As you can see on the above example, each `A` record can have optional 4th column with MAC address.

**When you finished creating your zone configuration**, add this script to your /etc/crontab file (one entry per router):

```
0 * * * * root /opt/zonemaster/scripts/mikrotik/update-dhcp.php yourzone router.yourdomain.com 192.168.8.0 255.255.255.0
```

The last 2 arguments here (192.168.8.0 and 255.255.255.0) are the local network address and mask, that are configured in DHCP pool of `router.yourdomain.com` router.
