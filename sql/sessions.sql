CREATE TABLE IF NOT EXISTS app_sessions (
    id_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    data LONGBLOB NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    INDEX idx_app_sessions_expiry (expires_at)
) ENGINE=InnoDB;
