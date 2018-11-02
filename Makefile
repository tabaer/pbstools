VERSION = 3.4.4
RELEASE = 1
ROOT = /
PREFIX    = /usr/local
WEBPREFIX = /var/www/html/pbsacct
CFGPREFIX = $(PREFIX)/etc
DBSERVER  = localhost
DBADMIN   = root
MPICC = mpicc
MPILIBS = 
PKGDIR = $(shell pwd)/pkg

default:
	@echo "Run \"make install\" or \"make install-all\" to install pbstools"

install: usertools admintools

install-all: usertools admintools mpitools dbtools

usertools: ja pbsdcp qexec supermover dmsub dagsub job-vm-launch pbs-spark-submit jobarray-to-pcp

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
	cd $(PREFIX)/bin && ln -sf qexec qlogin
	cd $(PREFIX)/bin && ln -sf qexec qmpiexec
	cd $(PREFIX)/bin && ln -sf qexec qmpirun
	cd $(PREFIX)/bin && ln -sf qexec qrsh
	cd $(PREFIX)/bin && ln -sf qexec qsh
	install -m 0644 doc/man1/qexec.1 $(PREFIX)/share/man/man1
	cd $(PREFIX)/share/man/man1 && ln -sf qexec.1 qlogin.1
	cd $(PREFIX)/share/man/man1 && ln -sf qexec.1 qmpiexec.1
	cd $(PREFIX)/share/man/man1 && ln -sf qexec.1 qmpirun.1
	cd $(PREFIX)/share/man/man1 && ln -sf qexec.1 qrsh.1
	cd $(PREFIX)/share/man/man1 && ln -sf qexec.1 qsh.1

supermover:
	install -d $(PREFIX)/bin
	install -m 0755 bin/supermover $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/supermover.1 $(PREFIX)/share/man/man1
	install -d $(CFGPREFIX)
	install -m 0644 etc/supermover.cfg $(CFGPREFIX)

dmsub:
	install -d $(PREFIX)/bin
	install -m 0755 bin/dmsub $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1	
	install -m 0644 doc/man1/dmsub.1 $(PREFIX)/share/man/man1
	install -d $(CFGPREFIX)
	install -m 0644 etc/dmsub.cfg $(CFGPREFIX)

dagsub:
	install -d $(PREFIX)/bin
	install -m 0755 bin/dagsub $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1	
	install -m 0644 doc/man1/dagsub.1 $(PREFIX)/share/man/man1

job-vm-launch:
	install -d $(PREFIX)/bin
	install -m 0755 bin/job-vm-launch $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1	
	install -m 0644 doc/man1/job-vm-launch.1 $(PREFIX)/share/man/man1

jobarray-to-pcp:
	install -d $(PREFIX)/bin
	install -m 0755 bin/jobarray-to-pcp $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/jobarray-to-pcp.1 $(PREFIX)/share/man/man1

pbs-spark-submit:
	install -d $(PREFIX)/bin
	install -m 0755 bin/pbs-spark-submit $(PREFIX)/bin
	install -d $(PREFIX)/share/man/man1	
	install -m 0644 doc/man1/pbs-spark-submit.1 $(PREFIX)/share/man/man1

admintools: reaver pbsacct-python

reaver:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/reaver $(PREFIX)/sbin
	install -d $(PREFIX)/share/man/man8
	install -m 0644 doc/man8/reaver.8 $(PREFIX)/share/man/man8

pbsacct-python:
	cd src/python && python setup.py install --single-version-externally-managed --root=$(ROOT)
	install -m 0755 sbin/transform-accounting-log $(PREFIX)/sbin/

mpitools: parallel-command-processor pbsdcp-scatter jobarray-to-pcp

parallel-command-processor:
	install -d $(PREFIX)/bin
	$(MPICC) src/parallel-command-processor.c -o $(PREFIX)/bin/parallel-command-processor $(MPILIBS)
	install -d $(PREFIX)/share/man/man1
	install -m 0644 doc/man1/parallel-command-processor.1 $(PREFIX)/share/man/man1

pbsdcp-scatter:
	install -d $(PREFIX)/bin
	cd src/pbsdcp-scatter ; make MPICC=$(MPICC)
	install -m 0755 src/pbsdcp-scatter/pbsdcp-scatter $(PREFIX)/bin

dbtools: pbsacct-collector pbsacct-php pbsacct-db jobscript-watcher

js:
	install -d $(PREFIX)/bin
	install -m 0750 bin/js $(PREFIX)/bin

pbsacct-collector:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/job-db-update $(PREFIX)/sbin
	install -m 0750 sbin/jobscript-to-db $(PREFIX)/sbin
	install -m 0750 sbin/spool-jobscripts $(PREFIX)/sbin
	install -m 0750 sbin/sw_app-index $(PREFIX)/sbin
	install -m 0750 sbin/sw_app-cache $(PREFIX)/sbin

pbsacct-php:
	install -d $(WEBPREFIX)
	install -m 0644 web/default.css $(WEBPREFIX)
	install -m 0644 web/db.cfg $(WEBPREFIX)
	sed -i 's/localhost/$(DBSERVER)/' $(WEBPREFIX)/db.cfg
	install -m 0644 web/*.php $(WEBPREFIX)
	install -m 0644 web/*.js $(WEBPREFIX)
	cp -R web/phplib $(WEBPREFIX)/

pbsacct-db:
	install -d $(CFGPREFIX)/pbsacct
	install -m 0640 etc/create-tables.mysql $(CFGPREFIX)/pbsacct
	install -m 0640 etc/create-tables.sqlite $(CFGPREFIX)/pbsacct
	install -m 0640 etc/pbsacctdb.cfg $(CFGPREFIX)

dnotify-pbs:
	ln -s /usr/bin/dnotify $(PREFIX)/bin/dnotify-pbs
	install -m 0755 etc/rc.d/dnotify-pbs $(CFGPREFIX)/init.d

jobscript-watcher:
	install -d $(PREFIX)/sbin
	install -m 0750 sbin/jobscript-watcher $(PREFIX)/sbin
	install -d $(CFGPREFIX)/init.d
	install -m 0755 etc/rc.d/jobscript-watcher $(CFGPREFIX)/init.d

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
	install -m 0750 deprecated/sbin/find-outlyers $(PREFIX)/sbin
	install -m 0750 deprecated/sbin/jobstats $(PREFIX)/sbin
	install -m 0750 deprecated/sbin/qtracejob.pbs-server $(PREFIX)/sbin
	install -m 0750 deprecated/sbin/showscript.pbs-server $(PREFIX)/sbin
	install -d $(PREFIX)/share/man/man8
	install -m 0644 deprecated/doc/man/man8/dezombify.8 $(PREFIX)/share/man/man8

package:
	mkdir -p $(PKGDIR)
	git ls-files | tar -c --transform 's,^,pbstools-$(VERSION)/,' -T - | gzip > $(PKGDIR)/pbstools-$(VERSION).tar.gz

rpm: package
	rpmbuild --define "_topdir $(PKGDIR)" \
	--define "_builddir %{_topdir}" \
	--define "_rpmdir %{_topdir}" \
	--define "_srcrpmdir %{_topdir}" \
	--define "_specdir %{_topdir}" \
	--define "_sourcedir %{_topdir}" \
	--define "ver $(VERSION)" \
	--define "rel $(RELEASE)" \
	-ba pbstools.spec
