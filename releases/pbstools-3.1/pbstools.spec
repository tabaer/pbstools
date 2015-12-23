Summary: Tools for the PBS family of batch systems (OpenPBS, PBS Pro, TORQUE)
Name: pbstools
Version: 3.0
Release: 1%{?dist}
License: GPLv2
Group: System Environment/Base
Vendor:  National Institute for Computational Sciences, University of Tennessee
URL: http://www.nics.tennessee.edu/~troy/pbstools/
Source0: %{name}-%{version}.tar.gz
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Prefix: /usr
%description
Several utilities that have been developed at NICS, OSC, and elsewhere
to aid in the administration and management of PBS variants (including
OpenPBS, PBS Pro, and TORQUE).


%package ja
Summary:  PBStools Job Accounting
Group:  System Environment/Base
Requires: python
%description ja
ja provides job accounting within a PBS job, similar to the command of
the same name in NQE.


%package pbsdcp
Summary:  PBStools Distributed Copy
Group:  System Environment/Base
Requires: perl
%description pbsdcp
pbsdcp is a distributed copy command within a PBS job.


%package sge-compat
Summary:  PBStools Grid Engine Compatibility
Group:  System Environment/Base
Requires: perl
%description sge-compat
qexec is a PBS workalike for the SGE qlogin, qrsh, and qsh commands. 


%package -n supermover
Summary:  Supermover
Group:  System Environment/Base
Requires: python
%description -n supermover
supermover is a highly configurable wrapper around other data transfer
utilities such as scp, globus-url-copy, and hsi.


%package dmsub
Summary:  PBStools Data Movement Job Submission
Group:  System Environment/Base
Requires: python,supermover
%description dmsub
dmsub is a tool for submitting data movement jobs. It understands data
transfer descriptions in the formats of DMOVER, RFT, and Stork; it can
also use several different data movement tools, including supermover.


%package dagsub
Summary:  PBStools Directed Acyclic Graph Job Submission
Group:  System Environment/Base
Requires: python,dmsub
%description dagsub
dagsub is a workalike for condor_submit_dag. This allows the
submission of large, complex sets of dependent jobs using a relatively
simple syntax. It relies on dmsub for data movement.


%package reaver
Summary:  PBStools Process Killer
Group:  System Environment/Base
Requires:  perl
%description reaver
reaver is a tool to find (and optionally clean up) processes on a PBS host
which have not been allocated jobs on that host.


%package -n pbsacct-collector
Summary:  pbsacct Data Collector
Group:  System Environment/Base
Requires:  perl,perl-DBD-MySQL
%description -n pbsacct-collector
pbsacct-collector is the data collection core of the pbsacct workload
analysis system.  It should be installed on the same host as a
pbs_server instance for OpenPBS, PBS Pro, or TORQUE.


%package -n pbsacct-php
Summary: pbsacct Web Front End
Group:  System Environment/Base
Requires: httpd,php,php-pear-Spreadsheet-Excel-Writer
%description -n pbsacct-php
pbsacct-php is the web front end for the pbsacct workload analysis
system.  It should be installed on a web server host.


%package -n pbsacct-db
Summary:  pbsacct Database Backend
Group:  System Environment/Base
Requires: mysql-server
%description -n pbsacct-db
pbsacct-db is the database backend for the pbsacct workload analysis
system.  It should be installed on a database server running MySQL.

%package -n pbsacct-jobscript-watcher
Summary:  pbsacct Job Script Capture Service
Group:  System Environment/Base
Requires:  inotify-tools,perl
%description -n pbsacct-jobscript-watcher
pbsacct-jobscript-watcher is an optional part of the pbsacct workload
analysis system that captures job scripts as they are submitted.  It
should be installed on the same host as a pbs_server instance for
OpenPBS, PBS Pro, or TORQUE.

%prep
%setup -q


%install
make PREFIX=%{buildroot}/%{_prefix} WEBPREFIX=%{buildroot}/var/www/html/pbsacct CFGPREFIX=%{buildroot}/%{_sysconfdir} install dbtools


%files
%doc README
%doc INSTALL
%doc deprecated.txt

%files ja
%{_bindir}/ja
%doc %{_mandir}/man1/ja.1.gz

%files pbsdcp
%{_bindir}/pbsdcp
%doc %{_mandir}/man1/pbsdcp.1.gz

%files sge-compat
%{_bindir}/qexec
%{_bindir}/qlogin
%{_bindir}/qmpiexec
%{_bindir}/qmpirun
%{_bindir}/qrsh
%{_bindir}/qsh
%doc %{_mandir}/man1/qexec.1.gz
%doc %{_mandir}/man1/qlogin.1.gz
%doc %{_mandir}/man1/qmpiexec.1.gz
%doc %{_mandir}/man1/qmpirun.1.gz
%doc %{_mandir}/man1/qrsh.1.gz
%doc %{_mandir}/man1/qsh.1.gz

%files -n supermover
%{_bindir}/supermover
%config /%{_sysconfdir}/supermover.cfg
%doc %{_mandir}/man1/supermover.1.gz

%files dmsub
%{_bindir}/dmsub
%config /%{_sysconfdir}/dmsub.cfg
%doc %{_mandir}/man1/dmsub.1.gz

%files dagsub
%{_bindir}/dagsub
%doc %{_mandir}/man1/dagsub.1.gz

%files reaver
%{_sbindir}/reaver
%doc %{_mandir}/man8/reaver.8.gz

%files -n  pbsacct-collector
%{_sbindir}/job-db-update
%{_sbindir}/fixup-nodect
%{_sbindir}/jobscript-to-db
%{_sbindir}/spool-jobscripts

%files -n pbsacct-php
%dir /var/www/html/pbsacct
/var/www/html/pbsacct/default.css
/var/www/html/pbsacct/db.cfg
/var/www/html/pbsacct/*.php

%files -n pbsacct-db
%dir %{_sysconfdir}/pbsacct
%{_sysconfdir}/pbsacct/create-tables.sql

%files -n pbsacct-jobscript-watcher
%{_sbindir}/jobscript-watcher
%{_sysconfdir}/init.d/jobscript-watcher
