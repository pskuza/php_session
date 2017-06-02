#!/usr/bin/env bash

#easy way I guess since parallel did not want to work for me
for i in {1..20};
do
    curl -v -c cookie.jar -b cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=4&locking=true" &
done

wait

#get result
curl -v -c cookie.jar -b cookie.jar "http://127.0.0.1:8080/SessionMysql.php?tests=5&locking=true"

#debug dump database

mysql -u root dev -e "Select * FROM sessions;"

exit 0