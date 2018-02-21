CREATE TABLE `sessions` (
  `id`          CHAR(64)            NOT NULL,
  `session_data`        TEXT                         DEFAULT NULL,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `remember_me` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `garbage_collection_index` (`remember_me`, `created_at`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
