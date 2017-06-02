#!/usr/bin/env bash

#for multiple local tests
rm -f cookie.jar

#easy way I guess since parallel did not want to work for me
curl -s -c cookie.jar -b cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=4&locking=true"
#fix racy condition in the test
sleep 1
for i in {1..19};
do
    curl -s -c cookie.jar -b cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=4&locking=true" &
done

wait

#get result
curl -s -c cookie.jar -b cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=5&locking=true"

exit 0