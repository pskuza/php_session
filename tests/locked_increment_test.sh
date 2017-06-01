#!/usr/bin/env bash

seq 20 | parallel -n0 -j5 -q curl -s -c /tmp/cookie.jar -b /tmp/cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=4&locking=true"

curl -s -c /tmp/cookie.jar -b /tmp/cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=5&locking=true"

exit 0