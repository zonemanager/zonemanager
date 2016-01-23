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

With ZoneMaster you can manage all your internal and external DNS servers from a single
point. You just have to create a few simple files with syntax similar to BIND zone files
(you can use comments):

Example main internal configuration file (common for all locations):

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

ZoneMaster reads such set of files and automatically updates DNS servers configuration.


# Supported DNS service providers

ZoneMaster currently supports:

- MikroTik static DNS (internal DNS, including scanning current static DNS configuration
  and creating ZoneMaster configuration straight from it)
- Amazon Route53 (for public domain configuration)
- /etc/hosts file (for internal use on management server, to avoid relying on DNS server)
- BIND (both public and internal, preliminary support)

Future support is planned for:

- BIND (improve existing support)
- Cisco static DNS (internal DNS)


# How it works

### MikroTik RouterOS-based routers

If you're just about to start using ZoneMaster, you can automatically create zone files
just by scanning static DNS entries in your MikroTik router:

```
/opt/zonemaster/scripts/mikrotik-scan-dns.php 192.168.8.1 >/etc/local/.dns/zone.all
```

This script will create complete and working zone file, which could be later edited and
fine-tuned manually.

Having configured zone files, you can push updates to MikroTik router by using another
script (you can add it to your crontab):

```
/opt/zonemaster/scripts/mikrotik-update-dns.php home 192.168.8.1
/opt/zonemaster/scripts/mikrotik-update-dns.php office1 192.168.11.1
/opt/zonemaster/scripts/mikrotik-update-dns.php office2 192.168.12.1
```

Note that to make these script work, you have to create ssh key pair and install public
key on all routers you plan to use. Here's the details in polish, how to do it:
http://fajne.it/automatyzacja-backupu-routera-mikrotik.html


### local /etc/hosts file on management server

After creating `/etc/local/.dns/zone.*` files, just add this command to your crontab:

```
/opt/zonemaster/scripts/update-hosts.sh
```


### Amazon Route53

First, buy or move your domain to Amazon Route53, create hosted zone for it and note its ID.

Install `awscli` AWS command line tool:

```
apt-get install python-pip
pip install awscli
aws configure
```

Create `/etc/local/.dns/zone.public` file:

```
# basic domain configuration (static entries)
mx.yourdomain.com                        A        52.35.36.25
```

and optionally `/etc/local/.dns/zone.public-yourdomain.com` file:

```
# this file contains frequently changed entries
dynamic.yourdomain.com                   CNAME    ec2-52-10-70-178.us-west-2.compute.amazonaws.com
dynamic2.yourdomain.com                  CNAME    ec2-52-35-36-25.us-west-2.compute.amazonaws.com
dynamic3.yourdomain.com                  CNAME    ec2-52-34-55-16.us-west-2.compute.amazonaws.com
```

Finally, add this script to your crontab:

```
/opt/zonemaster/scripts/aws-update-zone.php default yourdomain.com Z25SD356N45NLE
```
