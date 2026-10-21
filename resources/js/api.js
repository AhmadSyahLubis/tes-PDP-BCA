export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

let pendingRefresh = null;

async function refreshSession() {
    if (!pendingRefresh) {
        pendingRefresh = request('/api/refresh', { method: 'POST' }, false)
            .finally(() => { pendingRefresh = null; });
    }

    return pendingRefresh;
}

async function request(path, options = {}, retry = true) {
    const isForm = options.body instanceof FormData;
    const response = await fetch(path, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            ...(options.body && !isForm ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers,
        },
    });

    if (response.status === 401 && retry && !['/api/login', '/api/refresh', '/api/logout'].includes(path)) {
        try {
            await refreshSession();
            return request(path, options, false);
        } catch {}
    }

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const firstError = Object.values(data.errors ?? {})[0]?.[0];
        throw new ApiError(firstError || data.message || 'Permintaan gagal.', response.status, data.errors ?? {});
    }
    return data;
}

export function api(path, options = {}) {
    return request(path, options);
}

export function rupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
}

export function dateTime(value) {
    return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';
}

export const statusLabels = {
    submitted: 'Menunggu persetujuan', approved: 'Disetujui', rejected: 'Ditolak',
    issued: 'Dokumen terbit', signed: 'Sudah TTD', disbursed: 'Dicairkan',
};
