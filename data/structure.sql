PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE `calls` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `request_id` int  NOT NULL,
  `ct` FLOAT  DEFAULT NULL,
  `wt` FLOAT  DEFAULT NULL,
  `cpu` FLOAT  DEFAULT NULL,
  `mu` FLOAT  DEFAULT NULL,
  `pmu` FLOAT  DEFAULT NULL,
  `caller` text  DEFAULT NULL,
  `callee` text  NOT NULL
);
CREATE TABLE `requests` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT ,
  `request_host` text  NOT NULL,
  `request_uri` text  NOT NULL,
  `request_timestamp` int
);
ANALYZE sqlite_master;
DELETE FROM sqlite_sequence;
CREATE INDEX 'sd' on calls(callee);
CREATE VIEW call_main as
SELECT
  c.*,
  c.wt - sum(cc.wt) AS time_main,
  c.cpu - sum(cc.cpu) AS cpu_main
FROM "calls" AS c
  LEFT JOIN "calls" AS cc
    ON (cc.request_id = c.request_id
        AND c.callee = cc.caller
  )
GROUP BY
  c.callee;
COMMIT;
