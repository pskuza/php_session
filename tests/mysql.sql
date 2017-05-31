CREATE TABLE `sessions` (
  `id`          CHAR(64)         NOT NULL,
  `data`        MEDIUMTEXT                DEFAULT NULL,
  `timestamp`   INT(10) UNSIGNED NOT NULL,
  `remember_me` TINYINT(1)       NOT NULL DEFAULT 0
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
