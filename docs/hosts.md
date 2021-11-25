# /etc/hosts file

`/etc/hosts` is a special file, that contains mapping between particular hostnames and IP addresses.

If Zone Manager is run on the same host, as primary DNS server, it can provision this file with all `A` records, and all `CNAME` records, for which destination hostname also has `A` record.

## Domain configuration structure

Zone Manager uses 2 types of domain configuration: `internal` and `public` (`/etc/hosts` file is always `internal`).

Internal configuration is divided into 3 files:

`~/.zonemanager/dns/zone.all` - common for all configured zones, holds all static entries (updated manually)

`~/.zonemanager/dns/zone.yourzone` - separate for each configured zone, holds all zone-specific static entries (you can eg. have multiple offices, and in each office set hostname `printer.office` to have different IP address, specific to the local network)

`~/.zonemanager/dns/zone.yourzone-dynamic` - separate for each configured zone, holds all zone-specific dynamic entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

## /etc/hosts restrictions

When generating `/etc/hosts` file, Zone Manager skips these records:

- all wildcard records (with `*` character, eg. `*.samba.office`)
- `TXT` records (only `A` and `CNAME` are supported)
- `CNAME` records pointing to external hosts, that are not configured in database as `A` records

## Provisioning process

Once you completed creating an initial database for Zone Manager, you can this script to your `/etc/crontab` file:

```
*/30 * * * * root /opt/zonemanager/scripts/hosts/cron.sh
```
