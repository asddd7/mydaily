const encoder = new TextEncoder();
let cachedAccessToken = null;
let cachedTokenExpiry = 0;

function json(data, status = 200) {
    return new Response(JSON.stringify(data), {
        status,
        headers: { 'content-type': 'application/json; charset=utf-8' },
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

async function syncTask(request, env, taskId = null) {
    if (!await authorize(request, env)) return json({ error: 'Unauthorized' }, 401);

    if (request.method === 'DELETE') {
        if (!taskId) return json({ error: 'Task ID is required' }, 400);
        await env.DB.batch([
            env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE task_id = ?').bind(taskId),
            env.DB.prepare('DELETE FROM task_reminders WHERE task_id = ?').bind(taskId),
        ]);
        return json({ synced: true, removed: true });
    }

    const data = await request.json();
    if (!data.task_id || !data.user_id || !data.title || !data.reminder_at || !data.target_url) {
        return json({ error: 'Invalid reminder payload' }, 422);
    }

    const taskKey = String(data.task_id);
    await env.DB.prepare(`
        INSERT INTO task_reminders (task_id, user_id, title, reminder_at, target_url, completed_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NULL, ?)
        ON CONFLICT(task_id) DO UPDATE SET
            user_id = excluded.user_id,
            title = excluded.title,
            reminder_at = excluded.reminder_at,
            target_url = excluded.target_url,
            completed_at = CASE
                WHEN task_reminders.reminder_at != excluded.reminder_at THEN NULL
                ELSE task_reminders.completed_at
            END,
            updated_at = excluded.updated_at
    `).bind(
        taskKey, String(data.user_id), String(data.title),
        String(data.reminder_at), String(data.target_url), new Date().toISOString(),
    ).run();
    return json({ synced: true });
}

async function syncDevice(request, env, tokenHash = null) {
    if (!await authorize(request, env)) return json({ error: 'Unauthorized' }, 401);

    if (request.method === 'DELETE') {
        if (!tokenHash) return json({ error: 'Token hash is required' }, 400);
        await env.DB.batch([
            env.DB.prepare('DELETE FROM task_reminder_deliveries WHERE token_hash = ?').bind(tokenHash),
            env.DB.prepare('DELETE FROM push_devices WHERE token_hash = ?').bind(tokenHash),
        ]);
        return json({ synced: true, removed: true });
    }

    const data = await request.json();
    if (!data.user_id || !data.token || !data.token_hash) {
        return json({ error: 'Invalid device payload' }, 422);
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
    return json({ synced: true });
}

async function syncSnapshot(request, env) {
    if (!await authorize(request, env)) return json({ error: 'Unauthorized' }, 401);
    const data = await request.json();
    if (!data.user_id || !Array.isArray(data.reminders) || !Array.isArray(data.devices)) {
        return json({ error: 'Invalid snapshot payload' }, 422);
    }

    const userId = String(data.user_id);
    const statements = [];
    for (const reminder of data.reminders) {
        if (!reminder.task_id || !reminder.title || !reminder.reminder_at || !reminder.target_url) continue;
        statements.push(env.DB.prepare(`
            INSERT INTO task_reminders (task_id, user_id, title, reminder_at, target_url, completed_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NULL, ?)
            ON CONFLICT(task_id) DO UPDATE SET
                user_id = excluded.user_id,
                title = excluded.title,
                reminder_at = excluded.reminder_at,
                target_url = excluded.target_url,
                completed_at = CASE
                    WHEN task_reminders.reminder_at != excluded.reminder_at THEN NULL
                    ELSE task_reminders.completed_at
                END,
                updated_at = excluded.updated_at
        `).bind(
            taskId, userId, String(reminder.title), String(reminder.reminder_at),
            String(reminder.target_url), new Date().toISOString(),
        ));
    }
    for (const device of data.devices) {
        if (!device.token || !device.token_hash) continue;
        statements.push(env.DB.prepare(`
            INSERT INTO push_devices (token_hash, user_id, token, updated_at)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(token_hash) DO UPDATE SET
                user_id = excluded.user_id,
                token = excluded.token,
                updated_at = excluded.updated_at
        `).bind(String(device.token_hash), userId, String(device.token), new Date().toISOString()));
    }

    for (let offset = 0; offset < statements.length; offset += 40) {
        await env.DB.batch(statements.slice(offset, offset + 40));
    }
    return json({ synced: true, reminders: data.reminders.length, devices: data.devices.length });
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

async function runScheduledReminders(env) {
    const now = new Date().toISOString();
    const { results: reminders } = await env.DB.prepare(`
        SELECT task_id, user_id, title, reminder_at, target_url
        FROM task_reminders
        WHERE reminder_at <= ? AND completed_at IS NULL
        ORDER BY reminder_at
        LIMIT 25
    `).bind(now).all();

    let sent = 0;
    let failed = 0;
    for (const reminder of reminders) {
        const { results: devices } = await env.DB.prepare(
            'SELECT token_hash, token FROM push_devices WHERE user_id = ? LIMIT 20',
        ).bind(reminder.user_id).all();

        for (const device of devices) {
            const existing = await env.DB.prepare(
                'SELECT 1 FROM task_reminder_deliveries WHERE task_id = ? AND reminder_at = ? AND token_hash = ? LIMIT 1',
            ).bind(reminder.task_id, reminder.reminder_at, device.token_hash).first();
            if (existing) continue;

            try {
                const result = await sendReminder(env, reminder, device);
                if (result === 'sent') {
                    await env.DB.prepare(
                        'INSERT OR IGNORE INTO task_reminder_deliveries (task_id, reminder_at, token_hash, sent_at) VALUES (?, ?, ?, ?)',
                    ).bind(reminder.task_id, reminder.reminder_at, device.token_hash, new Date().toISOString()).run();
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

        if (devices.length === 0) {
            await env.DB.prepare('UPDATE task_reminders SET completed_at = ? WHERE task_id = ?')
                .bind(new Date().toISOString(), reminder.task_id).run();
        } else {
            const { count } = await env.DB.prepare(
                'SELECT COUNT(*) AS count FROM task_reminder_deliveries WHERE task_id = ? AND reminder_at = ?',
            ).bind(reminder.task_id, reminder.reminder_at).first();
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
        if (url.pathname === '/health' && request.method === 'GET') {
            return json({ ok: true, service: 'mydaily-push-reminders' });
        }
        if (url.pathname === '/sync/task' && request.method === 'POST') {
            return syncTask(request, env);
        }
        const taskDelete = url.pathname.match(/^\/sync\/task\/([0-9]+)$/);
        if (taskDelete && request.method === 'DELETE') {
            return syncTask(request, env, taskDelete[1]);
        }
        if (url.pathname === '/sync/device' && request.method === 'POST') {
            return syncDevice(request, env);
        }
        if (url.pathname === '/sync/snapshot' && request.method === 'POST') {
            return syncSnapshot(request, env);
        }
        const deviceDelete = url.pathname.match(/^\/sync\/device\/([a-f0-9]{64})$/);
        if (deviceDelete && request.method === 'DELETE') {
            return syncDevice(request, env, deviceDelete[1]);
        }
        return json({ error: 'Not found' }, 404);
    },

    async scheduled(_event, env, ctx) {
        ctx.waitUntil(runScheduledReminders(env));
    },
};
