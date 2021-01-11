[![Build Status](https://travis-ci.org/zonemanager/zonemanager.png?branch=master)](https://travis-ci.org/zonemanager/zonemanager)

![Zone Manager logo](docs/logo.png)


# Overview

Zone Manager is a tool designed for companies, that manage many domains, networks and associated DNS/DHCP servers. It allows having one central database of DNS/DHCP records and replicating them in a controlled way to various DNS/DHCP software or hardware:

- [MikroTik](docs/mikrotik.md) routers - both DNS and DHCP, for internal networks (eg. office)
- [Amazon Route53](docs/aws.md) service - for public domain DNS configuration
- [Cloudflare](docs/cloudflare.md) service - for public domain DNS configuration
- [BIND 9](docs/bind.md) - for both public and internal DNS configurations
- [ISC-DHCP server](docs/dhcp.md) - for internal networks
- [/etc/hosts](docs/hosts.md) file - DNS fallback for specific cases, to avoid relying on DNS server

Click on chosen link to see the detailed setup instructions.

The idea behind Zone Manager is that instead of having eg. 50 various places for configuring DNS entries for various domains and customers, user can maintain one simple and easy to understand, central database, and no longer needs to care about various DNS admin panels, passwords for external accounts etc.

# Zone Manager database

## File format

Zone Manager database resembles BIND zone file format, but is simpler:

```
# network devices
router.home                         A          192.168.8.1
router.office                       A          192.168.11.1
router.dc1                          A          192.168.26.1

# home servers
printer.home                        A          192.168.8.2       23:54:23:35:45:65
samba.home                          A          192.168.8.3       76:65:54:43:32:21
movies.home                         CNAME      samba.home

# company servers
server.dc1                          A          192.168.26.3      75:2c:35:23:54:94
printer.dc1                         A          192.168.26.4      75:2c:3f:45:43:23

# CRM - directly from internal network
crm.companydomain.com               CNAME      server.dc1

# customer domains
yourdomain.com                      CNAME      ec2-52-10-70-178.us-west-2.compute.amazonaws.com
*.yourdomain.com                    CNAME      yourdomain.com
```

The main difference between BIND zone file and Zone Manager database is that all entries are mixed in one file (in fact, 5 files or more, see below), and that all entries are written in full form (not stripping suffix).

Description of columns:

1. Full hostname (required)
2. Record type (required, one of: `A`, `CNAME`, `TXT`)
3. Record value (required, IP address for `A`, hostname for `CNAME`, text string for `TXT` - possibly multi-line)
4. MAC address for DHCP (optional)

## Supported DNS record types

Zone Manager can manage 3 types of records: `A`, `CNAME`, and `TXT`. All other record types are supported in a way specific for particular DNS platform, but cannot be managed directly.

## Public and internal DNS

Zone Manager allows managing both `public` (Internet-wide) and `internal` (local) DNS services. Also, it is written to allow easy integration with other applications and custom-made scripts. That's why its database is divided into 5 or more files (all of them have exactly the same format).

##### Internal zones configuration is divided into 3 files:

`/etc/local/.dns/zone.all` - common for all configured zones, holds all **static** entries (updated manually)

`/etc/local/.dns/zone.yourzone` - separate for each configured zone, holds all zone-specific **static** entries (you can eg. have multiple offices, and in each office set hostname `printer.office` to have different IP address, specific to the local network)

`/etc/local/.dns/zone.yourzone-dynamic` - separate for each configured zone, holds all zone-specific **dynamic** entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

##### Public domains configuration is divided into 2 files:

`/etc/local/.dns/zone.public` - common for all configured domains, holds all **static** entries (updated manually)

`/etc/local/.dns/zone.public-yourdomain.com` - separate for each configured domain, holds all **dynamic** entries (these files are meant to be generated automatically by some external tool, at your disposal - otherwise they should be empty)

# Compatible operating systems

Zone Manager is fully tested with Debian 8.x (Jessie), and all Ubuntu LTS versions since 14.04 LTS.

# Security

Zone Manager relies on [Server Farmer](http://serverfarmer.org/basics.html) management framework and inherits very similar security implications:

1. Central management server, called *farm manager*, has ssh root keys for **all** other managed servers, routers and DNS/DHCP services. Therefore, someone who has access to *farm manager*, can do literaly **everything** with your network, as well as with all networks, servers, domains etc. managed for your customers.

2. Therefore, both Server Farmer and Zone Manager are intentionally designed with one important functional limitation in mind: there can be only one *primary* administrator (who has access to *farm manager* and all ssh keys), and possibly unlimited number of other people with privileged access to particular managed servers/services.

Such security model fits many software houses and IT outsourcing companies. But before starting your adventure with Zone Manager and/or Server Farmer, consider first, if this security model is acceptable for you and your company.

# How to contribute

We are welcome to contributions of any kind: bug fixes, added code comments, support for new operating system versions or hardware etc.

If you want to contribute:
- fork this repository and clone it to your machine
- create a feature branch and do the change inside it
- push your feature branch to github and create a pull request

# License

|                      |                                          |
|:---------------------|:-----------------------------------------|
| **Author:**          | Tomasz Klim (<opensource@tomaszklim.pl>) |
| **Copyright:**       | Copyright 2016-2021 Tomasz Klim          |
| **License:**         | MIT                                      |

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
