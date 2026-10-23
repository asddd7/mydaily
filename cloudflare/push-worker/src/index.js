const encoder = new TextEncoder();
const appOrigin = 'https://mydaily.infinityfreeapp.com';
let cachedAccessToken = null;
let cachedTokenExpiry = 0;

function allowedOrigins(env) {
    return new Set([
        appOrigin,
        ...(env.ALLOWED_ORIGINS || '')
            .split(',')
            .map(origin => origin.trim().replace(/\/$/, ''))
            .filter(Boolean),
    ]);
}

function json(data, status = 200, origin = appOrigin) {
    return new Response(JSON.stringify(data), {
        status,
        headers: {
            'content-type': 'application/json; charset=utf-8',
            'access-control-allow-origin': origin,
            'access-control-allow-methods': 'GET, POST, DELETE, OPTIONS',
            'access-control-allow-headers': 'Authorization, Content-Type',
            'access-control-max-age': '86400',
            vary: 'Origin',
        },
    });
}

function base64Url(bytes) {
    let binary = '';
    for (const byte of bytes) binary += String.fromCharCode(byte);
    return btoa(binary).replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');
}

function base64UrlText(value) {
    return base64Url(encoder.encode(value));
}

function decodeBase64UrlText(value) {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(normalized + '='.repeat((4 - normalized.length % 4) % 4));
    return new TextDecoder().decode(Uint8Array.from(binary, character => character.charCodeAt(0)));
}

function pemToBytes(pem) {
    const content = pem.replace(/-----BEGIN PRIVATE KEY-----|-----END PRIVATE KEY-----|\s/g, '');
    const binary = atob(content);
    return Uint8Array.from(binary, character => character.charCodeAt(0));
}

async function getFcmAccessToken(env) {
    if (cachedAccessToken && cachedTokenExpiry > Date.now() + 60_000) {
        return cachedAccessToken;
    }

    const serviceAccount = JSON.parse(env.FIREBASE_SERVICE_ACCOUNT_JSON);
    const issuedAt = Math.floor(Date.now() / 1000);
    const header = base64UrlText(JSON.stringify({ alg: 'RS256', typ: 'JWT' }));
    const claim = base64UrlText(JSON.stringify({
        iss: serviceAccount.client_email,
        scope: 'https://www.googleapis.com/auth/firebase.messaging',
        aud: 'https://oauth2.googleapis.com/token',
        iat: issuedAt,
        exp: issuedAt + 3600,
    }));
    const unsignedJwt = `${header}.${claim}`;
    const privateKey = await crypto.subtle.importKey(
        'pkcs8',
        pemToBytes(serviceAccount.private_key),
        { name: 'RSASSA-PKCS1-v1_5', hash: 'SHA-256' },
        false,
        ['sign'],
    );
    const signature = await crypto.subtle.sign(
        'RSASSA-PKCS1-v1_5',
        privateKey,
        encoder.encode(unsignedJwt),
    );
    const assertion = `${unsignedJwt}.${base64Url(new Uint8Array(signature))}`;
    const tokenResponse = await fetch('https://oauth2.googleapis.com/token', {
        method: 'POST',
        headers: { 'content-type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            assertion,
        }),
    });
    const tokenData = await tokenResponse.json();
    if (!tokenResponse.ok || !tokenData.access_token) {
        throw new Error(`Google OAuth failed (${tokenResponse.status}).`);
    }

    cachedAccessToken = tokenData.access_token;
    cachedTokenExpiry = Date.now() + Number(tokenData.expires_in || 3600) * 1000;
    return cachedAccessToken;
}

async function authorize(request, env) {
    const secret = env.SYNC_SECRET || '';
    const supplied = request.headers.get('authorization') || '';
    return secret.length >= 32 && supplied === `Bearer ${secret}`;
}

async function verifyBrowserTicket(request, env, expectedUserId) {
    const authorization = request.headers.get('authorization') || '';
    const ticket = authorization.startsWith('Bearer ') ? authorization.slice(7) : '';
    const parts = ticket.split('.');
    if (parts.length !== 3) return false;

    try {
        const secret = env.SYNC_SECRET || '';
        if (secret.length < 32) return false;
        const key = await crypto.subtle.importKey(
            'raw', encoder.encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['verify'],
        );
        const signature = Uint8Array.from(
            atob(parts[2].replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - parts[2].length % 4) % 4)),
            character => character.charCodeAt(0),
        );
        const valid = await crypto.subtle.verify(
            'HMAC', key, signature, encoder.encode(`${parts[0]}.${parts[1]}`),
        );
        if (!valid) return false;

        const header = JSON.parse(decodeBase64UrlText(parts[0]));
        const claims = JSON.parse(decodeBase64UrlText(parts[1]));
        return header.alg === 'HS256'
            && claims.aud === 'mydaily-push-sync'
            && Number(claims.exp) > Math.floor(Date.now() / 1000)
            && String(claims.sub) === String(expectedUserId);
    } catch {
        return false;
    }
}

async function syncTask(request, env, taskId = null, origin = appOrigin) {
    if (!await authorize(request, env)) return json({ error: 'Unauthorized' }, 401, origin);

    if (request.method === 'DELETE') {
        if (!taskId) return json({ error: 'Task ID is required' }, 400, origin);
        await env.DB.batch([
            env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE task_id = ?').bind(taskId),
            env.DB.prepare('DELETE FROM task_reminders WHERE task_id = ?').bind(taskId),
        ]);
        return json({ synced: true, removed: true }, 200, origin);
    }

    const data = await request.json();
    if (!data.task_id || !data.user_id || !data.title || !data.reminder_at || !data.target_url) {
        return json({ error: 'Invalid reminder payload' }, 422, origin);
    }
    const repeatType = ['once', 'every_minute', 'hourly', 'daily', 'weekly'].includes(data.repeat_type)
        ? data.repeat_type : 'once';

    const taskKey = String(data.task_id);
    await env.DB.prepare(`
        INSERT INTO task_reminders (task_id, user_id, title, reminder_at, repeat_type, target_url, completed_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
        ON CONFLICT(task_id) DO UPDATE SET
            user_id = excluded.user_id,
            title = excluded.title,
            reminder_at = excluded.reminder_at,
            repeat_type = excluded.repeat_type,
            target_url = excluded.target_url,
            completed_at = CASE
                WHEN task_reminders.reminder_at != excluded.reminder_at OR task_reminders.repeat_type != excluded.repeat_type THEN NULL
                ELSE task_reminders.completed_at
            END,
            updated_at = excluded.updated_at
    `).bind(
        taskKey, String(data.user_id), String(data.title),
        String(data.reminder_at), repeatType, String(data.target_url), new Date().toISOString(),
    ).run();
    return json({ synced: true }, 200, origin);
}

async function syncDevice(request, env, tokenHash = null, origin = appOrigin) {
    if (!await authorize(request, env)) return json({ error: 'Unauthorized' }, 401, origin);

    if (request.method === 'DELETE') {
        if (!tokenHash) return json({ error: 'Token hash is required' }, 400, origin);
        await env.DB.batch([
            env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE token_hash = ?').bind(tokenHash),
            env.DB.prepare('DELETE FROM daily_digest_deliveries WHERE token_hash = ?').bind(tokenHash),
            env.DB.prepare('DELETE FROM push_devices WHERE token_hash = ?').bind(tokenHash),
        ]);
        return json({ synced: true, removed: true }, 200, origin);
    }

    const data = await request.json();
    if (!data.user_id || !data.token || !data.token_hash) {
        return json({ error: 'Invalid device payload' }, 422, origin);
    }

    await env.DB.prepare(`
        INSERT INTO push_devices (token_hash, user_id, token, updated_at)
        VALUES (?, ?, ?, ?)
        ON CONFLICT(token_hash) DO UPDATE SET
            user_id = excluded.user_id,
            token = excluded.token,
            updated_at = excluded.updated_at
    `).bind(
        String(data.token_hash), String(data.user_id), String(data.token), new Date().toISOString(),
    ).run();
    return json({ synced: true }, 200, origin);
}

async function syncSnapshot(request, env, corsOrigin = appOrigin) {
    const requestOrigin = request.headers.get('origin');
    if (!allowedOrigins(env).has(requestOrigin)) return json({ error: 'Origin not allowed' }, 403, corsOrigin);
    const data = await request.json();
    if (!data.user_id || !Array.isArray(data.reminders) || !Array.isArray(data.devices)
        || (data.daily_items !== undefined && !Array.isArray(data.daily_items))) {
        return json({ error: 'Invalid snapshot payload' }, 422, corsOrigin);
    }

    const userId = String(data.user_id);
    if (!await verifyBrowserTicket(request, env, userId)) return json({ error: 'Unauthorized' }, 401, corsOrigin);

    const validReminders = data.reminders.filter(reminder =>
        reminder.task_id && reminder.title && reminder.reminder_at && reminder.target_url,
    ).map(reminder => ({
        ...reminder,
        repeat_type: ['once', 'every_minute', 'hourly', 'daily', 'weekly'].includes(reminder.repeat_type)
            ? reminder.repeat_type : 'once',
    }));
    const validDevices = data.devices.filter(device => device.token && device.token_hash);
    const taskIds = new Set(validReminders.map(reminder => String(reminder.task_id)));
    const tokenHashes = new Set(validDevices.map(device => String(device.token_hash)));

    const { results: existingReminders } = await env.DB.prepare(
        'SELECT task_id, reminder_at, repeat_type FROM task_reminders WHERE user_id = ?',
    ).bind(userId).all();
    const { results: existingDevices } = await env.DB.prepare(
        'SELECT token_hash FROM push_devices WHERE user_id = ?',
    ).bind(userId).all();
    const deletions = [];

    for (const row of existingReminders) {
        const taskId = String(row.task_id);
        if (!taskIds.has(taskId)) {
            deletions.push(env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE task_id = ?').bind(taskId));
            deletions.push(env.DB.prepare('DELETE FROM task_reminders WHERE task_id = ? AND user_id = ?').bind(taskId, userId));
        }
    }
    for (const row of existingDevices) {
        const tokenHash = String(row.token_hash);
        if (!tokenHashes.has(tokenHash)) {
            deletions.push(env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE token_hash = ?').bind(tokenHash));
            deletions.push(env.DB.prepare('DELETE FROM daily_digest_deliveries WHERE token_hash = ?').bind(tokenHash));
            deletions.push(env.DB.prepare('DELETE FROM push_devices WHERE token_hash = ? AND user_id = ?').bind(tokenHash, userId));
        }
    }
    for (let offset = 0; offset < deletions.length; offset += 40) {
        await env.DB.batch(deletions.slice(offset, offset + 40));
    }

    const upserts = [];
    for (const reminder of validReminders) {
        const taskId = String(reminder.task_id);
        const current = existingReminders.find(row => String(row.task_id) === taskId);
        if (current && (String(current.reminder_at) !== String(reminder.reminder_at)
            || String(current.repeat_type || 'once') !== String(reminder.repeat_type))) {
            upserts.push(env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE task_id = ?').bind(taskId));
        }
        upserts.push(env.DB.prepare(`
            INSERT INTO task_reminders (task_id, user_id, title, reminder_at, repeat_type, target_url, completed_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
            ON CONFLICT(task_id) DO UPDATE SET
                user_id = excluded.user_id,
                title = excluded.title,
                reminder_at = excluded.reminder_at,
                repeat_type = excluded.repeat_type,
                target_url = excluded.target_url,
                completed_at = CASE
                    WHEN task_reminders.reminder_at != excluded.reminder_at OR task_reminders.repeat_type != excluded.repeat_type THEN NULL
                    ELSE task_reminders.completed_at
                END,
                updated_at = excluded.updated_at
        `).bind(
            taskId, userId, String(reminder.title), String(reminder.reminder_at), String(reminder.repeat_type),
            String(reminder.target_url), new Date().toISOString(),
        ));
    }
    for (const device of validDevices) {
        upserts.push(env.DB.prepare(`
            INSERT INTO push_devices (token_hash, user_id, token, updated_at)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(token_hash) DO UPDATE SET
                user_id = excluded.user_id,
                token = excluded.token,
                updated_at = excluded.updated_at
        `).bind(String(device.token_hash), userId, String(device.token), new Date().toISOString()));
    }

    const dailyItems = (data.daily_items || []).filter(item =>
        item && ['task', 'mark'].includes(item.type) && item.id && item.title
        && /^\d{4}-\d{2}-\d{2}$/.test(String(item.due_date)) && item.target_url,
    ).slice(0, 200).map(item => ({
        type: String(item.type),
        id: String(item.id),
        title: String(item.title).slice(0, 160),
        due_date: String(item.due_date),
        target_url: String(item.target_url).startsWith('/') ? String(item.target_url) : '/',
    }));
    upserts.push(env.DB.prepare(`
        INSERT INTO daily_digest_snapshots (user_id, items_json, updated_at)
        VALUES (?, ?, ?)
        ON CONFLICT(user_id) DO UPDATE SET
            items_json = excluded.items_json,
            updated_at = excluded.updated_at
    `).bind(userId, JSON.stringify(dailyItems), new Date().toISOString()));

    for (let offset = 0; offset < upserts.length; offset += 40) {
        await env.DB.batch(upserts.slice(offset, offset + 40));
    }
    return json({ synced: true, reminders: validReminders.length, devices: validDevices.length, daily_items: dailyItems.length }, 200, corsOrigin);
}

async function sendReminder(env, reminder, device) {
    const accessToken = await getFcmAccessToken(env);
    const response = await fetch(
        `https://fcm.googleapis.com/v1/projects/${encodeURIComponent(env.FIREBASE_PROJECT_ID)}/messages:send`,
        {
            method: 'POST',
            headers: {
                authorization: `Bearer ${accessToken}`,
                'content-type': 'application/json',
            },
            body: JSON.stringify({
                message: {
                    token: device.token,
                    data: {
                        title: 'Pengingat tugas MyDaily',
                        body: `Waktunya mengerjakan: ${reminder.title}`,
                        url: reminder.target_url,
                    },
                    webpush: { headers: { Urgency: 'high' } },
                },
            }),
        },
    );

    if (response.ok) return 'sent';
    const responseBody = await response.json().catch(() => ({}));
    const errorCode = responseBody?.error?.details?.find(item => item.errorCode)?.errorCode;
    if (errorCode === 'UNREGISTERED' || response.status === 404) return 'unregistered';
    console.error('FCM rejected reminder', response.status, errorCode || 'unknown');
    return 'failed';
}

async function sendDailyDigest(env, userId, items, device) {
    const taskCount = items.filter(item => item.type === 'task').length;
    const markCount = items.filter(item => item.type === 'mark').length;
    const first = items.slice(0, 2).map(item => item.title).join(', ');
    const parts = [];
    if (taskCount) parts.push(`${taskCount} tugas`);
    if (markCount) parts.push(`${markCount} penanda`);
    const body = `Ada ${parts.join(' dan ')} aktif${first ? `. Berikutnya: ${first}` : ''}.`;
    const accessToken = await getFcmAccessToken(env);
    const response = await fetch(
        `https://fcm.googleapis.com/v1/projects/${encodeURIComponent(env.FIREBASE_PROJECT_ID)}/messages:send`,
        {
            method: 'POST',
            headers: { authorization: `Bearer ${accessToken}`, 'content-type': 'application/json' },
            body: JSON.stringify({
                message: {
                    token: device.token,
                    data: { title: 'Pengingat harian MyDaily', body, url: items[0]?.target_url || '/tasks' },
                    webpush: { headers: { Urgency: 'normal' } },
                },
            }),
        },
    );
    if (response.ok) return 'sent';
    const responseBody = await response.json().catch(() => ({}));
    const errorCode = responseBody?.error?.details?.find(item => item.errorCode)?.errorCode;
    if (errorCode === 'UNREGISTERED' || response.status === 404) return 'unregistered';
    console.error('FCM rejected daily digest', response.status, errorCode || 'unknown');
    return 'failed';
}

function jakartaClock(date) {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(date);
    return Object.fromEntries(parts.filter(part => part.type !== 'literal').map(part => [part.type, part.value]));
}

async function runDailyDigests(env, now) {
    const clock = jakartaClock(now);
    // Run once after 08:00 WIB; delivery keys prevent repeats for the local day.
    if (Number(clock.hour) < 8) return { sent: 0, failed: 0 };

    await env.DB.prepare(`
        DELETE FROM daily_digest_deliveries WHERE local_date < ?
    `).bind(`${clock.year}-${clock.month}-${clock.day}`).run();

    const { results: snapshots } = await env.DB.prepare(`
        SELECT user_id, items_json FROM daily_digest_snapshots
        WHERE updated_at >= ?
        ORDER BY updated_at DESC
        LIMIT 500
    `).bind(new Date(now.getTime() - 45 * 86_400_000).toISOString()).all();

    let sent = 0;
    let failed = 0;
    for (const snapshot of snapshots) {
        let items;
        try { items = JSON.parse(snapshot.items_json); } catch { continue; }
        if (!Array.isArray(items) || items.length === 0) continue;
        const { results: devices } = await env.DB.prepare(
            'SELECT token_hash, token FROM push_devices WHERE user_id = ? LIMIT 20',
        ).bind(snapshot.user_id).all();
        for (const device of devices) {
            const delivered = await env.DB.prepare(
                'SELECT 1 FROM daily_digest_deliveries WHERE local_date = ? AND user_id = ? AND token_hash = ? LIMIT 1',
            ).bind(`${clock.year}-${clock.month}-${clock.day}`, snapshot.user_id, device.token_hash).first();
            if (delivered) continue;
            try {
                const result = await sendDailyDigest(env, snapshot.user_id, items, device);
                if (result === 'sent') {
                    await env.DB.prepare(`
                        INSERT OR IGNORE INTO daily_digest_deliveries (local_date, user_id, token_hash, sent_at)
                        VALUES (?, ?, ?, ?)
                    `).bind(`${clock.year}-${clock.month}-${clock.day}`, snapshot.user_id, device.token_hash, now.toISOString()).run();
                    sent++;
                } else if (result === 'unregistered') {
                    await env.DB.batch([
                        env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE token_hash = ?').bind(device.token_hash),
                        env.DB.prepare('DELETE FROM daily_digest_deliveries WHERE token_hash = ?').bind(device.token_hash),
                        env.DB.prepare('DELETE FROM push_devices WHERE token_hash = ?').bind(device.token_hash),
                    ]);
                } else failed++;
            } catch (error) {
                console.error('Daily digest send error', snapshot.user_id, error?.message || 'unknown');
                failed++;
            }
        }
    }
    return { sent, failed };
}

function dueOccurrenceAt(reminder, now) {
    const start = Date.parse(reminder.reminder_at);
    if (!Number.isFinite(start) || start > now.getTime()) return null;
    const intervals = {
        every_minute: 60_000,
        hourly: 3_600_000,
        daily: 86_400_000,
        weekly: 604_800_000,
    };
    const interval = intervals[reminder.repeat_type];
    if (!interval) return reminder.repeat_type === 'once' ? new Date(start).toISOString() : null;

    // Send only the latest due occurrence so a delayed cron does not emit a burst.
    const occurrence = start + Math.floor((now.getTime() - start) / interval) * interval;
    return new Date(occurrence).toISOString();
}

async function runScheduledReminders(env) {
    const now = new Date();
    const { results: reminders } = await env.DB.prepare(`
        SELECT task_id, user_id, title, reminder_at, repeat_type, target_url
        FROM task_reminders
        WHERE reminder_at <= ? AND completed_at IS NULL
        ORDER BY reminder_at
        LIMIT 25
    `).bind(now.toISOString()).all();

    let sent = 0;
    let failed = 0;
    for (const reminder of reminders) {
        const occurrenceAt = dueOccurrenceAt(reminder, now);
        if (!occurrenceAt) continue;
        const { results: devices } = await env.DB.prepare(
            'SELECT token_hash, token FROM push_devices WHERE user_id = ? LIMIT 20',
        ).bind(reminder.user_id).all();

        for (const device of devices) {
            const existing = await env.DB.prepare(
                'SELECT 1 FROM task_reminder_deliveries WHERE task_id = ? AND reminder_at = ? AND token_hash = ? LIMIT 1',
            ).bind(reminder.task_id, occurrenceAt, device.token_hash).first();
            if (existing) continue;

            try {
                const result = await sendReminder(env, reminder, device);
                if (result === 'sent') {
                    await env.DB.prepare(
                        'INSERT OR IGNORE INTO task_reminder_deliveries (task_id, reminder_at, token_hash, sent_at) VALUES (?, ?, ?, ?)',
                    ).bind(reminder.task_id, occurrenceAt, device.token_hash, new Date().toISOString()).run();
                    sent++;
                } else if (result === 'unregistered') {
                    await env.DB.batch([
                        env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE token_hash = ?').bind(device.token_hash),
                        env.DB.prepare('DELETE FROM push_devices WHERE token_hash = ?').bind(device.token_hash),
                    ]);
                } else {
                    failed++;
                }
            } catch (error) {
                console.error('Reminder send error', reminder.task_id, error?.message || 'unknown');
                failed++;
            }
        }

        if (reminder.repeat_type === 'once' && devices.length === 0) {
            await env.DB.prepare('UPDATE task_reminders SET completed_at = ? WHERE task_id = ?')
                .bind(new Date().toISOString(), reminder.task_id).run();
        } else if (reminder.repeat_type === 'once') {
            const { count } = await env.DB.prepare(
                'SELECT COUNT(*) AS count FROM task_reminder_deliveries WHERE task_id = ? AND reminder_at = ?',
            ).bind(reminder.task_id, occurrenceAt).first();
            if (Number(count) >= devices.length) {
                await env.DB.prepare('UPDATE task_reminders SET completed_at = ? WHERE task_id = ?')
                    .bind(new Date().toISOString(), reminder.task_id).run();
            }
        }
    }

    return { checked: reminders.length, sent, failed };
}

export default {
    async fetch(request, env) {
        const url = new URL(request.url);
        const origin = request.headers.get('origin');
        const origins = allowedOrigins(env);
        const corsOrigin = origin && origins.has(origin) ? origin : appOrigin;
        if (request.method === 'OPTIONS') {
            if (!origin || !origins.has(origin)) return json({ error: 'Origin not allowed' }, 403);
            return new Response(null, {
                status: 204,
                headers: {
                    'access-control-allow-origin': origin,
                    'access-control-allow-methods': 'GET, POST, DELETE, OPTIONS',
                    'access-control-allow-headers': 'Authorization, Content-Type',
                    'access-control-max-age': '86400',
                    vary: 'Origin',
                },
            });
        }
        if (origin && !origins.has(origin)) return json({ error: 'Origin not allowed' }, 403);
        if (url.pathname === '/health' && request.method === 'GET') {
            return json({ ok: true, service: 'mydaily-push-reminders' }, 200, corsOrigin);
        }
        if (url.pathname === '/sync/task' && request.method === 'POST') {
            return syncTask(request, env, null, corsOrigin);
        }
        const taskDelete = url.pathname.match(/^\/sync\/task\/([0-9]+)$/);
        if (taskDelete && request.method === 'DELETE') {
            return syncTask(request, env, taskDelete[1], corsOrigin);
        }
        if (url.pathname === '/sync/device' && request.method === 'POST') {
            return syncDevice(request, env, null, corsOrigin);
        }
        if (url.pathname === '/sync/snapshot' && request.method === 'POST') {
            return syncSnapshot(request, env, corsOrigin);
        }
        const deviceDelete = url.pathname.match(/^\/sync\/device\/([a-f0-9]{64})$/);
        if (deviceDelete && request.method === 'DELETE') {
            return syncDevice(request, env, deviceDelete[1], corsOrigin);
        }
        return json({ error: 'Not found' }, 404, corsOrigin);
    },

    async scheduled(event, env, ctx) {
        ctx.waitUntil((async () => {
            try {
                if (event.cron === '0 1 * * *') {
                    const dailyDigests = await runDailyDigests(env, new Date());
                    console.log('Daily digest summary', JSON.stringify(dailyDigests));
                    return;
                }

                const reminders = await runScheduledReminders(env);
                console.log('Task reminder summary', JSON.stringify(reminders));
            } catch (error) {
                console.error('Scheduled push run failed', error?.message || 'unknown');
                throw error;
            }
        })());
    },
};
