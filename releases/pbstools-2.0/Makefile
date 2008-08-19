PREFIX    = /usr/local
WEBPREFIX = /var/www/html/pbsacct
DBSERVER  = localhost
DBADMIN   = root
MPICC = mpicc
MPILIBS = 

default:
	@echo "Run \"make install\" or \"make install-all\" to install pbstools"

install: usertools admintools

install-all: usertools admintools mpitools statstools dbtools

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
	install -d $(PREFIX)/man/man1
	install -m 0644 doc/man1/ja.1 $(PREFIX)/man/man1
	install -m 0644 doc/man1/pbsdcp.1 $(PREFIX)/man/man1
	install -m 0644 doc/man1/qexec.1 $(PREFIX)/man/man1
	install -m 0644 doc/man1/qpeek.1 $(PREFIX)/man/man1
	install -m 0644 doc/man1/qps.1 $(PREFIX)/man/man1
	ln -s $(PREFIX)/man/man1/qexec.1 $(PREFIX)/man/man1/qlogin.1
	ln -s $(PREFIX)/man/man1/qexec.1 $(PREFIX)/man/man1/qmpiexec.1
	ln -s $(PREFIX)/man/man1/qexec.1 $(PREFIX)/man/man1/qmpirun.1
	ln -s $(PREFIX)/man/man1/qexec.1 $(PREFIX)/man/man1/qrsh.1
	ln -s $(PREFIX)/man/man1/qexec.1 $(PREFIX)/man/man1/qsh.1

admintools:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/dezombify $(PREFIX)/sbin
	install -m 0750 sbin/qtracejob.pbs-server $(PREFIX)/sbin
	install -m 0750 sbin/reaver $(PREFIX)/sbin
	install -m 0750 sbin/showscript.pbs-server $(PREFIX)/sbin
	install -d $(PREFIX)/man/man8
	install -m 0644 doc/man8/dezombify.8 $(PREFIX)/man/man8
	install -m 0644 doc/man8/reaver.8 $(PREFIX)/man/man8

mpitools:
	install -d $(PREFIX)/bin
	$(MPICC) src/parallel-command-processor.c -o $(PREFIX)/bin/parallel-command-processor $(MPILIBS)
	install -d $(PREFIX)/man/man1
	install -m 0644 doc/man1/parallel-command-processor.1 $(PREFIX)/man/man1
	cd src/pbsdcp-scatter ; make MPICC=$(MPICC)
	install -m 0755 src/pbsdcp-scatter/pbsdcp-scatter $(PREFIX)/bin

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
	install -m 0640 web/db.cfg $(WEBPREFIX)
	sed -i 's/localhost/$(DBSERVER)/' $(WEBPREFIX)/db.cfg
	install -m 0640 web/*.php $(WEBPREFIX)
# note that the following will prompt for the DB admin password
	mysql -h $(DBSERVER) -u $(DBADMIN) -p < etc/create-tables.sql

