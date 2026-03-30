export async function fetchJsonWithSession(url, { loginUrl = '', timeoutMs = 10000, options = {} } = {}) {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), timeoutMs);

    try {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {}),
        };

        const res = await fetch(url, {
            cache: 'no-store',
            ...options,
            headers,
            signal: controller.signal,
        });

        const data = await res.json().catch(() => null);

        if (res.status === 401 && data?.session_expired && loginUrl) {
            window.location.href = loginUrl;
        }

        return { ok: res.ok, status: res.status, data };
    } finally {
        window.clearTimeout(timeout);
    }
}

export async function fetchRequiredJson(url, { loginUrl = '', timeoutMs = 10000, options = {} } = {}) {
    const result = await fetchJsonWithSession(url, { loginUrl, timeoutMs, options });
    if (!result.ok) {
        throw new Error(result.data?.message || `HTTP ${result.status}`);
    }
    return result.data;
}
