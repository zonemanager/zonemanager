# MikroTik RouterOS-based routers

If you're just about to start using ZoneMaster, you can automatically create zone files
just by scanning static DNS entries in your MikroTik router:

```
/opt/zonemaster/scripts/mikrotik/scan-dns.php 192.168.8.1 >/etc/local/.dns/zone.all
```

This script will create complete and working zone file, which could be later edited and
fine-tuned manually.

Having configured zone files, you can push updates to MikroTik router by using another
script (you can add it to your crontab):

```
/opt/zonemaster/scripts/mikrotik/update-dns.php home 192.168.8.1
/opt/zonemaster/scripts/mikrotik/update-dns.php office1 192.168.11.1
/opt/zonemaster/scripts/mikrotik/update-dns.php office2 192.168.12.1
```

Note that to make these scripts work, you have to create ssh key pair and install public
key on all routers you plan to use. Here's the details (in polish language), how to do it:
http://fajne.it/automatyzacja-backupu-routera-mikrotik.html
