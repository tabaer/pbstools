CREATE TABLE Jobs (
  jobid        VARCHAR(32) PRIMARY KEY,
  system       VARCHAR(8),
  username     VARCHAR(8),
  groupname    VARCHAR(8),
  jobname      TINYTEXT,
  nproc        INT UNSIGNED,
  nodes        TEXT,
  queue        TINYTEXT,
  submit_ts    INT,
  start_ts     INT,
  end_ts       INT,
  cput_req     TIME,
  cput         TIME,
  walltime_req TIME,
  walltime     TIME,
  mem_req      TINYTEXT,
  mem_kb       INT UNSIGNED,
  vmem_req     TINYTEXT,
  vmem_kb      INT UNSIGNED,
  hostlist     TEXT,
  script       MEDIUMTEXT
);

