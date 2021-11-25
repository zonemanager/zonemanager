# ISC-DHCP server

## Overview

Zone Manager can provision ISC-DHCP servers. The whole provisioning process is based on Server Farmer ssh/scp-passwordless connections, so you first need to install Server Farmer:

- on all DHCP server hosts that you want to provision
- on Zone Manager central instance (it has to be *farm manager* for DHCP server hosts)

You can find more instructions about Server Farmer [here](http://serverfarmer.org/getting-started.html).

## Provisioning process

Once you completed creating an initial database for Zone Manager, you can add such scripts to your `/etc/crontab` file (one entry per DHCP server):

```
*/30 * * * * root /opt/zonemanager/scripts/dhcpd/cron.sh your-dhcp-server.local yourzone 192.168.8.0 255.255.255.0 authoritative
```

Arguments 192.168.8.0 and 255.255.255.0 are the local network address and mask, that are configured in DHCP pool. Zone Manager will filter out all database entries, that don't match given network - so you can safely mix entries for different physical networks and DHCP servers within a single database.

How the provisioning process works:

- dhcpd.conf file is created locally and saved into `~/.zonemanager/cache` directory
- dhcpd.conf file is copied using scp to `/etc/dhcp` directory on DHCP server
- `isc-dhcp-server` service is restarted

## Configuration structure

Zone Manager is primarily focused on DNS management. It keeps configuration for internal (eg. home, office) zones divided into 3 files:

`~/.zonemanager/dns/zone.all` - common for all configured zones, holds all static entries (updated manually)

`~/.zonemanager/dns/zone.yourzone` - separate for each configured zone, holds all zone-specific static entries (you can eg. have multiple offices, and in each office set hostname `printer.office` to have different IP address, specific to the local network)

`~/.zonemanager/dns/zone.yourzone-dynamic` - separate for each configured zone, holds all zone-specific dynamic entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

Example entries:

```
server.dc1                               A          192.168.8.3       75:2c:35:23:54:94
printer.dc1                              A          192.168.8.4       75:2c:3f:45:43:23
```

The last, 4th column is optional and contains MAC address. Only entries with MAC address are used for DHCP configuration.

### Configuration templates

Each physical network (zone) has its own configuration template. This is the example template named `~/.zonemanager/dns/dhcpd.yourzone`:

```
ddns-update-style none;
default-lease-time 1800;
max-lease-time 3600;
@@authoritative@@

log-facility local7;

option domain-name "office";
option domain-name-servers 192.168.8.1, 192.168.8.2;
option routers 192.168.8.1;
option broadcast-address 192.168.8.255;

subnet 192.168.8.0 netmask 255.255.255.0 {
    range 192.168.8.3 192.168.8.249;
}

@@entries@@
```

Such template should contain 2 keywords, which are expanded by Zone Manager:

- `@@entries@@` - expanded to generated list of DHCP records
- `@@authoritative@@` - expanded to either `authoritative;` configuration directive, or to empty string (depending on the last argument in crontab invocation, see above)
