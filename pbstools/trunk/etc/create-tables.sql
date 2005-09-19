CREATE DATABASE pbsacct;
USE pbsacct;
CREATE TABLE Jobs (
  jobid        VARCHAR(32) PRIMARY KEY,
  system       VARCHAR(8),
  username     VARCHAR(8),
  groupname    VARCHAR(8),
  jobname      TINYTEXT,
  nproc        INT UNSIGNED,
  mppe         INT UNSIGNED,
  mppssp       INT UNSIGNED,
  nodes        TEXT,
  queue        TINYTEXT,
  submit_ts    INT,
  start_ts     INT,
  end_ts       INT,
  cput_req     TIME DEFAULT '00:00:00',
  cput         TIME DEFAULT '00:00:00',
  walltime_req TIME DEFAULT '00:00:00',
  walltime     TIME DEFAULT '00:00:00',
  mem_req      TINYTEXT,
  mem_kb       INT UNSIGNED,
  vmem_req     TINYTEXT,
  vmem_kb      INT UNSIGNED,
  hostlist     TEXT,
  exit_status  INT,
  script       MEDIUMTEXT
);
CREATE INDEX system_jobs ON Jobs (system);
CREATE INDEX user_jobs ON Jobs (username);
CREATE INDEX group_jobs ON Jobs (groupname);
CREATE INDEX queue_jobs ON Jobs (queue(16));
GRANT ALL PRIVILEGES ON Jobs TO 'pbsacct'@'localhost' IDENTIFIED BY 'pbsRroxor';
GRANT SELECT ON Jobs TO 'webapp'@'localhost';
