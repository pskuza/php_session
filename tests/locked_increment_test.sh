#!/usr/bin/env bash

seq 20 | parallel -n0 -j5 -q "curl -c cookie.jar -b cookie.jar http://127.0.0.1:8080/SessionMysql.php?tests=4&locking=true"

curl -c cookie.jar -b cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=5&locking=true"

exit 0