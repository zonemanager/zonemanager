# BIND 9

ZoneMaster can also generate files by scanning your live DNS configuration, assuming that
your DNS server allows AXFR zone transfer (which is disabled in most public DNS servers,
but enabled by default on most BIND installations):

```
/opt/zonemaster/scripts/bind/axfr-scan-domain.php internaldomain.com
```

This will generate 2 files:
- /etc/local/.dns/bind.internaldomain.com.dist, which contains BIND 9 zone template with
  all records scanned with AXFR transfer except A/CNAME/TXT
- /etc/local/.dns/zone.internaldomain.com.dist, which contains A/CNAME/TXT records

Now you have to review these files, adjust them manually if needed, and rename (cut
".dist" extension).

When you have created the final set of files, execute or add to your crontab:

```
/opt/zonemaster/scripts/bind/cron.sh your-dns-server.com internaldomain.com
```
