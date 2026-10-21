import { reactive } from 'vue';
import { api } from './api';

export const auth = reactive({ user: null, loaded: false });

export async function loadSession() {
    if (auth.loaded) return;
    try {
        const result = await api('/api/session');
        auth.user = result.user;
    } catch {
        auth.user = null;
    }
    auth.loaded = true;
}
