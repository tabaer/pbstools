The pbsacct Python module is an inteface for processing accounting log data
from the various PBS variants (TORQUE, PBS Pro, and OpenPBS), including the
support sending that data to a database for persistance and analysis.  DB
persistance is currently supported for MySQL/MariaDB, with SQLite and
PostgreSQL in various stages of development.

For example, here is a simple example of using the module to read a TORQUE
accounting log file and print information about the jobs logged therein::

  #!/use/bin/env python
  import pbsacct
  jobs = pbsacct.jobs_from_file("/var/spool/torque/server_priv/accounting/20180627")
  for jobid in jobs.keys():
    job = jobs[jobid]
    print "%s: user=%s group=%s account=%s" % \
          (jobid,job.user(),job.group(),job.account())
  # Here endeth the program

More complete examples (including DB connectivity) can be found at:

https://github.com/tabaer/pbstools/blob/master/sbin/job-db-update
https://github.com/tabaer/pbstools/blob/master/sbin/jobscript-to-db
https://github.com/tabaer/pbstools/blob/master/sbin/pbsacct-dump
https://github.com/tabaer/pbstools/blob/master/sbin/sw_app-cache
https://github.com/tabaer/pbstools/blob/master/sbin/sw_app-index

The pbsacct Python module is part of the pbstools collection of utilities:

https://github.com/tabaer/pbstools

