CREATE TABLE IF NOT EXISTS daily_digest_snapshots (
    user_id TEXT PRIMARY KEY NOT NULL,
    items_json TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS daily_digest_deliveries (
    local_date TEXT NOT NULL,
    user_id TEXT NOT NULL,
    token_hash TEXT NOT NULL,
    sent_at TEXT NOT NULL,
    PRIMARY KEY (local_date, user_id, token_hash)
);
