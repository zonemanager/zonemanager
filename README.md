# The problem

Imagine that you manage a medium-size local network, split across several offices and/or
data centers, where you have:

- servers and desktop computers (always connected in the same place)
- laptops, tablets and mobile phones (connected in different offices)

All these locations are connected using VPN tunnels and all these devices are visible for
each other, but you still have to maintain separate DHCP (static leases) and DNS servers
for each location.

And while maintaining DHCP separately for each location can be acceptable, manual
maintaining several DNS servers with similar internal entries can be true PITA.

The situation is even worse, if your locations are behind NAT, and you want to serve some
websites to Internet. In such case you have to maintain separately private and public DNS
entries.


# The solution

With ZoneMaster you can manage all your internal and external DNS servers from a single
point. You just have to create a few simple files with syntax similar to BIND zone files
(you can use comments):

1. Main internal configuration file:

```
# network devices
router.home                              A          192.168.8.1
router.office1                           A          192.168.11.1
router.office2                           A          192.168.12.1
ap1.office1                              A          192.168.11.2
ap1.office2                              A          192.168.12.2

# home servers
dell1.home                               A          192.168.8.2
dell2.home                               A          192.168.8.3
movies.home                              CNAME      dell1.home

# desktop computers in the office
accounting1.office1                      A          192.168.11.61
accounting2.office1                      A          192.168.11.62

# laptops mobile across locations
lap-boss.office1                         A          192.168.11.60
lap-boss.office2                         A          192.168.12.60

# servers
server1.dc1                              A          192.168.26.3
server2.dc1                              A          192.168.26.4

# CRM - directly from internal network
crm.companydomain.com                    CNAME      server2.dc1
```

2. Internal configuration file for Office 1:

```
boss.internal                            CNAME      lap-boss.office1
```

3. Internal configuration file for Office 2:

```
boss.internal                            CNAME      lap-boss.office2
```

4. Main public configuration file:

```
# basic domain configuration (can and should be extended)
companydomain.com                        A          1.2.3.4
*.companydomain.com                      CNAME      companydomain.com

# CRM - public office IP address
crm.companydomain.com                    A          11.22.33.44
```


ZoneMaster reads such set of files and automatically updates DNS servers configuration.
It supports:

- Amazon Route53 (for public domain configuration)
- BIND (both public and internal)
- /etc/hosts file (for internal use on management server, to avoid relying on DNS server)

TODO support for:

- improved BIND support
- MikroTik static DNS (internal)
- Cisco static DNS (internal)
