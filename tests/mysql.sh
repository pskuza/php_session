#!/bin/bash
mysql -u root -e "CREATE DATABASE dev;USE dev;CREATE TABLE `sessions` (`id` char(64) NOT NULL,`data` mediumtext DEFAULT NULL,`timestamp` int(10) unsigned NOT NULL,`remember_me` tinyint(1) NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"


