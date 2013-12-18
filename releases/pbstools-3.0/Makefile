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

usertools: ja pbsdcp qexec supermover dmsub dagsub

ja:
	install -d $(PREFIX)/bin
	install -m 0755 bin/ja $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/ja.1 $(PREFIX)/share/man/man1

pbsdcp:
	install -d $(PREFIX)/bin
	install -m 0755 bin/pbsdcp $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/pbsdcp.1 $(PREFIX)/share/man/man1

qexec:
	install -d $(PREFIX)/bin
	install -m 0755 bin/qexec $(PREFIX)/bin
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qlogin
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qmpiexec
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qmpirun
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qrsh
	ln -s $(PREFIX)/bin/qexec $(PREFIX)/bin/qsh
	install -m 0644 doc/man1/qexec.1 $(PREFIX)/share/man/man1
	ln -s $(PREFIX)/share/man/man1/qexec.1 $(PREFIX)/share/man/man1/qlogin.1
	ln -s $(PREFIX)/share/man/man1/qexec.1 $(PREFIX)/share/man/man1/qmpiexec.1
	ln -s $(PREFIX)/share/man/man1/qexec.1 $(PREFIX)/share/man/man1/qmpirun.1
	ln -s $(PREFIX)/share/man/man1/qexec.1 $(PREFIX)/share/man/man1/qrsh.1
	ln -s $(PREFIX)/share/man/man1/qexec.1 $(PREFIX)/share/man/man1/qsh.1

supermover:
	install -d $(PREFIX)/bin
	install -m 0755 bin/supermover $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/supermover.1 $(PREFIX)/share/man/man1

dmsub:
	install -d $(PREFIX)/bin
	install -m 0755 bin/dmsub $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1	
	install -m 0644 doc/man1/dmsub.1 $(PREFIX)/share/man/man1

dagsub:
	install -d $(PREFIX)/bin
	install -m 0755 bin/dagsub $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1	
	install -m 0644 doc/man1/dagsub.1 $(PREFIX)/share/man/man1

admintools: reaver

reaver:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/reaver $(PREFIX)/sbin
	install -d $(PREFIX)/share/man/man8
	install -m 0644 doc/man8/reaver.8 $(PREFIX)/share/man/man8

mpitools: parallel-command-processor pbsdcp-scatter

parallel-command-processor:
	install -d $(PREFIX)/bin
	$(MPICC) src/parallel-command-processor.c -o $(PREFIX)/bin/parallel-command-processor $(MPILIBS)
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/parallel-command-processor.1 $(PREFIX)/share/man/man1

pbsdcp-scatter:
	install -d $(PREFIX)/bin
	cd src/pbsdcp-scatter ; make MPICC=$(MPICC)
	install -m 0755 src/pbsdcp-scatter/pbsdcp-scatter $(PREFIX)/bin

statstools: jobstats find-outlyers

jobstats:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/jobstats $(PREFIX)/sbin

find-outlyers:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/find-outlyers $(PREFIX)/sbin

dbtools: js job-db-update jobscript-to-db pbsacct-php

js:
	install -d $(PREFIX)/bin
	install -m 0755 bin/js $(PREFIX)/bin

job-db-update:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/job-db-update $(PREFIX)/sbin
	install -m 0750 sbin/fixup-nodect $(PREFIX)/sbin

jobscript-to-db:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/jobscript-to-db $(PREFIX)/sbin
	install -m 0750 sbin/spool-jobscripts $(PREFIX)/sbin

pbsacct-php:
	install -d $(WEBPREFIX)
	install -m 0640 web/default.css $(WEBPREFIX)
	install -m 0640 web/db.cfg $(WEBPREFIX)
	sed -i 's/localhost/$(DBSERVER)/' $(WEBPREFIX)/db.cfg
	install -m 0640 web/*.php $(WEBPREFIX)

dnotify-pbs:
	ln -s /usr/bin/dnotify $(PREFIX)/bin/dnotify-pbs
	install -m 0755 etc/rc.d/dnotify-pbs /etc/rc.d/init.d

jobscript-watcher:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/jobscript-watcher $(PREFIX)/sbin
	install -m 0755 etc/rc.d/jobscript-watcher /etc/rc.d/init.d

# stuff that's no longer installed by default
deprecatedtools:
	install -d $(PREFIX)/bin
	install -m 0755 deprecated/bin/jobinfo $(PREFIX)/bin
	install -m 0755 deprecated/bin/qpeek $(PREFIX)/bin
	install -m 0755 deprecated/bin/qps $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1
	install -m 0644 deprecated/doc/man/man1/qpeek.1 $(PREFIX)/share/man/man1
	install -m 0644 deprecated/doc/man/man1/qps.1 $(PREFIX)/share/man/man1
	install -d $(PREFIX)/sbin
	install -m 0750 deprecated/sbin/dezombify $(PREFIX)/sbin
	install -m 0750 deprecated/sbin/qtracejob.pbs-server $(PREFIX)/sbin
	install -m 0750 deprecated/sbin/showscript.pbs-server $(PREFIX)/sbin
	install -d $(PREFIX)/share/man/man8
	install -m 0644 deprecated/doc/man/man8/dezombify.8 $(PREFIX)/share/man/man8
