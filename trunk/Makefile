PREFIX    = /usr/local
WEBPREFIX = /var/www/html/pbsacct
DBSERVER  = localhost
DBADMIN   = root

default: usertools admintools

all: usertools admintools statstools dbtools

usertools:
	install -d $(PREFIX)/bin
	install -m 0755 bin/ja $(PREFIX)/bin
	install -m 0755 bin/pbsdcp $(PREFIX)/bin
	install -m 0755 bin/qexec $(PREFIX)/bin
	install -m 0755 bin/qpeek $(PREFIX)/bin
	install -m 0755 bin/qps $(PREFIX)/bin
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qlogin
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qmpiexec
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qmpirun
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qrsh
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qsh

admintools:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/dezombify $(PREFIX)/sbin
	install -m 0750 sbin/qtracejob.pbs-server $(PREFIX)/sbin
	install -m 0750 sbin/reaver $(PREFIX)/sbin
	install -m 0750 sbin/showscript.pbs-server $(PREFIX)/sbin

statstools:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/jobstats $(PREFIX)/sbin
	install -m 0750 sbin/find-outlyers $(PREFIX)/sbin

dbtools:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/job-db-update $(PREFIX)/sbin
	install -m 0750 sbin/jobscript-to-db $(PREFIX)/sbin
	install -m 0750 sbin/spool-jobscripts $(PREFIX)/sbin
	install -d $(WEBPREFIX)
	install -m 0640 web/default.css $(WEBPREFIX)
	install -m 0640 web/*.php $(WEBPREFIX)
# note that the following will prompt for the DB admin password
	mysql -h $(DBSERVER) -u $(DBADMIN) -p < etc/create-tables.sql

