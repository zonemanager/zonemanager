[![Build Status](https://travis-ci.org/tomaszklim/zonemaster.png?branch=master)](https://travis-ci.org/tomaszklim/zonemaster)


# The problem

Imagine that you manage a medium-size local network, split across several offices and/or
data centers, where you have:

- servers and desktop computers (always connected in the same place)
- laptops, tablets and mobile phones (connected in different offices)

All these locations are connected using VPN tunnels and all these devices are visible for
each other, but you still have to maintain separate DHCP (static leases) and DNS servers
for each location. And while maintaining DHCP separately for each location can be (barely
but still) acceptable, manual maintaining several DNS servers with similar internal entries
can be true PITA.

The situation is even worse, if your locations are behind NAT, and you want to serve some
websites to Internet. In such case you have to maintain separately private and public DNS
entries.


# The solution

With ZoneMaster you can manage all your internal and external, DHCP and DNS servers from a
single point. You just have to create a few simple files (manually or using provided scripts)
with syntax similar to BIND zone files (and you can use comments inside them):

Example main internal configuration file (common for all your locations):

```
# network devices
router.home                              A          192.168.8.1
router.office1                           A          192.168.11.1
router.office2                           A          192.168.12.1
ap1.office1                              A          192.168.11.2      00:11:22:33:44:55
ap1.office2                              A          192.168.12.2      01:12:23:34:45:56

# home servers
dell1.home                               A          192.168.8.2       76:65:54:43:32:21
dell2.home                               A          192.168.8.3       23:54:23:35:45:65
movies.home                              CNAME      dell1.home

# desktop computers in the office
accounting1.office1                      A          192.168.11.61     00:34:56:78:91:23
accounting2.office1                      A          192.168.11.62     01:23:45:67:89:ab

# laptops mobile across locations
lap-boss.office1                         A          192.168.11.60     24:35:56:46:14:55
lap-boss.office2                         A          192.168.12.60     24:35:56:46:14:55

# servers
server1.dc1                              A          192.168.26.3      75:2c:35:23:54:94
server2.dc1                              A          192.168.26.4      75:2c:3f:45:43:23

# CRM - directly from internal network
crm.companydomain.com                    CNAME      server2.dc1
```

Example internal configuration file for Office 1:

```
boss.internal                            CNAME      lap-boss.office1
```

Example internal configuration file for Office 2:

```
boss.internal                            CNAME      lap-boss.office2
```

Example public configuration file:

```
# basic domain configuration (can and should be extended)
companydomain.com                        A          1.2.3.4  # some external hosting
*.companydomain.com                      CNAME      companydomain.com

# CRM - public office IP address
crm.companydomain.com                    A          11.22.33.44
```

ZoneMaster reads these zone files and automatically pushes all changes (added,
changed or removed records) to proper DNS and DHCP servers.


# Supported DNS service providers

ZoneMaster currently supports:

- [MikroTik](docs/mikrotik.md) static DNS and DHCP (including importing current DNS configuration to ZoneMaster database)
- [Amazon Route53](docs/aws.md) (for public domain configuration)
- [/etc/hosts](docs/hosts.md) file (for internal use on management server, to avoid relying on DNS server)
- [BIND 9](docs/bind.md) (both public and internal  configurations)
- [ISC-DHCP server](docs/dhcp.md)


# How it works


### local /etc/hosts file on management server

After creating `/etc/local/.dns/zone.*` files, just add this command to your crontab:

```
/opt/zonemaster/scripts/hosts/cron.sh
```
