CREATE TABLE IF NOT EXISTS task_reminders (
    task_id TEXT PRIMARY KEY NOT NULL,
    user_id TEXT NOT NULL,
    title TEXT NOT NULL,
    reminder_at TEXT NOT NULL,
    repeat_type TEXT NOT NULL DEFAULT 'once',
    target_url TEXT NOT NULL,
    completed_at TEXT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS task_reminders_due_idx
    ON task_reminders(reminder_at);

CREATE INDEX IF NOT EXISTS task_reminders_user_idx
    ON task_reminders(user_id);

CREATE TABLE IF NOT EXISTS push_devices (
    token_hash TEXT PRIMARY KEY NOT NULL,
    user_id TEXT NOT NULL,
    token TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS push_devices_user_idx
    ON push_devices(user_id);

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

CREATE TABLE IF NOT EXISTS task_reminder_deliveries (
    task_id TEXT NOT NULL,
    reminder_at TEXT NOT NULL,
    token_hash TEXT NOT NULL,
    sent_at TEXT NOT NULL,
    PRIMARY KEY (task_id, reminder_at, token_hash)
);
