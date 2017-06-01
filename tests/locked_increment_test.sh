#!/usr/bin/env bash

#easy way I guess sind parallel did not want to work for me
for i in {1..50};
do
    curl -s -c /tmp/cookie.jar -b /tmp/cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=4&locking=true" &
done

wait

#get result
curl -s -c /tmp/cookie.jar -b /tmp/cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=5&locking=true"

exit 0