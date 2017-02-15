#!/bin/sh

rm -f map.sqlite;

sqlite3 map.sqlite << EOF
BEGIN TRANSACTION;
PRAGMA foreign_keys=OFF;
CREATE TABLE map ( branch TEXT DEFAULT NULL, git_url TEXT DEFAULT NULL, jenkins_url TEXT DEFAULT NULL );
INSERT INTO map VALUES ('refs/heads/master','git@gitlab.my.domain:internal/hooktest.git','hooktest/' );
COMMIT;
EOF
